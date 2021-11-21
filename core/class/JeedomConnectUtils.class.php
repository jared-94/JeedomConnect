<?php

/* * ***************************Includes********************************* */

class JeedomConnectUtils {


    public static function getCmdForGenericType($genericTypes, $eqLogicId = null) {
        $cmds = cmd::byGenericType($genericTypes, $eqLogicId);
        log::add('JeedomConnect', 'debug', "found:" . count($cmds));

        $results = array();
        foreach ($cmds as $cmd) {
            $eqLogic = $cmd->getEqLogic();
            if ($eqLogic->getIsEnable() == 0) continue;
            $results[$eqLogic->getId()]['name'] = $eqLogic->getName();
            $results[$eqLogic->getId()]['room'] = $eqLogic->getObject() ? $eqLogic->getObject()->getName() : 'none';
            $results[$eqLogic->getId()]['roomId'] = $eqLogic->getObject() ? $eqLogic->getObject()->getId() : null;
            $results[$eqLogic->getId()]['cmds'][] = array(
                'id' => $cmd->getId(),
                'humanName' => '#' . $cmd->getHumanName() . '#',
                'name' => $cmd->getName(),
                'type' => $cmd->getType(),
                'subType' => $cmd->getSubType(),
                'generic_type' => $cmd->getGeneric_type(),
                'minValue' => $cmd->getConfiguration('minValue'),
                'maxValue' => $cmd->getConfiguration('maxValue'),
                'unit' => $cmd->getUnite(),
                'value' => $cmd->getValue(),
                'icon' => self::getIconAndColor($cmd->getDisplay('icon'))
            );
            log::add('JeedomConnect', 'debug', "cmd:{$eqLogic->getId()}/{$eqLogic->getName()}-{$cmd->getId()}/{$cmd->getName()}");
        }

        log::add('JeedomConnect', 'debug', 'temp results:' . count($results) . '-' . json_encode($results));
        return $results;
    }

    public static function getGenericType($widgetConfig) {
        $genericTypes = array();
        foreach ($widgetConfig['options'] as $option) {
            if (isset($option['generic_type']) && $option['generic_type'] != '') {
                $genericTypes[] = $option['generic_type'];
            }
        }
        return array_unique($genericTypes);
    }

    public static function createAutoWidget($_widget_Type, $_widgetConf, $_cmd_GenType) {

        $result = array();
        foreach ($_cmd_GenType as $eqLogicId => $eqLogicConfig) {
            $current = array();
            $current['enable'] = true;
            $current['type'] = $_widget_Type;
            $current['room'] = intval($eqLogicConfig['roomId']);
            $current['name'] = $eqLogicConfig['name'];

            foreach ($_widgetConf['options'] as $option) {
                if (isset($option['category']) && isset($option['generic_type'])) {
                    if ($option['category'] == 'cmd') {
                        foreach ($eqLogicConfig['cmds'] as $cmds) {
                            if ($cmds['generic_type'] != $option['generic_type']) continue;

                            $current[$option['id']] = $cmds;
                            break;
                        }
                    }
                    if ($option['category'] == 'cmdList') {
                        $cmdList = [];
                        foreach ($eqLogicConfig['cmds'] as $cmds) {
                            if ($cmds['generic_type'] != $option['generic_type']) continue;
                            array_push($cmdList, $cmds);
                        }
                        if (count($cmdList) > 0) {
                            $current[$option['id']] = $cmdList;
                        }
                    }
                }
            }

            array_push($result, $current);
        }
        log::add('JeedomConnect', 'debug', 'temp createAutoWidget:' .  json_encode($result));
        return $result;
    }


    public static function filterWidgetsWithStrictMode($results, $eqLogicId, $widgetConfig) {
        $isStrict = config::byKey('isStrict', 'JeedomConnect', true);
        foreach ($results as $eqLogicId => $eqLogicConfig) {
            log::add('JeedomConnect', 'debug', "checking eqLogic {$eqLogicId}/{$eqLogicConfig['name']}");
            $requiredCmdWithGenericTypeInConfig = false;
            $requiredCmdWithGenericTypeFound = false;
            foreach ($widgetConfig['options'] as $option) {
                if (isset($option['generic_type']) && isset($option['required']) && $option['required'] == true) {
                    $requiredCmdWithGenericTypeInConfig = true;
                    log::add('JeedomConnect', 'debug', "checking {$option['generic_type']}");
                    $requiredCmdWithGenericTypeFound = false;
                    foreach ($eqLogicConfig['cmds'] as $cmds) {
                        if ($cmds['generic_type'] == $option['generic_type']) {
                            $requiredCmdWithGenericTypeFound = true;
                            break;
                        }
                    }
                    if ($isStrict && !$requiredCmdWithGenericTypeFound) {
                        log::add('JeedomConnect', 'debug', "Strict mode and could not find a required cmd with generic type {$option['generic_type']} for eqLogic {$eqLogicId}/{$eqLogicConfig['name']}, removing it from results");
                        unset($results[$eqLogicId]);
                        break;
                    }
                }
            }
            if (!$isStrict && $requiredCmdWithGenericTypeInConfig && !$requiredCmdWithGenericTypeFound) {
                log::add('JeedomConnect', 'debug', "Could not find ANY required cmd with generic type {$option['generic_type']} for eqLogic {$eqLogicId}/{$eqLogicConfig['name']}, removing it from results");
                unset($results[$eqLogicId]);
            }
        }

        return $results;
    }


