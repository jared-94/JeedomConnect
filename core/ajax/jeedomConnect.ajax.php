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
    require_once dirname(__FILE__) . '/../class/JeedomConnectWidget.class.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new \Exception(__('401 - Accès non autorisé', __FILE__));
    }

	if (init('action') == 'orderWidget') {

		$widgetArray= JeedomConnectWidget::getWidgets();

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

			$img = $widget['img'] ;

			$opacity = $widget['enable'] ? '' : 'disableCard';
			$widgetName = $widget['name'] ;
			$widgetRoom = $widget['roomName'] ; ;
			$id = $widget['id'];
			$widgetType = $widget['type'];

			$name = '<span class="label labelObjectHuman" style="text-shadow : none;">'.$widgetRoom.'</span><br><strong> '.$widgetName.'</strong>' ;

			$listWidget .= '<div class="widgetDisplayCard cursor '.$opacity.'" data-widget_id="' . $id . '" data-widget_type="' . $widgetType . '" >';
			$listWidget .= '<img src="' . $img . '"/>';
			$listWidget .= '<br>';
			$listWidget .= '<span class="name">' . $name . '</span>';
			$listWidget .= '</div>';

		}
		ajax::success(array('widgets' => $listWidget) );

	}

	if (init('action') == 'getJeedomObject') {
		$list = array();
		$options = '';
		foreach ((jeeObject::buildTree(null, false)) as $object) {
			$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
			array_push($list, array("id" => intval( $object->getId() ), "name" => $object->getName() ) ) ;
		}
		// echo $options;
		ajax::success( array('details' => $list, 'options' => $options) );

	}

	if (init('action') == 'saveWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax saveWidgetConfig for id >' . init('eqId') . '<');

		$id = init('eqId') ?: JeedomConnectWidget::incrementIndex();
		$newConfWidget = array();
		$newConfWidget['imgPath'] = init('imgPath') ;
		$jcTemp = json_decode(init('widgetJC'), true)	;
		$jcTemp['id'] = intval($id);
		$newConfWidget['widgetJC'] = json_encode($jcTemp);

		JeedomConnectWidget::saveConfig($newConfWidget, $id) ;

		if (! is_null(init('eqId'))  && init('eqId') != '' ){
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$eqLogic->checkEqAndUpdateConfig(init('eqId')) ;
			}
		}


		ajax::success(array('id' => $id));

	}

	if (init('action') == 'migrateConfiguration') {

		$scope = init('scope') ?? '' ;
		$more = false;
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			if ( ( $scope == 'all' ) || ( ( $scope == 'enableOnly' ) && $eqLogic->getIsEnable() ) ){
				log::add('JeedomConnect_migration', 'info', 'migrate conf for equipment ' . $eqLogic->getName() ) ;
				$eqLogic->moveToNewConfig();
			}
			else{
				log::add('JeedomConnect_migration', 'info', 'configuration for equipement "'.$eqLogic->getName().'" not migrated because equipement disabled');
				$more = true;
			}
		}

		ajax::success(array('more' => $more ));
	}

	if (init('action') == 'reinitEquipement') {
		$nbEq = 0;
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$eqLogic->resetConfigFile();
			$nbEq ++;
		}

		ajax::success(array('eqLogic' => $nbEq));
	}

	if (init('action') == 'countWigdetUsage') {
		$data = JeedomConnectWidget::countWidgetByEq();
		ajax::success($data);
	}

	if (init('action') == 'removeWidgetConfig') {
		$allConfig = (init('all') !== null) && init('all') ;

		if ( $allConfig ){
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig -- ALL widgets will be removed');
			$allWidgets = JeedomConnectWidget::getAllConfigurations();
			$nb = 0;
			foreach ($allWidgets as $widget ) {
				JeedomConnectWidget::removeWidgetConf($widget['key']);
				$nb ++;
			}
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig -- widget index reinit');
			JeedomConnectWidget::removeWidgetConf('index::max');

			$nbEq = 0;
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$eqLogic->resetConfigFile();
				$nbEq ++;
			}

			ajax::success(array('widget' => $nb, 'eqLogic' => $nbEq));
		}
		else{
			log::add('JeedomConnect', 'debug', '-- manage fx ajax removeWidgetConfig for id >' . init('eqId') . '<');
			JeedomConnectWidget::removeWidget(init('eqId'));
			ajax::success();
		}

	}

	if (init('action') == 'duplicateWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax duplicateWidgetConfig for id >' . init('eqId') . '<');
		$newId = JeedomConnectWidget::duplicateWidget(init('eqId'));
		ajax::success(array('duplicateId' => $newId) );

	}

	if (init('action') == 'getWidgetConfig') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax getWidgetConfig for id >' . init('eqId') . '<');
		$widget = JeedomConnectWidget::getWidgets(init('eqId'));

		if ( $widget == '' ) {
			ajax::error('Erreur - pas d\'équipement trouvé');
		}
		else{
			$widgetConf = $widget['widgetJC'] ?? '';
			$configJson = json_decode($widgetConf);

			if ($configJson == null){
				ajax::error('Erreur - pas de configuration pour ce widget');
			}
			else{
				ajax::success($configJson);
			}
		}
	}

	if (init('action') == 'getWidgetConfigAll') {
		log::add('JeedomConnect', 'debug', '-- manage fx ajax getWidgetConfigAll ~~ retrieve config for ALL widgets');
		$widgets = JeedomConnectWidget::getWidgets();

		if ($widgets == '') {
			log::add('JeedomConnect', 'debug', 'no widgets found');
			ajax::error('Erreur - pas d\'équipement trouvé');
		}
		else{
			$result = array();
			foreach ($widgets as $widget) {
				$monWidget = json_decode( $widget['widgetJC'], true) ;
				array_push($result, $monWidget ) ;
			}
			log::add('JeedomConnect', 'debug', 'getWidgetConfigAll ~~ result : ' . json_encode($result) );
			ajax::success($result);

		}
	}


	if (init('action') == 'saveConfig') {
    	$config = init('config');
		$apiKey = init('apiKey');

		$configJson = json_decode($config);
		$eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
		if (!is_object($eqLogic) or $configJson == null) {
			ajax::error('Erreur');
		} else {
      $eqLogic->saveConfig($configJson);
			$eqLogic->setConfiguration('configVersion', $configJson->payload->configVersion);
			$eqLogic->save();			
			ajax::success();
		}
  }

  if (init('action') == 'getConfig') {
    $apiKey = init('apiKey');
    $eqLogic = \eqLogic::byLogicalId($apiKey, 'JeedomConnect');
	$allConfig = (init('all') !== null) && init('all') ;
	$saveGenerated = (init('all') !== null) && init('all') ;
    if (!is_object($eqLogic)) {
		ajax::error('Erreur - no equipment found');
	}
	else {
		//$eqLogic->updateConfig();
		$configJson = $eqLogic->getConfig($allConfig, $saveGenerated);
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
