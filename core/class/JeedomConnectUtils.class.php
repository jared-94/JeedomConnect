<?php

/* * ***************************Includes********************************* */

class JeedomConnectUtils {

    public static function getCustomPathIcon(JeedomConnect $eqLogic) {
        $plugin = plugin::byId('JeedomConnect');

        $platform = $eqLogic->getConfiguration('platformOs');
        $standardIcon = $plugin->getPathImgIcon();

        if ($platform == '') return $standardIcon;

        $path_parts = pathinfo($standardIcon);
        $extension = $path_parts['extension'];
        $extensionSize = strlen($extension) + 1;
        $finalIcon = substr($standardIcon, 0, $extensionSize * -1) . '_' . $platform . '.' . $extension;

        if (!file_exists($finalIcon)) return $standardIcon;

        return $finalIcon;
    }

    public static function isCoreGreaterThan($version = '0.0.0') {
        $update = update::byTypeAndLogicalId('core', 'jeedom');
        if (is_object($update)) {
            $currentVersion =  $update->getLocalVersion();
            return version_compare($currentVersion, $version, ">");
        }

        return  false;
    }

    public static function getInstallDetails(): string {

        $infoPlugin = '<b>Jeedom Core</b> : ' . config::byKey('version', 'core', '#NA#') . '<br/>';

        $beta_version = false;

        $plugin = plugin::byId('JeedomConnect');
        $update = $plugin->getUpdate();
        if (is_object($update)) {
            $version = $update->getConfiguration('version');
            if ($version && $version != 'stable') $beta_version = true;
        }


        $infoPlugin .= '<b>Version JC</b> : ' . ($beta_version ? '[beta] ' : '') . config::byKey('version', 'JeedomConnect', '#NA#') . '<br/><br/>';
        $infoPlugin .= '<b>Equipements</b> : <br/>';

        /** @var array<JeedomConnect> $eqLogics */
        $eqLogics = eqLogic::byType($plugin->getId());

        foreach ($eqLogics as $eqLogic) {
            $platformOs = $eqLogic->getConfiguration('platformOs');
            $platform = $platformOs != '' ? 'sur ' . $platformOs : $platformOs;

            $versionAppConfig = $eqLogic->getConfiguration('appVersion');
            $versionApp = $versionAppConfig != '' ? 'v' . $versionAppConfig : $versionAppConfig;

            $connexionType = $eqLogic->getConfiguration('useWs') == '1' ? 'ws'  : '';
            $withPolling = $eqLogic->getConfiguration('polling') == '1' ? 'polling'  : '';

            $cpl =  (($connexionType . $withPolling) == '')  ? '' : ' (' . ((($connexionType != '' && $withPolling != '')) ? ($connexionType . '/' . $withPolling) : (($connexionType ?: '')  . ($withPolling ?: ''))) . ')';

            $infoPlugin .= '&nbsp;&nbsp;' . $eqLogic->getName();
            if ($platform == '' && $versionApp == '') {
                $infoPlugin .= ' : non enregistré<br/>';
            } else {
                $infoPlugin .=  ' : ' . $versionApp . ' ' . $platform . $cpl . '<br/>';
            }
        }

        return $infoPlugin;
    }

    /**
     * @param plugin $pluginObj
     * @return array
     */
    public static function getPluginDetails($pluginObj) {

        $update = update::byLogicalId($pluginObj->getId());
        $item = array();
        $item['pluginId'] =  $update->getLogicalId();
        $item['name'] = $pluginObj->getName();
        $item['img'] = $pluginObj->getPathImgIcon();
        $item['changelogLink'] =  $pluginObj->getChangelog();
        $item['docLink'] =  $pluginObj->getDocumentation();
        $item['doNotUpdate'] = $update->getConfiguration('doNotUpdate') == 1;
        $item['pluginType'] = $update->getConfiguration('version');
        $item['currentVersion'] =  $update->getLocalVersion();
        $item['updateVersion'] = $update->getRemoteVersion();
        $item['isActive'] = $pluginObj->isActive() == "1";
        $item['logFiles'] = $pluginObj->getLogList();

        return $item;
    }

    public static function getCmdForGenericType($genericTypes, $eqLogicId = null) {
        $cmds = cmd::byGenericType($genericTypes, $eqLogicId);
        // log::add('JeedomConnect', 'debug', "found:" . count($cmds));

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
            // log::add('JeedomConnect', 'debug', "cmd:{$eqLogic->getId()}/{$eqLogic->getName()}-{$cmd->getId()}/{$cmd->getName()}");
        }

