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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/JeedomConnectWidget.class.php';

class JeedomConnect extends eqLogic {

   /*     * *************************Attributs****************************** */

	public static $_initialConfig = array(
		'type' => 'JEEDOM_CONFIG',
		'formatVersion' => '1.0',
		'idCounter' => 0,
		'payload' => array(
			'configVersion' => 0,
			'tabs' => array(),
			'sections' => array(),
			'rooms' => array(),
			'groups' => array(),
			'widgets' => array()
		)
	);

	public static $_notifConfig = array(
		'idCounter' => 0,
		'channels' => array(
			array(
				'id' => 'default',
				'name' => 'Défaut'
			)
		),
		'notifs' => array(
			array(
				'id' => 'defaultNotif',
				'name' => 'Notification',
				'channel' => 'default',
				'index' => 0
			)
		)
	);

	public static $_resources_dir = __DIR__ . '/../../resources/';
	public static $_data_dir = __DIR__ . '/../../data/';
	public static $_config_dir = __DIR__ . '/../../data/configs/';
	public static $_qr_dir = __DIR__ . '/../../data/qrcodes/';
	public static $_notif_dir = __DIR__ . '/../../data/notifs/';

    /*     * ***********************Methode static*************************** */

    public static function deamon_info() {
        $return = array();
				$return['state'] = count(system::ps('core/php/server.php')) > 0 ? 'ok' : 'nok';
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start($_debug = false) {
				self::deamon_stop();
        log::add('JeedomConnect', 'info', 'Starting daemon');
				$cmd = 'php ' . dirname(__FILE__) . '/../../core/php/server.php';
				$cmd .= ' >> ' . log::getPathToLog('JeedomConnect') . ' 2>&1 &';

				shell_exec($cmd);
        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('JeedomConnect', 'error', 'Unable to start daemon');
            return false;
        }
    }

    public static function deamon_stop() {
        log::add('JeedomConnect', 'info', 'Stopping daemon');
				if (count(system::ps('core/php/server.php')) > 0) {
					system::kill('core/php/server.php', false);
				}
    }

		/*     * *********************Méthodes d'instance************************* */

	public function saveConfig($config) {
		if (!is_dir(self::$_config_dir)) {
			mkdir(self::$_config_dir);
		}
		$config_file = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		try {
			log::add('JeedomConnect', 'debug', 'Saving conf in file : ' . $config_file );
			file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to write file : ' . $e->getMessage());
		}

	}

