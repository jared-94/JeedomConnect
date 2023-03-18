<?php

require_once __DIR__  . '/JeedomConnectLock.class.php';
require_once __DIR__  . '/JeedomConnectLogs.class.php';

class JeedomConnectDeviceControl {

    // $activeControlIds : list of active devices ID, null if we requiere all devices without states
    // $lastUpdateTime : last polling time, 0 if first time polling and want all infos immediately, null if we requiere all devices without states
    public static function getDevices($eqLogic, $activeControlIds, $lastUpdateTime) {

        $devices = array();
        $cmdData = array();
        $idList = array();
        $widgetsAll = $eqLogic->getGeneratedConfigFile()['payload']['widgets'];
        $widgets = array();
        foreach ($widgetsAll as $widget) {
            if (!in_array($widget['id'], $idList)) {
                array_push($widgets, $widget);
                array_push($idList, $widget['id']);
            };
        }

        if ($activeControlIds != null) {
            $cmdIds = array();
            foreach ($widgets as $widget) {
                if (in_array($widget['id'], $activeControlIds)) {
                    $cmdIds = array_merge($cmdIds, self::getInfosCmdIds($widget));
                }
            }
            $cmdIds = array_unique(array_filter($cmdIds, 'strlen'));
            if ($lastUpdateTime == 0) {
                $cmdData = self::getCmdValues($cmdIds);
            } else {
                $newUpdateTime =  self::waitForEvents($cmdIds, $lastUpdateTime);
                $cmdData = self::getCmdValues($cmdIds);
                $cmdData['lastUpdateTime'] = $newUpdateTime;
            }

            foreach ($activeControlIds as $deviceId) {
                $widgetIndex = array_search($deviceId, array_column($widgets, 'id'));
                if ($widgetIndex !== false) {
                    $deviceConfig = self::getDeviceConfig($widgets[$widgetIndex], $cmdData['data']);
                    if ($deviceConfig != null) {
                        array_push($devices, $deviceConfig);
                    } else {
                        array_push($devices, array('id' => $deviceId, 'deviceStatus' => "ERROR"));
                    }
                } else {
                    array_push($devices, array('id' => $deviceId, 'deviceStatus' => "NOT_FOUND"));
                }
            }
        } else {
            foreach ($widgets as $widget) {
                $deviceConfig = self::getDeviceConfig($widget, $cmdData['data']);
                if ($deviceConfig != null) {
                    array_push($devices, $deviceConfig);
                }
            }
        }

        return array("devices" => $devices, 'lastUpdateTime' => $cmdData['lastUpdateTime']);
    }

    private static function getInfosCmdIds($widget) {
        $cmdIds = array();
        switch ($widget['type']) {
            case 'generic-info-string':
            case 'generic-info-numeric':
            case 'generic-slider':
            case 'generic-switch':
            case 'power':
            case 'single-light-switch':
            case 'temperature':
            case 'door':
            case 'window':
                $cmdIds = array($widget['statusInfo']['id']);
                break;
            case 'single-light-dim':
            case 'single-light-color':
                $cmdIds = array($widget['statusInfo']['id'], $widget['brightInfo']['id']);
                break;
            case 'thermostat':
                $cmdIds = array($widget['statusInfo']['id'], $widget['setpointInfo']['id'], $widget['modeInfo']['id']);
                break;
        }
        return array_merge($cmdIds, self::getCmdIdFromText($widget["name"]));
    }

