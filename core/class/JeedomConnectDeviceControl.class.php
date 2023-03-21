<?php

class JeedomConnectDeviceControl {

    /**
     * Return the "device" (from Android POV) available
     *
     * @param JeedomConnect $eqLogic
     * @param array|null $activeControlIds : list of active devices ID, null if we requiere all devices without states
     * @param int|null $lastUpdateTime : last polling time, 0 if first time polling and want all infos immediately, null if we requiere all devices without states
     * @return void
     */
    public static function getDevices($eqLogic, $activeControlIds, $lastUpdateTime) {

        $devices = array();
        $cmdData = array(
            'data' => null,
            'lastUpdateTime' => 0  // or time() ?
        );

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
            $cmdIds = array();
            foreach ($widgets as $widget) {
                if (in_array($widget['id'], $activeControlIds)) {
                    $cmdIds = array_merge($cmdIds, JeedomConnectUtils::getInfosCmdIds($widget));
                }
            }

            $cmdIds = array_unique(array_filter($cmdIds, 'strlen'));
            $cmdData = JeedomConnectUtils::getCmdValues($cmdIds);
            if ($lastUpdateTime != 0) {
                $newUpdateTime =  self::waitForEvents($cmdIds, $lastUpdateTime);
                $cmdData['lastUpdateTime'] = $newUpdateTime;
            }

            foreach ($activeControlIds as $deviceId) {
                if ($widgetIndex = array_search($deviceId, array_column($widgets, 'id')) !== false) {
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
                $deviceConfig = self::getDeviceConfig($widget, $cmdData['data']);
                if ($deviceConfig != null) {
                    $devices[] =  $deviceConfig;
                }
            }
        }

        return array("devices" => $devices, 'lastUpdateTime' => $cmdData['lastUpdateTime']);
    }



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
            // 'title' => JeedomConnectUtils::getFormatedText($widget["name"], $cmdData),
            'subtitle' => JeedomConnectUtils::getRoomName($widget),
            'zone' => config::byKey('name') ?? JeedomConnectUtils::getRoomName($widget)
        );

        if (key_exists('allowOnUnlock', $widget) && $widget['allowOnUnlock']) {
            $device['allowOnUnlock'] = true;
        }

        $deviceType = "TYPE_UNKNOWN";
        $controlTemplate = "TYPE_STATELESS";

        switch ($widget['type']) {
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
                $hasBrightness = is_numeric($cmdData[$widget['brightInfo']['id']]);
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
                $hasMode = $cmdData[$widget['modeInfo']['id']] != null;
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

    private static function waitForEvents($cmdIds, $lastUpdateTime) {
        set_time_limit(300);
        while (true) {
            // JCLog::debug('checking event', '_events');
            $events = event::changes($lastUpdateTime);
            $changed = false;
            foreach ($events['result'] as $event) {
                if ($event['name'] == 'cmd::update') {
                    if (in_array($event['option']['cmd_id'], $cmdIds)) {
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                return $events['datetime'];
            }
            sleep(1);
        }
    }
}