	public function getConfig($replace = false) {

		if ( $this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == ''){
			log::add('JeedomConnect', 'error', '¤¤¤¤¤ getConfig for ApiKey EMPTY !' );
			return null;
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		$configFile = file_get_contents($config_file_path);
		$jsonConfig = json_decode($configFile, true);

		if ( ! $replace ){
			log::add('JeedomConnect', 'debug', '¤¤¤¤¤ only send the config file without enrichment for apikey ' . $this->getConfiguration('apiKey') );
			return $jsonConfig;
		}

		$roomIdList = array();
		foreach ($jsonConfig['payload']['widgets'] as $key => $widget) {
			$widgetData = JeedomConnectWidget::getWidgets( $widget['id'] );

			if ( empty( $widgetData )  ) {
				// ajax::error('Erreur - pas d\'équipement trouvé');
				log::add('JeedomConnect', 'debug', 'Erreur - pas de widget trouvé avec l\'id ' . $widget['id']);
			}
			else{
				$configJson = $widgetData[0]['widgetJC'] ?? '';
				$widgetConf = json_decode($configJson, true);

				foreach ($widgetConf as $key2 => $value2) {
					$widget[$key2] = $value2;
				}
				$widget['id'] = intval($widget['id']) ;

				if (isset($widget['room'])){
					array_push($roomIdList , $widget['room'] ) ;
				}

				$jsonConfig['payload']['widgets'][$key] = $widget;

			}
		}

		$allRooms = array();
		foreach (array_unique($roomIdList) as $item ) {
			if ($item != 'global') {
				$roomList = $this->getJeedomObject($item);
				array_push($allRooms, $roomList);
			}
		}
		$jsonConfig['payload']['rooms'] = $allRooms ;

		$widgetStringFinal = json_encode( $jsonConfig , JSON_PRETTY_PRINT) ;
		//log::add('testTLE', 'info', ' ¤¤¤¤¤ getConfig - final widget : ' . $widgetStringFinal );

		file_put_contents($config_file_path.'.generated', $widgetStringFinal );

		$jsonConfig = json_decode($widgetStringFinal, true);
		return $jsonConfig;
	}

	public function getJeedomObject($id){

		$obj = jeeObject::byId($id) ;

		if ( !is_object($obj)){
			return null;
		}

		$result = array("id" => intval( $obj->getId() ), "name" => $obj->getName() , "index" => $obj->getPosition() ) ;
		return $result;
	}




	public function updateConfig() {
		$jsonConfig = $this->getConfig();
		$changed = false;
		$hasMenu = count($jsonConfig['payload']['tabs']) > 0 || count($jsonConfig['payload']['sections']) > 0;
		//remove groups with no parent
		if ($hasMenu) {
			foreach ($jsonConfig['payload']['groups'] as $index => $group) {
				if (array_search($group['parentId'], array_column($jsonConfig['payload']['tabs'], 'id')) === false
					&& array_search($group['parentId'], array_column($jsonConfig['payload']['sections'], 'id')) === false) {
						log::add('JeedomConnect', 'info', 'Remove group '. $group['name']);
						$changed = true;
						unset($jsonConfig['payload']['groups'][$index]);
				}
			}
		}

		foreach ($jsonConfig['payload']['widgets'] as $index => $widget) {
			//remove widget with no parent
			if ($hasMenu) {
				if (array_search($widget['parentId'], array_column($jsonConfig['payload']['tabs'], 'id')) === false
					&& array_search($widget['parentId'], array_column($jsonConfig['payload']['sections'], 'id')) === false
					&& array_search($widget['parentId'], array_column($jsonConfig['payload']['groups'], 'id')) === false
			) {
						$changed = true;
						log::add('JeedomConnect', 'info', 'Remove widget '. $widget['name']);
						unset($jsonConfig['payload']['widgets'][$index]);
						continue;
				}
			}

			foreach ($widget as $item => $value) {
				//update rooms to new format
				if ($item == "room" && !is_int($value)) {
					$changed = true;
					$key = false;
					foreach ($jsonConfig['payload']['rooms'] as $key => $val) {
						if ($val['name'] == $value) {
							$jsonConfig['payload']['widgets'][$index][$item] = $val['id'];
						}
					}
				}
				//update cmd to new format
				if (substr_compare($item, 'Info', strlen($item)-4, 4) === 0 || substr_compare($item, 'Action', strlen($item)-6, 6) === 0) {
					if (is_string($value)) {
						$changed = true;
						if ($value == "") {
							unset($jsonConfig['payload']['widgets'][$index][$item]);
						}
						$cmd = cmd::byId($value);
						if (is_object($cmd)) {
							$jsonConfig['payload']['widgets'][$index][$item] = array(
								'category' => 'cmd',
								'id' => $cmd->getId(),
								'type' => $cmd->getType(),
								'subType' => $cmd->getSubType()
							);
							if ($cmd->getConfiguration('minValue') != '') {
								$jsonConfig['payload']['widgets'][$index][$item]['minValue'] = $cmd->getConfiguration('minValue');
							}
							if ($cmd->getConfiguration('maxValue') != '') {
								$jsonConfig['payload']['widgets'][$index][$item]['maxValue'] = $cmd->getConfiguration('maxValue');
							}
							if ($cmd->getUnite() != '') {
								$jsonConfig['payload']['widgets'][$index][$item]['unit'] = $cmd->getUnite();
							}
							if ($cmd->getValue() != '') {
								$jsonConfig['payload']['widgets'][$index][$item]['value'] = $cmd->getValue();
							}
							if (array_key_exists($item . "Secure", $jsonConfig['payload']['widgets'][$index])) {
								$jsonConfig['payload']['widgets'][$index][$item]['secure'] = true;
								unset($jsonConfig['payload']['widgets'][$index][$item . "Secure"]);
							}
							if (array_key_exists($item . "Confirm", $jsonConfig['payload']['widgets'][$index])) {
								$jsonConfig['payload']['widgets'][$index][$item]['confirm'] = true;
								unset($jsonConfig['payload']['widgets'][$index][$item . "Confirm"]);
							}
						}
					}
				}
			}
		}
		if ($changed) {
			$jsonConfig['payload']['widgets'] = array_values($jsonConfig['payload']['widgets']);
			$jsonConfig['payload']['groups'] = array_values($jsonConfig['payload']['groups']);
			log::add('JeedomConnect', 'info', 'Config file updated for '. $this->getName() . ':' . json_encode($jsonConfig));
			$this->saveConfig($jsonConfig);
		}
	}

	public function saveNotifs($config) {
		//update channels
		$data = array(
			"type" => "SET_CHANNELS"
		);
		$data["payload"]["channels"] = $config['channels'];


		if (!is_dir(self::$_notif_dir)) {
			mkdir(self::$_notif_dir);
		}
		$config_file = self::$_notif_dir . $this->getConfiguration('apiKey') . ".json";
		file_put_contents($config_file, json_encode($config));
		$this->sendNotif('defaultNotif', $data);

		//Update cmds
		foreach ($config['notifs'] as $notif) {
			$this->addCmd($notif);
		}
		//Remove unused cmds
		foreach (cmd::byEqLogicId($this->getId()) as $cmd) {
			if (strpos(strtolower($cmd->getLogicalId()), 'notif') !== false ) {
				$remove = true;
				foreach ($config['notifs'] as $notif) {
					if ($cmd->getLogicalId() == $notif['id']) {
						$remove = false;
					}
				}
				if ($remove) {
					log::add('JeedomConnect', 'debug', 'remove cmd '.$cmd->getName());
					$cmd->remove();
				}
			}
		}
	}

	public function addCmd($notif) {
		$cmdNotif = $this->getCmd(null, $notif['id']);
		if (!is_object($cmdNotif)) {
			log::add('JeedomConnect', 'debug', 'add new cmd '.$notif['name']);
			$cmdNotif = new JeedomConnectCmd();
		}
		$cmdNotif->setLogicalId($notif['id']);
		$cmdNotif->setName(__($notif['name'], __FILE__));
		$cmdNotif->setOrder(0);
		$cmdNotif->setEqLogic_id($this->getId());
		$cmdNotif->setDisplay('generic_type', 'GENERIC_ACTION');
		$cmdNotif->setType('action');
		$cmdNotif->setSubType('message');
		$cmdNotif->setIsVisible(1);
		$cmdNotif->save();
	}

	public function getNotifs() {
		$config_file = self::$_notif_dir . $this->getConfiguration('apiKey') . ".json";
		if (!file_exists($config_file)) {
			$this->saveNotifs(self::$_notifConfig);
		}
		$config = file_get_contents($config_file);
		return json_decode($config, true);
	}

	public function generateQRCode() {
		if (!is_dir(self::$_qr_dir)) {
				mkdir(self::$_qr_dir);
		}
		$user = user::byHash($this->getConfiguration('userHash'));
		if ($user == null) {
			$user = user::all()[0];
			$this->setConfiguration('userHash', $user->getHash());
		}

		$connectData = array(
			'useWs' => config::byKey('useWs', 'JeedomConnect', false),
    	'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
      'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
      'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
      'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'),
			'apiKey' => $this->getConfiguration('apiKey'),
			'userHash' => $user->getHash()
		);

		log::add('JeedomConnect', 'debug', 'Generate qrcode with data '.json_encode($connectData));
		$request = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . json_encode($connectData);
		file_put_contents(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png', file_get_contents($request));
	}

	public function registerDevice($id, $name) {
		$this->setConfiguration('deviceId', $id);
		$this->setConfiguration('deviceName', $name);
		$this->save();
	}

	public function removeDevice() {
		$this->setConfiguration('deviceId', '');
		$this->setConfiguration('deviceName', '');
		$this->save();
	}

	public function registerToken($token) {
		$this->setConfiguration('token', $token);
		$this->save();
	}

	public function sendNotif($notifId, $data) {
		if ($this->getConfiguration('token') == null) {
			log::add('JeedomConnect', 'info', "No token defined. Please connect your device first");
			return;
		}
		$postData = array(
			'to' => $this->getConfiguration('token'),
			'priority' => 'high'
		);
		$data["payload"]["time"] = time();
		$postData["data"] = $data;
		foreach ($this->getNotifs()['notifs'] as $notif) {
			if ($notif['id'] == $notifId) {
				unset($notif['name']);
				$postData["data"]["payload"] = array_merge($postData["data"]["payload"], $notif);
			}
		}

		$sendBin = '';
		switch (php_uname("m")) {
    case "x86_64":
        $sendBin = "sendNotif_x64";
        break;
    case "armv7l":
        $sendBin = "sendNotif_arm";
        break;
		case "aarch64":
				$sendBin = "sendNotif_arm64";
				break;
		case "i686":
				$sendBin = "sendNotif_x32";
				break;
		}
		if ($sendBin == '') {
			log::add('JeedomConnect', 'info', "Error while detecting system architecture. " . php_uname("m") . " detected");
			return;
		}
		$binFile =  __DIR__ . "/../../resources/" . $sendBin;
		if (!is_executable($binFlie)) {
			chmod($binFile, 0555);
		}
		$cmd = $binFile . " -data='". json_encode($postData) ."' 2>&1";
		log::add('JeedomConnect', 'info', "Send notification with data ".json_encode($postData["data"]));
		$output = shell_exec($cmd);
		if (is_null($output) || empty($output)) {
			log::add('JeedomConnect', 'info', "Error while sending notification");
			return;
		} else {
			log::add('JeedomConnect', 'debug', "Send output : " . $output);
		}
	}

	public function addGeofenceCmd($geofence) {
		log::add('JeedomConnect', 'debug', "Add or update geofence cmd : " . json_encode($geofence) );

		$geofenceCmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'geofence_' . $geofence['identifier']);
			if (!is_object($geofenceCmd)) {
				$geofenceCmd = new JeedomConnectCmd();
				$geofenceCmd->setLogicalId('geofence_' . $geofence['identifier']);
				$geofenceCmd->setEqLogic_id($this->getId());
				$geofenceCmd->setType('info');
				$geofenceCmd->setSubType('binary');
				$geofenceCmd->setIsVisible(1);
			}
			$geofenceCmd->setName(__($geofence['extras']['name'], __FILE__));
			$geofenceCmd->setConfiguration('latitude', $geofence['latitude']);
			$geofenceCmd->setConfiguration('longitude', $geofence['longitude']);
			$geofenceCmd->setConfiguration('radius', $geofence['radius']);
			$geofenceCmd->save();
	}

