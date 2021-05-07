<?php

/* * ***************************Includes********************************* */
// require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class JeedomConnectActions extends config {

	public static $_plugin_id = 'JeedomConnect' ;

	public static function incrementIndex(){
		$current = config::byKey('actionIndex::max', self::$_plugin_id  ) ?: '0' ;

		$next = intval($current) +1 ;
		config::save('actionIndex::max', strval($next) , self::$_plugin_id ) ;
		return $next;
	}


	public static function addAction($action, $apiKey){
		$newId = self::incrementIndex();
		$result = array(
			'apiKey' => $apiKey,
			'payload' => $action
		);
		//log::add(self::$_plugin_id, 'debug', 'add actions' . json_encode($result) ) ;
		config::save('action::'.$newId, json_encode($result) , self::$_plugin_id ) ;
	}


	public static function getAllAction($apiKey){
		$result = array();
		foreach( config::searchKey('action::', self::$_plugin_id )  as $action){
			if ($action['value']['apiKey'] == $apiKey) {
				array_push($result, $action['value']['payload']);
			}			
		}
		return $result;
	}

	public static function removeAllAction($apiKey) {
		log::add(self::$_plugin_id, 'debug', 'removing actions' ) ;
		foreach( config::searchKey('action::', self::$_plugin_id )  as $action){
			$value = json_decode($action['value'], true);
			if ($action['value']['apiKey'] == $apiKey) {
				self::remove($action['key'], self::$_plugin_id);	
			}
		}
	}

}
