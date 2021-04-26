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
			'summaries' => array(),
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
	public static $_plugin_info_dir = __DIR__ . '/../../plugin_info/';
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


	public static function backup(){
		JeedomConnectWidget::exportWidgetConf();
	}


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

	public function getConfig($replace = false, $saveGenerated = false) {

		if ( $this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == ''){
			log::add('JeedomConnect', 'error', '¤¤¤¤¤ getConfig for ApiKey EMPTY !' );
			return null;
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		if (! file_exists($config_file_path)){
			log::add('JeedomConnect', 'warning', 'file ' . $config_file_path . ' does not exist' );
			return null;
		}
		$configFile = file_get_contents($config_file_path);
		$jsonConfig = json_decode($configFile, true);

		if ( ! $replace ){
			log::add('JeedomConnect', 'debug', '¤¤¤¤¤ only send the config file without enrichment for apikey ' . $this->getConfiguration('apiKey') );
			return $jsonConfig;
		}

		$roomIdList = array();
		$widgetList = array();
		$widgetIdInGroup = array();
		$maxIndex = 0;
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
				array_push($widgetList, $widget['id'] );

				if (isset($widget['widgets'])){
					foreach ($widget['widgets'] as $itemGroup) {
						array_push($widgetIdInGroup, $itemGroup['id'] );
					}
				}
				
				if (isset($widget['moreWidgets'])){
					foreach ($widget['moreWidgets'] as $itemGroup) {
						array_push($widgetIdInGroup, $itemGroup['id'] );
					}
				}

				if (isset($widget['room'])){
					array_push($roomIdList , $widget['room'] ) ;
				}


				if ( $widget['type'] == 'choices-list'){
					$choices = self::getChoiceData($widget['listAction']['id'] ) ;
					$widget['choices'] = $choices;
				}

				$jsonConfig['payload']['widgets'][$key] = $widget;
				$maxIndex = $key;

			}
		}

		while ( count($widgetIdInGroup) > 0 ) {
			$moreWidget = array();
			// remove duplicate id
			$widgetIdInGroup = array_unique($widgetIdInGroup);
			// check if for each widgetId found in a group, the widget itself has his configuration
			// already detailed in the config file, if not, then add it
			foreach ($widgetIdInGroup as $item) {
				if ( ! in_array($item, $widgetList)){
					// log::add('JeedomConnect', 'debug', 'the widget ['. $item . '] does not exist in the config file. Adding it.');
					$newWidgetData = JeedomConnectWidget::getWidgets( $item );

					if ( empty( $newWidgetData )  ) {
						// ajax::error('Erreur - pas d\'équipement trouvé');
					}
					else{
						$newWidgetJC = $newWidgetData[0]['widgetJC'] ?? '';
						$newWidgetConf = json_decode($newWidgetJC, true);

						$newWidgetConf['id'] = intval($newWidgetConf['id']) ;
						$newWidgetConf['parentId'] = null ;
						$newWidgetConf['index'] = 999999999 ;

						if (isset($newWidgetConf['room'])){
							array_push($roomIdList , $newWidgetConf['room'] ) ;
						}

						if (isset($newWidgetConf['widgets'])){
							foreach ($newWidgetConf['widgets'] as $itemWidget) {
								array_push($moreWidget, intval($itemWidget['id']) );
							}
						}

						$maxIndex = $maxIndex +1;
						$jsonConfig['payload']['widgets'][$maxIndex] = $newWidgetConf;

						array_push($widgetList, $newWidgetConf['id'] );
					}

				}
			}

			if ( count($moreWidget) >0 ) log::add('JeedomConnect', 'debug', 'more widgets children to add -- ' . json_encode($moreWidget) ) ;
			$widgetIdInGroup = $moreWidget ;

		}


		//add equipement password
		$pwd = $this->getConfiguration('pwdAction' , null) ;
		$jsonConfig['payload']['password'] = $pwd ;

		//custom path
		$jsonConfig['payload']['userImgPath'] = config::byKey('userImgPath',   'JeedomConnect') ;

		//add summary details
		$objSummary = config::byKey('object:summary') ;
		$allSummaries = $jsonConfig['payload']['summaries'] ?? [] ;
		foreach ( $allSummaries as $index => $summary) {
			if ( array_key_exists($summary['key'] , $objSummary) ){
				$newSummary = $summary;
				$newSummary['calcul']=$objSummary[$summary['key']]['calcul'];
				$newSummary['unit']=$objSummary[$summary['key']]['unit'];
				$newSummary['count']=$objSummary[$summary['key']]['count'];
				$newSummary['allowDisplayZero']=  $objSummary[$summary['key']]['allowDisplayZero'] ;
				$newSummary['ignoreIfCmdOlderThan']=  $objSummary[$summary['key']]['ignoreIfCmdOlderThan'];

				$jsonConfig['payload']['summaries'][$index] = $newSummary;
			}
		}

		if ( $saveGenerated ) {
			cache::set('jcConfig' . $this->getConfiguration('apiKey'), json_encode( $jsonConfig));
			file_put_contents($config_file_path.'.generated', json_encode( $jsonConfig , JSON_PRETTY_PRINT) );
		}

		// $jsonConfig = json_decode($widgetStringFinal, true);
		return $jsonConfig;
	}


	public function getGeneratedConfigFile() {

		if ( $this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == ''){
			log::add('JeedomConnect', 'error', '¤¤¤¤¤ getConfig for ApiKey EMPTY !' );
			return null;
		}

		$cacheConf = cache::byKey('jcConfig' . $this->getConfiguration('apiKey'))->getValue();
		if ($cacheConf != '') {
			return json_decode($cacheConf, true);
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json.generated";
		if (! file_exists($config_file_path)){
			log::add('JeedomConnect', 'warning', 'file ' . $config_file_path . ' does not exist' );
			return null;
		}

		$configFile = file_get_contents($config_file_path);
		$jsonConfig = json_decode($configFile, true);
		return $jsonConfig;

	}

	public static function getChoiceData($cmdId){
		$choice = array();

		$cmd = cmd::byId($cmdId);

		if (! is_object($cmd)){
			log::add('JeedomConnect', 'warning', $cmdId. ' is not a valid cmd Id');
			return $choice;
		}

		$cmdConfig = $cmd->getConfiguration('listValue');

		if ($cmdConfig !=  '') {
			log::add('JeedomConnect', 'debug', 'value of listValue ' . json_encode($cmdConfig));

			foreach (explode(';', $cmdConfig) as $list) {
				$selectData = explode('|', $list);

				if ( count($selectData) == 1 ) {
					$id = $value = $selectData[0] ;
				}
				else{
					$id = $selectData[0] ;
					$value = $selectData[1] ;
				}

				$choice_info = array(
				'id' => $id,
				'value' => $value
				);
				array_push($choice, $choice_info);
			}
		}

		log::add('JeedomConnect', 'debug', 'final choices list => '.json_encode($choice) );
		return $choice;

	}


	public function getWidgetId(){
		$ids = array();

		$conf = self::getConfig(true);

		foreach ($conf['payload']['widgets'] as $item) {
			array_push( $ids, $item['id']);
		}

		log::add('JeedomConnect', 'debug', ' fx  getWidgetId -- result final ' . json_encode($ids) );
		return $ids;

	}

	public function isWidgetIncluded($widgetId){
		
		$ids = $this->getWidgetId();

		if ( in_array( $widgetId, $ids) ){
			return true;
		}
		return false;

	}

	public function getJeedomObject($id){

		$obj = jeeObject::byId($id) ;

		if ( !is_object($obj)){
			return null;
		}

		$result = array("id" => intval( $obj->getId() ), "name" => $obj->getName() ) ;
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
		$user = user::byId($this->getConfiguration('userId'));
		if ($user == null) {
			$user = user::all()[0];
			$this->setConfiguration('userId', $user->getId());
		}

		$connectData = array(
			'useWs' => $this->getConfiguration('useWs', 0),
    		'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
      		'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
      		'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
      		'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'),
			'apiKey' => $this->getConfiguration('apiKey'),
			'userHash' => $user->getHash(),
			'eqName' => $this->getName()
		);

		log::add('JeedomConnect', 'debug', 'Generate qrcode with data '.json_encode($connectData));

		require_once dirname(__FILE__) . '/../php/phpqrcode.php';
		QRcode::png( json_encode($connectData), self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');

		// $request = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . json_encode($connectData);
		// file_put_contents(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png', file_get_contents($request));
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
		if (!is_executable($binFile)) {
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

	public function setCoordinates($lat, $lgt, $alt, $timestamp) {
		$positionCmd = $this->getCmd(null, 'position');
		$info = $lat . "," . $lgt;
		if ($this->getConfiguration('addAltitude', false)) {
			$info += "," . $alt;
		}
		$positionCmd->event($info, date('Y-m-d H:i:s', strtotime($timestamp)));
		$this->setGeofencesByCoordinates($lat, $lgt, $timestamp);
	}

	public function setGeofencesByCoordinates($lat, $lgt, $timestamp) {
		foreach (cmd::byEqLogicId($this->getId()) as $cmd) {
			if (strpos(strtolower($cmd->getLogicalId()), 'geofence') !== false ) {
				$dist = $this->getDistance($lat, $lgt, $cmd->getConfiguration('latitude'), $cmd->getConfiguration('longitude'));
				if ($dist < $cmd->getConfiguration('radius')) {
					if ($cmd->execCmd() != 1) {
						log::add('JeedomConnect', 'debug', "Set 1 for geofence " . $cmd->getName());
						$cmd->event(1, date('Y-m-d H:i:s', strtotime($timestamp)));
					}
				} else {
					if ($cmd->execCmd() != 0) {
						log::add('JeedomConnect', 'debug', "Set 0 for geofence " . $cmd->getName());
						$cmd->event(0, date('Y-m-d H:i:s', strtotime($timestamp)));
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

		if ($this->getConfiguration('pwdChanged') == 'true' ) {
			$confStd = $this->getConfig();
			$configVersion = $confStd['payload']['configVersion'] + 1 ;
			log::add('JeedomConnect', 'debug', ' saving new conf after password changed -- updating configVersion to ' . $configVersion);

			//update configVersion in the file
			$confStd['payload']['configVersion'] =  $configVersion ;
			$this->saveConfig($confStd);

			//update configVersion in the equipment configuration
			$this->setConfiguration('configVersion', $configVersion);
			$this->setConfiguration('pwdChanged',  'false') ;
			$this->save();

			$this->getConfig(true, true) ;

		}

    }

    public function preUpdate() {

		if ($this->getConfiguration('scenariosEnabled') == '' ) {
			$this->setConfiguration('scenariosEnabled', '1');
			$this->save();
		}

    }

    public function postUpdate() {
		// Position format : latitude,longitude,altitude
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

		// Activity values : still, on_foot, running, on_bicycle and in_vehicle
		$activityCmd = $this->getCmd(null, 'activity');
		if (!is_object($activityCmd)) {
			$activityCmd = new JeedomConnectCmd();
			$activityCmd->setLogicalId('activity');
			$activityCmd->setEqLogic_id($this->getId());
			$activityCmd->setType('info');
			$activityCmd->setSubType('string');
			$activityCmd->setIsVisible(1);
		}
		$activityCmd->setName(__('Activité', __FILE__));
		$activityCmd->save();
		
    }

    public function preRemove() {
			unlink(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
			unlink(self::$_config_dir . $this->getConfiguration('apiKey') . ".json");
			unlink(self::$_notif_dir . $this->getConfiguration('apiKey') . ".json");
    }

    public function postRemove()
    {
    }

	public function checkEqAndUpdateConfig($widgetId){

		log::add('JeedomConnect', 'debug', 'Checking if widget '.$widgetId.' exist on equipment "' . $this->getName() . '" ['.$this->getConfiguration('apiKey').']' );

		$conf = $this->getConfig(true);
		$exist = false ;

		if (! $conf ){
			log::add('JeedomConnect', 'debug', 'No config content retrieved');
			return;
		}


		foreach ($conf['payload']['widgets'] as $widget) {
			if ( $widget['id']  == $widgetId  ){
				$exist = true;
				break;
			}
		}

		if ( $exist ){
			$this->generateNewConfigVersion($widgetId);
		}
		else{
			log::add('JeedomConnect', 'debug', $widgetId . ' NOT found in the current equipment');
		}

	}

	public function generateNewConfigVersion($widgetId='widgets'){
			$confStd = $this->getConfig();
			$configVersion = $confStd['payload']['configVersion'] + 1 ;
			log::add('JeedomConnect', 'debug', $widgetId . ' found in the current equipment -- updating configVersion to ' . $configVersion);

			//update configVersion in the file
			$confStd['payload']['configVersion'] =  $configVersion ;
			$this->saveConfig($confStd);

			//update configVersion in the equipment configuration
			$this->setConfiguration('configVersion', $configVersion);
			$this->save();

			log::add('JeedomConnect', 'debug', 'Renewing the version of the widgets configuration');
			$this->getConfig(true, true) ;
			return true;
	}

	public static function getWidgetParam(){
		$widgetsConfigJonFile = json_decode(file_get_contents(self::$_resources_dir . 'widgetsConfig.json'), true);

		$result = array();
		foreach ($widgetsConfigJonFile['widgets'] as $config) {
			$result[$config['type']] = $config['name'];
		}
		return $result;
	}

	public function removeWidgetConf($idToRemoveList){

		$remove = false;

		$conf = $this->getConfig();

		log::add('JeedomConnect', 'debug', 'Removing widget in equipement config file -- ' . json_encode($conf) );
		if ( $conf ){
			foreach ($conf['payload']['widgets'] as $key => $value) {
				if ( in_array( $value['id'] , $idToRemoveList ) ){
					log::add('JeedomConnect', 'debug', 'Removing that widget item config -- ' . json_encode($value));
					unset($conf['payload']['widgets'][$key]);
					$remove = true;
				}
			}

			if ($remove) {
				$conf['payload']['widgets'] = array_values($conf['payload']['widgets']);
				log::add('JeedomConnect', 'info', 'Widget ID '.json_encode($idToRemoveList). ' has been removed on equipement ' . $this->getName() );
				$this->saveConfig($conf);
				$this->generateNewConfigVersion();
				return;
			}
			else{
				log::add('JeedomConnect', 'info', 'Widget ID '.json_encode($idToRemoveList). ' not found in equipement ' . $this->getName() );
			}
		}
		else{
			log::add('JeedomConnect', 'warning', 'No config content retrieved');
		}
		return;

	}

	public function resetConfigFile(){
		log::add('JeedomConnect', 'debug', 'reseting configuration for equipment "' . $this->getName() . '" ['.$this->getConfiguration('apiKey').']');
		self::saveConfig(self::$_initialConfig);
	}



	public static function getPluginInfo(){

		$pluginInfo = json_decode(file_get_contents(self::$_plugin_info_dir . 'version.json'), true);
		$branchInfo = json_decode(file_get_contents(self::$_plugin_info_dir . 'branch.json'), true);

		$result = array_merge($pluginInfo, $branchInfo);

		return $result ;

	}

	public static function displayMessageInfo(){

		$pluginInfo = self::getPluginInfo();

		$apkVersionRequired = $pluginInfo['require'] ;
		$playStoreUrl = htmlentities('<a href="' . $pluginInfo['storeUrl'] . '" target="_blank">Play Store</a>');
		message::add( 'JeedomConnect',  'Ce plugin nécessite d\'utiliser l\'application en version minimum : '.$apkVersionRequired.'. Si nécessaire, pensez à mettre à jour votre application depuis le ' . $playStoreUrl ) ;

		if ( $pluginInfo['typeVersion'] == 'beta'){
			$enrollmentLink = htmlentities('<a href="' . $pluginInfo['enrollment'] . '" target="_blank">en cliquant ici</a>');
			message::add( 'JeedomConnect',  'Si ça n\'est pas déjà fait, pensez à vous inscrire dans le programme beta-testeur de l\'application sur le Store : ' . $enrollmentLink ) ;
		}

	}

	/**
	 ************************************************************************
	 ****************** FUNCTION TO UPDATE CONF FILE FORMAT *****************
	 ******************    AND CREATE WIDGET ACCORDINGLY    *****************
	 ************************************************************************
	 */

	public function moveToNewConfig(){
		log::add('JeedomConnect_migration', 'info', 'starting configuration migration for new format - equipement "' . $this->getName() . '"');

		//get the config file brut
		$configFile = $this->getConfig(false)  ;
		log::add('JeedomConnect_migration', 'info', 'original JSON configFile : ' . json_encode($configFile)  );

		//if the configFile is not defined with the new format
		// ie : exist key formatVersion
		if ($configFile == '' ){
			log::add('JeedomConnect_migration', 'warning', 'no configuration file found');
		}
		elseif( ! array_key_exists('formatVersion', $configFile) ) {
			$newConfWidget = array() ;

			// create array matching between
			// JC room ID <=> jeedom Object ID

			// sort array by index in order to recreate a good index array
			usort($configFile['payload']['rooms'], function($a, $b) {return strcmp($a['index'], $b['index']);});
			$indexRoom = 0;
			$indexRoomToRemove = array();
			$newRoomsArray = array();
			$existingRooms = array();
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

					$existingRooms[$room['id']] = $room['object'];

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
				else if (array_key_exists('room', $widget) && $widget['room'] == 'global') {
					$widget['room'] = 'global' ;
				}
				else{
					unset($widget['room']);
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
					'answer' => $_options['answer'] ?? null,
					'timeout' => $_options['timeout'] ?? null
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
