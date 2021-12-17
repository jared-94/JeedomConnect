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

	if (!isConnect('admin')) {
		throw new \Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'orderWidget') {

		$widgetArray = JeedomConnectWidget::getWidgets();

		switch (init('orderBy')) {
			case 'name':
				$widgetName = array_column($widgetArray, 'name');
				array_multisort($widgetName, SORT_ASC, $widgetArray);
				break;

			case 'type':
				$widgetType = array_column($widgetArray, 'type');
				$widgetName = array_column($widgetArray, 'name');
				array_multisort($widgetType, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
				break;

			default:
				$roomName  = array_column($widgetArray, 'roomName');
				$widgetName = array_column($widgetArray, 'name');

				array_multisort($roomName, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
				break;
		}

		$listWidget = '';
		foreach ($widgetArray as $widget) {

			$img = $widget['img'];

			$opacity = $widget['enable'] ? '' : 'disableCard';
			$widgetName = $widget['name'];
			$widgetRoom = $widget['roomName'];;
			$id = $widget['id'];
			$widgetType = $widget['type'];

			$name = '<span class="label labelObjectHuman" style="text-shadow : none;">' . $widgetRoom . '</span><br><strong> ' . $widgetName . '</strong>';

			$listWidget .= '<div class="widgetDisplayCard cursor ' . $opacity . '" data-widget_id="' . $id . '" data-widget_type="' . $widgetType . '" >';
			$listWidget .= '<img src="' . $img . '"/>';
			$listWidget .= '<br>';
			$listWidget .= '<span class="name">' . $name . '</span>';
			$listWidget .= '</div>';
		}
		ajax::success(array('widgets' => $listWidget));
	}

	if (init('action') == 'getJeedomObject') {
		$list = array();
		$options = '';
		foreach ((jeeObject::buildTree(null, false)) as $object) {
			$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
			array_push($list, array("id" => intval($object->getId()), "name" => $object->getName()));
		}
		// echo $options;
		ajax::success(array('details' => $list, 'options' => $options));
	}

	if (init('action') == 'getCmdsForWidgetType') {
		$widget_type = init('widget_type');
		$eqLogicId = !is_numeric(init('eqLogic_Id')) ? null : init('eqLogic_Id');
		log::add('JeedomConnect', 'debug', 'getCmdsForWidgetType:' . $widget_type . ' - for eqLogicId : ' . $eqLogicId);

		$results = JeedomConnectUtils::generateWidgetWithGenType($widget_type, $eqLogicId);
		log::add('JeedomConnect', 'debug', 'final generic result:' . count($results) . '-' . json_encode($results));

		ajax::success($results);
	}

	if (init('action') == 'saveWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax saveWidgetConfig for id >' . init('eqId') . '<');

		$id = init('eqId') ?: JeedomConnectWidget::incrementIndex();
		$newConfWidget = array();
		$newConfWidget['imgPath'] = init('imgPath');
		$jcTemp = json_decode(init('widgetJC'), true);
		$jcTemp['id'] = intval($id);
		$newConfWidget['widgetJC'] = json_encode($jcTemp);

		JeedomConnectWidget::saveConfig($newConfWidget, $id);

		if (!is_null(init('eqId'))  && init('eqId') != '') {
			/** @var JeedomConnect $eqLogic */
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$eqLogic->checkEqAndUpdateConfig(init('eqId'));
			}
		}


		ajax::success(array('id' => $id));
	}

	if (init('action') == 'migrateConfiguration') {

		$scope = init('scope') ?? '';
		$more = false;
		/** @var JeedomConnect $eqLogic */
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			if (($scope == 'all') || (($scope == 'enableOnly') && $eqLogic->getIsEnable())) {
				log::add('JeedomConnect_migration', 'info', 'migrate conf for equipment ' . $eqLogic->getName());
				$eqLogic->moveToNewConfig();
			} else {
				log::add('JeedomConnect_migration', 'info', 'configuration for equipement "' . $eqLogic->getName() . '" not migrated because equipement disabled');
				$more = true;
			}
		}

		ajax::success(array('more' => $more));
	}

	if (init('action') == 'exportWidgets') {
		log::add('JeedomConnect', 'debug', 'ajax -- fx exportWidgets');
		JeedomConnectWidget::exportWidgetConf();
		ajax::success();
	}

	if (init('action') == 'exportCustomData') {
		log::add('JeedomConnect', 'debug', 'ajax -- fx exportCustomData');
		JeedomConnectWidget::exportWidgetCustomConf();
		ajax::success();
	}

	if (init('action') == 'uploadWidgets') {
		log::add('JeedomConnect', 'debug', 'ajax -- fx uploadWidgets');
		try {
			JeedomConnectWidget::uploadWidgetConf(init('data'));
			ajax::success("Import avec succès");
		} catch (Exception $e) {
			ajax::success($e->getMessage());
		}
	}


	if (init('action') == 'reinitEquipement') {
		$nbEq = 0;
		/** @var JeedomConnect $eqLogic */
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$eqLogic->resetConfigFile();
			$nbEq++;
		}

		ajax::success(array('eqLogic' => $nbEq));
	}

	if (init('action') == 'getWidgetMass') {
		$ids = init('id') ?? 'all';
		$allWidgets = JeedomConnectWidget::getWidgets($ids);

		$jsonConfig = json_decode(file_get_contents(__DIR__ . '/../config/widgetsConfig.json'), true);
		$widgetArrayConfig = array();
		foreach ($jsonConfig['widgets'] as $config) {
			$widgetArrayConfig[$config['type']] =  $config;
		}


		$widgetsByEquipment = array();
		/** @var JeedomConnect $eqLogic */
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$item = array();

			$widgetForEq = $eqLogic->getWidgetId();
			$item['eqId'] = $eqLogic->getId();
			$item['eqName'] = $eqLogic->getName();
			$item['widgets'] = $widgetForEq;

			array_push($widgetsByEquipment, $item);
		}
		log::add('JeedomConnect', 'debug', 'ajax -- widgetsByEquipment => ' . json_encode($widgetsByEquipment));

		$html = '';
		foreach ($allWidgets as $widget) {
			$widgetJC = json_decode($widget['widgetJC'], true);
			$html .= ($ids == 'all') ? '<tr class="tr_object" data-widget_id="' . $widget['id'] . '" >' : '';
			$html .= '<td style="width:40px;"><span class="label label-info objectAttr bt_openWidget" data-l1key="widgetId" style="cursor: pointer !important;">' . $widget['id'] . '</span></td>';

			// **********    TYPE    ****************
			$html .= '<td style="width:40px;"><span class="label objectAttr" data-l1key="type" data-l2key="' . $widget['type'] . '">' . str_replace('de génériques ', '',  $widgetArrayConfig[$widget['type']]['name']) . '</span></td>';


			// **********    ROOM    ****************
			$html .= '<td >';
			$html .= '<select style="width:150px;" class="objectAttr"  data-l1key="roomId">';
			$html .= '<option value="none">Aucun</option>';

			foreach ((jeeObject::buildTree(null, false)) as $object) {
				$select = ($widget['roomId'] == $object->getId()) ? 'selected' : '';
				$html .= ' <option value="' . $object->getId() . '" ' . $select . '>' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
			}

			$html .= '</select>';
			$html .= '</td>';
			// ****************************************

			$html .= '<td style="width:40px;"><input type="text" class="objectAttr" data-l1key="name" value="' . cmd::cmdToHumanReadable($widget['name']) . '" /></td>';


			// **********   SUBTITLE    ****************
			/*
			$hasSubTitle = false;
			foreach ($widgetArrayConfig[$widget['type']]['options'] as $opt)
			{
				if ($opt['id'] != 'subtitle') continue;
				$hasSubTitle = true;
				break;
			}
			*/
			if (isset($widgetJC['subtitle'])) {
				// if ( $hasSubTitle && isset($widgetJC['subtitle']) ){
				$html .= '<td style="width:40px;"><input type="text" class="objectAttr"  data-l1key="subtitle" value="' . cmd::cmdToHumanReadable($widgetJC['subtitle']) . '" /></td>';
			} else {
				$html .= '<td style="width:40px;"></td>';
			}

			// **********  END SUBTITLE ****************

			if ($widget['enable']) {
				$html .= '<td align="center" style="width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="enable" /></td>';
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="enable" /></td>';
			}

			// **********    DISPLAY    ****************
			$dataDisplayMode = array();
			foreach ($widgetArrayConfig[$widget['type']]['options'] as $opt) {
				if ($opt['id'] != 'display') continue;
				$dataDisplayMode = $opt['choices'];
				break;
			}
			$html .= '<td style="width:150px;">';
			$html .= '<select class="objectAttr"  data-l1key="display">';
			$html .= '<option value="none">Aucun</option>';

			foreach ($dataDisplayMode as $display) {
				$select = (isset($widgetJC['display']) && $widgetJC['display'] == $display['id']) ? 'selected' : '';
				$html .= ' <option value="' . $display['id'] . '" ' . $select . '>' . $display['name'] . '</option>';
			}

			$html .= '</select>';
			$html .= '</td>';
			// ************ END DISPLAY **************


			// **********    HIDE OTIONS    ****************
			$hideOptions = array();
			foreach ($widgetArrayConfig[$widget['type']]['options'] as $opt) {
				if ($opt['id'] != 'hideItem') continue;

				foreach ($opt['choices'] as $choice) {
					$hideOptions[] = $choice['id'];
				}
				break;
			}

			if (in_array('hideTitle', $hideOptions)) {
				if (isset($widgetJC['hideTitle']) && $widgetJC['hideTitle']) {
					$html .= '<td align="center" style="width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="hideTitle" /></td>';
				} else {
					$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideTitle" /></td>';
				}
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideTitle" disabled /></td>';
			}

			if (in_array('hideSubTitle', $hideOptions)) {
				if (isset($widgetJC['hideSubTitle']) && $widgetJC['hideSubTitle']) {
					$html .= '<td align="center" style="width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="hideSubTitle"  /></td>';
				} else {
					$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideSubTitle" /></td>';
				}
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideSubTitle" disabled /></td>';
			}

			if (in_array('hideStatus', $hideOptions)) {
				if (isset($widgetJC['hideStatus']) && $widgetJC['hideStatus']) {
					$html .= '<td align="center" style="max-width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="hideStatus" /></td>';
				} else {
					$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideStatus" /></td>';
				}
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideStatus" disabled /></td>';
			}

			if (in_array('hideIcon', $hideOptions)) {
				if (isset($widgetJC['hideIcon']) && $widgetJC['hideIcon']) {
					$html .= '<td align="center" style="width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="hideIcon" /></td>';
				} else {
					$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideIcon" /></td>';
				}
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="hideIcon" disabled /></td>';
			}
			// **********    END HIDE OTIONS    ****************



			if (isset($widgetJC['blockDetail']) && $widgetJC['blockDetail']) {
				$html .= '<td align="center" style="width:65px;"><input type="checkbox" class="objectAttr" checked data-l1key="blockDetail" /></td>';
			} else {
				$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="objectAttr" data-l1key="blockDetail" /></td>';
			}


			//**************  EQUIPEMENT INCLUSION **********************/
			$nb = 0;
			$names = '';
			$label = ' labelObjectHuman';
			foreach ($widgetsByEquipment as $item) {

				if (in_array($widget['id'], $item['widgets'])) {
					$nb++;
					$names .= ($names == '') ? $item['eqName'] : ', ' . $item['eqName'];
					$label = ' label-success';
				}
			}
			$html .= '<td style="width:60px;" class=""><span class="label ' . $label . ' nbEquipIncluded" data-title="' . $names . '" title="' . $names . '">' . $nb . '</span></td>';

			//************************************/

			$html .= '<td align="center" style="width:75px;"><input type="checkbox" class="removeWidget"/></td>';

			$html .= ($ids == 'all') ? '</tr>' : '';
		}

		ajax::success($html);
	}

	if (init('action') == 'updateWidgetMass') {

		$widgetReceived = init('widgetsObj');

		foreach ($widgetReceived as $widgetData) {
			$existingWidget = JeedomConnectWidget::getConfiguration($widgetData['widgetId']);
			log::add('JeedomConnect', 'debug', 'massUpdate - widget [' . $widgetData['widgetId'] . '] will be updated -- current data ' . json_encode($existingWidget));

			$widgetJC = json_decode($existingWidget['widgetJC'], true);

			$widgetJC['enable'] = boolval($widgetData['enable']);
			$widgetJC['name'] = cmd::humanReadableToCmd($widgetData['name']);
			$widgetJC['subtitle'] = cmd::humanReadableToCmd($widgetData['subtitle']);

			$widgetJC['room'] = intval($widgetData['roomId']);

			$widgetJC['display'] = $widgetData['display'];

			$widgetJC['hideTitle'] = boolval($widgetData['hideTitle']);
			$widgetJC['hideSubTitle'] = boolval($widgetData['hideSubTitle']);
			$widgetJC['hideStatus'] = boolval($widgetData['hideStatus']);
			$widgetJC['hideIcon'] = boolval($widgetData['hideIcon']);
			$widgetJC['blockDetail'] = boolval($widgetData['blockDetail']);

			$existingWidget['widgetJC'] = json_encode($widgetJC);

			JeedomConnectWidget::saveConfig($existingWidget, $widgetData['widgetId']);
		}

		ajax::success();
	}

	if (init('action') == 'countWigdetUsage') {
		$data = JeedomConnectWidget::countWidgetByEq();
		ajax::success($data);
	}

	if (init('action') == 'removeWidgetConfig') {
		$allConfig = (init('all') !== null) && init('all');

		if ($allConfig) {
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig -- ALL widgets will be removed');
			$allWidgets = JeedomConnectWidget::getAllConfigurations();
			$nb = 0;
			foreach ($allWidgets as $widget) {
				JeedomConnectWidget::removeWidgetConf($widget['key']);
				$nb++;
			}
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig -- widget index reinit');
			JeedomConnectWidget::removeWidgetConf('index::max');

			$nbEq = 0;
			/** @var JeedomConnect $eqLogic */
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$eqLogic->resetConfigFile();
				$nbEq++;
			}

			ajax::success(array('widget' => $nb, 'eqLogic' => $nbEq));
		} else {
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig for id >' . init('eqId') . '<');
			JeedomConnectWidget::removeWidget(init('eqId'));
			ajax::success();
		}
	}

	if (init('action') == 'duplicateWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax duplicateWidgetConfig for id >' . init('eqId') . '<');
		$newId = JeedomConnectWidget::duplicateWidget(init('eqId'));
		ajax::success(array('duplicateId' => $newId));
	}

	if (init('action') == 'getWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax getWidgetConfig for id >' . init('eqId') . '<');
		$widget = JeedomConnectWidget::getWidgets(init('eqId'));

		if ($widget == '') {
			ajax::error('Erreur - pas d\'équipement trouvé');
		} else {
			$widgetConf = $widget['widgetJC'] ?? '';
			$configJson = json_decode($widgetConf);

			if ($configJson == null) {
				ajax::error('Erreur - pas de configuration pour ce widget');
			} else {
				ajax::success($configJson);
			}
		}
	}

	if (init('action') == 'getWidgetConfigAll') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax getWidgetConfigAll ~~ retrieve config for ALL widgets');
		$widgets = JeedomConnectWidget::getWidgets('all', false, true);

		if ($widgets == '') {
			log::add('JeedomConnect', 'warning', 'no widgets found');
			//ajax::error('Erreur - pas d\'équipement trouvé');
		}

		$list = array();
		$options = '';
		foreach ((jeeObject::buildTree(null, false)) as $object) {
			$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
			array_push($list, array("id" => intval($object->getId()), "name" => $object->getName()));
		}

		log::add('JeedomConnect', 'debug', 'getWidgetConfigAll ~~ result : ' . json_encode($widgets));

		ajax::success(array('widgets' => $widgets, 'room_details' => $list, 'room_options' => $options));
	}

	if (init('action') == 'getWidgetExistance') {
		$myId = init('id');
		$arrayName = array();
		/** @var JeedomConnect $eqLogic */
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$eqIds = $eqLogic->getWidgetId();
			log::add('JeedomConnect', 'debug', 'all ids for eq [' . $eqLogic->getName() . '] : ' . json_encode($eqIds));
			if (in_array($myId, $eqIds)) {
				log::add('JeedomConnect', 'debug', $myId . ' exist in [' . $eqLogic->getName() . ']');
				array_push($arrayName, $eqLogic->getName());
			} else {
				log::add('JeedomConnect', 'debug', $myId . ' does NOT exist in [' . $eqLogic->getName() . ']');
			}
		}

		log::add('JeedomConnect', 'debug', 'ajax -- all name final -- ' . json_encode($arrayName));
		ajax::success(array('names' => $arrayName));
	}

	if (init('action') == 'humanReadableToCmd') {

		$stringWithCmdId = cmd::humanReadableToCmd(init('human'));
		if (strcmp($stringWithCmdId, init('human')) == 0) {
			log::add('JeedomConnect', 'debug', 'ajax -- fx humanReadableToCmd -- string is the same with humanCmdString and cmdId => ' . $stringWithCmdId);
			// ajax::error('La commande n\'existe pas');
		}
		ajax::success($stringWithCmdId);
	}

	if (init('action') == 'cmdToHumanReadable') {

		$cmdIdToHuman = cmd::cmdToHumanReadable(init('strWithCmdId'));
		if (strcmp($cmdIdToHuman, init('strWithCmdId')) == 0) {
			log::add('JeedomConnect', 'debug', 'ajax -- fx cmdToHumanReadable -- string is the same with cmdId and no cmdId => ' . $cmdIdToHuman);
			// ajax::error('La commande n\'existe pas');
		}
		ajax::success($cmdIdToHuman);
	}

	if (init('action') == 'getEquipments') {

		$result = array();
		/** @var JeedomConnect $eqLogic */
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$apiKey = $eqLogic->getConfiguration('apiKey');
			$name = $eqLogic->getName();
			$eqId = $eqLogic->getId();
			array_push($result, array('apiKey' => $apiKey, 'name' => $name, 'eqId' => $eqId));
		}
		ajax::success($result);
	}

	if (init('action') == 'copyConfig') {
		$from = init('from');
		$toArray = init('to');

		$copy = JeedomConnect::copyConfig($from, $toArray);

		ajax::success($copy);
	}

	if (init('action') == 'saveConfig') {
		$config = init('config');
		$apiKey = init('apiKey');

		$configJson = json_decode($config);
		/** @var JeedomConnect $eqLogic */
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic) or $configJson == null) {
			ajax::error('Erreur');
		} else {
			$eqLogic->saveConfig($configJson);
			$eqLogic->setConfiguration('configVersion', $configJson->payload->configVersion);
			$eqLogic->save(true);

			$eqLogic->getConfig(true, true);
			$eqLogic->cleanCustomData();
			ajax::success();
		}
	}

	if (init('action') == 'getConfig') {
		$apiKey = init('apiKey');
		/** @var JeedomConnect $eqLogic */
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		$allConfig = (init('all') !== null) && init('all');
		$saveGenerated = (init('all') !== null) && init('all');
		if (!is_object($eqLogic)) {
			ajax::error('Erreur - no equipment found');
		} else {
			//$eqLogic->updateConfig();
			$configJson = $eqLogic->getConfig($allConfig, $saveGenerated);
			ajax::success($configJson);
		}
	}

	if (init('action') == 'getNotifs') {
		$apiKey = init('apiKey');
		/** @var JeedomConnect $eqLogic */
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
		/** @var JeedomConnect $eqLogic */
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic) or $configJson == null) {
			ajax::error('Erreur');
		} else {
			$eqLogic->saveNotifs($configJson);
			ajax::success();
		}
	}

	if (init('action') == 'saveNotifAll') {
		$cmdList = init('cmdList');
		if ($cmdList == "") $cmdList = array();
		log::add('JeedomConnect', 'debug', 'saveNotifAll - info received : ' . json_encode($cmdList));
		config::save('notifAll', json_encode($cmdList), 'JeedomConnect');
		ajax::success();
	}

	if (init('action') == 'uploadImg') {
		$filename = $_FILES['file']['name'];
		$destination = __DIR__ . '/../../data/img/user_files/';
		if (!is_dir($destination)) {
			mkdir($destination);
		}
		$location = $destination . $filename;

		if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {
			ajax::success();
		} else {
			ajax::error();
		}
	}

	if (init('action') == 'removeDevice') {
		$id = init('id');
		/** @var JeedomConnect $eqLogic */
		$eqLogic = \eqLogic::byId($id);
		$eqLogic->removeDevice();
		ajax::success();
	}

	if (init('action') == 'getCmd') {
		$id = init('id');
		if ($id == '') throw new Exception("id est obligatoire");

		$cmd = (preg_match("/^\d+$/", $id)) ? cmd::byId($id) : cmd::byString($id);
		if (!is_object($cmd)) {
			throw new Exception(__('Commande inconnue : ', __FILE__) . $id);
		}
		ajax::success(array(
			'id' => $cmd->getId(),
			'type' => $cmd->getType(),
			'subType' => $cmd->getSubType(),
			'humanName' => $cmd->getHumanName(),
			'name' => $cmd->getName(),
			'minValue' => $cmd->getConfiguration('minValue'),
			'maxValue' => $cmd->getConfiguration('maxValue'),
			'unit' => $cmd->getUnite(),
			'value' => $cmd->getValue(),
			'icon' => $cmd->getDisplay('icon')
		));
	}

	if (init('action') == 'getImgList') {
		$internalImgPath = __DIR__ . '/../../data/img/';
		$userImgPath = $internalImgPath . "user_files/";

		$internal = array_diff(scandir($internalImgPath), array('..', '.', 'user_files'));
		$user = array_diff(scandir($userImgPath), array('..', '.'));

		$result = [
			'internal' => $internal,
			'user' => $user
		];

		ajax::success($result);
	}

	if (init('action') == 'generateQRcode') {
		$id = init('id');
		/** @var JeedomConnect $eqLogic */
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
	} else {
		ajax::error(displayException($e), $e->getCode());
	}
}
