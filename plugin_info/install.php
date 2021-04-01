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
    config::save('userImgPath', 'plugins/JeedomConnect/data/img/user_files/' , 'JeedomConnect') ;
    $img_dir = __DIR__ . '/../data/img/user_files/';
    if (!is_dir($img_dir)) {
			mkdir($img_dir);
		}
  }
}

function JeedomConnect_update() {
  log::add('JeedomConnect', 'info', 'Restart daemon');
  JeedomConnect::deamon_start();

  foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
    $eqLogic->updateConfig();
  }

  // message::add( 'JeedomConnect',  'Installation terminée.<br>Cette nouvelle version nécessite des actions de votre part pour fonctionner correctement. Merci de lire <a href=\"https://jared-94.github.io/JeedomConnectDoc/fr_FR/\" target=\"_blank\">la doc</a>.') ;
  $docLink = htmlentities('<a href="https://jared-94.github.io/JeedomConnectDoc/fr_FR/" target="_blank">https://jared-94.github.io/JeedomConnectDoc/fr_FR/</a>');
  message::add( 'JeedomConnect',  'Mise à jour terminée. Cette nouvelle version nécessite des actions de votre part pour fonctionner correctement -- pensez à lire la doc : ' . $docLink ) ;

  JeedomConnect::displayMessageInfo();

  if (config::byKey('userImgPath',   'JeedomConnect') == '') {
    config::save('userImgPath', 'plugins/JeedomConnect/data/img/user_files/' , 'JeedomConnect') ;
    $img_dir = __DIR__ . '/../data/img/user_files/';
    if (!is_dir($img_dir)) {
			mkdir($img_dir);
		}
  }
}

function JeedomConnect_remove() {
}
