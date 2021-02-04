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

 require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
 require_once dirname(__FILE__) . "/../class/apiHelper.class.php";

header('Access-Control-Allow-Origin: *');
header("Content-Type: text/event-stream\n\n");
header('Cache-Control: no-cache');
ignore_user_abort(true);

$apiKey = init('apiKey');
$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

if (!is_object($eqLogic)) {
  log::add('JeedomConnect', 'debug', "Can't find eqLogic");
  throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
}

$config = $eqLogic->getConfig();
$lastReadTimestamp = time();

while (true) {
  if (connection_aborted() || !connection_status() == CONNECTION_NORMAL) {
      sleep(2);
      die();
  }
  $newConfig = apiHelper::lookForNewConfig(eqLogic::byLogicalId($apiKey, 'JeedomConnect'), $config);
  if ($newConfig != false) {
    $config = $newConfig;
    $result = array(
      'datetime' => time(),
      'result' => array()
    );
    array_push($result['result'], array(
      'name' => 'config::setConfig',
      'option' => $config
    ));
    log::add('JeedomConnect', 'debug', "eventServer send new config : " . json_encode($result));
    echo "data: " . json_encode($result) . "\n\n";
    echo "id: " . $lastReadTimestamp . "\n\n";
    ob_flush();
    flush();
    sleep(1);
  }
  $events = event::changes($lastReadTimestamp);
  $data = getData($events);
  if (count($data['result']) > 0) {
    //log::add('JeedomConnect', 'debug', "eventServer send : " . json_encode($data));
    echo "data: " . json_encode($data) . "\n\n";
    echo "id: " . $lastReadTimestamp . "\n\n";
    ob_flush();
    flush();
    $lastReadTimestamp = time();
  }
  sleep(1);
}

function getData($events) {
  global $eqLogic, $config;
  $infoIds = apiHelper::getInfoCmdList($config);
  $scIds = apiHelper::getScenarioList($config);
  $objIds = apiHelper::getObjectList($config);
  $result = array(
    'datetime' => $events['datetime'],
    'result' => array()
  );

  foreach ($events['result'] as $event) {
    if ($event['name'] == 'jeeObject::summary::update') {
      if (in_array($event['option']['object_id'], $objIds)) {
        array_push($result['result'], $event);
      }
    }
    if ($event['name'] == 'scenario::update') {
      if (in_array($event['option']['scenario_id'], $scIds)) {
        array_push($result['result'], $event);
      }
    }
    if ($event['name'] == 'cmd::update') {
      if (in_array($event['option']['cmd_id'], $infoIds) ) {
        array_push($result['result'], $event);
      }
    }
  }
  return $result;
}
