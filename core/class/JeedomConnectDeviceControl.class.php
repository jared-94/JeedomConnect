<?php

class JeedomConnectDeviceControl {

    /**
     * Return the "device" (from Android POV) available
     *
     * @param JeedomConnect $eqLogic
     * @param array|null $activeControlIds : list of active devices ID, null if we requiere all devices without states
     * @return void
     */
    public static function getDevices($eqLogic, $activeControlIds) {

        $devices = array();

        $widgetsAll = $eqLogic->getGeneratedConfigFile()['payload']['widgets'];
        $widgets = array();
        $idList = array();
        foreach ($widgetsAll as $widget) {
            if ((!key_exists('hideControlDevice', $widget) || !$widget['hideControlDevice']) && !in_array($widget['id'], $idList)) {
                $widgets[] =  $widget;
                $idList[] =  $widget['id'];
            };
        }
        // JCLog::debug('getDevices - all widgets ' . json_encode($widgets));
        // JCLog::debug('getDevices - all ids ' . json_encode($idList));

        if ($activeControlIds != null) {

            $currentActiveControls = explode(',', $eqLogic->getConfiguration('activeControlIds'));
            if ($currentActiveControls != $activeControlIds) {
                JCLog::trace('adding activeControlIds in eqLogic - old : ' . json_encode($currentActiveControls) . ' - new : ' . json_encode($activeControlIds));
                $eqLogic->addInEqConfiguration('activeControlIds', $activeControlIds);
            }

            $cmdIds = array();
            foreach ($widgets as $widget) {
                if (in_array($widget['id'], $activeControlIds)) {
                    $cmdIds = array_merge($cmdIds, JeedomConnectUtils::getInfosCmdIds($widget));
                }
            }

            $cmdIds = array_unique(array_filter($cmdIds, 'strlen'));
            $cmdData = JeedomConnectUtils::getCmdValues($cmdIds);

            foreach ($activeControlIds as $deviceId) {
                if (($widgetIndex = array_search($deviceId, array_column($widgets, 'id'))) !== false) {
                    $deviceConfig = self::getDeviceConfig($widgets[$widgetIndex], $cmdData['data']);
                    if ($deviceConfig != null) {
                        $devices[] =  $deviceConfig;
                    } else {
                        $devices[] = array('id' => $deviceId, 'deviceStatus' => "ERROR");
                    }
                } else {
                    $devices[] = array('id' => $deviceId, 'deviceStatus' => "NOT_FOUND");
                }
            }
        } else {
            foreach ($widgets as $widget) {
                $deviceConfig = self::getDeviceConfig($widget, null);
                if ($deviceConfig != null) {
                    $devices[] =  $deviceConfig;
                }
            }
        }

        // sort by room, then name
        usort($devices, function ($a, $b) {
            if (strtolower($a['subtitle']) ==  strtolower($b['subtitle'])) {
                return strcmp(strtolower($a['title']),  strtolower($b['title']));
            }
            return strcmp(strtolower($a['subtitle']),  strtolower($b['subtitle']));
        });

        return array("devices" => $devices);
    }


