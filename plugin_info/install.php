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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function JeedomConnect_install() {

  JeedomConnect::displayMessageInfo();

  if (config::byKey('userImgPath',   'JeedomConnect') == '') {
    config::save('userImgPath', 'plugins/JeedomConnect/data/img/user_files/', 'JeedomConnect');
  }

  if (!is_dir(__DIR__ . '/../../../' . config::byKey('userImgPath',   'JeedomConnect'))) {
    mkdir(__DIR__ . '/../../../' . config::byKey('userImgPath',   'JeedomConnect'));
  }

  if (config::byKey('migration::imgCond',   'JeedomConnect') == '') {
    JeedomConnect::migrateCondImg();
  }

  if (config::byKey('migration::customData',   'JeedomConnect') == '') {
    JeedomConnect::migrateCustomData();
  }

  if (config::byKey('migration::notifAll',   'JeedomConnect') == '') {
    JeedomConnect::migrationAllNotif();
  }

  $pluginInfo = JeedomConnect::getPluginInfo();
  config::save('version', $pluginInfo['version'] ?? '#NA#', 'JeedomConnect');

  JeedomConnectUtils::addCronCheckDaemon();
  JeedomConnect::createMapEquipment();
}

function JeedomConnect_update() {

  /** @var JeedomConnect $eqLogic */
  foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
    $eqLogic->updateConfig();
    $eqLogic->generateNewConfigVersion();
  }

  if (config::byKey('userImgPath',   'JeedomConnect') == '') {
    config::save('userImgPath', 'plugins/JeedomConnect/data/img/user_files/', 'JeedomConnect');
  } else {
    $userImgPath = ltrim(config::byKey('userImgPath',   'JeedomConnect'), "/");
    if (substr($userImgPath, -1) != "/") {
      $userImgPath .= "/";
    }
    config::save('userImgPath', $userImgPath, 'JeedomConnect');
  }

  if (!is_dir(__DIR__ . '/../../../' . config::byKey('userImgPath',   'JeedomConnect'))) {
    mkdir(__DIR__ . '/../../../' . config::byKey('userImgPath',   'JeedomConnect'));
  }

  if (config::byKey('migration::imgCond',   'JeedomConnect') == '') {
    JeedomConnect::migrateCondImg();
  }

  if (config::byKey('migration::customData',   'JeedomConnect') == '') {
    JeedomConnect::migrateCustomData();
  }

  if (config::byKey('migration::notifAll',   'JeedomConnect') == '') {
    JeedomConnect::migrationAllNotif();
  }

  if (config::byKey('migration::appPref',   'JeedomConnect') == '') {
    JeedomConnect::migrateAppPref();
  }

  $pluginInfo = JeedomConnect::getPluginInfo();
  config::save('version', $pluginInfo['version'] ?? '#NA#', 'JeedomConnect');

  JeedomConnectUtils::addCronCheckDaemon();
  JeedomConnect::createMapEquipment();


  //////// PLEASE KEEP IT AT THE END !! 
  // FORCE save on all equipments to save new cmd
  /** @var JeedomConnect $eqLogic */
  foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
    $eqLogic->save();
  }
  ///---------- NOTHING BELOW PLZ !!!!!!!! 
}

function JeedomConnect_remove() {
  try {
    $crons = cron::searchClassAndFunction('JeedomConnect', 'checkDaemon');
    if (is_array($crons)) {
      foreach ($crons as $cron) {
        $cron->remove();
      }
    }
  } catch (Exception $e) {
  }
}
