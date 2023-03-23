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
            case 'alarm':
                $hasArmActions = $widget['onAction']['id'] != null && $widget['offAction']['id'] != null;
                $deviceType = "TYPE_LOCK";
                $controlTemplate = $hasArmActions ? "TYPE_TOGGLE" : "TYPE_STATELESS";
                $device['status'] = $cmdData[$widget['enableInfo']['id']] > 0 ? 'on' : 'off';
                if ($widget['onAction']['id'] != null) {
                    $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['onAction']);
                }
                if ($widget['offAction']['id'] != null) {
                    $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['offAction']);
                }
                $device['statusText'] = $widget['modeInfo']['id'] != null ? $cmdData[$widget['modeInfo']['id']]
                    : $device['status'] == 'on' ? "Armé" : "Désarmé";
                $device['colorEnabled'] = "#8000FF00";
                break;

            case 'brightness':
            case 'humidity':
                $device['icon'] = 'ic_fluent_brightness_high_24_regular';
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'camera':
                if (empty($widget['snapshotUrl']) && $widget['snapshotUrlInfo'] == null) {
                    return null;
                }
                $deviceType = "TYPE_CAMERA";
                $controlTemplate = "TYPE_THUMBNAIL";
                break;

            case 'door':
                $controlTemplate = "TYPE_TOGGLE";
                $deviceType = "TYPE_DOOR";
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $device['status'] == 'on' ? "Ouvert" : "Fermé";
                break;

            case 'frontgate':
                $hasStatus = $widget['statusInfo']['id'] != null;
                $deviceType = "TYPE_GATE";
                $controlTemplate = $hasStatus != null ? "TYPE_TOGGLE" : "TYPE_STATELESS";
                $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['openAction']);
                $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['closeAction']);
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $hasStatus ? $device['status'] == 'on' ? "Ouvert" : "Fermé" : "";
                break;

            case 'generic-action-other':
                $device['icon'] = 'ic_fluent_filmstrip_play_24_regular';
                $device['action'] = JeedomConnectUtils::getActionCmd($widget['actions'][0]); // we only consider the first action
                break;

            case 'generic-info-binary':
                $controlTemplate = "TYPE_TOGGLE";
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                if ($device['status']) {
                    $device['statusText'] = empty($widget['text1']) ? "1" : $widget['text1'];
                } else {
                    $device['statusText'] = empty($widget['text0']) ? "0" : $widget['text0'];
                }
                // $device['icon'] = ''; // TODO
                break;

            case 'generic-info-numeric':
                $device['icon'] = 'ic_fluent_number_circle_1_24_regular';
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'power':
                $device['icon'] = 'ic_fluent_flash_24_regular';
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'generic-info-string':
                $device['icon'] = 'ic_fluent_text_t_24_regular';
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']];
                break;

            case 'generic-slider':
                // $device['icon'] = ''; // TODO
                $controlTemplate = "TYPE_RANGE";
                JeedomConnectUtils::getRangeStatus($cmdData, $widget['statusInfo'], $device);
                $device['rangeAction'] = JeedomConnectUtils::getActionCmd($widget['sliderAction']);
                break;

            case 'generic-switch':
                $deviceType = "TYPE_SWITCH";
                $controlTemplate = "TYPE_TOGGLE";
                $device['icon'] = 'ic_fluent_toggle_left_24_regular';
                $device['onAction'] = JeedomConnectUtils::getActionCmd($widget['onAction']);
                $device['offAction'] = JeedomConnectUtils::getActionCmd($widget['offAction']);
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $device['status'] == 'on' ? "ON" : "OFF";
                break;

            case 'pir':
                $controlTemplate = "TYPE_TOGGLE";
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] > 0 ? "En alerte" : "Absent";
<<<<<<< HEAD
                $device['colorEnabled'] = "#80FF0000";
=======
                $icon_alert = 'ic_fluent_alert_on_24_regular';
                $icon_alert_none = 'ic_fluent_snooze_24_regular';
                $device['icon'] = $cmdData[$widget['statusInfo']['id']] > 0 ? $icon_alert : $icon_alert_none;
                $device['iconColor'] = $cmdData[$widget['statusInfo']['id']] > 0 ? '#ff0000' : '';
>>>>>>> d0ffb051454705c1653313981ee33090d6a5a0f1
                break;

            case 'plug':
                $deviceType = "TYPE_OUTLET";
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
                $device['icon'] = 'ic_fluent_arrow_routing_rectangle_multiple_24_regular';
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
                if (!empty($cmdData[$widget['colorInfo']['id']])) {
                    $color = $cmdData[$widget['colorInfo']['id']];
                    if (is_string($color) && strlen($color) == 7 && substr($color, 0, 1) === "#") {
                        $device['colorEnabled'] = "#80" . substr($color, 1);
                    }
                }
                break;

            case 'temperature':
                $deviceType = "TYPE_THERMOSTAT";
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] . ($widget['statusInfo']['unit'] ?? "");
                break;

            case 'air-con':
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
                $controlTemplate = "TYPE_TOGGLE";
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
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
