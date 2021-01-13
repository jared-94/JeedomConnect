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


$jsonData = file_get_contents("php://input");
log::add('JeedomConnect', 'debug', 'HTTP API received '.$jsonData);
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

if (!is_object($eqLogic) && $method != 'GET_PLUGIN_CONFIG') {
  log::add('JeedomConnect', 'debug', "Can't find eqLogic");
  throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
}

switch ($method) {
  case 'GET_PLUGIN_CONFIG':
    $jsonrpc->makeSuccess(array(
      'type' => 'PLUGIN_CONFIG',
      'payload' => array(
        'useWs' => config::byKey('useWs', 'JeedomConnect', false),
        'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
        'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
        'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
        'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090')
      )
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
      $jsonrpc->makeSuccess(array( 'type' => 'BAD_DEVICE' ));
      return;
    }

    //check version requierement
    if (version_compare($params['appVersion'], $versionJson->require, "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad version requierement");
      $jsonrpc->makeSuccess(array( 'type' => 'APP_VERSION_ERROR' ));
      return;
    }
    if (version_compare($versionJson->version, $params['pluginRequire'], "<")) {
      log::add('JeedomConnect', 'warning', "Failed to connect : bad plugin requierement");
      $jsonrpc->makeSuccess(array( 'type' => 'PLUGIN_VERSION_ERROR' ));
      return;
    }
    $result = array(
      'type' => 'WELCOME',
      'payload' => array(
        'pluginVersion' => $versionJson->version,
        'configVersion' => $eqLogic->getConfiguration('configVersion'),
        'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
        'jeedomURL' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external'))
      )
    );
    log::add('JeedomConnect', 'debug', 'send '.json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_CONFIG':
    $jsonrpc->makeSuccess($eqLogic->getConfig());
    break;
  case 'GET_CMD_INFO':
    $result = getCmdInfoData($eqLogic);
    log::add('JeedomConnect', 'debug', 'Send '.json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_SC_INFO':
    $result = getScenarioData($eqLogic);
    log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
    $jsonrpc->makeSuccess($result);
    break;
  case 'GET_GEOFENCES':
    $result = getGeofencesData($eqLogic);
    log::add('JeedomConnect', 'info', 'GEOFENCES '.json_encode($result));
    if (count($result['payload']['geofences']) > 0) {
      log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
      $jsonrpc->makeSuccess($result);
    }
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
  if (array_key_exists('geofence', $params) ) {
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
  }
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
}



function getInfoCmdList($eqLogic) {
  $return = array();
  foreach ($eqLogic->getConfig()['payload']['widgets'] as $widget) {
    foreach ($widget as $item => $value) {
      if (substr_compare($item, 'Info', strlen($item)-4, 4) === 0) {
        array_push($return, $value);
      }
    }
  }
  return array_unique($return);
}

function getScenarioList($eqLogic) {
  $return = array();
  foreach ($eqLogic->getConfig()['payload']['widgets'] as $widget) {
    if ($widget['type'] == 'scenario') {
      array_push($return, $widget['scenarioId']);
    }
  }
  return $return;
}

function getCmdInfoData($eqLogic) {
  $cmds = cmd::byIds(getInfoCmdList($eqLogic));
  $result = array(
    'type' => 'SET_CMD_INFO',
    'payload' => array()
  );

  foreach ($cmds as $cmd) {
    $state = $cmd->getCache(array('valueDate', 'value'));
    $cmd_info = array(
      'id' => $cmd->getId(),
      'value' => $state['value'],
      'modified' => strtotime($state['valueDate'])
    );
    array_push($result['payload'], $cmd_info);
  }
  return $result;

}

function getScenarioData($eqLogic, $all=false) {
  $scIds = getScenarioList($eqLogic);
  $result = array(
    'type' => $all ? 'SET_ALL_SC' : 'SET_SC_INFO',
    'payload' => array()
  );

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
        'active' => $sc->getIsActive() ? 1 : 0
      );
      array_push($result['payload'], $sc_info);
    }
  }
  return $result;
}

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

?>
