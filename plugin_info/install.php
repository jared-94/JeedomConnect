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
}

function JeedomConnect_update() {
  log::add('JeedomConnect', 'info', 'Restart daemon');
  JeedomConnect::deamon_start();

  /** @var JeedomConnect $eqLogic */
  foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
    $eqLogic->updateConfig();
    $eqLogic->generateNewConfigVersion();
  }

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

  // FORCE save on all equipments to save new cmd
  /** @var JeedomConnect $eqLogic */
  foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
    $eqLogic->save();
  }

  $pluginInfo = JeedomConnect::getPluginInfo();
  config::save('version', $pluginInfo['version'] ?? '#NA#', 'JeedomConnect');
}

function JeedomConnect_remove() {
}