	public function removeGeofenceCmd($geofence) {
		log::add('JeedomConnect', 'debug', "Remove geofence cmd : " . json_encode($geofence));

		$geofenceCmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'geofence_' . $geofence['identifier']);
		if(is_object($geofenceCmd)) {
			$geofenceCmd->remove();
		}
	}

	public function setCoordinates($lat, $lgt) {
		$positionCmd = $this->getCmd(null, 'position');
		$this->checkAndUpdateCmd('position', $lat . "," . $lgt);
		$this->setGeofencesByCoordinates($lat, $lgt);
	}

	public function setGeofencesByCoordinates($lat, $lgt) {
		foreach (cmd::byEqLogicId($this->getId()) as $cmd) {
			if (strpos(strtolower($cmd->getLogicalId()), 'geofence') !== false ) {
				$dist = $this->getDistance($lat, $lgt, $cmd->getConfiguration('latitude'), $cmd->getConfiguration('longitude'));
				if ($dist < $cmd->getConfiguration('radius')) {
					if ($cmd->execCmd() != 1) {
						log::add('JeedomConnect', 'debug', "Set 1 for geofence " . $cmd->getName());
						$cmd->event(1);
					}
				} else {
					if ($cmd->execCmd() != 0) {
						log::add('JeedomConnect', 'debug', "Set 0 for geofence " . $cmd->getName());
						$cmd->event(0);
					}
				}
			}
		}
	}

	public function getDistance($lat1, $lon1, $lat2, $lon2) {
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$dist = ($dist * 60 * 1.1515) * 1609.344;
		return floor($dist);
	}



    public function preInsert() {

		if ($this->getConfiguration('apiKey') == '') {
			$this->setConfiguration('apiKey', bin2hex(random_bytes(16)));
			$this->setLogicalId($this->getConfiguration('apiKey'));
			$this->generateQRCode();
		}

    }

    public function postInsert() {

		$this->setIsEnable(1);
		if ($this->getConfiguration('configVersion') == '') {
			$this->setConfiguration('configVersion', 0);
		}
		$this->save();

		$this->saveConfig(self::$_initialConfig);
		$this->saveNotifs(self::$_notifConfig);

    }

    public function preSave()
    {
    }

    public function postSave() {
    }

    public function preUpdate() {

		if ($this->getConfiguration('scenariosEnabled') == '' ) {
			$this->setConfiguration('scenariosEnabled', '1');
			$this->save();
		}

    }

    public function postUpdate() {

			$positionCmd = $this->getCmd(null, 'position');
				if (!is_object($positionCmd)) {
					$positionCmd = new JeedomConnectCmd();
					$positionCmd->setLogicalId('position');
					$positionCmd->setEqLogic_id($this->getId());
					$positionCmd->setType('info');
					$positionCmd->setSubType('string');
					$positionCmd->setIsVisible(1);
				}
				$positionCmd->setName(__('Position', __FILE__));
				$positionCmd->save();
    }

    public function preRemove() {
			unlink(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
			unlink(self::$_config_dir . $this->getConfiguration('apiKey') . ".json");
			unlink(self::$_notif_dir . $this->getConfiguration('apiKey') . ".json");
    }

    public function postRemove()
    {
    }


	/**
	 ************************************************************************
	 ****************** FUNCTION TO UPDATE CONF FILE FORMAT *****************
	 ******************    AND CREATE WIDGET ACCORDINGLY    *****************
	 ************************************************************************
	 */


	public function moveToNewConfig(){
		log::add('JeedomConnect_migration', 'info', 'starting configuration migration for new format - equipement "' . $this->getName() . '"');

		//check if equipment is enable
		if ( $this->getIsEnable() ){

			//get the config file brut
			$configFile = $this->getConfig(false)  ;
			log::add('JeedomConnect_migration', 'info', 'original JSON configFile : ' . json_encode($configFile)  );

			//if the configFile is not defined with the new format
			// ie : exist key formatVersion
			if (! array_key_exists('formatVersion', $configFile) ) {
				$newConfWidget = array() ;

				// create array matching between
				// JC room ID <=> jeedom Object ID
				$existingRooms = array();
				foreach($configFile['payload']['rooms'] as $room){
					if ( array_key_exists('object', $room) ){
						$existingRooms[$room['id']] = $room['object'];
					}
				}
				log::add('JeedomConnect_migration', 'info', 'all rooms/object matching : ' . json_encode($existingRooms) );

				// manage group, and provide new id
				$groupIndex = 999000;
				$existingGroups = array();
				log::add('JeedomConnect_migration', 'info', 'Group objects -- BEFORE : ' . json_encode($configFile['payload']['groups']) );
				foreach($configFile['payload']['groups'] as $key => $group){

					$newGroup = $group;
					$existingGroups[$group['id']] = $groupIndex;
					$newGroup['id'] = $groupIndex;

					$configFile['payload']['groups'][$key] = $newGroup;
					$groupIndex += 1 ;
				}
				log::add('JeedomConnect_migration', 'info', 'Group objects with new Ids-- AFTER : ' . json_encode($configFile['payload']['groups']) );


				$widgetsIncluded = array();
				$widgetsMatching = array();

				// for each widget in config file create the associate widget equipment
				foreach($configFile['payload']['widgets'] as $key => $widget){
					log::add('JeedomConnect_migration', 'info', 'starting migration for widget "' . $widget['name'] . '"' );

					// create config widget with new format
					$newWidget = array();

					// check if parentId is a group one, if so then apply the new group Id
					if ( array_key_exists($widget['parentId'], $existingGroups ) ) {
						$newWidget['parentId'] = $existingGroups[$widget['parentId']] ;
					}
					else{
						$newWidget['parentId'] = $widget['parentId'];
					}
					$newWidget['index'] = $widget['index'];

					// retrieve the img to display for the widget based on the type
					$widgetsConfigJonFile = json_decode(file_get_contents(self::$_resources_dir . 'widgetsConfig.json'), true);
					$imgPath = '';
					foreach ($widgetsConfigJonFile['widgets'] as $config) {
						if ( $config['type'] == $widget['type']){
							$imgPath = 'plugins/JeedomConnect/data/img/'. $config['img'];
							break;
						}
					}
					$newConfWidget['imgPath'] = $imgPath ;

					// attached the widget to the jeedom object
					if (array_key_exists('room', $widget)
							&& ! is_null($widget['room'] )
								&& array_key_exists(intval($widget['room']), $existingRooms ) ) {
						$widget['room'] = $existingRooms[$widget['room']] ;
					}
					else{
						$widget['room'] = $widget['room'] == 'global' ? 'global' : null;
					}

					//generate a random logicalId
					$widgetId = JeedomConnectWidget::incrementIndex();

					unset($widget['parentId']);
					unset($widget['index']);

					$previousId = $widget['id'];
					$widget['id'] = $widgetId ;
					// save json config on a dedicated config var
					$newConfWidget['widgetJC'] = json_encode($widget) ;

					JeedomConnectWidget::saveConfig($newConfWidget, $widgetId) ;

					// retrieve the eqLogic ID
					$newWidget['id'] = intval($widgetId) ;
					$widgetsMatching[$previousId] = $widgetId;

					if ( array_key_exists('widgets', $widget ) ){
						array_push($widgetsIncluded, $widgetId ) ;
					}

					//save the new widget data into the original config array
					$configFile['payload']['widgets'][$key] = $newWidget;

					log::add('JeedomConnect_migration', 'info', 'conf saved [DB] for widget ' . json_encode($widget) );
					log::add('JeedomConnect_migration', 'info', 'conf saved [file] for widget "' . json_encode($newWidget) . '"' );

				}

				// for each widget which includes other widgets (group, favourite,..)
				// we need to update the widget ID
				log::add('JeedomConnect_migration', 'info', 'checking widget included into other widgets');
				foreach($widgetsIncluded as $widget){
					$widgetJC = JeedomConnectWidget::getConfiguration($widget, 'widgetJC');
					$conf = json_decode($widgetJC, true );
					log::add('JeedomConnect_migration', 'info', 'working on widget "' .$conf['name'] . '" [id:' . $conf['id'] .']' );
					foreach($conf['widgets'] as $index => $obj){
						$newObj = array();
						foreach ($obj as $key => $value) {
							if ( $key == 'id'){
								$newObj['id'] = $widgetsMatching[$value];
								log::add('JeedomConnect_migration', 'info', 'replacing widget child id "'.$value . '" with new Id "'. $widgetsMatching[$value] .'"');
							}
							else{
								$newObj[$key] = $value;
							}
						}
						$conf['widgets'][$index] = $newObj ;
					}

					JeedomConnectWidget::setConfiguration($widget, 'widgetJC', json_encode($conf) );
				}

				// review rooms info
				// sort array by index in order to recreate a good index array
				usort($configFile['payload']['rooms'], function($a, $b) {return strcmp($a['index'], $b['index']);});
				$indexRoom = 0;
				$indexRoomToRemove = array();
				$newRoomsArray = array();
				log::add('JeedomConnect_migration', 'info', 'updating rooms ');
				foreach($configFile['payload']['rooms'] as $key => $room){
					if ( array_key_exists('object', $room )
							&& ! is_null($room['object']) ) {

						log::add('JeedomConnect_migration', 'info', 'working on room "' .$room['name']. '"' );

						$roomObject = jeeObject::byId($room['object']);

						if ( is_object($roomObject) ){
							// set the name with the Jeedom One
							$currentRoom['name'] = $roomObject->getName() ;
						}
						elseif ($room['name'] == 'global') {
							// do nothing
							$currentRoom['name'] = $room['name'] ;
						}
						else{
							// if object doesnt exist in jeedom, we remove it
							log::add('JeedomConnect_migration', 'info', 'Room '.$room['name'].' is not migrated as it is not attached to an existing jeedom object [objectId incorrect]' ) ;
							continue;
						}

						// set the main id to the jeedom object id
						$currentRoom['id'] = $room['object'];

						$currentRoom['index'] = $indexRoom;

						log::add('JeedomConnect_migration', 'info', 'new info -- name : "' .$currentRoom['name']. '"  -- id : ' . $currentRoom['id'] );

						//save the new widget data into the original config array
						array_push($newRoomsArray, $currentRoom);

						$indexRoom ++;
					}
					else{
						log::add('JeedomConnect_migration', 'info', 'Room "'.$room['name'].'" is not migrated as it is not attached to an existing jeedom object' ) ;
					}
				}

				$configFile['payload']['rooms'] = $newRoomsArray ;

				//add info about new format in file
				$configFile = array_merge( array_slice( $configFile, 0, 1 ), array('formatVersion' => '1.0'), array_slice( $configFile, 1 ) );
				log::add('JeedomConnect_migration', 'info', 'final config file : ' . json_encode($configFile)  );

				// make a backup file
				$originalFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json' ;
				$backupFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json.bkp' ;
				copy($originalFile, $backupFile );
				log::add('JeedomConnect_migration', 'info', 'backup file created -- ' . $backupFile) ;

				// save the file
				file_put_contents($originalFile, json_encode($configFile , JSON_PRETTY_PRINT) );
				log::add('JeedomConnect_migration', 'info', 'new configuration file saved ') ;
			}
			else{
				log::add('JeedomConnect_migration', 'info', 'Configuration file already into new format');
			}
		}
		else{
			log::add('JeedomConnect_migration', 'info', 'configuration for equipement "'.$this->getName().'" not migrated because equipement disabled');
		}
		return;
	}

}




class JeedomConnectCmd extends cmd {

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
		if ($this->getType() != 'action') {
			return;
		}
		$eqLogic = $this->getEqLogic();
		if (strpos(strtolower($this->getLogicalId()), 'notif') !== false) {
			log::add('JeedomConnect', 'info', json_encode($_options));
			$data = array(
				'type' => 'DISPLAY_NOTIF',
				'payload' => array(
					'cmdId' => $this->getId(),
					'title' => str_replace("'", "&#039;", $_options['title']),
					'message' => str_replace("'", "&#039;", $_options['message']),
					'answer' => $_options['answer'],
					'timeout' => $_options['timeout']
				)
			);
			if (isset($_options["files"])) {
				$files = array();
				foreach ($_options["files"] as $file) {
					array_push($files, realpath($file));
				}
				$data['payload']['files'] = $files;

      }
			$eqLogic->sendNotif($this->getLogicalId(), $data);
		}
	}

}
