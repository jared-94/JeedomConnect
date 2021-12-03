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
 *
 */

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

class apiHelper {

  public static function dispatch($type, $method, $eqLogic, $param, $apiKey) {

    try {
      switch ($method) {
        case 'PING':
          $eqLogic->setConfiguration('appState', 'active');
          $eqLogic->save();
          return null;
          break;

        case 'REGISTER_DEVICE':
          $rdk = self::registerUser($eqLogic, $param['userHash'], $param['rdk']);
          return $rdk;
          break;

        case 'GET_AVAILABLE_EQUIPEMENT':
          $result = self::getAvailableEquipement($param['userHash']);
          return $result;
          break;

        case 'CMD_EXEC':
          self::execCmd($param['id'], $param['options'] ?? null);
          return null;
          break;

        case 'CMDLIST_EXEC':
          self::execMultipleCmd($param['cmdList']);
          return null;
          break;

        case 'SC_EXEC':
          self::execSc($param['id'], $param['options']);
          return null;
          break;

        case 'SC_STOP':
          self::stopSc($param['id']);
          return null;
          break;

        case 'SC_SET_ACTIVE':
          self::setActiveSc($param['id'], $param['active']);
          return null;
          break;

        case 'GET_PLUGIN_CONFIG':
          $conf = self::getPluginConfig($eqLogic);
          return $conf;
          break;

        case 'GET_CONFIG':
          // TODO wrong way to send an answer
          $config = $eqLogic->getGeneratedConfigFile();
          return $config;
          break;

        case 'GET_PWD':
          // TODO wrong way to send an answer
          return array('pwd' => $eqLogic->getConfiguration('pwdAction', null));
          break;

        case 'GET_BATTERIES':
          $config = self::getBatteries();
          return $config;
          break;

        case 'GET_CMD_INFO':
          $result = self::getCmdInfoData($eqLogic->getGeneratedConfigFile());
          return $result;
          break;

        case 'GET_OBJ_INFO':
          $result = self::getObjectData($eqLogic->getGeneratedConfigFile());
          return $result;
          break;

        case 'GET_SC_INFO':
          $result = self::getScenarioData($eqLogic->getGeneratedConfigFile());
          return $result;
          break;

        case 'GET_ALL_SC':
          $eqLogic->setConfiguration('scAll', 1);
          $eqLogic->save();
          $result = self::getScenarioData($eqLogic->getGeneratedConfigFile(), true);
          return $result;
          break;

        case 'GET_INFO':
          $result = self::getAllInformations($eqLogic);
          return $result;
          break;

        case 'SET_APPSTATE':
          $eqLogic->setConfiguration('appState', $param['state']);
          $eqLogic->save();
          return null;
          break;

        case 'GET_JEEDOM_DATA':
          $result = self::getFullJeedomData();
          return $result;
          break;

        case 'GET_WIDGET_DATA':
          $result = self::getWidgetData();
          return $result;
          break;

        case 'GET_WIDGET_WITH_GEN_TYPE':
          $result = self::getWidgetFromGenType($param['widget_type'], $param['eqId'] ?? null);
          return $result;
          break;

        case 'GET_PLUGINS_UPDATE':
          return self::getPluginsUpdate();
          break;

        case 'DO_PLUGIN_UPDATE':
          $result = self::doUpdate($param['pluginId']);
          return array('result' => $result);
          break;

        case 'GET_JEEDOM_GLOBAL_HEALTH':
          return self::getJeedomHealthDetails($apiKey);
          break;

        case 'DAEMON_PLUGIN_RESTART':
          $result = self::restartDaemon($param['userId'], $param['pluginId']);
          return array('result' => $result);
          break;

        case 'DAEMON_PLUGIN_STOP':
          $result = self::stopDaemon($param['userId'], $param['pluginId']);
          return array('result' => $result);
          break;

        case 'UNSUBSCRIBE_SC':
          $eqLogic->setConfiguration('scAll', 0);
          $eqLogic->save();
          return null;
          break;

        case 'GET_HISTORY':
          return self::getHistory($param['id'], $param['options']);
          break;

        case 'GET_FILES':
          return self::getFiles($param['folder'], $param['recursive']);
          break;

        case 'REMOVE_FILE':
          return self::removeFile($param['file']);
          break;

        case 'SET_BATTERY':
          self::saveBatteryEquipment($apiKey, $param['level']);
          return null;
          break;

        case 'SET_WIDGET':
          self::setWidget($param['widget']);
          return null;
          break;

        case 'ADD_WIDGETS':
          self::addWidgets($eqLogic, $param['widgets'], $param['parentId'], $param['index']);
          return null;
          break;

        case 'REMOVE_WIDGET':
          self::removeWidget($eqLogic, $param['widgetId']);
          return null;
          break;

        case 'MOVE_WIDGET':
          self::moveWidget($eqLogic, $param['widgetId'], $param['destinationId'], $param['destinationIndex']);
          return null;
          break;

        case 'SET_CUSTOM_WIDGETS':
          self::setCustomWidgetList($eqLogic, $param['customWidgetList']);
          return null;
          break;

        case 'SET_GROUP':
          self::setGroup($eqLogic, $param['group']);
          return null;
          break;

        case 'REMOVE_GROUP':
          self::removeGroup($eqLogic, $param['id']);
          return null;
          break;

        case 'ADD_GROUP':
          self::addGroup($eqLogic, $param['group']);
          return null;
          break;

        case 'MOVE_GROUP':
          self::moveGroup($eqLogic, $param['groupId'], $param['destinationId'], $param['destinationIndex']);
          return null;
          break;

        case 'REMOVE_GLOBAL_WIDGET':
          self::removeGlobalWidget($param['id']);
          return null;
          break;

        case 'ADD_GLOBAL_WIDGETS':
          $result = self::addGlobalWidgets($param['widgets']);
          return $result;
          break;

        case 'SET_BOTTOM_TABS':
          self::setBottomTabList($eqLogic, $param['tabs'], $param['migrate'], $param['idCounter']);
          return null;
          break;

        case 'REMOVE_BOTTOM_TAB':
          self::removeBottomTab($eqLogic, $param['id']);
          return null;
          break;

        case 'SET_TOP_TABS':
          self::setTopTabList($eqLogic, $param['tabs'], $param['migrate'], $param['idCounter']);
          return null;
          break;

        case 'REMOVE_TOP_TAB':
          self::removeTopTab($eqLogic, $param['id']);
          return null;
          break;

        case 'MOVE_TOP_TAB':
          self::moveTopTab($eqLogic, $param['sectionId'], $param['destinationId']);
          return null;
          break;

        case 'SET_PAGE_DATA':
          self::setPageData($eqLogic, $param['rootData'], $param['idCounter']);
          return null;
          break;

        case 'SET_ROOMS':
          self::setRooms($eqLogic, $param['rooms']);
          return null;
          break;

        case 'SET_SUMMARIES':
          self::setSummaries($eqLogic, $param['summaries']);
          return null;
          break;

        case 'SET_BACKGROUNDS':
          self::setBackgrounds($eqLogic, $param['backgrounds']);
          return null;
          break;

        case 'SET_APP_CONFIG':
          self::setAppConfig($apiKey, $param['config']);
          return null;
          break;

        case 'GET_APP_CONFIG':
          return self::getAppConfig($apiKey, $param['configId']);
          break;

        case 'ADD_GEOFENCE':
          self::addGeofence($eqLogic, $param['geofence'], $param['coordinates']);
          return null;
          break;

        case 'REMOVE_GEOFENCE':
          self::removeGeofence($eqLogic, $param['geofence']);
          return null;
          break;

        case 'GET_GEOFENCES':
          $result = self::getGeofencesData($eqLogic);
          return $result;
          break;

        case 'GET_NOTIFS_CONFIG':
          $result = self::getNotifConfig($eqLogic);
          return $result;
          break;

        case 'GEOLOC':
          self::setGeofence($eqLogic, $param);
          return null;
          break;

        case 'ASK_REPLY':
          self::setAskReply($param);
          return null;
          break;

        case 'GET_EVENTS':
          $eqLogic->setConfiguration('lastSeen', time());
          $eqLogic->save();


          $newConfig = array(
            'type' => 'SET_CONFIG',
            'payload' => apiHelper::lookForNewConfig(eqLogic::byLogicalId($apiKey, 'JeedomConnect'), $param['configVersion']) ?: array()
          );


          $actions = self::getJCActions($apiKey);

          $allEvents = self::addTypeInPayload(self::getEventsFull($eqLogic, $param['lastReadTimestamp']), 'ALL_EVENTS');

          $payload = array($newConfig, $actions, $allEvents);
          $result = self::addTypeInPayload($payload, 'SET_EVENTS');

          return $result;
          break;


        case 'CONNECT':
          $result = self::checkConnexion($eqLogic, $param);
          return $result;
          break;

        default:
          return self::raiseException($method . ' [' . $type . '] - method not defined');
          break;
      }
    } catch (Exception $e) {
      return self::raiseException($method . ' [' . $type . '] - ' . $e->getMessage());
    }
  }