    /**
     * Create the payload of each "device" for available widget type
     *
     * @param array $widget widget config
     * @param array|null $cmdData data already existing
     * @return void
     */
    private static function getDeviceConfig($widget, $cmdData) {
        JCLog::trace('checking device config for widget ' . json_encode($widget));

        try {
            $expEval = JeedomConnectUtils::getExpressionEvaluated($widget["name"]);
            // JCLog::debug('expression evaluated : ' . json_encode($expEval));
        } catch (Exception $e) {
            JCLog::warning('Exception with expression evaluation => ' . $e->getMessage());
            $expEval = array('result' => $widget["name"]);
        }

        $device = array(
            'id' => strval($widget['id']),
            'widgetId' => strval($widget['widgetId']),
            'title' => $expEval['result'],
            'subtitle' => JeedomConnectUtils::getRoomName($widget),
            'zone' => config::byKey('name') ?? JeedomConnectUtils::getRoomName($widget)
        );

        if (key_exists('allowOnUnlock', $widget) && $widget['allowOnUnlock']) {
            $device['allowOnUnlock'] = true;
        }

        $deviceType = "TYPE_UNKNOWN";
        $controlTemplate = "TYPE_STATELESS";

        switch ($widget['type']) {
            case 'camera':
                if (empty($widget['snapshotUrl']) && $widget['snapshotUrlInfo'] == null) {
                    return null;
                }
                $deviceType = "TYPE_CAMERA";
                $controlTemplate = "TYPE_THUMBNAIL";
                break;
            case 'door':
                $deviceType = "TYPE_DOOR";
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] > 0 ? "Ouvert" : "Fermé";
                break;

            case 'generic-action-other':
                $device['action'] = JeedomConnectUtils::getActionCmd($widget['actions'][0]); // we only consider the first action
                break;

            case 'generic-info-numeric':
            case 'power':
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'generic-info-string':
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']];
                break;

            case 'generic-slider':
                $controlTemplate = "TYPE_RANGE";
                JeedomConnectUtils::getRangeStatus($cmdData, $widget['statusInfo'], $device);
                $device['rangeAction'] = JeedomConnectUtils::getActionCmd($widget['sliderAction']);
                break;

            case 'generic-switch':
                $deviceType = "TYPE_SWITCH";
                $controlTemplate = "TYPE_TOGGLE";
                $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['onAction']);
                $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['offAction']);
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $device['status'] == 'on' ? "ON" : "OFF";
                break;

            case 'scenario':
                $device['action'] = array(
                    'action' => 'execSc',
                    'scenarioId' => $widget['scenarioId'],
                    'options' => $widget['options']
                );
                break;
            case 'shutter':
                $deviceType = 'TYPE_SHUTTER';
                if ($widget['statusInfo']['subType'] == "numeric") {
                    if ($widget['positionAction']['id'] != null) {
                        if ($widget['upAction']['id'] != null && $widget['downAction']['id'] != null) {
                            $controlTemplate = "TYPE_TOGGLE_RANGE";
                        } else {
                            $controlTemplate = "TYPE_RANGE";
                        }
                    } else if ($widget['upAction']['id'] != null && $widget['downAction']['id'] != null) {
                        $controlTemplate = "TYPE_TOGGLE";
                    }
                } else if ($widget['statusInfo']['subType'] == "binary") {
                    if ($widget['upAction']['id'] != null && $widget['downAction']['id'] != null) {
                        $controlTemplate = "TYPE_TOGGLE";
                    }
                }
                if ($controlTemplate == "TYPE_TOGGLE" || $controlTemplate = "TYPE_TOGGLE_RANGE") {
                    $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['upAction']);
                    $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['downAction']);
                    $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                    $device['statusText'] = $device['status'] == 'on' ? "Ouvert" : "Fermé";
                }
                if ($controlTemplate == "TYPE_RANGE" || $controlTemplate = "TYPE_TOGGLE_RANGE") {
                    $device['rangeAction'] = JeedomConnectUtils::getActionCmd($widget['positionAction']);
                    JeedomConnectUtils::getRangeStatus($cmdData, $widget['statusInfo'], $device);
                }

                break;
            case 'single-light-switch':
                $deviceType = "TYPE_LIGHT";
                $controlTemplate = "TYPE_TOGGLE";
                $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['onAction']);
                $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['offAction']);
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $device['status'] == 'on' ? "ON" : "OFF";
                break;

            case 'single-light-dim':
            case 'single-light-color':
                $hasBrightness = $widget['brightInfo']['id'] != null;
                $deviceType = "TYPE_LIGHT";
                $controlTemplate = $hasBrightness ? "TYPE_TOGGLE_RANGE" : "TYPE_TOGGLE";
                $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['onAction']);
                $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['offAction']);
                $device['rangeAction'] = JeedomConnectUtils::getActionCmd($widget['brightAction']);
                if ($hasBrightness) {
                    JeedomConnectUtils::getRangeStatus($cmdData, $widget['brightInfo'], $device);
                }

                if ($widget['statusInfo']['id'] != null) {
                    $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                } else {
                    $device['status'] = $cmdData[$widget['brightInfo']['id']] > 0 ? 'on' : 'off';
                }
                $device['statusText'] = $device['status'] == 'on' ? "ON" : "OFF";
                break;

            case 'temperature':
                $deviceType = "TYPE_THERMOSTAT";
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'thermostat':
                $hasMode = $widget['modeInfo']['id'] != null;
                $deviceType = $hasMode ? "TYPE_THERMOSTAT" : "TYPE_AC_HEATER";
                $controlTemplate = $hasMode ? "TYPE_TEMPERATURE" : "TYPE_RANGE";
                JeedomConnectUtils::getRangeStatus($cmdData, $widget['setpointInfo'], $device);
                $device['rangeAction'] = JeedomConnectUtils::getActionCmd($widget['setpointAction']);
                $device['modeStatus'] = JeedomConnectUtils::experimentalGetMode($cmdData[$widget['modeInfo']['id']]);
                $device['modes'] = JeedomConnectUtils::getModes($widget['modes']);
                $device['statusText'] = $cmdData[$widget['modeInfo']['id']];
                break;

            case 'window':
                $deviceType = "TYPE_WINDOW";
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] > 0 ? "Ouvert" : "Fermé";
                break;

            default:
                return null;
        }
        $device['deviceStatus'] = $widget['enable'] ? "OK" : "DISABLED";
        $device['deviceType'] = $deviceType;
        $device['controlTemplate'] = $controlTemplate;

        return $device;
    }

    /**
     * Return cmd IDs used in widgets based on $activeControlIds (=> widget's id)
     *
     * @param JeedomConnect $eqLogic
     * @param array $activeControlIds
     * @return void
     */
    public static function getInfoCmdIdsFromControls($eqLogic, $activeControlIds) {
        $widgetsAll = $eqLogic->getGeneratedConfigFile()['payload']['widgets'];
        $res = array();
        foreach ($widgetsAll as $widget) {
            if (in_array($widget['id'], $activeControlIds)) {
                $res = array_merge($res, JeedomConnectUtils::getInfosCmdIds($widget));
            };
        }
        return array_unique(array_filter($res, 'strlen'));
    }
}
