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
              if ($info['type'] == 'cmd') {
                array_push($return, $info['id']);
              }
            }
          }
          if ($item == 'moreInfos') {
            foreach ($value as $i => $info) {
              if ($info['type'] == 'cmd') {
                array_push($return, $info['id']);
              }
            }
          }
        }
      }
    }
    return array_unique($return);
  }

  public static function getCmdInfoData($config) {
    $cmds = cmd::byIds(self::getInfoCmdList($config));
    $result = array();

    foreach ($cmds as $cmd) {
      $state = $cmd->getCache(array('valueDate', 'value'));
      $cmd_info = array(
        'id' => $cmd->getId(),
        'value' => $state['value'],
        'modified' => strtotime($state['valueDate'])
      );
      array_push($result, $cmd_info);
    }
    return $result;
  }

  // SCEANRIO FUNCTIONS

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

  public static function getScenarioData($config) {
    $scIds = self::getScenarioList($config);
    $result = array();

    foreach (scenario::all() as $sc) {
      if (in_array($sc->getId(), $scIds)) {
        $state = $sc->getCache(array('state', 'lastLaunch'));
        $sc_info = array(
          'id' => $sc->getId(),
          'name' => $sc->getName(),
          'object' => $sc->getObject() == null ? 'Aucun' : $sc->getObject()->getName(),
          'group' => $sc->getGroup() == '' ? 'Aucun' : $sc->getGroup(),
          'status' => $state['state'],
          'lastLaunch' => strtotime($state['lastLaunch']),
          'active' => $sc->getIsActive() ? 1 : 0
        );
        array_push($result, $sc_info);
      }
    }
    return $result;
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

  public static function getObjectData($config) {
  	$objIds = self::getObjectList($config);
    $result = array();

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
        array_push($result, $object_info);
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
  	array_push($result, $global_info);
    return $result;
  }

  // GEOFENCE FUNCTIONS
  function getGeofencesData($eqLogic) {
    $result = array(
      'type' => 'SET_GEOFENCES',
      'payload' => array(
        'geofences' => array()
      )
    );
    foreach ($eqLogic->getCmd('info') as $cmd) {
      if (substr( $cmd->getLogicalId(), 0, 8 ) === "geofence") {
        array_push($result['payload']['geofences'], array(
          'identifier' => substr( $cmd->getLogicalId(), 9 ),
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
    return $result;
  }

  //PLUGIN CONF FUNCTIONS
  function getPluginConfig() {
  	return array(
  		'useWs' => config::byKey('useWs', 'JeedomConnect', false),
  		'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
  		'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
  		'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
  		'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090')
  	);
  }

 // REGISTER FUNCTION
 public static function registerUser($eqLogic, $userHash, $rdk, $user = null) {
   if ($user == null) {
     $user = user::byHash($userHash);
   }
   if (!isset($user)) {
     return null;
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
       setcookie('registerDevice', $userHash . '-' . $rdk,['expires' => time() + 365 * 24 * 3600,'samesite' => 'Strict','httponly' => true,'path' => '/','secure' => (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https')]);
     }else{
       setcookie('registerDevice', $userHash . '-' . $rdk, time() + 365 * 24 * 3600, "/; samesite=strict", '',  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'), true);
     }
   @session_start();
   $user->refresh();
   $user->setOptions('registerDevice', $registerDevice);
   $user->save();
   @session_write_close();
   return $rdk;
 }

 // Config Watcher
 public static function lookForNewConfig($eqLogic, $config) {
   $configVersion = $eqLogic->getConfiguration('configVersion');
   //log::add('JeedomConnect', 'debug',   "apiHelper : Look for new config, compare ".$configVersion." and ".$config['payload']['configVersion']);
   if ($configVersion != $config['payload']['configVersion']) {
      log::add('JeedomConnect', 'debug', "apiHelper : New configuration");
      //return $eqLogic->getConfig(true);
      return $eqLogic->getGeneratedConfigFile();
    }
    return false;
 }

 // EVENTS FUNCTION
 public static function getEvents($events, $config) {
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
   $objIds = self::getObjectList($config);

   foreach ($events['result'] as $event) {
     if ($event['name'] == 'jeeObject::summary::update') {
       array_push($result_obj['payload'], $event['option']);
     }
     if ($event['name'] == 'scenario::update') {
       if (in_array($event['option']['scenario_id'], $scIds) || $client->sendAllSc) {
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
       if (in_array($event['option']['cmd_id'], $infoIds) ) {
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
     log::add('JeedomConnect', 'info', 'Get history from: '.$startTime.' to '.$endTime);
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

}
?>
