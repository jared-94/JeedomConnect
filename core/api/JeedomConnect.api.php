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
$data = json_decode($jsonData, true);

//log::add('JeedomConnect', 'debug', 'HTTP API received '.$jsonData);

$eqLogic = eqLogic::byLogicalId($data['apiKey'], 'JeedomConnect');
if (!is_object($eqLogic)) {
  throw new Exception(__('No valid API key', __FILE__), -32699);
}

log::add('JeedomConnect', 'debug', 'HTTP API received '.$jsonData);

switch ($data['type']) {
  case 'PING':
    echo '{"result": "ok"}';
    break;
  case 'CMD_EXEC':
    $cmd = cmd::byId($data['payload']['id']);
    if (!is_object($cmd)) {
      log::add('JeedomConnect', 'error', "Can't find command");
      return;
    }
    $cmd->execCmd($option = $data['payload']['options']);
    break;
  case 'SC_EXEC':
    $sc = \scenario::byId($data['payload']['id']);
    $sc->launch();
    break;
  case 'ASK_REPLY':
    $answer = $data['payload']['answer'];
    $cmd = cmd::byId($data['payload']['cmdId']);
    if ($cmd->askResponse($answer)) {
      log::add('JeedomConnect', 'debug', 'reply to ask OK');
    }
    break;
  case 'GEOLOC':
    if (array_key_exists('location', $data) and  array_key_exists('geofence', $data['location']) ) {
      $geofenceCmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'geofence_' . $data['location']['geofence']['identifier']);
      if (!is_object($geofenceCmd)) {
        log::add('JeedomConnect', 'error', "Can't find geofence command");
        return;
      }
      if ($data['location']['geofence']['action'] == 'ENTER') {
        if ($geofenceCmd->execCmd() != 1) {
          log::add('JeedomConnect', 'debug', "Set 1 for geofence " . $data['location']['geofence']['extras']['name']);
          $geofenceCmd->event(1);
        }
      } else if ($data['location']['geofence']['action'] == 'EXIT') {
        if ($geofenceCmd->execCmd() != 0) {
          log::add('JeedomConnect', 'debug', "Set 0 for geofence " . $data['location']['geofence']['extras']['name']);
          $geofenceCmd->event(0);
        }
      }
    } else {
      $eqLogic->setGeofencesByCoordinates($data['location']['coords']['latitude'], $data['location']['coords']['longitude']);
    }
    break;
}


?>
