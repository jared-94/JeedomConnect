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
	require_once dirname(__FILE__) . '/../class/JeedomConnect.class.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect()) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'getDefaultPosition') {

		list($lng, $lat) = JeedomConnectUtils::getJcCoordinates();
		list($lngDefault, $latDefault) = JeedomConnectUtils::getDefaultCoordinates();

		$defaultZoom = (($lat . $lng) == ($latDefault . $lngDefault)) ? 'Autour Paris' : 'Autour de mon Jeedom';

		ajax::success(array('lng' => $lng, 'lat' => $lat, 'defaultText' => $defaultZoom));
	}

	if (init('action') == 'getAllPositions') {
		$result = array();
		$id = init('id', 'all');

		$user = user::byId($_SESSION['user']->getId());
		if (!is_object($user)) ajax::error('unable to find user details');

		if ($id == 'all') {
			$eqLogics = JeedomConnect::getAllJCequipment();
		} else {
			/** @var cmd $cmdTmp */
			$cmdTmp = cmd::byId($id);
			if (!is_object($cmdTmp)) return;
			$eqTmp = eqLogic::byId($cmdTmp->getEqLogic_id());
			$eqLogics = array($eqTmp);
		}

		/** @var JeedomConnect $eqLogic */
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('displayPosition', 0) == 0) continue;

			/** @var cmd $cmd */
			$cmd = $eqLogic->getCmd(null, 'position');
			if (!is_object($cmd)) continue;
			// JCLog::debug("position cmd/id => " . $cmd->getId());
			if (!$cmd->hasRight($user)) {
				JCLog::warning('limited user try to access equipment position');
				continue;
			}

			/** @var string $position */
			$position = $cmd->execCmd();
			if ($position == "") continue;

			$data = explode(',', $position);
			if (count($data) < 2) continue;
			$cmdDistance = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),  'distance');
			$distance = is_object($cmdDistance) ? number_format(floatval($cmdDistance->execCmd()), 0, ',', ' ') . ' ' . $cmdDistance->getUnite() : '';
			$img = $eqLogic->getConfiguration('customImg', 'plugins/JeedomConnect/data/img/pin.png');
			$infoImg = getimagesize('/var/www/html/' . $img);
			$result[] = array(
				'id' => $cmd->getId(),
				'name' => $eqLogic->getName(),
				'eqId' => $eqLogic->getId(),
				'lat' => round($data[0], 6),
				'lng' => round($data[1], 6),
				'lastSeen' => $cmd->getCollectDate(),
				'icon' => $img,
				'infoImg' => $infoImg,
				'distance' => $distance
			);
		}
		ajax::success($result);
	}


	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
} catch (Exception $e) {
	if (function_exists('displayException')) {
		ajax::error(displayException($e), $e->getCode());
	} else {
		ajax::error($e->getMessage(), $e->getCode());
	}
}
