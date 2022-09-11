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
require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";

ob_end_clean();


function sse($data = null) {
  if (!is_null($data)) {
    echo "data:" . json_encode($data);
    echo "\r\n\r\n";
    if (@ob_get_level() > 0) for ($i = 0; $i < @ob_get_level(); $i++) @ob_flush();
    @flush();
  }
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
ignore_user_abort(true);


$apiKey = init('apiKey');
/** @var \JeedomConnect */
$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

try {
  if (!is_object($eqLogic)) {
    JCLog::debug("[SSE] Can't find eqLogic");
    throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
  }
  $id = rand(0, 1000);
  JCLog::debug("[SSE] eventServer init client #" . $id);


  $config = $eqLogic->getGeneratedConfigFile();
  $lastReadTimestamp = time();
  $lastHistoricReadTimestamp = time();
  $step = 0;

  sse(
    json_encode(
      apiHelper::getAllInformations($eqLogic)
    )
  );

  $eqLogic->setConfiguration('sessionId', $id);
  $eqLogic->setConfiguration('connected', 1);
  $eqLogic->setConfiguration('scAll', 0);
  $eqLogic->setConfiguration('appState', 'active');
  $eqLogic->save(true);

  while (true) {
    /** @var \JeedomConnect */
    $logic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

    if (!is_object($logic)) {
      throw new Exception("[SSE] EqLogic not found anymore");
    }

    if ($logic->getConfiguration('useWs', 0) == 1) {
      JCLog::debug("[SSE] connexion switched to WS - stop sse for client #" . $id . '  -- die');
      die();
    }

    if ($logic->getConfiguration('polling', 0) == 1) {
      JCLog::debug("[SSE] connexion switched to Polling - stop sse for client #" . $id . '  -- die');
      die();
    }

    if ($logic->getConfiguration('sessionId', 0) != $id) {
      JCLog::debug("[SSE] new sse client is active - stop sse for client #" . $id . '  -- die');
      die();
    }

    if (connection_aborted() || connection_status() != CONNECTION_NORMAL) {
      JCLog::debug("[SSE] eventServer connexion closed for client #" . $id);
      if ($logic->getConfiguration('sessionId', 0) == $id) {
        $logic->setConfiguration('connected', 0);
        $logic->setConfiguration('appState', 'background');
        $logic->save();
      }
      die();
    }


    $params = array(
      'configVersion' => $config['payload']['configVersion'],
      'lastReadTimestamp' => $lastReadTimestamp,
      'lastHistoricReadTimestamp' => $lastHistoricReadTimestamp,
    );

    $result = apiHelper::dispatch('SSE', 'GET_EVENTS', $logic, $params, $apiKey);
    $sendInfo = false;
    $log = true;
    if ($result != null) {
      // JCLog::debug("receive from GET_EVENTS => " . json_encode($result));
      if ($result['type'] ==  "SET_EVENTS") {
        foreach ($result['payload'] as $item) {
          if ($item['type'] == 'DATETIME') {
            $lastReadTimestamp = floatval($item['payload']);
          } elseif ($item['type'] == 'HIST_DATETIME') {
            $lastHistoricReadTimestamp = floatval($item['payload']);
          } else {
            // check if there is at least one other item to send Cmd, Sc, Obj
            $sendInfo = ($sendInfo || (key_exists('payload', $item) && is_array($item['payload']) && count($item['payload']) > 0));
            // JCLog::debug("sendInfo : " . ($sendInfo ? 'true' : 'false'));
            if ($sendInfo) {
              $log = false;
              break;
            }
          }
        }
      } else {
        if ($result['type'] ==  "CONFIG_AND_INFOS") {
          // JCLog::debug("eventServer - saving new config ! => " . json_encode($result['payload']['config']));
          $config = $result['payload']['config'];
        }
        $sendInfo = true;
      }

      if ($sendInfo) {
        if ($log) {
          JCLog::debug("[SSE] eventServer sending => " . json_encode($result));
        } else {
          JCLog::trace("[SSE] eventServer sending => " . json_encode($result));
        }
        sse(json_encode($result));
      }
    }

    if (!$sendInfo) {
      $step += 1;
      if ($step == 5) {
        // JCLog::debug("eventServer heartbeat to #" . $id);
        sse(json_encode(array('event' => 'heartbeat')));
        $step = 0;
      }
    }
    sleep(1);
  }
} catch (Exception $e) {
  // JCLog::error('on sse ' . $e->getMessage());
  $result = apiHelper::raiseException($e->getMessage(), 'SSE');
  sse(json_encode($result));
}
