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
  
  $docLink = htmlentities('<a href="https://github.com/jared-94/JeedomConnectDoc/raw/master/resources/apk/stable/JeedomConnect-0.17.1.apk" target="_blank">télécharger la nouvelle version ici</a>');
  message::add( 'JeedomConnect',  'Cette version nécessite le téléchargement d\'un nouvel APK --> ' . $docLink ) ;
  
}

function JeedomConnect_update() {
  log::add('JeedomConnect', 'info', 'Restart daemon');
  JeedomConnect::deamon_start();

  foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
    $eqLogic->updateConfig();
  }
  
  $docLink = htmlentities('<a href="https://github.com/jared-94/JeedomConnectDoc/raw/master/resources/apk/stable/JeedomConnect-0.17.1.apk" target="_blank">télécharger la nouvelle version ici</a>');
  message::add( 'JeedomConnect',  'Cette nouvelle version nécessite le téléchargement d\'un nouvel APK --> ' . $docLink ) ;
	

}

function JeedomConnect_remove() {
}
