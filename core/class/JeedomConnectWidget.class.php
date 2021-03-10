<?php

/* * ***************************Includes********************************* */
// require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class JeedomConnectWidget extends config {

	public static $_plugin_id = 'JeedomConnect' ;

	public static function getMaxIndex(){

		return config::byKey('index::max', self::$_plugin_id) ?: '0' ;

	}

	public static function incrementIndex(){

		log::add(self::$_plugin_id, 'debug', 'increment widget index ');
		$current = config::byKey('index::max', self::$_plugin_id  ) ?: '0' ;
		log::add(self::$_plugin_id, 'debug', 'current index : ' . $current );

		$next = intval($current) +1 ;
		config::save('index::max', strval($next) , self::$_plugin_id ) ;
		log::add(self::$_plugin_id, 'debug', 'incrementIndex done' );
		return $next;

	}

	public static function setConfiguration($_widgetId, $_key, $_value){

		$currentConf = self::getConfiguration($_widgetId);
		log::add(self::$_plugin_id, 'debug', ' ## current conf ' . json_encode($currentConf) );
		$conf = utils::setJsonAttr($currentConf, $_key, $_value);
		log::add(self::$_plugin_id, 'debug', ' ## conf saved ' . json_encode($conf) );
		return self::saveConfig($conf, $_widgetId);

	}

	public static function getConfiguration($_widgetId, $_key = '', $_default = ''){

		$conf = self::byKey('widget::'.$_widgetId, self::$_plugin_id ) ;
		// log::add(self::$_plugin_id, 'info', ' ##  getConfiguration -- id '. $_widgetId. ' = retrieved : ' . json_encode($conf) ) ;
		return utils::getJsonAttr($conf, $_key, $_default);

	}

	public static function getJsonData($_data, $_key = '', $_default = ''){

		// log::add(self::$_plugin_id, 'info', ' ##  getJsonData  -- data received => ' . json_encode($_data) );
		$conf = json_encode($_data) ;
		return utils::getJsonAttr( $conf , $_key, $_default);

	}

	public static function getAllConfigurations(){

		$result = array();
		foreach( config::searchKey('widget', self::$_plugin_id )  as $config){
			$newConf['id'] = $config['key'];
			$newConf['conf'] = $config['value'];

			array_push($result, $newConf);
		}
		return $result;
	}


	public static function getWidgets($_id = '' ){

		if ( $_id === '' ){
			log::add(self::$_plugin_id, 'debug', 'getWidgets for all widget');
			$widgets = JeedomConnectWidget::getAllConfigurations();
		}
		else{
			$widgets = array();
			$tmp = JeedomConnectWidget::getConfiguration($_id, '', null)  ;
			if (! empty($tmp)) {
				//log::add(self::$_plugin_id, 'debug', ' tmp result for '.$_id. '>' . json_encode($tmp) . '<' );
				$tmp['conf'] = $tmp ;
				array_push($widgets, $tmp);
			}
		}

		$widgetArray = array();
		if ( ! empty($widgets) ){
			foreach ($widgets as $widget) {
				$widgetItem = array() ;

				$widgetItem['img'] = $widget['conf']['imgPath'] ?: plugin::byId(self::$_plugin_id)->getPathImgIcon() ;

				$widgetJC = json_decode($widget['conf']['widgetJC'], true);
				$widgetItem['widgetJC'] = $widget['conf']['widgetJC'] ?? '';
				$widgetItem['enable'] = $widgetJC['enable'];
				$widgetItem['name'] = $widgetJC['name'] ?? 'inconnu';
				$widgetItem['type'] = $widgetJC['type'] ?? 'none';
				$widgetItem['roomId'] = $widgetJC['room'] ?? '' ;
				$widgetRoomObjet = jeeObject::byId($widgetItem['roomId']) ;
				$widgetItem['roomName'] = (! is_null($widgetRoomObjet)) ? ( $widgetItem['roomId'] == 'global' ? 'Global' : $widgetRoomObjet->getName() ) : 'Aucun';
				$widgetItem['id'] = $widgetJC['id'] ?? 'none' ;

				array_push($widgetArray, $widgetItem);
			}

			$roomName  = array_column($widgetArray, 'roomName');
			$widgetName = array_column($widgetArray, 'name');

			array_multisort($roomName, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);

			//log::add(self::$_plugin_id, 'debug', ' final result sent >' . json_encode($widgetArray) );
		}
		return $widgetArray;

	}

	public static function saveConfig($conf, $widgetId = null){

		$cpl = '';
		if ( is_null($widgetId)){
			$widgetId = self::incrementIndex();
			$cpl = ' [new creation]';
		}

		log::add(self::$_plugin_id, 'debug', 'saveConfiguration details received for id : ' . $widgetId . $cpl . ' - conf : ' . json_encode($conf) );
		config::save('widget::'.$widgetId, $conf , self::$_plugin_id ) ;
		log::add(self::$_plugin_id, 'debug', 'saveConfiguration done' );
		return $widgetId;

	}

	public static function removeWidget($idToRemove=''){
		if ( ! $idToRemove ){
			log::add('JeedomConnect', 'warning', 'Removing widget(s) -- no data received -- abort');
			return;
		}

		if ( is_array($idToRemove)){
			log::add('JeedomConnect', 'info', 'Removing widget(s) -- data received : (array) ' . json_encode($idToRemove) );
			$arrayIdToRemove= $idToRemove ;
		}
		else{
			log::add('JeedomConnect', 'info', 'Removing widget -- data received : (int) ' . json_encode($idToRemove) );
			$arrayIdToRemove= array( $idToRemove );
		}


		// remove the widget ID inside json file config of each JC equipement
		foreach (JeedomConnect::byType('JeedomConnect') as $eqLogic) {
			$apiKey = $eqLogic->getConfiguration('apiKey');
			if ( $apiKey !=  ''){
				$eqLogic->removeWidgetConf($arrayIdToRemove);
			}
		}

		// remove the widget ID inside widgets of a widget (favourite, group, ...)
		$allWidgets = self::getAllConfigurations() ;
		log::add('JeedomConnect', 'info', 'all widget : ' . json_encode($allWidgets) );
		foreach ($allWidgets as $widget) {
			$hasChanged = false;
			$conf = json_decode($widget['conf']['widgetJC'], true) ;
			
			if ( ! array_key_exists('widgets', $conf ) ){
				continue;
			}

			foreach($conf['widgets'] as $index => $obj){
				
				if ( in_array( $obj['id'] ,  $arrayIdToRemove ) ){
					log::add('JeedomConnect', 'info', 'removing obj id : ' .  $obj['id'] . ' at index ' . $index . ' for parent ' .$widget['id'] );
					unset($conf['widgets'][$index]);
					$hasChanged = true;
				}
				
			}
			
			$conf['widgets'] = array_values($conf['widgets']);
			if ($hasChanged) self::setConfiguration(str_replace('widget::','',$widget['id']), 'widgetJC', json_encode($conf) );
			
		}
		
		foreach ($arrayIdToRemove as $idToRemove ) {
			// finally remove the widget config itself
			log::add(self::$_plugin_id, 'debug', 'removing widget id : ' . $idToRemove ) ;
			self::remove('widget::'.$idToRemove, self::$_plugin_id);	
		}
		
		return; 

	}

	public static function duplicateWidget($widgetId){

		log::add(self::$_plugin_id, 'debug', 'duplicating widget id : ' . $widgetId ) ;
		$configInit = self::getConfiguration($widgetId);

		$config = json_decode($configInit, true);

		$newId = self::incrementIndex();
		$config['widgetJC']['id'] = $newId ;

		self::saveConfig(json_encode($config), $newId);
		return $newId;

	}




}
