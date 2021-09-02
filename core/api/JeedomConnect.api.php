<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

header('Content-Type: application/json');

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectWidget.class.php";

$jsonData = file_get_contents("php://input");
log::add('JeedomConnect', 'debug', 'HTTP API received ' . $jsonData);
$jsonrpc = new jsonrpc($jsonData);

if ($jsonrpc->getJsonrpc() != '2.0') {
  throw new Exception(__('Requête invalide. Version JSON-RPC invalide : ', __FILE__) . $jsonrpc->getJsonrpc(), -32001);
}

$params = $jsonrpc->getParams();
$method = $jsonrpc->getMethod();

if ($method == 'GEOLOC') {
  $apiKey = $jsonrpc->getId();
} else {
  $apiKey = $params['apiKey'];
}

$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

if (!is_object($eqLogic) && $method != 'GET_PLUGIN_CONFIG' && $method != 'GET_AVAILABLE_EQUIPEMENT') {
  log::add('JeedomConnect', 'debug', "Can't find eqLogic");
  throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
}


switch ($method) {
  case 'GET_AVAILABLE_EQUIPEMENT':
    $eqLogics = eqLogic::byType('JeedomConnect');

    if (is_null($eqLogics)) {
      throw new Exception(__("No equipment available", __FILE__), -32699);
    } else {
      $result = array();
      $userConnected = user::byHash($params['userHash']);
      $userConnectedProfil = is_object($userConnected) ? $userConnected->getProfils() : null;
      foreach ($eqLogics as $eqLogic) {

        $userOnEquipment = user::byId($eqLogic->getConfiguration('userId'));
        $userOnEquipmentHash = !is_null($userOnEquipment) ? $userOnEquipment->getHash() : null;

        if (strtolower($userConnectedProfil) == 'admin' || $userOnEquipmentHash == $params['userHash']) {
          array_push($result, array(
            'logicalId' => $eqLogic->getLogicalId(),
            'name' => $eqLogic->getName(),
            'enable' => $eqLogic->getIsEnable(),
            'useWs' => $eqLogic->getConfiguration('useWs', 0)
          ));
        }
      }
    }

    $jsonrpc->makeSuccess(array(
      'type' => 'AVAILABLE_EQUIPEMENT',
      'payload' => $result
    ));
    break;

  case 'GET_PLUGIN_CONFIG':
    $jsonrpc->makeSuccess(array(
      'type' => 'PLUGIN_CONFIG',
      'payload' => apiHelper::getPluginConfig()
    ));
    break;
  case 'CONNECT':
    $versionPath = dirname(__FILE__) . '/../../plugin_info/version.json';
    $versionJson = json_decode(file_get_contents($versionPath));
    if ($eqLogic->getConfiguration('deviceId') == '') {
      log::add('JeedomConnect', 'info', "Register new device {$params['deviceName']}");
      $eqLogic->registerDevice($params['deviceId'], $params['deviceName']);
    }
    $eqLogic->registerToken($params['token']);
    //check registered device
    if ($eqLogic->getConfiguration('deviceId') != $params['deviceId']) {
      log::add('JeedomConnect', 'warning', "Authentication failed (invalid device)");
      $jsonrpc->makeSuccess(array('type' => 'BAD_DEVICE'));
      return;
    }

    //check version requierement
    if (version_compare($params['appVersion'], $versionJson->require, "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad version requierement");
      $jsonrpc->makeSuccess(array(
        'type' => 'APP_VERSION_ERROR',
        'payload' => JeedomConnect::getPluginInfo()
      ));
      return;
    }
    if (version_compare($versionJson->version, $params['pluginRequire'], "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad plugin requierement");
      $jsonrpc->makeSuccess(array('type' => 'PLUGIN_VERSION_ERROR'));
      return;
    }
    $user = user::byId($eqLogic->getConfiguration('userId'));
    if ($user == null) {
      $user = user::all()[0];
      $eqLogic->setConfiguration('userId', $user->getId());
      $eqLogic->save();
    }

    $config = $eqLogic->getGeneratedConfigFile();

    //check config content
    if (is_null($config)) {
      log::add('JeedomConnect', 'warning', "Failed to connect : empty config file");
      $jsonrpc->makeSuccess(array('type' => 'EMPTY_CONFIG_FILE'));
      return;
    }
    //check config format version
    if (!array_key_exists('formatVersion', $config)) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad format version");
      $jsonrpc->makeSuccess(array('type' => 'FORMAT_VERSION_ERROR'));
      return;
    }

    $eqLogic->setConfiguration('platformOs', $params['platformOs']);
    $eqLogic->save();

    $result = array(
      'type' => 'WELCOME',
      'payload' => array(
        'pluginVersion' => $versionJson->version,
        'useWs' => $eqLogic->getConfiguration('useWs', 0),
        'userHash' => $user->getHash(),
        'userId' => $user->getId(),
        'userProfil' => $user->getProfils(),
        'configVersion' => $eqLogic->getConfiguration('configVersion'),
        'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
        'webviewEnabled' => $eqLogic->getConfiguration('webviewEnabled') == '1',
        'editEnabled' => $eqLogic->getConfiguration('editEnabled') == '1',
        'pluginConfig' => apiHelper::getPluginConfig(),
        'cmdInfo' => apiHelper::getCmdInfoData($config),
        'scInfo' => apiHelper::getScenarioData($config),
        'objInfo' => apiHelper::getObjectData($config)
      )
    );
    log::add('JeedomConnect', 'debug', 'send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_EVENTS':
    $eqLogic->setConfiguration('lastSeen', time());
    $eqLogic->save();
    $config = $eqLogic->getGeneratedConfigFile();
    $newConfig = apiHelper::lookForNewConfig(eqLogic::byLogicalId($apiKey, 'JeedomConnect'), $params['configVersion']);
    if ($newConfig != false) {
      log::add('JeedomConnect', 'debug', "pollingServer send new config : " . json_encode($newConfig));
      $jsonrpc->makeSuccess(array($newConfig));
      return;
    }

    $actions = JeedomConnectActions::getAllAction($apiKey);
    if (count($actions) > 0) {
      $result = array(
        'type' => 'ACTIONS',
        'payload' => array()
      );
      foreach ($actions as $action) {
        array_push($result['payload'], $action['value']['payload']);
      }
      log::add('JeedomConnect', 'debug', "send action " . json_encode(array($result)));
      JeedomConnectActions::removeAllAction($actions);
      $jsonrpc->makeSuccess(array($result));
      return;
    }
    $events = event::changes($params['lastReadTimestamp']);
    $data = apiHelper::getEvents($events, $config, $eqLogic->getConfiguration('scAll', 0) == 1);
    $jsonrpc->makeSuccess($data);
    break;
  case 'REGISTER_DEVICE':
    $rdk = apiHelper::registerUser($eqLogic, $params['userHash'], $params['rdk']);
    if (!isset($rdk)) {
      log::add('JeedomConnect', 'debug', "user not valid");
      throw new Exception(__("User not valid", __FILE__), -32699);
    }
    $jsonrpc->makeSuccess(array(
      'type' => 'REGISTERED',
      'payload' => array(
        'rdk' => $rdk
      )
    ));
    break;
  case 'GET_CONFIG':
    $result = $eqLogic->getGeneratedConfigFile();
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_PWD':
    $jsonrpc->makeSuccess(array('pwd' => $eqLogic->getConfiguration('pwdAction', null)));
    break;
  case 'GET_CMD_INFO':
    $result = array(
      'type' => 'SET_CMD_INFO',
      'payload' => apiHelper::getCmdInfoData($eqLogic->getGeneratedConfigFile())
    );
    log::add('JeedomConnect', 'debug', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_SC_INFO':
    $result = array(
      'type' => 'SET_SC_INFO',
      'payload' => apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile())
    );
    log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_ALL_SC':
    $result = array(
      'type' => 'SET_ALL_SC',
      'payload' => apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile(), true)
    );
    $eqLogic->setConfiguration('scAll', 1);
    $eqLogic->save();
    log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_JEEDOM_DATA':
    $result = apiHelper::getFullJeedomData();
    log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_WIDGET_DATA':
    $result = apiHelper::getWidgetData();
    $jsonrpc->makeSuccess($result);
    break;
  case 'UNSUBSCRIBE_SC':
    $eqLogic->setConfiguration('scAll', 0);
    $eqLogic->save();
    break;
  case 'GET_OBJ_INFO':
    $result = array(
      'type' => 'SET_OBJ_INFO',
      'payload' => apiHelper::getObjectData($eqLogic->getGeneratedConfigFile())
    );
    log::add('JeedomConnect', 'info', 'Send objects ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_INFO':
    $config = $eqLogic->getGeneratedConfigFile();
    $result = array(
      'type' => 'SET_INFO',
      'payload' => array(
        'cmds' => apiHelper::getCmdInfoData($config),
        'scenarios' => apiHelper::getScenarioData($config),
        'objects' => apiHelper::getObjectData($config)
      )
    );
    log::add('JeedomConnect', 'info', 'Send info ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_HISTORY':
    $jsonrpc->makeSuccess(apiHelper::getHistory($params['id'], $params['options']));
    break;
  case 'GET_BATTERIES':
    $jsonrpc->makeSuccess(apiHelper::getBatteries());
    break;
  case 'GET_GEOFENCES':
    $result = apiHelper::getGeofencesData($eqLogic);
    log::add('JeedomConnect', 'info', 'GEOFENCES ' . json_encode($result));
    if (count($result['payload']['geofences']) > 0) {
      log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
      $jsonrpc->makeSuccess($result);
    }
    break;
  case 'GET_JEEDOM_GLOBAL_HEALTH':
    $jsonrpc->makeSuccess(apiHelper::getJeedomHealthDetails($apiKey));
    break;
  case 'DAEMON_PLUGIN_RESTART':
    $jsonrpc->makeSuccess(array('result' => apiHelper::restartDaemon($params['userId'], $params['pluginId'])));
    break;
  case 'DAEMON_PLUGIN_STOP':
    $jsonrpc->makeSuccess(array('result' => apiHelper::stopDaemon($params['userId'], $params['pluginId'])));
    break;
  case 'GET_PLUGINS_UPDATE':
    $jsonrpc->makeSuccess(apiHelper::getPluginsUpdate());
    break;
  case 'DO_PLUGIN_UPDATE':
    $jsonrpc->makeSuccess(array('result' => apiHelper::doUpdate($params['pluginId'])));
    break;
  case 'CMD_EXEC':
    apiHelper::execCmd($params['id'], $params['options']);
    $jsonrpc->makeSuccess();
    break;
  case 'CMDLIST_EXEC':
    apiHelper::execMultipleCmd($params['cmdList']);
    $jsonrpc->makeSuccess();
    break;
  case 'SC_EXEC':
    apiHelper::execSc($params['id'], $params['options']);
    $jsonrpc->makeSuccess();
    break;
  case 'SC_STOP':
    apiHelper::stopSc($params['id']);
    $jsonrpc->makeSuccess();
    break;
  case 'SC_SET_ACTIVE':
    apiHelper::setActiveSc($params['id'], $params['active']);
    $jsonrpc->makeSuccess();
    break;
  case 'SET_BATTERY':
    apiHelper::saveBatteryEquipment($apiKey, $params['level']);
    $jsonrpc->makeSuccess();
    break;
  case 'SET_WIDGET':
    apiHelper::setWidget($params['widget']);
    $jsonrpc->makeSuccess();
    break;
  case 'ADD_WIDGETS':
    apiHelper::addWidgets($eqLogic, $params['widgets'], $params['parentId'], $params['index']);
    $jsonrpc->makeSuccess();
    break;
  case 'REMOVE_WIDGET':
    apiHelper::removeWidget($eqLogic, $params['widgetId']);
    $jsonrpc->makeSuccess();
    break;
  case 'MOVE_WIDGET':
    apiHelper::moveWidget($eqLogic, $params['widgetId'], $params['destinationId'], $params['destinationIndex']);
    $jsonrpc->makeSuccess();
    break;
  case 'SET_CUSTOM_WIDGETS':
    apiHelper::setCustomWidgetList($eqLogic, $params['customWidgetList']);
    $jsonrpc->makeSuccess();
    break;
  case 'SET_GROUP':
    apiHelper::setGroup($eqLogic, $params['group']);
    $jsonrpc->makeSuccess();
    break;
  case 'REMOVE_GROUP':
    apiHelper::removeGroup($eqLogic, $params['id']);
    $jsonrpc->makeSuccess();
    break;
  case 'ADD_GROUP':
    apiHelper::addGroup($eqLogic, $params['group']);
    $jsonrpc->makeSuccess();
    break;
  case 'MOVE_GROUP':
    apiHelper::moveGroup($eqLogic, $params['groupId'], $params['destinationId'], $params['destinationIndex']);
    $jsonrpc->makeSuccess();
    break;
  case 'REMOVE_GLOBAL_WIDGET':
    apiHelper::removeGlobalWidget($params['id']);
    $jsonrpc->makeSuccess();
    break;
  case 'ADD_GLOBAL_WIDGET':
    $jsonrpc->makeSuccess(apiHelper::addGlobalWidget($params['widget']));
    break;
  case 'SET_BOTTOM_TABS':
    apiHelper::setBottomTabList($eqLogic, $params['tabs'], $params['migrate'], $params['idCounter']);
    break;
  case 'REMOVE_BOTTOM_TAB':
    apiHelper::removeBottomTab($eqLogic, $params['id']);
    break;
  case 'SET_TOP_TABS':
    apiHelper::setTopTabList($eqLogic, $params['tabs'], $params['migrate'], $params['idCounter']);
    break;
  case 'REMOVE_TOP_TAB':
    apiHelper::removeTopTab($eqLogic, $params['id']);
    break;
  case 'MOVE_TOP_TAB':
    apiHelper::moveTopTab($eqLogic, $params['sectionId'], $params['destinationId']);
    $jsonrpc->makeSuccess();
    break;
  case 'SET_PAGE_DATA':
    apiHelper::setPageData($eqLogic, $params['rootData'], $params['idCounter']);
    break;
  case 'SET_ROOMS':
    apiHelper::setRooms($eqLogic, $params['rooms']);
    break;
  case 'SET_SUMMARIES':
    apiHelper::setSummaries($eqLogic, $params['summaries']);
    break;
  case 'SET_BACKGROUNDS':
    apiHelper::setBackgrounds($eqLogic, $params['backgrounds']);
    break;
  case 'SET_APP_CONFIG':
    apiHelper::setAppConfig($apiKey, $params['config']);
    $jsonrpc->makeSuccess();
    break;
  case 'GET_APP_CONFIG':
    $result = apiHelper::getAppConfig($apiKey, $params['configId']);
    $jsonrpc->makeSuccess($result);
    break;
  case 'ADD_GEOFENCE':
    $eqLogic->addGeofenceCmd($params['geofence']);
    $jsonrpc->makeSuccess();
    break;
  case 'REMOVE_GEOFENCE':
    $eqLogic->removeGeofenceCmd($params['geofence']);
    $jsonrpc->makeSuccess();
    break;
  case 'GEOLOC':
    $ts = array_key_exists('timestampMeta', $params) ? floor($params['timestampMeta']['systemTime'] / 1000) : strtotime($params['timestamp']);
    $eqLogic->setCoordinates($params['coords']['latitude'], $params['coords']['longitude'], $params['coords']['altitude'], $params['activity']['type'], $params['battery']['level'] * 100, $ts);

    $activityCmd = $eqLogic->getCmd(null, 'activity');
    if (is_object($activityCmd)) {
      $activityCmd->event($params['activity']['type']);
    }

    if (array_key_exists('battery', $params)) {
      if ($params['battery']['level'] > -1) {
        $batteryCmd = $eqLogic->getCmd(null, 'battery');
        if (is_object($batteryCmd)) {
          $batteryCmd->event($params['battery']['level'] * 100, date('Y-m-d H:i:s', $ts));
        }
      }
    }


    /*if (array_key_exists('geofence', $params) ) {
    $geofenceCmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'geofence_' . $params['geofence']['identifier']);
    if (!is_object($geofenceCmd)) {
      log::add('JeedomConnect', 'error', "Can't find geofence command");
      return;
    }
    if ($params['geofence']['action'] == 'ENTER') {
      if ($geofenceCmd->execCmd() != 1) {
        log::add('JeedomConnect', 'debug', "Set 1 for geofence " . $params['geofence']['extras']['name']);
        $geofenceCmd->event(1);
      }
    } else if ($params['geofence']['action'] == 'EXIT') {
      if ($geofenceCmd->execCmd() != 0) {
        log::add('JeedomConnect', 'debug', "Set 0 for geofence " . $params['geofence']['extras']['name']);
        $geofenceCmd->event(0);
      }
    }
  } else {
    $eqLogic->setGeofencesByCoordinates($params['coords']['latitude'], $params['coords']['longitude']);
  }*/
    break;
  case 'ASK_REPLY':
    $answer = $params['answer'];
    $cmd = cmd::byId($params['cmdId']);
    if (!is_object($cmd)) {
      log::add('JeedomConnect', 'error', "Can't find command");
      return;
    }
    if ($cmd->askResponse($answer)) {
      log::add('JeedomConnect', 'debug', 'reply to ask OK');
    }
    break;
  case 'GET_FILES':
    $result = apiHelper::getFiles($params['folder'], $params['recursive']);
    log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'REMOVE_FILE':
    $result = apiHelper::removeFile($params['file']);
    log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
}
