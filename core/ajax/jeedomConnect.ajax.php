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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new \Exception(__('401 - Accès non autorisé', __FILE__));
    }


	if (init('action') == 'saveConfig') {
    $config = init('config');
		$apiKey = init('apiKey');

		$configJson = json_decode($config);
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic) or $configJson == null) {
			ajax::error('Erreur');
		} else {
			$eqLogic->setConfiguration('configVersion', $configJson->payload->configVersion);
			$eqLogic->save();
			$eqLogic->saveConfig($configJson);
			ajax::success();
		}
  }

  if (init('action') == 'getConfig') {
    $apiKey = init('apiKey');
    $eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
    if (!is_object($eqLogic)) {
			ajax::error('Erreur');
		} else {
      //$eqLogic->updateConfig();
			$configJson = $eqLogic->getConfig();
			ajax::success($configJson);
		}
  }

  if (init('action') == 'getNotifs') {
		$apiKey = init('apiKey');

		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic)) {
			ajax::error('Erreur');
		} else {
			$notifs = $eqLogic->getNotifs();
			ajax::success($notifs);
		}
  }

  if (init('action') == 'saveNotifs') {
    $config = init('config');
		$apiKey = init('apiKey');

		$configJson = json_decode($config, true);
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic) or $configJson == null) {
			ajax::error('Erreur');
		} else {
			$eqLogic->saveNotifs($configJson);
			ajax::success();
		}
  }

  if (init('action') == 'uploadImg') {
    $filename = $_FILES['file']['name'];
		$destination = __DIR__ . '/../../data/img/user_files/';
		if (!is_dir($destination)) {
			mkdir($destination);
		}
		$location = $destination.$filename;

		if (move_uploaded_file($_FILES['file']['tmp_name'],$location)){
			ajax::success();
		} else {
			ajax:error();
		}
  }

	if (init('action') == 'removeDevice') {
		$id = init('id');
		$eqLogic = \eqLogic::byId($id);
		$eqLogic->removeDevice();
		ajax::success();
	}

  if (init('action') == 'getCmd') {
    $cmd = cmd::byId(init('id'));
    if (!is_object($cmd)) {
				throw new Exception(__('Commande inconnue : ', __FILE__) . init('id'));
		}
    ajax::success(array(
      'id' => init('id'),
      'type' => $cmd->getType(),
      'subType' => $cmd->getSubType(),
      'humanName' => $cmd->getHumanName(),
      'minValue' => $cmd->getConfiguration('minValue'),
      'maxValue' => $cmd->getConfiguration('maxValue'),
      'unit' => $cmd->getUnite(),
      'value' => $cmd->getValue(),
      'icon' => $cmd->getDisplay('icon')
    ));
  }

	if (init('action') == 'getImgList') {
    $internalImgPath = __DIR__ . '/../../data/img/';
		$userImgPath = $internalImgPath."user_files/";

		$internal = array_diff(scandir($internalImgPath), array('..', '.','user_files'));
		$user = array_diff(scandir($userImgPath), array('..', '.'));

		$result = [
			'internal' => $internal,
			'user' => $user
		];

		ajax::success($result);
    }

	if (init('action') == 'generateQRcode') {
    $id = init('id');
		$eqLogic = \eqLogic::byId($id);
		if (!is_object($eqLogic)) {
			ajax::error('Erreur');
		} else {
			$eqLogic->generateQRCode();
			ajax::success();
		}
  }

   throw new \Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
} catch (\Exception $e) {
    if (function_exists('displayException')) {
        ajax::error(displayException($e), $e->getCode());
    }
    else {
        ajax::error(displayExeption($e), $e->getCode());
    }
}