        // log::add('JeedomConnect', 'debug', 'temp results:' . count($results) . '-' . json_encode($results));
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

    /**
     * return an array of widget of type $_widget_Type with commands matching the corresponding generic type
     *
     * @param string $_widget_Type
     * @param array $_widgetConf
     * @param array $_cmd_GenType
     * @return array
     */
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
        // log::add('JeedomConnect', 'debug', 'temp createAutoWidget:' .  json_encode($result));
        return $result;
    }

    public static function filterWidgetsWithStrictMode($results, $eqLogicId, $widgetConfig) {
        $isStrict = config::byKey('isStrict', 'JeedomConnect', true);
        foreach ($results as $eqLogicId => $eqLogicConfig) {
            // log::add('JeedomConnect', 'debug', "checking eqLogic {$eqLogicId}/{$eqLogicConfig['name']}");
            $requiredCmdWithGenericTypeInConfig = false;
            $requiredCmdWithGenericTypeFound = false;
            foreach ($widgetConfig['options'] as $option) {
                if (isset($option['generic_type']) && isset($option['required']) && $option['required'] == true) {
                    $requiredCmdWithGenericTypeInConfig = true;
                    // log::add('JeedomConnect', 'debug', "checking {$option['generic_type']}");
                    $requiredCmdWithGenericTypeFound = false;
                    foreach ($eqLogicConfig['cmds'] as $cmds) {
                        if ($cmds['generic_type'] == $option['generic_type']) {
                            $requiredCmdWithGenericTypeFound = true;
                            break;
                        }
                    }
                    if ($isStrict && !$requiredCmdWithGenericTypeFound) {
                        // log::add('JeedomConnect', 'debug', "Strict mode and could not find a required cmd with generic type {$option['generic_type']} for eqLogic {$eqLogicId}/{$eqLogicConfig['name']}, removing it from results");
                        unset($results[$eqLogicId]);
                        break;
                    }
                }
            }
            if (!$isStrict && $requiredCmdWithGenericTypeInConfig && !$requiredCmdWithGenericTypeFound) {
                // log::add('JeedomConnect', 'debug', "Could not find ANY required cmd with generic type {$option['generic_type']} for eqLogic {$eqLogicId}/{$eqLogicConfig['name']}, removing it from results");
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
                        if (isset($generatedWidget[$cmd]['id']) && $generatedWidget[$cmd]['id'] != ($widget[$cmd]['id'] ?? 'none')) {
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

    public static function generateWidgetWithGenType($_widget_type, $_eqLogicId) {

        if ($_widget_type == null) return null;

        $widgetConfigParam = JeedomConnect::getWidgetParam(false, array($_widget_type));
        $widgetConfig = $widgetConfigParam[$_widget_type] ?? null;

        if ($widgetConfig == null) return null;

        $genericTypes = JeedomConnectUtils::getGenericType($widgetConfig);
        if ($genericTypes == null) return null;

        $cmdGeneric = JeedomConnectUtils::getCmdForGenericType($genericTypes, $_eqLogicId);

        $widgetsAvailable = JeedomConnectUtils::filterWidgetsWithStrictMode($cmdGeneric, $_eqLogicId, $widgetConfig);

        $generatedWidgets = JeedomConnectUtils::createAutoWidget($_widget_type, $widgetConfig, $widgetsAvailable);

        $result = JeedomConnectUtils::widgetAlreadyExistWithRequiredCmd($generatedWidgets, $widgetConfig);
        // log::add('JeedomConnect', 'debug', 'generateWidgetWithGenType => ' . count($result) . '-' . json_encode($result));

        return $result;
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

    public static function isBeta() {
        $plugin = plugin::byId('JeedomConnect');
        $update = $plugin->getUpdate();
        if (is_object($update)) {
            $version = $update->getConfiguration('version');
            return ($version && $version != 'stable');
        }

        return false;
    }

    public static function getLinks() {

        $isBeta = self::isBeta();
        $linksData = json_decode(file_get_contents(JeedomConnect::$_plugin_info_dir . 'links.json'), true);

        foreach ($linksData as $key => $item) {
            if ($item['id'] == 'donate' && file_exists(JeedomConnect::$_plugin_info_dir . 'partiallink')) {
                unset($linksData[$key]);
                continue;
            }

            if (in_array($item['id'], array('doc', 'changelog')) && $isBeta) {
                $item['link'] .= "_beta";
                $linksData[$key] = $item;
                continue;
            }
        }

        // log::add('JeedomConnect', 'debug', 'result : ' .  json_encode($linksData));

        return $linksData;
    }

    public static function getFileContent($path) {

        if (!file_exists($path)) {
            log::add(__CLASS__, 'error', 'File not found  : ' . $path);
            return null;
        }

        $content = file_get_contents($path);

        if (is_json($content)) {
            return json_decode($content, true);
        }

        return $content;
    }

    public static function createListOption($data, $dict) {

        $list = '';
        foreach ($data as $item) {
            $val = $dict[$item] ?? $item;
            $list .= $item . '|' . $val . ';';
        }
        $list = ($list != '') ? substr($list, 0, -1) : '';

        return $list;
    }

    public static function getNotifData($token) {
        return array(
            "to" => $token,
            "android" => array(
                "priority" => "high",
            ),
            "apns" => array(
                "headers" => array(
                    "apns-push-type" => "background",
                    "apns-priority" => "5",
                    "apns-topic" => "com.jeedom-connect.app",
                ),
                "payload" => array(
                    "aps" => array(
                        "contentAvailable" => true,
                        "content-available" => true,
                    ),
                ),
            ),
            "collapse_key" => "type_a",
            "content_available" => true,
            "mutable_content" => true,
            "priority" => "high",
        );
    }
  
    /**
     * @return array
     */
    public static function getTimelineFolders() {
        $folders = array("main" => "Principal");

        foreach ((timeline::listFolder()) as $folder) {
            if ($folder == 'main') continue;
            $folders['custom'][$folder] = $folder;
        }

        return $folders;
    }

    /**
     * @param string $folder 
     * @return array
     */
    public static function getTimelineEvents($folder = 'main') {

        $return = array();
        $events = timeline::byFolder($folder);
        foreach ($events as $event) {
            // hasRight method available with core 4.2
            if (method_exists($event, 'hasRight') && !$event->hasRight()) {
                continue;
            }
            $info = self::getTimelineEventDetails($event);
            if ($info != null) {
                $return[] = $info;
            }
        }
        return $return;
    }

    /**
     * @param timelime $event 
     * @return array
     */
    public static function getTimelineEventDetails($event) {
        $return = array();
        $return['date'] = $event->getDatetime();
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $event->getDatetime());
        $return['timestamp'] = $d->getTimestamp();
        $return['type'] = $event->getType();
        $return['folder'] = $event->getFolder();

        switch ($event->getType()) {
            case 'cmd':
                $cmd = cmd::byId($event->getLink_id());
                if (!is_object($cmd)) {
                    return null;
                }
                $eqLogic = $cmd->getEqLogic();
                $object = $eqLogic->getObject();
                $return['object'] = is_object($object) ? $object->getName() : 'aucun';
                $return['name'] = $cmd->getName();

                $return['cmdtype'] = $cmd->getType();
                $return['plugin'] = $eqLogic->getEqType_name();
                $return['eqLogic'] = $eqLogic->getName();

                if ($cmd->getType() == 'info') {
                    $return['value'] = $event->getOptions('value');
                }
                break;

            case 'scenario':
                $scenario = scenario::byId($event->getLink_id());
                if (!is_object($scenario)) {
                    return null;
                }
                $object = $scenario->getObject();
                $return['object'] = is_object($object) ? $object->getName() : 'aucun';

                $return['name'] = $scenario->getName();
                $return['trigger'] =  $event->getOptions('trigger');
                break;
        }
        return $return;
    }

    /**
     * @param string $plugin 
     * @return array
     */
    public static function getJeedomMessages($plugin = '') {
        $nbMessage = message::nbMessage();
        $messages = array();

        if ($nbMessage != 0) {
            if ($plugin == '') {
                $messages = utils::o2a(message::all());
            } else {
                $messages = utils::o2a(message::byPlugin($plugin));
            }
        }

        return array('nbMessages' => $nbMessage, 'messages' => $messages);
    }

    /**
     * @param string|null $messageId 
     * @return array|Exception
     */
    public static function removeJeedomMessage($messageId = null) {

        if ($messageId == 'all') return message::removeAll();

        $message = message::byId($messageId);
        if (!is_object($message) || is_null($messageId)) {
            throw new Exception(__('Message inconnu. Vérifiez l\'ID', __FILE__));
        }
        $message->remove();

        return true;
    }

    /**
     * @param array $payload 
     * @param string $type 
     * @return array
     */
    public static function addTypeInPayload($payload, $type) {
        $result = array(
            'type' => $type,
            'payload' => $payload
        );

        return $result;
    }
}