  // GENERIC FUNCTIONS


  public static function addTypeInPayload($payload, $type) {
    $result = array(
      'type' => $type,
      'payload' => $payload
    );

    return $result;
  }

  public static function getAllInformations($eqLogic, $withType = true) {
    $returnType = 'SET_INFO';

    if (!is_object($eqLogic)) {
      throw new Exception('No equipment found');
    }

    $config = $eqLogic->getGeneratedConfigFile();
    $payload =  array(
      'cmds' => apiHelper::getCmdInfoData($config, false),
      'scenarios' => apiHelper::getScenarioData($config, false, false),
      'objects' => apiHelper::getObjectData($config, false)
    );

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }



  // CONNEXION FUNCTIONS
  public static function checkConnexion($eqLogic, $param, $withType = true) {

    $versionJson = JeedomConnect::getPluginInfo();

    if ($eqLogic->getConfiguration('deviceId') == '') {
      log::add('JeedomConnect', 'info', "Register new device {$param['deviceName']}");
      $eqLogic->registerDevice($param['deviceId'], $param['deviceName']);
    }
    $eqLogic->registerToken($param['token']);

    //check registered device
    if ($eqLogic->getConfiguration('deviceId') != $param['deviceId']) {
      log::add('JeedomConnect', 'warning', "Authentication failed (invalid device)");
      return array('type' => 'BAD_DEVICE');
    }

    //check if eqLogic is enable
    if (!$eqLogic->getIsEnable()) {
      log::add('JeedomConnect', 'warning', "Equipment " . $eqLogic->getName() . " disabled");
      return array('type' => 'EQUIPMENT_DISABLE');
    }

    //check version requirement
    if (version_compare($param['appVersion'], $versionJson['require'], "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad version requirement");
      return array(
        'type' => 'APP_VERSION_ERROR',
        'payload' => JeedomConnect::getPluginInfo()
      );
    }

    if (version_compare($versionJson['version'], $param['pluginRequire'], "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad plugin requirement");
      return array('type' => 'PLUGIN_VERSION_ERROR');
    }

    $user = user::byId($eqLogic->getConfiguration('userId'));
    if ($user == null) {
      $user = user::all()[0];
      $eqLogic->setConfiguration('userId', $user->getId());
      $eqLogic->save();
    }

    $userConnected = user::byHash($param['userHash']);
    if (!is_object($userConnected)) $userConnected = $user;

    $config = $eqLogic->getGeneratedConfigFile();

    //check config content
    if (is_null($config)) {
      log::add('JeedomConnect', 'warning', "Failed to connect : empty config file");
      return array('type' => 'EMPTY_CONFIG_FILE');
    }

    //check config format version
    if (!array_key_exists('formatVersion', $config)) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad format version");
      return array('type' => 'FORMAT_VERSION_ERROR');
    }

    $eqLogic->setConfiguration('platformOs', $param['platformOs']);
    $eqLogic->setConfiguration('appVersion', $param['appVersion'] ?? '#NA#');
    $eqLogic->setConfiguration('polling', $param['polling'] ?? '0');
    $eqLogic->setConfiguration('connected', 1);
    $eqLogic->setConfiguration('scAll', 0);
    $eqLogic->setConfiguration('appState', 'active');
    $eqLogic->save();