    private static function getDeviceConfig($widget, $cmdData) {
        $device = array(
            'id' => strval($widget['id']),
            'widgetId' => strval($widget['widgetId']),
            'title' => self::getFormatedText($widget["name"], $cmdData),
            'subtitle' => self::getRoomName($widget),
            'zone' => config::byKey('name') ?? self::getRoomName($widget)
        );

        $deviceType = "TYPE_UNKNOWN";
        $controlTemplate = "TYPE_STATELESS";

        switch ($widget['type']) {
            case 'door':
                $deviceType = "TYPE_DOOR";
                $device['statusText'] = $cmdData[$widget['statusInfo']['id']] > 0 ? "Ouvert" : "Fermé";
                break;
            case 'generic-action-other':
                $device['action'] = self::getActionCmd($widget['actions'][0]); // we only consider the first action
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
                self::getRangeStatus($cmdData, $widget['statusInfo'], $device);
                $device['rangeAction'] = self::getActionCmd($widget['sliderAction']);
                break;
            case 'generic-switch':
                $deviceType = "TYPE_SWITCH";
                $controlTemplate = "TYPE_TOGGLE";
                $device['onAction'] = self::getActionCmd($widget['onAction']);
                $device['offAction'] = self::getActionCmd($widget['offAction']);
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
                $device['onAction'] = self::getActionCmd($widget['onAction']);
                $device['offAction'] = self::getActionCmd($widget['offAction']);
                $device['status'] = $cmdData[$widget['statusInfo']['id']] > 0 ? 'on' : 'off';
                $device['statusText'] = $device['status'] == 'on' ? "ON" : "OFF";
                break;
            case 'single-light-dim':
            case 'single-light-color':
                $hasBrightness = is_numeric($cmdData[$widget['brightInfo']['id']]);
                $deviceType = "TYPE_LIGHT";
                $controlTemplate = $hasBrightness ? "TYPE_TOGGLE_RANGE" : "TYPE_TOGGLE";
                $device['onAction'] = self::getActionCmd($widget['onAction']);
                $device['offAction'] = self::getActionCmd($widget['offAction']);
                $device['rangeAction'] = self::getActionCmd($widget['brightAction']);
                if ($hasBrightness) {
                    self::getRangeStatus($cmdData, $widget['brightInfo'], $device);
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
                self::getRangeStatus($cmdData, $widget['setpointInfo'], $device);
                $device['rangeAction'] = self::getActionCmd($widget['setpointAction']);
                $device['modeStatus'] = self::experimentalGetMode($cmdData[$widget['modeInfo']['id']]);
                $device['modes'] = self::getModes($widget['modes']);
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

    private static function getCmdValues($cmdIds) {
        $data = array();

        foreach ($cmdIds as $cmdId) {
            $cmd = cmd::byId($cmdId);
            if (is_object($cmd)) {
                $data[$cmdId] = $cmd->execCmd();
            }
        }
        return array('data' => $data, 'lastUpdateTime' => time());
    }

    private static function waitForEvents($cmdIds, $lastUpdateTime) {
        set_time_limit(300);
        while (true) {
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

    // Utils

    private static function getRoomName($widget) {
        if (!array_key_exists('room', $widget)) return "";
        $roomObjet = jeeObject::byId(intval($widget['room']));
        return is_object($roomObjet) ? $roomObjet->getName() : 'Aucun';
    }

    private static function getActionCmd($action) {
        $res = array(
            'action' => 'execCmd',
            'cmdId' =>  $action['id']
        );
        if ($action['options'] != null)
            $res['options'] = $action['options'];
        if ($action['confirm']) {
            $res['challenge'] = "ACK";
        } else if ($action['security'] || $action['pwd']) {
            $res['challenge'] = "PIN";
        }
        return $res;
    }

    private static function getRangeStatus($cmdData, $action, &$device) {
        $device['rangeStatus'] = $cmdData[$action['id']];
        if (isset($action['minValue'])) {
            $device['minValue'] = floatval($action['minValue']);
        }
        if (isset($action['maxValue'])) {
            $device['maxValue'] = floatval($action['maxValue']);
        }
        if (isset($action['step'])) {
            $device['stepValue'] = floatval($action['step']);
        }
        if (isset($action['unit'])) {
            $device['rangeUnit'] = $action['unit'];
        }
    }

    private static function getCmdIdFromText($text) {
        preg_match_all("/#.*?#/", $text, $match);
        $res = array_map(function ($t) {
            return str_replace("#", "", $t);
        }, $match[0]);
        return $res;
    }

    private static function getFormatedText($text, $cmdData) {
        foreach (self::getCmdIdFromText($text) as $id) {
            $text = str_replace("#${id}#", $cmdData[$id], $text);
        }
        return $text;
    }

    private static function experimentalGetMode($modeName) {
        $modeName = strtolower($modeName);
        if (in_array($modeName, ["off", "eteindre", "éteindre"])) return 'off';
        if (self::string_contains($modeName, "froid")) return 'cold';
        if (self::string_contains($modeName, "chaud")) return 'heat';
        if (self::string_contains($modeName, "auto")) return 'heat_cool';
        if (self::string_contains($modeName, "eco")) return 'eco';

        return "";
    }

    private static function getModes($modes) {
        $res = array();
        foreach ($modes as $mode) {
            array_push($res, self::experimentalGetMode($mode['name']));
        }
        return array_unique($res);
    }

    private static function string_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}