    public static function widgetAlreadyExistWithRequiredCmd($allGeneratedWidgets, $widgetConfig) {
        $allExistingWidgets = JeedomConnectWidget::getWidgets('all', false, true);
        // log::add('JeedomConnect', 'debug', "All existing widgets currently : " . json_encode($allExistingWidgets));

        $cmdsWithGenType = array();
        foreach ($widgetConfig['options'] as $config) {
            if (key_exists('generic_type', $config) && in_array($config['category'], array('cmd', 'cmdList'))) {
                array_push($cmdsWithGenType, $config['id']);
            }
        }
        // log::add('JeedomConnect', 'debug', "All required Cmds id : " . json_encode($cmdsWithGenType));

        foreach ($allGeneratedWidgets as $key => $generatedWidget) {
            if (count($cmdsWithGenType) == 0) {
                // log::add('JeedomConnect', 'debug', "no required cmds found -- skipped control");
                $generatedWidget['alreadyExist'] = false;
            } else {
                // log::add('JeedomConnect', 'debug', "will check for generatedWidget " . json_encode($generatedWidget));
                foreach ($allExistingWidgets as $widget) {
                    $allCmdAlreadyUsed = true;
                    foreach ($cmdsWithGenType as $cmd) {
                        // log::add('JeedomConnect', 'debug', "will check for {$cmd} : generated=>" . ($generatedWidget[$cmd]['id'] ?? 'none') . ' // widget=>' . ($widget[$cmd]['id'] ?? 'none'));
                        if (($generatedWidget[$cmd]['id'] ?? 'none') != ($widget[$cmd]['id'] ?? 'none')) {
                            $allCmdAlreadyUsed = false;
                            // log::add('JeedomConnect', 'debug', " -- return false !");
                            break;
                        }
                    }
                    if ($allCmdAlreadyUsed) {
                        // log::add('JeedomConnect', 'debug', " -- same id found !!");
                        // log::add('JeedomConnect', 'debug', " ** generatedWidget already exist with widget id " . $widget['id']);
                        $generatedWidget['alreadyExist'] = true;
                        break;
                    }
                    $generatedWidget['alreadyExist'] = false;
                }
            }
            $allGeneratedWidgets[$key] = $generatedWidget;
        }
        // log::add('JeedomConnect', 'debug', "all generated final ==> " . json_encode($allGeneratedWidgets));
        return $allGeneratedWidgets;
    }


    public static function getIconAndColor($iconClass) {
        $newIconClass = trim(preg_replace('/ icon_(red|yellow|blue|green|orange)/', '', $iconClass));
        $matches = array();
        preg_match('/(.*)class=\"(.*)\"(.*)/', $iconClass, $matches);

        if (count($matches) > 3) {
            list($iconType, $iconImg) = explode(" ", $matches[2], 2);
            $iconType = ($iconType == 'icon') ? 'jeedom' : 'fa';
            $iconImg = ($iconType == 'fa') ? trim(str_replace('fa-', '', $iconImg)) : trim($iconImg);

            preg_match('/(.*) icon_(.*)/', $iconImg, $matches);
            $color = '';
            if (count($matches) > 2) {
                switch ($matches[2]) {
                    case 'blue':
                        $color = '#0000FF';
                        break;
                    case 'yellow':
                        $color = '#FFFF00';
                        break;
                    case 'orange':
                        $color = '#FFA500';
                        break;
                    case 'red':
                        $color = '#FF0000';
                        break;
                    case 'green':
                        $color = '#008000';
                        break;
                    default:
                        $color = '';
                        break;
                }
                $iconImg = trim(str_replace('icon_' . $matches[2], '', $iconImg));
            }

            return array('icon' => $newIconClass, 'source' => $iconType, 'name' => $iconImg, 'color' => $color);
        }

        return array('icon' => $newIconClass, 'source' => '', 'name' => '', 'color' => '');
    }
}