    return self::getWelcomeMsg($eqLogic, $userConnected, $versionJson['version'], $withType);
  }


  public static function getWelcomeMsg($eqLogic, $userConnected, $pluginVersion, $withType = true) {
    $returnType = 'WELCOME';

    $config = $eqLogic->getGeneratedConfigFile();

    $payload = array(
      'pluginVersion' => $pluginVersion,
      'useWs' => $eqLogic->getConfiguration('useWs', 0),
      'userHash' => $userConnected->getHash(),
      'userId' => $userConnected->getId(),
      'userName' => $userConnected->getLogin(),
      'userProfil' => $userConnected->getProfils(),
      'configVersion' => $eqLogic->getConfiguration('configVersion'),
      'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
      'webviewEnabled' => $eqLogic->getConfiguration('webviewEnabled') == '1',
      'editEnabled' => $eqLogic->getConfiguration('editEnabled') == '1',
      'pluginConfig' => self::getPluginConfig($eqLogic, false),
      'cmdInfo' => self::getCmdInfoData($config, false),
      'scInfo' => self::getScenarioData($config, false, false),
      'objInfo' => self::getObjectData($config, false),
      'links' => JeedomConnectUtils::getLinks()

    );

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  public static function setAskReply($param) {
    $answer = $param['answer'];
    $cmd = cmd::byId($param['cmdId']);
    if (!is_object($cmd)) {
      throw new Exception("Can't find command [id=" . $param['cmdId'] . "]");
    }
    if (!$cmd->askResponse($answer)) {
      log::add('JeedomConnect', 'warning', 'issue while reply to ask');
    }

    // if ASK was sent to other equipment, then we will let them know that an answer was already given
    if (!empty($param['otherAskCmdId']) && !is_null($param['otherAskCmdId'])) {
      $eqLogic = eqLogic::byLogicalId($param['apiKey'], 'JeedomConnect');
      $eqName = $eqLogic->getName();

      foreach ($param['otherAskCmdId'] as $cmdId) {
        $cmd = JeedomConnectCmd::byId($cmdId);
        if (is_object($cmd)) {
          $cmd->cancelAsk($param['notificationId'], $answer, $eqName, $param['dateAnswer']);
        }
      }
    }

    return;
  }

  // EQUIPMENT FUNCTIONS
  public static function getAvailableEquipement($userHash, $withType = true) {
    $returnType = 'AVAILABLE_EQUIPEMENT';

    $eqLogics = eqLogic::byType('JeedomConnect');

    if (is_null($eqLogics)) {
      throw new Exception(__("No equipment available", __FILE__), -32699);
    }

    $payload = array();
    $userConnected = user::byHash($userHash);
    $userConnectedProfil = is_object($userConnected) ? $userConnected->getProfils() : null;
    foreach ($eqLogics as $eqLogic) {

      $userOnEquipment = user::byId($eqLogic->getConfiguration('userId'));
      $userOnEquipmentHash = !is_null($userOnEquipment) ? $userOnEquipment->getHash() : null;

      if (strtolower($userConnectedProfil) == 'admin' || $userOnEquipmentHash == $userHash) {
        array_push($payload, array(
          'logicalId' => $eqLogic->getLogicalId(),
          'name' => $eqLogic->getName(),
          'enable' => $eqLogic->getIsEnable(),
          'useWs' => $eqLogic->getConfiguration('useWs', 0)
        ));
      }
    }

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }


  // CMD FUNCTIONS

  public static function getInfoCmdList($config) {
    $return = array();
    foreach ($config['payload']['widgets'] as $widget) {
      foreach ($widget as $item => $value) {
        if (is_array($value)) {
          if (array_key_exists('type', $value)) {
            if ($value['type'] == 'info') {
              array_push($return, $value['id']);
            }
          }
          if ($item == 'moreInfos') {
            foreach ($value as $i => $info) {
              array_push($return, $info['id']);
            }
          }
        }
      }
    }
    if (array_key_exists('background', $config['payload'])) {
      foreach ($config['payload']['background']['condBackgrounds'] as $cond) {
        preg_match_all("/#([a-zA-Z0-9]*)#/", $cond['condition'], $matches);
        if (count($matches) > 0) {
          $matches = array_unique($matches[0]);
          foreach ($matches as $match) {
            $cmd = cmd::byId(str_replace('#', '', $match));
            if (is_object($cmd)) {
              array_push($return, str_replace('#', '', $match));
            }
          }
        }
      }
    }
    if (array_key_exists('weather', $config['payload'])) {
      $return = array_merge($return, array(
        $config['payload']['weather']['condition'],
        $config['payload']['weather']['condition_id'],
        $config['payload']['weather']['sunrise'],
        $config['payload']['weather']['sunset'],
        $config['payload']['weather']['temperature'],
        $config['payload']['weather']['temperature_min'],
        $config['payload']['weather']['temperature_max']
      ));
    }

    foreach ($config['payload']['customData']['widgets'] as $widgetId => $widget) {
      foreach ($widget as $item => $value) {
        if ($item == 'moreInfos') {
          foreach ($value as $i => $info) {
            array_push($return, $info['id']);
          }
        }
      }
    }

    return array_unique($return);
  }

  public static function getCmdInfoData($config, $withType = true) {
    $returnType = 'SET_CMD_INFO';

    $cmds = cmd::byIds(self::getInfoCmdList($config));
    $payload = array();

    foreach ($cmds as $cmd) {
      $state = $cmd->getCache(array('valueDate', 'value'));
      $cmd_info = array(
        'id' => $cmd->getId(),
        'value' => $state['value'],
        'modified' => strtotime($state['valueDate'])
      );
      array_push($payload, $cmd_info);
    }

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  // SCENARIO FUNCTIONS

  public static function getScenarioList($config) {
    $return = array();
    foreach ($config['payload']['widgets'] as $widget) {
      if (array_key_exists('type', $widget)) {
        if ($widget['type'] == 'scenario') {
          array_push($return, $widget['scenarioId']);
        }
      }
    }
    return array_unique($return);
  }

  public static function getScenarioData($config, $all = false, $withType = true) {
    $returnType = $all ? 'SET_ALL_SC' : 'SET_SC_INFO';

    $scIds = self::getScenarioList($config);
    $payload = array();

    foreach (scenario::all() as $sc) {
      if (in_array($sc->getId(), $scIds) || $all) {
        $state = $sc->getCache(array('state', 'lastLaunch'));
        $sc_info = array(
          'id' => $sc->getId(),
          'name' => $sc->getName(),
          'object' => $sc->getObject() == null ? 'Aucun' : $sc->getObject()->getName(),
          'group' => $sc->getGroup() == '' ? 'Aucun' : $sc->getGroup(),
          'status' => $state['state'],
          'lastLaunch' => strtotime($state['lastLaunch']),
          'active' => $sc->getIsActive() ? 1 : 0,
          'icon' => self::getScenarioIcon($sc)
        );
        array_push($payload, $sc_info);
      }
    }

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  public static function getScenarioIcon($sc) {
    if (strpos($sc->getDisplay('icon'), '<i') === 0) {
      return trim(str_replace(array('<i', 'class=', '"', '/>', '></i>'), '', $sc->getDisplay('icon')));
    }
    return null;
  }

  // OBJECT FUNCTIONS
  public static function getObjectList($config) {
    $return = array();
    foreach ($config['payload']['rooms'] as $room) {
      if (array_key_exists("id", $room)) {
        array_push($return, $room['id']);
      }
    }
    return array_unique($return);
  }

  public static function getObjectData($config, $withType = true) {
    $returnType = 'SET_OBJ_INFO';

    $objIds = self::getObjectList($config);
    $payload = array();

    foreach (jeeObject::all() as $object) {
      if (in_array($object->getId(), $objIds)) {
        $object_info = array(
          'object_id' => $object->getId(),
          'keys' => array()
        );
        foreach ($object->getConfiguration('summary') as $key => $value) {
          $sum = $object->getSummary($key);
          array_push($object_info['keys'], array(
            $key => array('value' => $sum, 'cmds' => $value)
          ));
        }
        array_push($payload, $object_info);
      }
    }
    $global_info = array(
      'object_id' => 'global',
      'keys' => array()
    );
    $globalSum = config::byKey('object:summary');
    foreach ($globalSum as $key => $value) {
      array_push($global_info['keys'], array(
        $key => array('value' => jeeObject::getGlobalSummary($key))
      ));
    }
    array_push($payload, $global_info);

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  // NOTIFICATION FUNCTIONS
  public static function getNotifConfig($eqLogic, $withType = true) {
    $returnType = 'SET_NOTIFS_CONFIG';

    $payload =  $eqLogic->getNotifs();

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  // GEOFENCE FUNCTIONS
  public static function addGeofence($eqLogic, $geo, $coordinates) {
    return $eqLogic->addGeofenceCmd($geo, $coordinates);
  }

  public static function removeGeofence($eqLogic, $geo) {
    return $eqLogic->removeGeofenceCmd($geo);
  }

  public static function setGeofence($eqLogic, $param) {
    $ts = array_key_exists('timestampMeta', $param) ? floor($param['timestampMeta']['systemTime'] / 1000) : strtotime($param['timestamp']);
    $eqLogic->setCoordinates($param['coords']['latitude'], $param['coords']['longitude'], $param['coords']['altitude'], $param['activity']['type'], $param['battery']['level'] * 100, $ts);

    $activityCmd = $eqLogic->getCmd(null, 'activity');
    if (is_object($activityCmd)) {
      $activityCmd->event($param['activity']['type']);
    }

    if (array_key_exists('battery', $param)) {
      if ($param['battery']['level'] > -1) {
        $batteryCmd = $eqLogic->getCmd(null, 'battery');
        if (is_object($batteryCmd)) {
          $batteryCmd->event($param['battery']['level'] * 100, date('Y-m-d H:i:s', $ts));
        }
      }
    }
    return;
  }
  public static function getGeofencesData($eqLogic) {
    $result = array(
      'type' => 'SET_GEOFENCES',
      'payload' => array(
        'geofences' => array()
      )
    );
    foreach ($eqLogic->getCmd('info') as $cmd) {
      if (substr($cmd->getLogicalId(), 0, 8) === "geofence") {
        array_push($result['payload']['geofences'], array(
          'identifier' => substr($cmd->getLogicalId(), 9),
          'extras' => array(
            'name' => $cmd->getName()
          ),
          'radius' => $cmd->getConfiguration('radius'),
          'latitude' => $cmd->getConfiguration('latitude'),
          'longitude' => $cmd->getConfiguration('longitude'),
          'notifyOnEntry' => true,
          'notifyOnExit' => true
        ));
      }
    }

    if (count($result['payload']['geofences']) > 0) {
      return $result;
    }
    return null;
  }

  //PLUGIN CONF FUNCTIONS
  public static function getPluginConfig($eqLogic, $withType = true) {
    $returnType = 'PLUGIN_CONFIG';

    $plugin = update::byLogicalId('JeedomConnect');

    $payload =  array(
      'useWs' => is_object($eqLogic) ?  $eqLogic->getConfiguration('useWs', 0) : 0,
      'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
      'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
      'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
      'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'),
      'pluginJeedomVersion' => $plugin->getLocalVersion()
    );

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  // REGISTER FUNCTION
  public static function registerUser($eqLogic, $userHash, $rdk, $user = null, $withType = true) {
    $returnType = 'REGISTERED';

    if ($user == null) {
      $user = user::byHash($userHash);
    }
    if (!isset($user)) {
      throw new Exception(__("User not valid", __FILE__), -32699);
    }
    $eqLogic->setConfiguration('userHash', $userHash);
    $eqLogic->save();

    $registerDevice = $user->getOptions('registerDevice', array());
    if (!is_array($registerDevice)) {
      $registerDevice = array();
    }
    if (!isset($rdk) || !isset($registerDevice[sha512($rdk)])) {
      $rdk = config::genKey();
    }
    $registerDevice[sha512($rdk)] = array();
    $registerDevice[sha512($rdk)]['datetime'] = date('Y-m-d H:i:s');
    $registerDevice[sha512($rdk)]['ip'] = getClientIp();
    $registerDevice[sha512($rdk)]['session_id'] = session_id();
    if (version_compare(PHP_VERSION, '7.3') >= 0) {
      setcookie('registerDevice', $userHash . '-' . $rdk, ['expires' => time() + 365 * 24 * 3600, 'samesite' => 'Strict', 'httponly' => true, 'path' => '/', 'secure' => (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')]);
    } else {
      setcookie('registerDevice', $userHash . '-' . $rdk, time() + 365 * 24 * 3600, "/; samesite=strict", '', (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'), true);
    }
    @session_start();
    $user->refresh();
    $user->setOptions('registerDevice', $registerDevice);
    $user->save();
    @session_write_close();

    return (!$withType) ? $rdk : self::addTypeInPayload(array('rdk' => $rdk), $returnType);
  }

  // Config Watcher
  public static function lookForNewConfig($eqLogic, $prevConfig) {
    $configVersion = $eqLogic->getConfiguration('configVersion');
    //log::add('JeedomConnect', 'debug',   "apiHelper : Look for new config, compare ".$configVersion." and ".$prevConfig);
    if ($configVersion != $prevConfig) {
      log::add('JeedomConnect', 'debug', "apiHelper : New configuration");
      return $eqLogic->getGeneratedConfigFile();
    }
    return false;
  }

  // JEEDOM FULL DATA
  public static function getFullJeedomData() {
    $result = array(
      'type' => 'SET_JEEDOM_DATA',
      'payload' => array(
        'cmds' => array(),
        'eqLogics' => array(),
        'objects' => array(),
        'scenarios' => array(),
        'summariesConfig' => array()
      )
    );

    foreach (cmd::all() as $item) {
      $array = utils::o2a($item);
      $cmd = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'type' => $array['type'],
        'subType' => $array['subType'],
        'eqLogic_id' => $array['eqLogic_id'],
        'unite' => $array['unite'],
        'isHistorized' => $array['isHistorized'],
        'configuration' => $array['configuration'],
      );
      array_push($result['payload']['cmds'], $cmd);
    }
    foreach (eqLogic::all() as $item) {
      $array = utils::o2a($item);
      $eqLogic = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'object_id' => $array['object_id'],
        'isEnable' => $array['isEnable']
      );
      array_push($result['payload']['eqLogics'], $eqLogic);
    }
    foreach (jeeObject::all() as $item) {
      $array = utils::o2a($item);
      $jeeObject = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'display' => JeedomConnectUtils::getIconAndColor($array['display']['icon'])
      );
      array_push($result['payload']['objects'], $jeeObject);
    }
    foreach (scenario::all() as $item) {
      $array = utils::o2a($item);
      $scenario = array(
        'id' => $array['id'],
        'name' => $array['name'],
        'group' => $array['group'],
        'object_id' => $array['object_id'],
      );
      array_push($result['payload']['scenarios'], $scenario);
    }

    foreach (config::byKey('object:summary') as $item) {
      $item['display'] = JeedomConnectUtils::getIconAndColor($item['icon']);
      $item['icon'] = trim(preg_replace('/ icon_(red|yellow|blue|green|orange)/', '', $item['icon']));
      array_push($result['payload']['summariesConfig'], $item);
    }

    return $result;
  }


  //WIDGET DATA
  public static function getWidgetData() {
    $result = array(
      'type' => 'SET_WIDGET_DATA',
      'payload' => array(
        'widgets' => JeedomConnectWidget::getWidgets()
      )
    );

    return $result;
  }

  // EDIT FUNCTIONS
  public static function setWidget($widget) {
    log::add('JeedomConnect', 'debug', 'save widget data');
    JeedomConnectWidget::updateWidgetConfig($widget);
  }

  public static function addWidgets($eqLogic, $widgets, $parentId, $index) {
    $curConfig = $eqLogic->getConfig();

    foreach ($curConfig['payload']['widgets'] as $i => $widget) {
      if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
        $curConfig['payload']['widgets'][$i]['index'] += count($widgets);
      }
    }
    foreach ($curConfig['payload']['groups'] as $i => $group) {
      if ($group['parentId'] == $parentId && $group['index'] > $index) {
        $curConfig['payload']['groups'][$i]['index'] += count($widgets);
      }
    }

    $curIndex = $index + 1;
    foreach ($widgets as $widget) {
      $newWidget = array(
        'index' => $curIndex,
        'widgetId' => $curConfig['idCounter'],
        'id' => $widget['id']
      );
      if (!is_null($parentId)) {
        $newWidget['parentId'] = $parentId;
      }
      array_push($curConfig['payload']['widgets'], $newWidget);
      $curIndex++;
      $curConfig['idCounter'] = $curConfig['idCounter'] + 1;
    }

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeWidget($eqLogic, $widgetId) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($widgetId, array_column($curConfig['payload']['widgets'], 'widgetId'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['widgets'][$toRemove]['parentId'];
      $index = $curConfig['payload']['widgets'][$toRemove]['index'];
      unset($curConfig['payload']['widgets'][$toRemove]);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $parentId && $group['index'] > $index) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
      }

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function moveWidget($eqLogic, $widgetId, $destinationId, $destinationIndex) {
    $curConfig = $eqLogic->getConfig();
    $widgetIndex = array_search($widgetId, array_column($curConfig['payload']['widgets'], 'widgetId'));
    if ($widgetIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['widgets'][$widgetIndex]['index'];
    $oldParentId = $curConfig['payload']['widgets'][$widgetIndex]['parentId'];
    if (isset($destinationIndex)) {
      $newIndex = $destinationIndex;
    } else {
      $newIndex = 0;
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
    }


    $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationParentIndex !== false) { //destination is a bottom tab
      $moved = true;
    } else {
      $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['sections'], 'id'));
      if ($destinationParentIndex !== false) { //destination is a top tab
        $moved = true;
      } else {
        $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['groups'], 'id'));
        if ($destinationParentIndex !== false) { //destination is a group
          $moved = true;
        }
      }
    }

    if ($moved) {
      //re-index pages
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['widgets'][$i]['index'] += 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['groups'][$i]['index'] += 1;
        }
      }

      $curConfig['payload']['widgets'][$widgetIndex]['parentId'] = $destinationId;
      $curConfig['payload']['widgets'][$widgetIndex]['index'] = $newIndex;


      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function setCustomWidgetList($eqLogic, $customWidgetList) {
    $apiKey = $eqLogic->getConfiguration('apiKey');
    foreach ($customWidgetList as $customWidget) {
      log::add('JeedomConnect', 'debug', 'save custom data for widget [' . $customWidget['widgetId'] . '] : ' . json_encode($customWidget));
      config::save('customData::' . $apiKey . '::' . $customWidget['widgetId'], json_encode($customWidget), 'JeedomConnect');
    }
    $eqLogic->generateNewConfigVersion();
  }

  public static function setGroup($eqLogic, $group) {
    $curConfig = $eqLogic->getConfig();
    $toEdit = array_search($group['id'], array_column($curConfig['payload']['groups'], 'id'));
    if ($toEdit !== false) {
      $curConfig['payload']['groups'][$toEdit] = $group;
      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function removeGroup($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['groups'], 'id'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['groups'][$toRemove]['parentId'];
      $index = $curConfig['payload']['groups'][$toRemove]['index'];
      unset($curConfig['payload']['groups'][$toRemove]);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);

      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($widget['parentId'] == $id) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $parentId && $group['index'] > $index) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
      }
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function addGroup($eqLogic, $curGroup) {
    $curConfig = $eqLogic->getConfig();
    $parentId = $curGroup['parentId'];
    $index = $curGroup['index'];
    $maxId = 999000;

    foreach ($curConfig['payload']['widgets'] as $i => $widget) {
      if ($widget['parentId'] == $parentId && $widget['index'] > $index) {
        $curConfig['payload']['widgets'][$i]['index'] += 1;
      }
    }
    foreach ($curConfig['payload']['groups'] as $i => $group) {
      if ($group['parentId'] == $parentId && $group['index'] > $index) {
        $curConfig['payload']['groups'][$i]['index'] += 1;
      }
      if ($group['id'] >= $maxId) {
        $maxId = $group['id'] + 1;
      }
    }

    $curGroup['id'] = $maxId;
    array_push($curConfig['payload']['groups'], $curGroup);
    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function moveGroup($eqLogic, $groupId, $destinationId, $destinationIndex) {
    $curConfig = $eqLogic->getConfig();
    $groupIndex = array_search($groupId, array_column($curConfig['payload']['groups'], 'id'));
    if ($groupIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['groups'][$groupIndex]['index'];
    $oldParentId = $curConfig['payload']['groups'][$groupIndex]['parentId'];

    if (isset($destinationIndex)) {
      $newIndex = $destinationIndex;
    } else {
      $newIndex = 0;
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $destinationId) {
          $newIndex++;
        }
      }
    }

    $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationParentIndex !== false) { //destination is a bottom tab
      $moved = true;
    } else {
      $destinationParentIndex = array_search($destinationId, array_column($curConfig['payload']['sections'], 'id'));
      if ($destinationParentIndex !== false) { //destination is a top tab
        $moved = true;
      }
    }

    if ($moved) {
      //re-index pages
      foreach ($curConfig['payload']['widgets'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['widgets'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['widgets'][$i]['index'] += 1;
        }
      }
      foreach ($curConfig['payload']['groups'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['groups'][$i]['index'] -= 1;
        }
        if ($item['parentId'] == $destinationId && $item['index'] >= $newIndex) {
          $curConfig['payload']['groups'][$i]['index'] += 1;
        }
      }

      $curConfig['payload']['groups'][$groupIndex]['parentId'] = $destinationId;
      $curConfig['payload']['groups'][$groupIndex]['index'] = $newIndex;

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function removeGlobalWidget($id) {
    JeedomConnectWidget::removeWidget($id);
  }

  public static function addGlobalWidgets($widgets) {
    $newConfWidget = array();
    $widgetsConfigJonFile = json_decode(file_get_contents(JeedomConnect::$_plugin_config_dir . 'widgetsConfig.json'), true);

    foreach ($widgets as $i => $widget) {
      $imgPath = '';
      foreach ($widgetsConfigJonFile['widgets'] as $config) {
        if ($config['type'] == $widget['type']) {
          $imgPath = 'plugins/JeedomConnect/data/img/' . $config['img'];
          break;
        }
      }
      $newConfWidget['imgPath'] = $imgPath;

      $widgetId = JeedomConnectWidget::incrementIndex();
      $widget['id'] = intval($widgetId);
      $widgets[$i]['id'] = $widgetId;

      $newConfWidget['widgetJC'] = json_encode($widget);

      config::save('widget::' . $widgetId, $newConfWidget, JeedomConnectWidget::$_plugin_id);
    }

    $result = array(
      'type' => 'SET_MULTIPLE_WIDGET_DATA',
      'payload' => array_values($widgets)
    );
    return $result;
  }

  public static function setBottomTabList($eqLogic, $tabs, $migrate = false, $idCounter) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;

    //remove children
    foreach ($tabs as $tab) {
      $oldTabIndex = array_search($tab['id'], array_column($curConfig['payload']['tabs'], 'id'));
      if ($tab['index'] < 0) { //tabs with negative index have to be removed
        if ($oldTabIndex !== false) {
          unset($curConfig['payload']['tabs'][$oldTabIndex]);
        }
        $sectionsToRemove = array();
        foreach ($curConfig['payload']['sections'] as $i => $section) {
          if ($section['parentId'] == $tab['id']) {
            array_push($sectionsToRemove, $section['id']);
            unset($curConfig['payload']['sections'][$i]);
          }
        }
        $groupsToRemove = array();
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          if ($group['parentId'] == $tab['id'] || in_array($group['parentId'], $sectionsToRemove)) {
            array_push($groupsToRemove, $group['id']);
            unset($curConfig['payload']['groups'][$i]);
          }
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] == $tab['id'] || in_array($widget['parentId'], $groupsToRemove) || in_array($widget['parentId'], $sectionsToRemove)) {
            unset($curConfig['payload']['widgets'][$i]);
          }
        }
      } else {
        if ($oldTabIndex !== false) {
          $curConfig['payload']['tabs'][$oldTabIndex] = $tab;
        } else {
          array_push($curConfig['payload']['tabs'], $tab);
        }
      }
      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
    }

    if ($migrate) {
      $firstTab = array_search(0, array_column($tabs, 'index'));
      $id = $tabs[$firstTab]['id'];
      if (count($curConfig['payload']['sections']) > 0) {
        foreach ($curConfig['payload']['sections'] as $i => $section) {
          $curConfig['payload']['sections'][$i]['parentId'] = $id;
        }
      } else {
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          $curConfig['payload']['groups'][$i]['parentId'] = $id;
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] < 999000) {
            $curConfig['payload']['widgets'][$i]['parentId'] = $id;
          }
        }
      }
    }

    $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
    $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
    $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeBottomTab($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['tabs'], 'id'));
    if ($toRemove !== false) {
      $index = $curConfig['payload']['tabs'][$toRemove]['index'];
      $parentId = $curConfig['payload']['tabs'][$toRemove]['parentId'];
      unset($curConfig['payload']['tabs'][$toRemove]);

      // remove children
      $sectionsToRemove = array();
      foreach ($curConfig['payload']['sections'] as $i => $section) {
        if ($section['parentId'] == $id) {
          array_push($sectionsToRemove, $section['id']);
          unset($curConfig['payload']['sections'][$i]);
        }
      }
      $groupsToRemove = array();
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $id || in_array($group['parentId'], $sectionsToRemove)) {
          array_push($groupsToRemove, $group['id']);
          unset($curConfig['payload']['groups'][$i]);
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $id || in_array($widget['parentId'], $groupsToRemove) || in_array($widget['parentId'], $sectionsToRemove)) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      //re-index tabs
      foreach ($curConfig['payload']['tabs'] as $i => $tab) {
        if ($tab['parentId'] == $parentId && $tab['index'] > $index) {
          $curConfig['payload']['tabs'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function setTopTabList($eqLogic, $tabs, $migrate = false, $idCounter) {
    if (count($tabs) == 0) {
      return;
    }
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;

    //remove children
    foreach ($tabs as $tab) {
      $oldTabIndex = array_search($tab['id'], array_column($curConfig['payload']['sections'], 'id'));
      if ($tab['index'] < 0) { //tabs with negative index have to be removed
        if ($oldTabIndex !== false) {
          unset($curConfig['payload']['sections'][$oldTabIndex]);
        }
        $groupsToRemove = array();
        foreach ($curConfig['payload']['groups'] as $i => $group) {
          if ($group['parentId'] == $tab['id']) {
            array_push($groupsToRemove, $group['id']);
            unset($curConfig['payload']['groups'][$i]);
          }
        }
        foreach ($curConfig['payload']['widgets'] as $i => $widget) {
          if ($widget['parentId'] == $tab['id'] || in_array($widget['parentId'], $groupsToRemove)) {
            unset($curConfig['payload']['widgets'][$i]);
          }
        }
      } else {
        if ($oldTabIndex !== false) {
          $curConfig['payload']['sections'][$oldTabIndex] = $tab;
        } else {
          array_push($curConfig['payload']['sections'], $tab);
        }
      }
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    }

    if ($migrate) {
      $firstTab = array_search(0, array_column($tabs, 'index'));
      $id = $tabs[$firstTab]['id'];
      $parentId = $tabs[$firstTab]['parentId'];
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if (!isset($group['parentId']) || $group['parentId'] == $parentId) {
          $curConfig['payload']['groups'][$i]['parentId'] = $id;
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if (!isset($widget['parentId']) || $widget['parentId'] == $parentId) {
          $curConfig['payload']['widgets'][$i]['parentId'] = $id;
        }
      }
    }

    $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
    $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
    $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

    $eqLogic->saveConfig($curConfig);
    $eqLogic->cleanCustomData();
    $eqLogic->generateNewConfigVersion();
  }

  public static function removeTopTab($eqLogic, $id) {
    $curConfig = $eqLogic->getConfig();
    $toRemove = array_search($id, array_column($curConfig['payload']['sections'], 'id'));
    if ($toRemove !== false) {
      $parentId = $curConfig['payload']['sections'][$toRemove]['parentId'];
      $index = $curConfig['payload']['sections'][$toRemove]['index'];
      unset($curConfig['payload']['sections'][$toRemove]);

      // remove children
      $groupsToRemove = array();
      foreach ($curConfig['payload']['groups'] as $i => $group) {
        if ($group['parentId'] == $id) {
          array_push($groupsToRemove, $group['id']);
          unset($curConfig['payload']['groups'][$i]);
        }
      }
      foreach ($curConfig['payload']['widgets'] as $i => $widget) {
        if ($widget['parentId'] == $id || in_array($widget['parentId'], $groupsToRemove)) {
          unset($curConfig['payload']['widgets'][$i]);
        }
      }
      //re-index sections
      foreach ($curConfig['payload']['sections'] as $i => $section) {
        if ($section['parentId'] == $parentId && $section['index'] > $index) {
          $curConfig['payload']['sections'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->cleanCustomData();
      $eqLogic->generateNewConfigVersion();
    }
  }

  public static function moveTopTab($eqLogic, $sectionId, $destinationId) {
    $curConfig = $eqLogic->getConfig();
    $sectionIndex = array_search($sectionId, array_column($curConfig['payload']['sections'], 'id'));
    if ($sectionIndex === false) {
      return;
    }

    $moved = false;

    $oldIndex = $curConfig['payload']['sections'][$sectionIndex]['index'];
    $oldParentId = $curConfig['payload']['sections'][$sectionIndex]['parentId'];

    $newIndex = 0;
    foreach ($curConfig['payload']['sections'] as $i => $item) {
      if ($item['parentId'] == $destinationId) {
        $newIndex++;
      }
    }

    $destinationIndex = array_search($destinationId, array_column($curConfig['payload']['tabs'], 'id'));
    if ($destinationIndex !== false) { //destination is a bottom tab
      $moved = true;
    }

    if ($moved) {
      $curConfig['payload']['sections'][$sectionIndex]['parentId'] = $destinationId;
      $curConfig['payload']['sections'][$sectionIndex]['index'] = $newIndex;
      //re-index initial tab
      foreach ($curConfig['payload']['sections'] as $i => $item) {
        if ($item['parentId'] == $oldParentId && $item['index'] > $oldIndex) {
          $curConfig['payload']['sections'][$i]['index'] -= 1;
        }
      }

      $curConfig['payload']['tabs'] = array_values($curConfig['payload']['tabs']);
      $curConfig['payload']['sections'] = array_values($curConfig['payload']['sections']);
      $curConfig['payload']['groups'] = array_values($curConfig['payload']['groups']);
      $curConfig['payload']['widgets'] = array_values($curConfig['payload']['widgets']);

      $eqLogic->saveConfig($curConfig);
      $eqLogic->generateNewConfigVersion();
    }
  }

  // Receive root data (already ordered) (widgets, groups) for a page view (index < 0 ==> remove)
  public static function setPageData($eqLogic, $rootData, $idCounter) {
    if (count($rootData) == 0) {
      return;
    }
    $curConfig = $eqLogic->getConfig();
    $curConfig['idCounter'] = $idCounter;
    $parentId = $rootData[0]['parentId'] ?? null;

    foreach ($rootData as $element) {
      $type = 'groups';
      $id = 'id';
      if (isset($element['widgetId'])) { //this is a widget
        $type = 'widgets';
        $id = 'widgetId';
      }
      $oldWidgetIndex = array_search($element[$id], array_column($curConfig['payload'][$type], $id));
      if ($element['index'] < 0) {
        if ($oldWidgetIndex !== false) {
          unset($curConfig['payload'][$type][$oldWidgetIndex]);
        }
      } else {
        if ($oldWidgetIndex !== false) { //update element
          $curConfig['payload'][$type][$oldWidgetIndex] = $element;
        } else { //add element
          array_push($curConfig['payload'][$type], $element);
        }
      }
      $curConfig['payload'][$type] = array_values($curConfig['payload'][$type]);
    }

    $eqLogic->saveConfig($curConfig);
    $eqLogic->cleanCustomData();
    $eqLogic->generateNewConfigVersion();
  }

  public static function setRooms($eqLogic, $rooms) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['rooms'] = $rooms;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function setSummaries($eqLogic, $summaries) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['summaries'] = $summaries;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }

  public static function setBackgrounds($eqLogic, $backgrounds) {
    $curConfig = $eqLogic->getConfig();
    $curConfig['payload']['background'] = $backgrounds;

    $eqLogic->saveConfig($curConfig);
    $eqLogic->generateNewConfigVersion();
  }



  public static function getWidgetFromGenType($_widget_type, $_eqLogicId) {
    $result = array(
      'type' => 'SET_WIDGET_WITH_GEN_TYPE',
      'payload' => JeedomConnectUtils::generateWidgetWithGenType($_widget_type, $_eqLogicId)
    );

    return $result;
  }



  // EVENTS FUNCTION
  public static function getEventsFull($eqLogic, $lastReadTimestamp) {

    $config = $eqLogic->getGeneratedConfigFile();

    $events = event::changes($lastReadTimestamp);

    $eventCount = count($events['result']);
    if ($eventCount == 0) {
      // log::add('JeedomConnect', 'debug', '--- no change - skipped (' . $eventCount . ')');
      $data = array();
    } elseif ($eventCount < 249) {
      // log::add('JeedomConnect', 'debug', '--- using cache (' . $eventCount . ')');
      $data = self::getEventsFromCache($events, $config, $eqLogic->getConfiguration('scAll', 0) == 1);
    } else {
      // log::add('JeedomConnect', 'debug', '*****  too many items, refresh all (' . $eventCount . ')');
      $data = self::getEventsGlobalRefresh($config, $eqLogic->getConfiguration('scAll', 0) == 1);
    }

    return $data;
  }


  public static function getEventsGlobalRefresh($config, $scAll = false) {
    $result = array(
      array(
        'type' => 'CMD_INFO',
        'payload' => apiHelper::getCmdInfoData($config)
      ),
      array(
        'type' => 'SC_INFO',
        'payload' => apiHelper::getScenarioData($config, $scAll)
      ), array(
        'type' => 'OBJ_INFO',
        'payload' => apiHelper::getObjectData($config)
      )
    );
    return $result;
  }

  public static function getEventsFromCache($events, $config, $scAll = false) {
    $result_cmd = array(
      'type' => 'CMD_INFO',
      'payload' => array()
    );
    $infoIds = self::getInfoCmdList($config);
    $result_sc = array(
      'type' => 'SC_INFO',
      'payload' => array()
    );
    $scIds = self::getScenarioList($config);
    $result_obj = array(
      'type' => 'OBJ_INFO',
      'payload' => array()
    );

    foreach ($events['result'] as $event) {
      if ($event['name'] == 'jeeObject::summary::update') {
        array_push($result_obj['payload'], $event['option']);
      }
      if ($event['name'] == 'scenario::update') {
        if (in_array($event['option']['scenario_id'], $scIds) || $scAll) {
          $sc_info = array(
            'id' => $event['option']['scenario_id'],
            'status' => $event['option']['state'],
            'lastLaunch' => strtotime($event['option']['lastLaunch'])
          );
          if (array_key_exists('isActive', $event['option'])) {
            $sc_info['active'] = $event['option']['isActive'];
          }
          array_push($result_sc['payload'], $sc_info);
        }
      }
      if ($event['name'] == 'cmd::update') {
        if (in_array($event['option']['cmd_id'], $infoIds)) {
          $cmd_info = array(
            'id' => $event['option']['cmd_id'],
            'value' => $event['option']['value'],
            'modified' => strtotime($event['option']['valueDate'])
          );
          array_push($result_cmd['payload'], $cmd_info);
        }
      }
    }
    return array($result_cmd, $result_sc, $result_obj);
  }

  //HISTORY
  public static function getHistory($id, $options = null) {
    $history = array();
    if ($options == null) {
      $history = history::all($id);
    } else {
      $startTime = date('Y-m-d H:i:s', $options['startTime']);
      $endTime = date('Y-m-d H:i:s', $options['endTime']);
      log::add('JeedomConnect', 'info', 'Get history from: ' . $startTime . ' to ' . $endTime);
      $history = history::all($id, $startTime, $endTime);
    }

    $result = array(
      'type' => 'SET_HISTORY',
      'payload' => array(
        'id' => $id,
        'data' => array()
      )
    );

    foreach ($history as $h) {
      array_push($result['payload']['data'], array(
        'time' => strtotime($h->getDateTime()),
        'value' => $h->getValue()
      ));
    }
    log::add('JeedomConnect', 'info', 'Send history (' . count($result['payload']['data']) . ' points)');
    return $result;
  }

  // BATTERIES
  public static function getBatteries() {
    $result = array(
      'type' => 'SET_BATTERIES',
      'payload' => JeedomConnect::getBatteryAllEquipements()
    );
    return $result;
  }

  public static function saveBatteryEquipment($apiKey, $level) {

    $eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

    if (is_object($eqLogic)) {

      $batteryCmd = $eqLogic->getCmd(null, 'battery');

      if (is_object($batteryCmd)) {
        $batteryCmd->event($level);
      }

      if (!$eqLogic->getConfiguration('hideBattery') || $eqLogic->getConfiguration('hideBattery', -2) == -2) {
        $eqLogic->setStatus("battery", $level);
        $eqLogic->setStatus("batteryDatetime", date('Y-m-d H:i:s'));
        //  log::add('JeedomConnect', 'warning', 'saveBatteryEquipment | SAVING battery saved on equipment page ');
      }
    } else {
      log::add('JeedomConnect', 'warning', 'saveBatteryEquipment | not able to retrieve an equipment for apiKey ' . $apiKey);
    }
  }


  // PLUGINS UPDATE

  public static function doUpdate($pluginId) {
    $update = update::byLogicalId($pluginId);

    if (!is_object($update)) {
      log::add('JeedomConnect', 'warning', 'doUpdate -- cannot update plugin ' . $pluginId);
      return false;
    }

    $update->doUpdate();
    return true;
  }

  public static function getPluginsUpdate() {
    $result = array(
      'type' => 'SET_PLUGINS_UPDATE',
      'payload' => JeedomConnect::getPluginsUpdate()
    );
    return $result;
  }

  // BACKUPS
  public static function setAppConfig($apiKey, $config) {
    $_backup_dir = __DIR__ . '/../../data/backups/';
    if (!is_dir($_backup_dir)) {
      mkdir($_backup_dir);
    }
    $eqDir = $_backup_dir . $apiKey . '/';
    if (!is_dir($eqDir)) {
      mkdir($eqDir);
    }
    $backupIndex = config::byKey('backupIndex::' . $apiKey, 'JeedomConnect');
    if (empty($backupIndex)) {
      $backupIndex = 0;
    }
    $backupIndex++;
    config::save('backupIndex::' . $apiKey, $backupIndex, 'JeedomConnect');

    $config_file = $eqDir . urlencode($config['name']) . '-' . $backupIndex . '.json';
    try {
      log::add('JeedomConnect', 'debug', 'Saving backup in file : ' . $config_file);
      file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', 'Unable to write file : ' . $e->getMessage());
    }
  }

  public static function getAppConfig($apiKey, $configId) {
    $_backup_dir = '/plugins/JeedomConnect/data/backups/';
    $eqDir = $_backup_dir . $apiKey;
    $files = self::getFiles($eqDir);
    $endFile = '-' . $configId . '.json';
    foreach ($files['payload']['files'] as $file) {
      if (substr_compare($file['path'], $endFile, -strlen($endFile)) === 0) {
        $config_file = file_get_contents($file['path']);
        return array(
          'type' => 'SET_APP_CONFIG',
          'payload' => array('config' => json_decode($config_file))
        );
      }
    }
    return false;
  }

  // JEEDOM & PLUGINS HEALTH

  public static function restartDaemon($userId, $pluginId) {
    $_plugin = \plugin::byId($pluginId);
    if (is_object($_plugin)) {
      log::add('JeedomConnect', 'debug', 'DAEMON restart by [' . $userId . '] =>' . $pluginId);
      $_plugin->deamon_start(true);
      return true;
    }
    return false;
  }

  public static function stopDaemon($userId, $pluginId) {
    $_plugin = \plugin::byId($pluginId);
    if (is_object($_plugin)) {
      log::add('JeedomConnect', 'debug', 'DAEMON stopped by [' . $userId . '] =>' . $pluginId);
      $_plugin->deamon_stop();
      return true;
    }
    return false;
  }

  public static function getJeedomHealthDetails($apiKey) {

    $result = array(
      'type' => 'SET_JEEDOM_GLOBAL_HEALTH',
      'payload' => JeedomConnect::getHealthDetails($apiKey)
    );
    return $result;
  }


  //EXEC ACTIONS
  public static function execCmd($id, $options = null) {
    $cmd = cmd::byId($id);
    if (!is_object($cmd)) {
      log::add('JeedomConnect', 'error', "Can't find command [id=" . $id . "]");
      return;
    }
    try {
      $options = array_merge($options ?? array(), array('comingFrom' => 'JeedomConnect'));
      $cmd->execCmd($options);
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function execMultipleCmd($cmdList) {
    foreach ($cmdList as $cmd) {
      self::execCmd($cmd['id'], $cmd['options']);
    }
  }

  public static function getJCActions($apiKey, $withType = true) {
    $returnType = 'ACTIONS';

    $actions = JeedomConnectActions::getAllActions($apiKey);
    $payload = array();
    if (count($actions) > 0) {
      foreach ($actions as $action) {
        array_push($payload, $action['value']['payload']);
      }
      JeedomConnectActions::removeActions($actions);
    }

    log::add('JeedomConnect', 'debug', "send action " . json_encode($payload));

    return (!$withType) ? $payload : self::addTypeInPayload($payload, $returnType);
  }

  // MANAGE SC          
  public static function execSc($id, $options = null) {
    if ($options == null) {
      $options = array(
        'action' => 'start',
        'scenario_id' => $id
      );
    }
    try {
      scenarioExpression::createAndExec('action', 'scenario', $options);
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function stopSc($id) {
    try {
      $sc = scenario::byId($id);
      $sc->stop();
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  public static function setActiveSc($id, $active) {
    try {
      $sc = scenario::byId($id);
      $sc->setIsActive($active);
      $sc->save();
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }
  }

  // FILES
  public static function getFiles($folder, $recursive = false) {
    $dir = __DIR__ . '/../../../..' . $folder;
    $result = array();
    try {
      if (is_dir($dir)) {
        $dh = new DirectoryIterator($dir);
        foreach ($dh as $item) {
          if (!$item->isDot() && substr($item, 0, 1) != '.') {
            if (!$item->isDir()) {
              array_push($result, array(
                'path' =>  $item->getPathname(),
                'timestamp' => $item->getMTime()
              ));
            } else if ($recursive) {
              $subFolderFiles = self::getFiles(str_replace(__DIR__ . '/../../../..', '', preg_replace('#/+#', '/', $item->getPathname())), true);
              $result = array_merge($result, $subFolderFiles['payload']['files']);
            }
          }
        }
      }
    } catch (Exception $e) {
      log::add('JeedomConnect', 'error', $e->getMessage());
    }

    return  array(
      'type' => 'SET_FILES',
      'payload' => array(
        'path' => $folder,
        'files' => $result
      )
    );
  }


  public static function removeFile($file) {
    $pathInfo = pathinfo($file);
    unlink($file);
    return
      self::getFiles(str_replace(__DIR__ . '/../../../..', '', preg_replace('#/+#', '/', $pathInfo['dirname'])), true);
  }

  public static function raiseException($type, $errMsg = '') {
    $result = array(
      "type" => "EXCEPTION",
      "payload" => "Error with '" . $type . "' method " . $errMsg
    );
    log::add('JeedomConnect', 'error', $result["payload"]);
    // log::add('JeedomConnect', 'error', 'Send ' . json_encode($result));

    return $result;
  }
}
