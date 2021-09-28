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
require_once dirname(__FILE__) . '/JeedomConnectActions.class.php';

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

	public static function backup() {
		JeedomConnectWidget::exportWidgetConf();
	}

	public static function copyConfig($from, $to) {

		$config_file_model = self::$_config_dir . $from . ".json";
		if (!file_exists($config_file_model)) {
			log::add('JeedomConnect', 'warning', 'file ' . $config_file_model . ' does not exist');
			return null;
		}
		$configFile = file_get_contents($config_file_model);

		foreach ($to as $item) {

			if ($item != $from) {

				$config_file_destination = self::$_config_dir . $item . ".json";
				try {
					log::add('JeedomConnect', 'debug', 'Copying config file from ' . $from . ' to ' . $item);
					file_put_contents($config_file_destination, $configFile);

					$eqLogic = eqLogic::byLogicalId($item, 'JeedomConnect');
					if (!is_object($eqLogic)) {
						log::add('JeedomConnect', 'debug', 'no objct found');
						continue;
					}
					$eqLogic->generateNewConfigVersion();
				} catch (Exception $e) {
					log::add('JeedomConnect', 'error', 'Unable to write file : ' . $e->getMessage());
				}
			}
		}

		return true;
	}

	public function cleanCustomData() {
		$apiKey = $this->getConfiguration('apiKey');
		$customData = config::searchKey('customData::' . $apiKey . '::', 'JeedomConnect');

		if (!empty($customData)) {
			$config = $this->getConfig();

			foreach ($customData as $item) {
				$search = array_search($item['value']['widgetId'], array_column($config['payload']['widgets'], 'widgetId'));
				if ($search === false) {
					log::add('JeedomConnect', 'debug', 'removing custom data (not used anymore) : ' . $item['value']['widgetId']);
					config::remove('customData::' . $apiKey . '::' . $item['value']['widgetId'], 'JeedomConnect');
				}
			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function saveConfig($config) {
		if (!is_dir(self::$_config_dir)) {
			mkdir(self::$_config_dir);
		}
		$config_file = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		try {
			log::add('JeedomConnect', 'debug', 'Saving conf in file : ' . $config_file);
			file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to write file : ' . $e->getMessage());
		}
	}

	public function getConfig($replace = false, $saveGenerated = false) {

		if ($this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == '') {
			log::add('JeedomConnect', 'error', '¤¤¤¤¤ getConfig for ApiKey EMPTY !');
			return null;
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		if (!file_exists($config_file_path)) {
			log::add('JeedomConnect', 'warning', 'file ' . $config_file_path . ' does not exist');
			return null;
		}
		$configFile = file_get_contents($config_file_path);
		$jsonConfig = json_decode($configFile, true);

		if (!$replace) {
			log::add('JeedomConnect', 'debug', '¤¤¤¤¤ only send the config file without enrichment for apikey ' . $this->getConfiguration('apiKey'));
			return $jsonConfig;
		}

		$roomIdList = array();
		$widgetList = array();
		$widgetIdInGroup = array();
		$maxIndex = 0;
		foreach ($jsonConfig['payload']['widgets'] as $key => $widget) {
			$widgetData = JeedomConnectWidget::getWidgets($widget['id']);
			$widgetId = $widget['widgetId'];
			if (empty($widgetData)) {
				// ajax::error('Erreur - pas d\'équipement trouvé');
				log::add('JeedomConnect', 'debug', 'Erreur - pas de widget trouvé avec l\'id ' . $widget['id']);
			} else {
				$configJson = $widgetData[0]['widgetJC'] ?? '';
				$widgetConf = json_decode($configJson, true);

				foreach ($widgetConf as $key2 => $value2) {
					$widget[$key2] = $value2;
				}
				$widget['id'] = intval($widget['id']);
				$widget['widgetId'] = intval($widgetId);
				array_push($widgetList, $widget['id']);

				if (isset($widget['widgets'])) {
					foreach ($widget['widgets'] as $itemGroup) {
						array_push($widgetIdInGroup, array('id' => $itemGroup['id'], 'parentId' =>  $widget['widgetId']));
					}
				}

				if (isset($widget['moreWidgets'])) {
					foreach ($widget['moreWidgets'] as $itemGroup) {
						array_push($widgetIdInGroup, array('id' => $itemGroup['id'], 'parentId' =>  $widget['widgetId']));
					}
				}

				if (isset($widget['room'])) {
					array_push($roomIdList, $widget['room']);
				}


				if ($widget['type'] == 'choices-list') {
					$choices = self::getChoiceData($widget['listAction']['id']);
					$widget['choices'] = $choices;
				}

				$jsonConfig['payload']['widgets'][$key] = $widget;
				$maxIndex = $key;
			}
		}

		$customData = $this->getCustomWidget();

		// moreWidgets in customData
		if (!empty($customData)) {
			if (array_key_exists('widgets', $customData)) {
				foreach ($customData['widgets'] as $widgetId => $customWidget) {
					if (isset($customWidget['moreWidgets'])) {
						foreach ($customWidget['moreWidgets'] as $itemGroup) {
							array_push($widgetIdInGroup, array('id' => $itemGroup['id'], 'parentId' =>  $widgetId));
						}
					}
				}
			}
		}

		while (count($widgetIdInGroup) > 0) {
			$moreWidget = array();
			// remove duplicate id
			//$widgetIdInGroup = array_unique($widgetIdInGroup);
			// check if for each widgetId found in a group, the widget itself has his configuration
			// already detailed in the config file, if not, then add it
			foreach ($widgetIdInGroup as $item) {
				if (!in_array($item['id'], $widgetList)) {
					// log::add('JeedomConnect', 'debug', 'the widget ['. $item . '] does not exist in the config file. Adding it.');
					$newWidgetData = JeedomConnectWidget::getWidgets($item['id']);

					if (empty($newWidgetData)) {
						// ajax::error('Erreur - pas d\'équipement trouvé');
					} else {
						$newWidgetJC = $newWidgetData[0]['widgetJC'] ?? '';
						$newWidgetConf = json_decode($newWidgetJC, true);

						$newWidgetConf['id'] = intval($newWidgetConf['id']);
						$newWidgetConf['index'] = 999999999;
						$newWidgetConf['widgetId'] = (intval($item['parentId']) + 1) * 100000 + $newWidgetConf['id'];

						if (isset($newWidgetConf['room'])) {
							array_push($roomIdList, $newWidgetConf['room']);
						}

						if (isset($newWidgetConf['widgets'])) {
							foreach ($newWidgetConf['widgets'] as $itemWidget) {
								array_push($moreWidget, array('id' => $itemWidget['id'], 'parentId' =>  $newWidgetConf['widgetId']));
							}
						}

						if (isset($newWidgetConf['moreWidgets'])) {
							foreach ($newWidgetConf['moreWidgets'] as $itemWidget) {
								array_push($moreWidget, array('id' => $itemWidget['id'], 'parentId' =>  $newWidgetConf['widgetId']));
							}
						}

						if ($newWidgetConf['type'] == 'choices-list') {
							$choices = self::getChoiceData($newWidgetConf['listAction']['id']);
							$newWidgetConf['choices'] = $choices;
						}

						$maxIndex = $maxIndex + 1;
						$jsonConfig['payload']['widgets'][$maxIndex] = $newWidgetConf;

						array_push($widgetList, $newWidgetConf['id']);
					}
				}
			}

			if (count($moreWidget) > 0) log::add('JeedomConnect', 'debug', 'more widgets children to add -- ' . json_encode($moreWidget));
			$widgetIdInGroup = $moreWidget;
		}


		//add equipement password
		$pwd = $this->getConfiguration('pwdAction', null);
		$jsonConfig['payload']['password'] = $pwd;

		//custom path
		$jsonConfig['payload']['userImgPath'] = config::byKey('userImgPath',   'JeedomConnect');

		//add summary details
		$objSummary = config::byKey('object:summary');
		$allSummaries = $jsonConfig['payload']['summaries'] ?? [];
		foreach ($allSummaries as $index => $summary) {
			if (array_key_exists($summary['key'], $objSummary)) {
				$newSummary = $summary;
				$newSummary['calcul'] = $objSummary[$summary['key']]['calcul'];
				$newSummary['unit'] = $objSummary[$summary['key']]['unit'];
				$newSummary['count'] = $objSummary[$summary['key']]['count'];
				$newSummary['allowDisplayZero'] =  $objSummary[$summary['key']]['allowDisplayZero'];
				$newSummary['ignoreIfCmdOlderThan'] =  $objSummary[$summary['key']]['ignoreIfCmdOlderThan'];

				if (array_key_exists('image', $newSummary) && array_key_exists('name', $newSummary['image'])) {
					$newSummary['image']['name'] =  trim($newSummary['image']['name']);
				}

				$jsonConfig['payload']['summaries'][$index] = $newSummary;
			}
		}

		//add customData
		$jsonConfig['payload']['customData'] = $customData == "" ? array('widgets' => array()) : $customData;

		if ($saveGenerated) {
			cache::set('jcConfig' . $this->getConfiguration('apiKey'), json_encode($jsonConfig));
			file_put_contents($config_file_path . '.generated', json_encode($jsonConfig, JSON_PRETTY_PRINT));
		}

		// $jsonConfig = json_decode($widgetStringFinal, true);
		return $jsonConfig;
	}

	public function getCustomWidget() {

		$myKey = 'customData::' . $this->getConfiguration('apiKey') . '::';
		// log::add('JeedomConnect', 'debug', 'looking for config key : ' .  myKey);

		$allCustomData = config::searchKey($myKey, 'JeedomConnect');

		$final = array();
		foreach ($allCustomData as $item) {
			$final[$item['value']['widgetId']] = $item['value'];
		}

		$result = array("widgets" => $final);
		// log::add('JeedomConnect', 'debug', 'resultat : ' .  json_encode($result));
		return $result;
	}

	public function getGeneratedConfigFile() {

		if ($this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == '') {
			log::add('JeedomConnect', 'error', '¤¤¤¤¤ getConfig for ApiKey EMPTY !');
			return null;
		}

		$cacheConf = cache::byKey('jcConfig' . $this->getConfiguration('apiKey'))->getValue();
		if ($cacheConf != '') {
			return json_decode($cacheConf, true);
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json.generated";
		if (!file_exists($config_file_path)) {
			log::add('JeedomConnect', 'warning', 'file ' . $config_file_path . ' does not exist  -- new try to generate one');
			$this->getConfig(true, true);
		}

		try {
			$configFile = file_get_contents($config_file_path);
			$jsonConfig = json_decode($configFile, true);
			return $jsonConfig;
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to generate configuration setting : ' . $e->getMessage());
			return null;
		}
	}

	public static function getChoiceData($cmdId) {
		$choice = array();

		$cmd = cmd::byId($cmdId);

		if (!is_object($cmd)) {
			log::add('JeedomConnect', 'warning', $cmdId . ' is not a valid cmd Id');
			return $choice;
		}

		$cmdConfig = $cmd->getConfiguration('listValue');

		if ($cmdConfig !=  '') {
			log::add('JeedomConnect', 'debug', 'value of listValue ' . json_encode($cmdConfig));

			foreach (explode(';', $cmdConfig) as $list) {
				$selectData = explode('|', $list);

				if (count($selectData) == 1) {
					$id = $value = $selectData[0];
				} else {
					$id = $selectData[0];
					$value = $selectData[1];
				}

				$choice_info = array(
					'id' => $id,
					'value' => $value
				);
				array_push($choice, $choice_info);
			}
		}

		log::add('JeedomConnect', 'debug', 'final choices list => ' . json_encode($choice));
		return $choice;
	}

	public function getWidgetId() {
		$ids = array();

		$conf = $this->getConfig(true);

		foreach ($conf['payload']['widgets'] as $item) {
			array_push($ids, $item['id']);
		}

		log::add('JeedomConnect', 'debug', ' fx  getWidgetId -- result final ' . json_encode($ids));
		return $ids;
	}

	public function isWidgetIncluded($widgetId) {

		$ids = $this->getWidgetId();

		if (in_array($widgetId, $ids)) {
			return true;
		}
		return false;
	}

	public function getJeedomObject($id) {

		$obj = jeeObject::byId($id);

		if (!is_object($obj)) {
			return null;
		}

		$result = array("id" => intval($obj->getId()), "name" => $obj->getName());
		return $result;
	}

	public function updateConfig() {
		$jsonConfig = $this->getConfig();
		$changed = false;
		$idCounter = $jsonConfig['idCounter'];
		//unique widget id
		foreach ($jsonConfig['payload']['widgets'] as $index => $widget) {
			if (!array_key_exists("widgetId", $widget)) {
				$jsonConfig['payload']['widgets'][$index]['widgetId'] = $idCounter;
				$idCounter++;
				$changed = true;
			}
		}
		//move to new background format
		if (array_key_exists("condImages", $jsonConfig['payload']['background'])) {
			$cond = array();
			foreach ($jsonConfig['payload']['background']['condImages'] as $i => $bgCond) {
				array_push($cond, array(
					'index' => $bgCond['index'],
					'condition' => $bgCond['condition'],
					'background' => array(
						'type' => 'image',
						'options' => $bgCond['image']
					)
				));
			}
			$jsonConfig['payload']['background']["condBackgrounds"] = $cond;
			unset($jsonConfig['payload']['background']['condImages']);
			$changed = true;
		}
		if (array_key_exists("image", $jsonConfig['payload']['background'])) {
			$jsonConfig['payload']['background']["background"] = array(
				'type' => 'image',
				'options' => $jsonConfig['payload']['background']['image']
			);
			unset($jsonConfig['payload']['background']['image']);
			$changed = true;
		}

		if ($changed) {
			$jsonConfig['idCounter'] = $idCounter;
			$jsonConfig['payload']['widgets'] = array_values($jsonConfig['payload']['widgets']);
			log::add('JeedomConnect', 'info', 'Config file updated for ' . $this->getName() . ':' . json_encode($jsonConfig));
			$this->saveConfig($jsonConfig);
			$this->generateNewConfigVersion();
		}
	}

	public function saveNotifs($config) {
		//update channels
		$data = array(
			"type" => "SET_NOTIFS_CONFIG",
			"payload" => $config
		);


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
			if (strpos(strtolower($cmd->getLogicalId()), 'notif') !== false) {
				$remove = true;
				foreach ($config['notifs'] as $notif) {
					if ($cmd->getLogicalId() == $notif['id'] || $cmd->getLogicalId() == 'notifall') {
						$remove = false;
						break;
					}
				}
				if ($remove) {
					log::add('JeedomConnect', 'debug', 'remove cmd ' . $cmd->getName());
					$cmd->remove();
				}
			}
		}
	}

	public function addCmd($notif) {
		$cmdNotif = $this->getCmd(null, $notif['id']);
		if (!is_object($cmdNotif)) {
			log::add('JeedomConnect', 'debug', 'add new cmd ' . $notif['name']);
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
		$cmdNotif->setDisplay('title_placeholder', __('Titre/Options', __FILE__));

		$notifAll = $notif['notifall'] ?: false;
		$cmdNotif->setConfiguration('notifAll', $notifAll);

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

		log::add('JeedomConnect', 'debug', 'Generate qrcode with data ' . json_encode($connectData));

		require_once dirname(__FILE__) . '/../php/phpqrcode.php';
		try {
			QRcode::png(json_encode($connectData), self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to generate a QR code : ' . $e->getMessage());
		}
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
			'to' => $this->getConfiguration('token')
		);
		if ($this->getConfiguration('platformOs') == 'android') {
			$postData['priority'] = 'high';
		} else {
			$postData = array_merge($postData, array(
				"mutable_content" => true,
				"content_available" => true,
				"collapse_key" => "type_a",
				"apns" => array(
					"payload" => array(
						"aps" => array(
							"contentAvailable" => true,
						)
					)
				)
			));
		}

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
		$cmd = $binFile . " -data='" . json_encode($postData) . "' 2>&1";
		log::add('JeedomConnect', 'info', "Send notification with data " . json_encode($postData["data"]));
		$output = shell_exec($cmd);
		if (is_null($output) || empty($output)) {
			log::add('JeedomConnect', 'info', "Error while sending notification");
			return;
		} else {
			log::add('JeedomConnect', 'debug', "Send output : " . $output);
		}
	}

	public function addGeofenceCmd($geofence) {
		log::add('JeedomConnect', 'debug', "Add or update geofence cmd : " . json_encode($geofence));

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
		if (is_object($geofenceCmd)) {
			$geofenceCmd->remove();
		}
	}

	public function setCoordinates($lat, $lgt, $alt, $activity, $batteryLevel, $timestamp) {
		$positionCmd = $this->getCmd(null, 'position');
		$info = $lat . "," . $lgt;
		if ($this->getConfiguration('addAltitude', false)) {
			$info .= "," . $alt;
			$info .= "," . $activity;
			$info .= "," . $batteryLevel;
		}
		$positionCmd->event($info, date('Y-m-d H:i:s', $timestamp));
		$this->setGeofencesByCoordinates($lat, $lgt, $timestamp);
	}

	public function setGeofencesByCoordinates($lat, $lgt, $timestamp) {
		foreach (cmd::byEqLogicId($this->getId()) as $cmd) {
			if (strpos(strtolower($cmd->getLogicalId()), 'geofence') !== false) {
				$dist = $this->getDistance($lat, $lgt, $cmd->getConfiguration('latitude'), $cmd->getConfiguration('longitude'));
				if ($dist < $cmd->getConfiguration('radius')) {
					if ($cmd->execCmd() != 1) {
						log::add('JeedomConnect', 'debug', "Set 1 for geofence " . $cmd->getName());
						$cmd->event(1, date('Y-m-d H:i:s', $timestamp));
					}
				} else {
					if ($cmd->execCmd() != 0) {
						log::add('JeedomConnect', 'debug', "Set 0 for geofence " . $cmd->getName());
						$cmd->event(0, date('Y-m-d H:i:s', $timestamp));
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

	public function preSave() {
	}

	public function postSave() {

		if ($this->getConfiguration('pwdChanged') == 'true') {
			$confStd = $this->getConfig();
			$configVersion = $confStd['payload']['configVersion'] + 1;
			log::add('JeedomConnect', 'debug', ' saving new conf after password changed -- updating configVersion to ' . $configVersion);

			//update configVersion in the file
			$confStd['payload']['configVersion'] =  $configVersion;
			$this->saveConfig($confStd);

			//update configVersion in the equipment configuration
			$this->setConfiguration('configVersion', $configVersion);
			$this->setConfiguration('pwdChanged',  'false');
			$this->save();

			$this->getConfig(true, true);
		}

		if ($this->getConfiguration('hideBattery')) {
			// log::add('JeedomConnect', 'debug', 'hiding battery : -2');
			$this->setStatus("battery");
		}
	}

	public function preUpdate() {

		if ($this->getConfiguration('scenariosEnabled') == '') {
			$this->setConfiguration('scenariosEnabled', '1');
			$this->save();
		}
		if ($this->getConfiguration('webviewEnabled') == '') {
			$this->setConfiguration('webviewEnabled', '1');
			$this->save();
		}
		if ($this->getConfiguration('editEnabled') == '') {
			$this->setConfiguration('editEnabled', '1');
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

		$batteryCmd = $this->getCmd(null, 'battery');
		if (!is_object($batteryCmd)) {
			$batteryCmd = new JeedomConnectCmd();
			$batteryCmd->setLogicalId('battery');
			$batteryCmd->setEqLogic_id($this->getId());
			$batteryCmd->setType('info');
			$batteryCmd->setSubType('numeric');
			$batteryCmd->setIsVisible(1);
		}
		$batteryCmd->setName(__('Batterie', __FILE__));
		$batteryCmd->save();

		$goToPageCmd = $this->getCmd(null, 'goToPage');
		if (!is_object($goToPageCmd)) {
			$goToPageCmd = new JeedomConnectCmd();
			$goToPageCmd->setLogicalId('goToPage');
			$goToPageCmd->setEqLogic_id($this->getId());
			$goToPageCmd->setType('action');
			$goToPageCmd->setSubType('message');
			$goToPageCmd->setIsVisible(1);
		}
		$goToPageCmd->setDisplay('title_disable', 1);
		$goToPageCmd->setDisplay('message_placeholder', __('Id page', __FILE__));
		$goToPageCmd->setName(__('Afficher page', __FILE__));
		$goToPageCmd->save();

		$launchAppCmd = $this->getCmd(null, 'launchApp');
		if (!is_object($launchAppCmd)) {
			$launchAppCmd = new JeedomConnectCmd();
			$launchAppCmd->setLogicalId('launchApp');
			$launchAppCmd->setEqLogic_id($this->getId());
			$launchAppCmd->setType('action');
			$launchAppCmd->setSubType('message');
			$launchAppCmd->setIsVisible(1);
		}
		$launchAppCmd->setDisplay('title_disable', 1);
		$launchAppCmd->setDisplay('message_placeholder', __('Nom de l\'application', __FILE__));
		$launchAppCmd->setName(__('Lancer App', __FILE__));
		$launchAppCmd->save();

		$unlinkCmd = $this->getCmd(null, 'unlink');
		if (!is_object($unlinkCmd)) {
			$unlinkCmd = new JeedomConnectCmd();
			$unlinkCmd->setLogicalId('unlink');
			$unlinkCmd->setEqLogic_id($this->getId());
			$unlinkCmd->setType('action');
			$unlinkCmd->setSubType('other');
			$unlinkCmd->setIsVisible(1);
		}
		$unlinkCmd->setName(__('Détacher', __FILE__));
		$unlinkCmd->save();

		$toaster = $this->getCmd(null, 'toaster');
		if (!is_object($toaster)) {
			$toaster = new JeedomConnectCmd();
			$toaster->setLogicalId('toaster');
			$toaster->setEqLogic_id($this->getId());
			$toaster->setType('action');
			$toaster->setSubType('message');
			$toaster->setIsVisible(1);
		}
		$toaster->setName(__('Pop-up', __FILE__));
		$toaster->setDisplay('title_disable', 1);
		$toaster->save();

		$notifall = $this->getCmd(null, 'notifall');
		if (!is_object($notifall)) {
			$notifall = new JeedomConnectCmd();
			$notifall->setLogicalId('notifall');
			$notifall->setEqLogic_id($this->getId());
			$notifall->setType('action');
			$notifall->setSubType('message');
			$notifall->setIsVisible(1);
		}
		$notifall->setName(__('Notifier les appareils JC', __FILE__));
		$notifall->save();

		$update_conf = $this->getCmd(null, 'update_pref_app');
		if (!is_object($update_conf)) {
			$update_conf = new JeedomConnectCmd();
			$update_conf->setLogicalId('update_pref_app');
			$update_conf->setEqLogic_id($this->getId());
			$update_conf->setType('action');
			$update_conf->setSubType('message');
		}
		$update_conf->setIsVisible(0);
		$update_conf->setDisplay('title_with_list', 1);
		$update_conf->setConfiguration('listValue', 'themeColor|Couleur thème;darkMode|Activer mode sombre;tracking|Activer le tracking;updateData|Recharger les données');
		$update_conf->setDisplay('title_placeholder', __('Choix du paramètre', __FILE__));
		$update_conf->setDisplay('title_disable', 1);
		$update_conf->setDisplay('message_placeholder', __('Valeur', __FILE__));
		$update_conf->setName(__('Modifier Préférences Appli', __FILE__));
		$update_conf->save();

		$send_sms = $this->getCmd(null, 'send_sms');
		if (!is_object($send_sms)) {
			$send_sms = new JeedomConnectCmd();
			$send_sms->setLogicalId('send_sms');
			$send_sms->setEqLogic_id($this->getId());
			$send_sms->setType('action');
			$send_sms->setSubType('message');
		}
		$send_sms->setIsVisible(1);
		$send_sms->setDisplay('title_placeholder', __('Numéro/Options', __FILE__));
		$send_sms->setName(__('Envoyer un SMS', __FILE__));
		$send_sms->save();
	}

	public function preRemove() {
		$apiKey = $this->getConfiguration('apiKey');
		unlink(self::$_qr_dir . $apiKey . '.png');
		unlink(self::$_config_dir . $apiKey . ".json");
		unlink(self::$_notif_dir . $apiKey . ".json");
		rmdir(__DIR__ . '/../../data/backup/' . $apiKey);

		$allKey = config::searchKey('customData::' . $this->getConfiguration('apiKey'), 'JeedomConnect');
		foreach ($allKey as $item) {
			config::remove('customData::' . $apiKey . '::' . $item['value']['widgetId'], 'JeedomConnect');
		}
	}

	public function postRemove() {
	}

	public static function checkAllEquimentsAndUpdateConfig($widgetId) {
		foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
			$eqLogic->checkEqAndUpdateConfig($widgetId);
		}
	}

	public function checkEqAndUpdateConfig($widgetId) {

		log::add('JeedomConnect', 'debug', 'Checking if widget ' . $widgetId . ' exist on equipment "' . $this->getName() . '" [' . $this->getConfiguration('apiKey') . ']');

		$conf = $this->getConfig(true);
		$exist = false;

		if (!$conf) {
			log::add('JeedomConnect', 'debug', 'No config content retrieved');
			return;
		}


		foreach ($conf['payload']['widgets'] as $widget) {
			if ($widget['id']  == $widgetId) {
				$exist = true;
				break;
			}
		}

		if ($exist) {
			$this->generateNewConfigVersion($widgetId);
		} else {
			log::add('JeedomConnect', 'debug', $widgetId . ' NOT found in the current equipment');
		}
	}

	public function generateNewConfigVersion($widgetId = 'widgets') {
		$confStd = $this->getConfig();
		$configVersion = $confStd['payload']['configVersion'] + 1;
		log::add('JeedomConnect', 'debug', $widgetId . ' found in the current equipment -- updating configVersion to ' . $configVersion);

		//update configVersion in the file
		$confStd['payload']['configVersion'] =  $configVersion;
		$this->saveConfig($confStd);

		//update configVersion in the equipment configuration
		$this->setConfiguration('configVersion', $configVersion);
		$this->save();

		log::add('JeedomConnect', 'debug', 'Renewing the version of the widgets configuration');
		$this->getConfig(true, true);
		return true;
	}

	public static function getWidgetParam($only_name = true) {
		$widgetsConfigJonFile = json_decode(file_get_contents(self::$_resources_dir . 'widgetsConfig.json'), true);

		$result = array();
		foreach ($widgetsConfigJonFile['widgets'] as $config) {
			$result[$config['type']] = $only_name ? $config['name'] : $config;
		}
		return $result;
	}

	public function moveWidgetIndex($widgetId, $parentId, $currentIndex, $newIndex) {
		try {
			log::add('JeedomConnect', 'debug', 'moveWidgetIndex data : ' . $widgetId . ' - ' . $parentId . ' - ' . $currentIndex . ' - ' . $newIndex);
			$conf = $this->getConfig();
			if ($conf) {
				foreach (array('widgets', 'groups') as $type) {
					log::add('JeedomConnect', 'debug', 'moveWidgetIndex -- dealing with type : ' . $type);
					foreach ($conf['payload'][$type] as $key => $value) {
						// log::add('JeedomConnect', 'debug', 'moveWidgetIndex -- checking widget  : ' . json_encode($conf['payload']['widgets'][$key]) ) ;
						// log::add('JeedomConnect', 'debug', 'moveWidgetIndex -- parentId  : ' . $value['parentId'] ) ;
						// log::add('JeedomConnect', 'debug', 'moveWidgetIndex -- index : ' . $value['index'] ) ;

						if ($value['parentId']  != $parentId) continue;
						if ($value['index']  == $currentIndex) {
							$conf['payload'][$type][$key]['index'] = $newIndex;
							continue;
						}

						if ($currentIndex < $newIndex) {
							if ($value['index']  < $currentIndex || $value['index'] > $newIndex) continue;

							$conf['payload'][$type][$key]['index'] = intval($conf['payload'][$type][$key]['index']) - 1;
						} else {
							if ($value['index']  > $currentIndex || $value['index'] < $newIndex) continue;

							$conf['payload'][$type][$key]['index'] = intval($conf['payload'][$type][$key]['index']) + 1;
						}
					}
				}

				$this->saveConfig($conf);
			}
			$this->generateNewConfigVersion();
			return true;
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to move index : ' . $e->getMessage());
			return false;
		}
	}

	public function removeWidgetConf($idToRemoveList) {

		$remove = false;

		$conf = $this->getConfig();

		log::add('JeedomConnect', 'debug', 'Removing widget in equipement config file -- ' . json_encode($conf));
		if ($conf) {
			foreach ($conf['payload']['widgets'] as $key => $value) {
				if (in_array($value['id'], $idToRemoveList)) {
					log::add('JeedomConnect', 'debug', 'Removing that widget item config -- ' . json_encode($value));
					unset($conf['payload']['widgets'][$key]);
					$remove = true;
				}
			}

			if ($remove) {
				$conf['payload']['widgets'] = array_values($conf['payload']['widgets']);
				log::add('JeedomConnect', 'info', 'Widget ID ' . json_encode($idToRemoveList) . ' has been removed on equipement ' . $this->getName());
				$this->saveConfig($conf);
				$this->cleanCustomData();
				$this->generateNewConfigVersion();
				return;
			} else {
				log::add('JeedomConnect', 'info', 'Widget ID ' . json_encode($idToRemoveList) . ' not found in equipement ' . $this->getName());
			}
		} else {
			log::add('JeedomConnect', 'warning', 'No config content retrieved');
		}
		return;
	}

	public function resetConfigFile() {
		log::add('JeedomConnect', 'debug', 'reseting configuration for equipment "' . $this->getName() . '" [' . $this->getConfiguration('apiKey') . ']');
		$this->saveConfig(self::$_initialConfig);
		$this->cleanCustomData();
	}

	public static function getPluginInfo() {

		$pluginInfo = json_decode(file_get_contents(self::$_plugin_info_dir . 'version.json'), true);
		$branchInfo = json_decode(file_get_contents(self::$_plugin_info_dir . 'branch.json'), true);

		$result = array_merge($pluginInfo, $branchInfo);

		return $result;
	}

	public static function displayMessageInfo() {

		$pluginInfo = self::getPluginInfo();

		$apkVersionRequired = $pluginInfo['require'];
		$playStoreUrl = htmlentities('<a href="' . $pluginInfo['storeUrl'] . '" target="_blank">Play Store</a>');
		message::add('JeedomConnect',  'Ce plugin nécessite d\'utiliser l\'application en version minimum : ' . $apkVersionRequired . '. Si nécessaire, pensez à mettre à jour votre application depuis le ' . $playStoreUrl);

		if ($pluginInfo['typeVersion'] == 'beta') {
			$enrollmentLink = htmlentities('<a href="' . $pluginInfo['enrollment'] . '" target="_blank">en cliquant ici</a>');
			message::add('JeedomConnect',  'Si ça n\'est pas déjà fait, pensez à vous inscrire dans le programme beta-testeur de l\'application sur le Store : ' . $enrollmentLink);
		}
	}

	/**
	 ************************************************************************
	 ****************** FUNCTION TO UPDATE CONF FILE FORMAT *****************
	 ******************    AND CREATE WIDGET ACCORDINGLY    *****************
	 ************************************************************************
	 */

	public function moveToNewConfig() {
		log::add('JeedomConnect_migration', 'info', 'starting configuration migration for new format - equipement "' . $this->getName() . '"');

		//get the config file brut
		$configFile = $this->getConfig(false);
		log::add('JeedomConnect_migration', 'info', 'original JSON configFile : ' . json_encode($configFile));

		//if the configFile is not defined with the new format
		// ie : exist key formatVersion
		if ($configFile == '') {
			log::add('JeedomConnect_migration', 'warning', 'no configuration file found');
		} elseif (!array_key_exists('formatVersion', $configFile)) {
			$newConfWidget = array();

			// create array matching between
			// JC room ID <=> jeedom Object ID

			// sort array by index in order to recreate a good index array
			usort($configFile['payload']['rooms'], function ($a, $b) {
				return strcmp($a['index'], $b['index']);
			});
			$indexRoom = 0;
			$indexRoomToRemove = array();
			$newRoomsArray = array();
			$existingRooms = array();
			log::add('JeedomConnect_migration', 'info', 'updating rooms ');
			foreach ($configFile['payload']['rooms'] as $key => $room) {
				if (
					array_key_exists('object', $room)
					&& !is_null($room['object'])
				) {

					log::add('JeedomConnect_migration', 'info', 'working on room "' . $room['name'] . '"');

					$roomObject = jeeObject::byId($room['object']);

					if (is_object($roomObject)) {
						// set the name with the Jeedom One
						$currentRoom['name'] = $roomObject->getName();
					} elseif ($room['name'] == 'global') {
						// do nothing
						$currentRoom['name'] = $room['name'];
					} else {
						// if object doesnt exist in jeedom, we remove it
						log::add('JeedomConnect_migration', 'info', 'Room ' . $room['name'] . ' is not migrated as it is not attached to an existing jeedom object [objectId incorrect]');
						continue;
					}

					$existingRooms[$room['id']] = $room['object'];

					// set the main id to the jeedom object id
					$currentRoom['id'] = $room['object'];

					$currentRoom['index'] = $indexRoom;

					log::add('JeedomConnect_migration', 'info', 'new info -- name : "' . $currentRoom['name'] . '"  -- id : ' . $currentRoom['id']);

					//save the new widget data into the original config array
					array_push($newRoomsArray, $currentRoom);

					$indexRoom++;
				} else {
					log::add('JeedomConnect_migration', 'info', 'Room "' . $room['name'] . '" is not migrated as it is not attached to an existing jeedom object');
				}
			}

			$configFile['payload']['rooms'] = $newRoomsArray;


			// manage group, and provide new id
			$groupIndex = 999000;
			$existingGroups = array();
			log::add('JeedomConnect_migration', 'info', 'Group objects -- BEFORE : ' . json_encode($configFile['payload']['groups']));
			foreach ($configFile['payload']['groups'] as $key => $group) {

				$newGroup = $group;
				$existingGroups[$group['id']] = $groupIndex;
				$newGroup['id'] = $groupIndex;

				$configFile['payload']['groups'][$key] = $newGroup;
				$groupIndex += 1;
			}
			log::add('JeedomConnect_migration', 'info', 'Group objects with new Ids-- AFTER : ' . json_encode($configFile['payload']['groups']));


			$widgetsIncluded = array();
			$widgetsMatching = array();

			// for each widget in config file create the associate widget equipment
			foreach ($configFile['payload']['widgets'] as $key => $widget) {
				log::add('JeedomConnect_migration', 'info', 'starting migration for widget "' . $widget['name'] . '"');

				// create config widget with new format
				$newWidget = array();

				// check if parentId is a group one, if so then apply the new group Id
				if (array_key_exists($widget['parentId'], $existingGroups)) {
					$newWidget['parentId'] = $existingGroups[$widget['parentId']];
				} else {
					$newWidget['parentId'] = $widget['parentId'];
				}
				$newWidget['index'] = $widget['index'];

				// retrieve the img to display for the widget based on the type
				$widgetsConfigJonFile = json_decode(file_get_contents(self::$_resources_dir . 'widgetsConfig.json'), true);
				$imgPath = '';
				foreach ($widgetsConfigJonFile['widgets'] as $config) {
					if ($config['type'] == $widget['type']) {
						$imgPath = 'plugins/JeedomConnect/data/img/' . $config['img'];
						break;
					}
				}
				$newConfWidget['imgPath'] = $imgPath;

				// attached the widget to the jeedom object
				if (
					array_key_exists('room', $widget)
					&& !is_null($widget['room'])
					&& array_key_exists(intval($widget['room']), $existingRooms)
				) {
					$widget['room'] = $existingRooms[$widget['room']];
				} else if (array_key_exists('room', $widget) && $widget['room'] == 'global') {
					$widget['room'] = 'global';
				} else {
					unset($widget['room']);
				}

				//generate a random logicalId
				$widgetId = JeedomConnectWidget::incrementIndex();

				unset($widget['parentId']);
				unset($widget['index']);

				$previousId = $widget['id'];
				$widget['id'] = $widgetId;
				// save json config on a dedicated config var
				$newConfWidget['widgetJC'] = json_encode($widget);

				JeedomConnectWidget::saveConfig($newConfWidget, $widgetId);

				// retrieve the eqLogic ID
				$newWidget['id'] = intval($widgetId);
				$widgetsMatching[$previousId] = $widgetId;

				if (array_key_exists('widgets', $widget)) {
					array_push($widgetsIncluded, $widgetId);
				}

				//save the new widget data into the original config array
				$configFile['payload']['widgets'][$key] = $newWidget;

				log::add('JeedomConnect_migration', 'info', 'conf saved [DB] for widget ' . json_encode($widget));
				log::add('JeedomConnect_migration', 'info', 'conf saved [file] for widget "' . json_encode($newWidget) . '"');
			}

			// for each widget which includes other widgets (group, favourite,..)
			// we need to update the widget ID
			log::add('JeedomConnect_migration', 'info', 'checking widget included into other widgets');
			foreach ($widgetsIncluded as $widget) {
				$widgetJC = JeedomConnectWidget::getConfiguration($widget, 'widgetJC');
				$conf = json_decode($widgetJC, true);
				log::add('JeedomConnect_migration', 'info', 'working on widget "' . $conf['name'] . '" [id:' . $conf['id'] . ']');
				foreach ($conf['widgets'] as $index => $obj) {
					$newObj = array();
					foreach ($obj as $key => $value) {
						if ($key == 'id') {
							$newObj['id'] = $widgetsMatching[$value];
							log::add('JeedomConnect_migration', 'info', 'replacing widget child id "' . $value . '" with new Id "' . $widgetsMatching[$value] . '"');
						} else {
							$newObj[$key] = $value;
						}
					}
					$conf['widgets'][$index] = $newObj;
				}

				JeedomConnectWidget::setConfiguration($widget, 'widgetJC', json_encode($conf));
			}

			//add info about new format in file
			$configFile = array_merge(array_slice($configFile, 0, 1), array('formatVersion' => '1.0'), array_slice($configFile, 1));
			log::add('JeedomConnect_migration', 'info', 'final config file : ' . json_encode($configFile));

			// make a backup file
			$originalFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json';
			$backupFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json.bkp';
			copy($originalFile, $backupFile);
			log::add('JeedomConnect_migration', 'info', 'backup file created -- ' . $backupFile);

			// save the file
			file_put_contents($originalFile, json_encode($configFile, JSON_PRETTY_PRINT));
			log::add('JeedomConnect_migration', 'info', 'new configuration file saved ');
		} else {
			log::add('JeedomConnect_migration', 'info', 'Configuration file already into new format');
		}

		return;
	}

	public static function migrateCustomData() {

		foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
			$apiKey = $eqLogic->getConfiguration('apiKey');
			// log::add('JeedomConnect_mig', 'debug', 'checking ' . $eqLogic->getName . ' [' . $apiKey . ']');

			$customDataOriginal = config::byKey('customData::' . $apiKey, 'JeedomConnect');
			foreach ($customDataOriginal as $item) {

				if (array_key_exists('widgets', $item['value'])) {
					foreach ($item['value']['widgets'] as $key => $value) {
						// log::add('JeedomConnect', 'debug', 'key : ' . $key . ' -- value :' . json_encode($value));
						config::save('customData::' . $apiKey . '::' . $key, json_encode($value), 'JeedomConnect');
					}
				}
			}
			config::remove('customData::' . $apiKey, 'JeedomConnect');
		}

		config::save('migration::customData', 'done', 'JeedomConnect');
	}

	public static function migrateCondImg() {
		try {
			$hasChangesConf = false;
			//****** UPDATE ALL WIDGETS CONFIG  ******
			foreach (JeedomConnectWidget::getWidgets() as $widget) {
				$currentChange = false;
				$widgetId = $widget['id'];
				$widgetJC = json_decode($widget['widgetJC'], true);

				if (isset($widgetJC['statusImages']) && count($widgetJC['statusImages']) > 0) {

					foreach ($widgetJC['statusImages'] as $key => $value) {
						if (isset($value['operator']) && isset($value['info']) && isset($value['value'])) {
							$hasChangesConf = true; //to make the new generation action [last one]
							$currentChange = true;

							$operator = $value['operator'] == '=' ? '==' : $value['operator'];

							$cond = '#' . $value['info']['id'] . '# ' . $operator . ' ' . $value['value'];
							$value['condition'] = $cond;
							unset($value['info']);
							unset($value['operator']);
							unset($value['value']);
							$widgetJC['statusImages'][$key] = $value;
						}
					}

					if ($currentChange) JeedomConnectWidget::setConfiguration($widgetId, 'widgetJC', json_encode($widgetJC));
				}
			}


			//****** UPDATE ALL EQUIPMENT JSON CONFIG (summary)  ******
			foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
				$hasChangesEq = false;
				$jsonConfig = $eqLogic->getConfig();

				foreach ($jsonConfig['payload']['summaries']  as $main => $summary) {

					if (isset($summary['statusImages']) && count($summary['statusImages']) > 0) {

						foreach ($summary['statusImages'] as $key => $value) {
							$currentChange = false;
							if (isset($value['operator']) && isset($value['info']) && isset($value['value'])) {
								$hasChangesEq = true;  // for the global save action [one before the last]

								$operator = $value['operator'] == '=' ? '==' : $value['operator'];

								$cond = '#' . $value['info']['id'] . '# ' . $operator . ' ' . $value['value'];
								$value['condition'] = $cond;
								unset($value['info']);
								unset($value['operator']);
								unset($value['value']);
								$summary['statusImages'][$key] = $value;
								$currentChange = true;
							}
						}

						if ($currentChange) $jsonConfig['payload']['summaries'][$main] = $summary;
					}
				}

				if ($hasChangesEq || $hasChangesConf) {
					$eqLogic->saveConfig($jsonConfig);
					$eqLogic->generateNewConfigVersion();
				}
			}

			config::save('migration::imgCond', 'done', 'JeedomConnect');
		} catch (Exception $e) {
			log::add('JeedomConnect', 'error', 'Unable to migrate Img Condition : ' . $e->getMessage());
		}
	}

	public function isConnected() {
		$url = config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external'));
		if ($this->getConfiguration('useWs', 0) == 0 && (strpos($url, 'jeedom.com') !== false || strpos($url, 'eu.jeedom.link')) !== false) {
			return time() - $this->getConfiguration('lastSeen', 0) < 3;
		} else {
			return $this->getConfiguration('appState', 0) == 'active';
		}
	}

	/******************************************************************************
	 * ************** FUNCTIONS TO RETRIEVE BATTERY DETAILS
	 * ****************************************************************************
	 */

	public static function getBatteryAllEquipements() {
		$list = array();
		foreach (eqLogic::all() as $eqLogic) {
			$battery_type = str_replace(array('(', ')'), array('', ''), $eqLogic->getConfiguration('battery_type', ''));
			if ($eqLogic->getIsEnable() && $eqLogic->getStatus('battery', -2) != -2) {
				array_push($list, self::getBatteryDetails($eqLogic));
			}
		}

		return $list;
	}

	public static function getBatteryDetails($eqLogic) {
		// $eqLogic = eqLogic::byId($eqLogicId);
		$result = array();
		$level = 'good';
		$batteryType = $eqLogic->getConfiguration('battery_type', '');
		$batteryTime = $eqLogic->getConfiguration('batterytime', 'NA');
		$batterySince = '';
		if ($batteryTime != 'NA') {
			$batterySince = round((strtotime(date("Y-m-d")) - strtotime(date("Y-m-d", strtotime($batteryTime)))) / 86400, 1);
		}

		$plugin = ucfirst($eqLogic->getEqType_name());

		$object_name = 'Aucun';
		$object_id = null;
		if (is_object($eqLogic->getObject())) {
			$object_name = $eqLogic->getObject()->getName();
			$object_id = $eqLogic->getObject()->getId();
		}

		if ($eqLogic->getStatus('battery') <= $eqLogic->getConfiguration('battery_danger_threshold', config::byKey('battery::danger'))) {
			$level = 'critical';
		} else if ($eqLogic->getStatus('battery') <= $eqLogic->getConfiguration('battery_warning_threshold', config::byKey('battery::warning'))) {
			$level = 'warning';
		}

		$result['eqName'] = $eqLogic->getName();
		$result['roomName'] = $object_name;
		$result['roomId'] = $object_id;
		$result['plugin'] = $plugin;

		$result['level'] = $level;

		$result['battery'] = $eqLogic->getStatus('battery', -2);
		$result['batteryType'] = $batteryType;
		$result['lastUpdate'] =  date("d/m/Y H:i:s", strtotime($eqLogic->getStatus('batteryDatetime', 'inconnue')));

		$result['lastReplace'] = ($batteryTime != 'NA') ? $batterySince : '';

		return $result;
	}

	public static function getCmdForAllNotif() {
		$result = array();
		foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
			// log::add('JeedomConnect', 'debug', '**** checking eqLogic : ' . $eqLogic->getName());
			if (!$eqLogic->getIsEnable()) continue;

			foreach ($eqLogic->getCmd() as $cmd) {
				// log::add('JeedomConnect', 'debug', '    | checking cmd : ' . $cmd->getName());
				if ($cmd->getLogicalId() != 'notifall' && strpos(strtolower($cmd->getLogicalId()), 'notif') !== false) {
					if ($cmd->getConfiguration('notifAll', false)) {
						// log::add('JeedomConnect', 'debug', '    ++ adding cmd : ' . $cmd->getId());
						$result[] = $cmd->getId();
					}
				}
			}
		}

		return $result;
	}

	/******************************************************************************
	 * ************** FUNCTIONS TO RETRIEVE HEALTH DETAILS
	 * **************       FOR JEEDOM and PLUGINS
	 * ****************************************************************************
	 */

	public static function getHealthDetails($apiKey) {
		$allPluginsData = array();
		$jeedomData = array();

		// get the number of update availables
		$nb = update::nbNeedUpdate();

		// CUSTOM FX to disable health details -- not available now on screen -- personal use :) -- tomtom
		$sendHealth = config::byKey('sendHealth', 'JeedomConnect', 'true');

		if ($sendHealth == 'true') {

			foreach (plugin::listPlugin(true) as $plugin) {

				if ($plugin->getHasDependency() == 1 || $plugin->getHasOwnDeamon() == 1 || method_exists($plugin->getId(), 'health')) {
					$plugin_id = $plugin->getId();

					$asNok = 0;
					$asPending = 0;

					$portInfo = null;
					if (config::byKey('port', $plugin->getId()) != '') {
						$portInfo = ucfirst(config::byKey('port', $plugin->getId()));
					}

					$dependencyInfo = null;
					if ($plugin->getHasDependency() == 1) {
						try {
							$dependancy_info = $plugin->dependancy_info();
							switch ($dependancy_info['state']) {
								case 'ok':
									$dependencyInfo = 'OK';
									break;
								case 'in_progress':
									$dependencyInfo = 'En cours';
									$asPending += 1;
									break;
								default:
									$dependencyInfo = 'KO';
									$asNok += 1;
									break;
							}
						} catch (Exception $e) {
							log::add('JeedomConnect', 'warning', 'HEALTH -- issue while getting dependancy_info -- ' . $e->getMessage());
						}
					}

					$daemonData = array();
					if ($plugin->getHasOwnDeamon() == 1) {
						try {

							$daemonData['setup'] = array();
							$daemon_info = $plugin->deamon_info();

							$daemonData['setup']['mode'] = $daemon_info['auto'] ? 'auto' : 'manuel';

							$daemonData['setup']['message'] = null;

							switch ($daemon_info['launchable']) {
								case 'ok':
									$daemonData['setup']['status'] = 'OK';
									break;
								case 'nok':
									if ($daemon_info['auto'] != 1) {
										$daemonData['setup']['status'] = 'Désactivé';
									} else {
										$daemonData['setup']['status'] = 'KO';
										$daemonData['setup']['message'] =  $daemon_info['launchable_message'];
										$asNok += 1;
									}
									break;
							}

							$daemonData['last_launch'] = $daemon_info['last_launch'];
							switch ($daemon_info['state']) {
								case 'ok':
									$daemonData['state'] = 'OK';
									break;
								case 'nok':
									if ($daemon_info['auto'] != 1) {
										$daemonData['state'] = 'Désactivé';
									} else {
										$daemonData['state'] = 'KO';
										$asNok += 1;
									}
									break;
							}
						} catch (Exception $e) {
							log::add('JeedomConnect', 'warning', 'HEALTH -- issue while getting daemon_info -- ' . $e->getMessage());
						}
					}

					$healthData = array();
					if (method_exists($plugin->getId(), 'health')) {

						try {
							foreach ($plugin_id::health() as $result) {
								$item = array();
								$item['test'] = $result['test'];
								$item['advice'] = $result['advice'];

								if (!$result['state']) {
									$asNok += 1;
								}
								$item['result'] = $result['result'];

								array_push($healthData, $item);
							}
						} catch (Exception $e) {
							log::add('JeedomConnect', 'warning', 'HEALTH -- issue while getting health info -- ' . $e->getMessage());
						}
					}


					$update = $plugin->getUpdate();
					$versionType = $versionDate = null;
					if (is_object($update)) {
						$versionType = $update->getConfiguration('version');
						$versionDate = $update->getLocalVersion();
					}

					$pluginData = array();

					$pluginData['id'] = $plugin_id;
					$pluginData['name'] = $plugin->getName();
					$pluginData['img'] = $plugin->getPathImgIcon();
					$pluginData['versionDate'] = $versionDate;
					$pluginData['versionType'] = $versionType;
					$pluginData['error'] = $asNok;
					$pluginData['pending'] = $asPending;
					$pluginData['portInfo'] = $portInfo;
					$pluginData['dependencyInfo'] = $dependencyInfo;
					$pluginData['daemonData'] = $daemonData;
					$pluginData['healthData'] = $healthData;

					array_push($allPluginsData, $pluginData);
				}
			}

			// get jeedom health page
			foreach ((jeedom::health()) as $datas) {
				$item = array();
				$item['name'] = $datas['name'];
				$item['comment'] = $datas['comment'];

				if ($datas['state'] === 2) {
					$item['state'] = 'warning';
				} else if ($datas['state']) {
					$item['state'] = 'OK';
				} else {
					$item['state'] = 'KO';
				}

				$item['result'] = $datas['result'];

				array_push($jeedomData, $item);
			}
		} else {
			log::add('JeedomConnect', 'debug', 'HEALTH -- skip');
		}

		$result = array('plugins' => $allPluginsData, 'jeedom' => $jeedomData, 'nbUpdate' => $nb);
		return $result;
	}

	public static function getPluginsUpdate() {
		update::checkAllUpdate();
		$nbNeedUpdate = update::nbNeedUpdate();

		$updateArr = array();
		if ($nbNeedUpdate != 0) {

			foreach (update::all() as $update) {

				if (strtolower($update->getStatus()) != 'update') continue;

				$item = array();
				$item['pluginId'] =  $update->getLogicalId();
				try {

					if ($update->getType() == 'core') {
						$item['message'] = 'La mise à jour du core n\'est possible depuis l\'application';
						$item['doNotUpdate'] = true;
						$item['name'] =  'Jeedom Core';

						$version = substr(jeedom::version(), 0, 3);
						$item['changelogLink'] =  'https://doc.jeedom.com/' . config::byKey('language', 'core', 'fr_FR') . '/core/' . $version . '/changelog';
					} else {
						$plugin = plugin::byId($update->getLogicalId());
						$item['name'] = $plugin->getName();
						$item['img'] = $plugin->getPathImgIcon();
						$item['changelogLink'] =  $plugin->getChangelog();
						$item['docLink'] =  $plugin->getDocumentation();
						$item['doNotUpdate'] = $update->getConfiguration('doNotUpdate') == 1;
						$item['pluginType'] = $update->getConfiguration('version');
					}

					$item['currentVersion'] =  $update->getLocalVersion();
					$item['updateVersion'] = $update->getRemoteVersion();
				} catch (Exception $e) {
					log::add('JeedomConnect', 'warning', 'PLUGIN UPDATE -- exception : ' . $e->getMessage());
					$item['message'] = 'Une erreur est survenue. Merci de regarder les logs.';
				}
				array_push($updateArr, $item);
			}
		}

		$result = array('nbUpdate' => $nbNeedUpdate, 'pluginsToUpdate' => $updateArr);
		return $result;
	}
}

class JeedomConnectCmd extends cmd {

	public static $_widgetPossibility = array(
		'custom' => true
	);

	public function dontRemoveCmd() {
		return true;
	}

	public function cancelAsk($notificationId, $answer, $eqNameAnswered, $dateAnswer) {
		$eqLogic = $this->getEqLogic();

		$data = array(
			'type' => 'ASK_ALREADY_ANSWERED',
			'payload' => array(
				'notificationId' => $notificationId,
				'answer' => $answer,
				'fromEquipement' => $eqNameAnswered,
				'dateAnswer' => $dateAnswer
			)
		);

		// log::add('JeedomConnect', 'debug', ' sending notif data ==> ' . json_encode($data));
		$eqLogic->sendNotif($this->getLogicalId(), $data);
	}

	public function execute($_options = array()) {
		if ($this->getType() != 'action') {
			return;
		}
		$eqLogic = $this->getEqLogic();

		// log::add('JeedomConnect', 'debug', 'start for : ' . $this->getLogicalId());

		$logicalId = ($this->getLogicalId() === 'notifall') ? 'notifall' : ((strpos(strtolower($this->getLogicalId()), 'notif') !== false) ? 'notif' : $this->getLogicalId());

		// log::add('JeedomConnect', 'debug', 'will execute action : ' . $logicalId . ' -- with option ' . json_encode($_options));

		switch ($logicalId) {
			case 'notifall':
				$cmdNotif = JeedomConnect::getCmdForAllNotif();
				$orignalCmdId = $this->getId();
				$timestamp = time();
				// log::add('JeedomConnect', 'debug', ' all cmd notif all : ' . json_encode($cmdNotif));
				foreach ($cmdNotif as $cmdId) {
					$cmd = cmd::byId($cmdId);
					$_options['orignalCmdId'] = $orignalCmdId;
					$_options['notificationId'] = $timestamp;

					$cmdNotifCopy = array_values(array_diff($cmdNotif, array($cmdId)));
					$_options['otherAskCmdId'] = count($cmdNotifCopy) > 0 ? $cmdNotifCopy : null;
					$cmd->execute($_options);
				}
				break;

			case 'notif':

				// log::add('JeedomConnect', 'debug', ' ----- running exec notif ! ---------');
				$myData = self::getTitleAndArgs($_options);

				$data = array(
					'type' => 'DISPLAY_NOTIF',
					'payload' => array(
						'cmdId' => $_options['orignalCmdId'] ?? $this->getId(),
						'title' => str_replace("'", "&#039;", $myData['title']),
						'message' => str_replace("'", "&#039;", $_options['message']),
						'answer' => $_options['answer'] ?? null,
						'timeout' => $_options['timeout'] ?? null,
						'notificationId' => $_options['notificationId'] ?? time(),
						'otherAskCmdId' => $_options['otherAskCmdId'] ?? null,
						'options' => $myData['args'] ?? null
					)
				);
				if (isset($_options["files"]) || isset($myData["files"])) {
					$files = array();
					$arrayMerge = array_merge($_options["files"] ?? array(), $myData["files"] ?? array());
					foreach ($arrayMerge as $file) {
						if (realpath($file)) array_push($files, realpath($file));
					}
					$data['payload']['files'] = $files;
				}
				// log::add('JeedomConnect_test', 'debug', ' notif payload ==> ' . json_encode($data));
				$eqLogic->sendNotif($this->getLogicalId(), $data);
				break;

			case 'goToPage':
				if (empty($_options['message'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" ... ');
					return;
				}
				$payload = array(
					'action' => 'goToPage',
					'pageId' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				}
				break;

			case 'toaster':
				if (empty($_options['message'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" ... ');
					return;
				}
				$payload = array(
					'action' => 'toaster',
					'message' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'launchApp':
				if (empty($_options['message'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" ... ');
					return;
				}
				$payload = array(
					'action' => 'launchApp',
					'packageName' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				}
				break;

			case 'unlink':
				$eqLogic->setConfiguration('deviceId', '');
				$eqLogic->setConfiguration('deviceName', '');
				$eqLogic->save();
				break;


			case 'update_pref_app':
				if (empty($_options['title'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('title_placeholder', 'Titre') . '" ... ');
					return;
				}
				if ($_options['title'] == 'none') {
					log::add('JeedomConnect', 'error', 'Please select an action for cmd "' . $this->getName() . '" ... ');
					return;
				}
				if (empty($_options['message']) && $_options['message'] != '0') {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" ... ');
					return;
				}

				switch (strtoupper($_options['message'])) {
					case 'START':
					case 'RUN':
					case 'MARCHE':
					case 'ON':
					case '1':
						$finalValue = 'ON';
						break;

					case 'STOP':
					case 'ARRET':
					case 'OFF':
					case '0':
						$finalValue = 'OFF';
						break;

					default:
						$finalValue = $_options['message'];
						break;
				}

				$payload = array(
					'action' => $_options['title'],
					'arg' => $finalValue
				);

				// log::add('JeedomConnect', 'debug', 'payload sent ' . json_encode($payload));
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'send_sms':
				if (empty($_options['title'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('title_placeholder', 'Titre') . '" ... ');
					return;
				}
				if (empty($_options['message'])) {
					log::add('JeedomConnect', 'error', 'Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" ... ');
					return;
				}

				$args = self::arg2arrayCustom($_options['title']);
				if (empty($args)) {
					$num = $_options['title'];
					$sim = null;
					$files = null;
				} elseif (!empty($args['numero'])) {
					$num = $args['numero'];
					$sim = $args['sim'] ?? null;
					$files = isset($args['files']) ? explode(',', $args['files']) :  null;
				} else {
					log::add('JeedomConnect', 'error', 'No numero found');
					return;
				}

				if (!is_null($files)) {
					$filesTemp = array();
					foreach ($files as $file) {
						if (realpath($file)) {
							array_push($filesTemp, realpath($file));
						}
					}
					$files = !empty($filesTemp) ? $filesTemp :  null;
				}

				$payload = array(
					'action' => 'send_sms',
					'numero' => $num,
					'message' => $_options['message'],
					'simId' => $sim,
					'files' => $files
				);

				// log::add('JeedomConnect_test', 'debug', 'payload sent ' . json_encode($payload));
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			default:
				log::add('JeedomConnect', 'error', 'unknow action [' . $logicalId . '] - options :' . json_encode($_options));
				break;
		}
	}


	public static function getTitleAndArgs($_options) {

		$optionsSupp = array();
		$args = array();
		$files = array();
		if (isset($_options['title'])) {
			$optionsSupp = self::arg2arrayCustom($_options['title']);
			//if no use of '|', then will try to use the standard fx
			// if (count($optionsSupp) == 0) $optionsSupp = arg2array($_options['title']);
		}

		if (empty($optionsSupp)) {
			$titre = $_options['title'] ?: '';
		} else {
			foreach ($optionsSupp as $key => $value) {
				$optionsSupp[$key] =  str_replace('"', '', $value);
			}

			$titre = isset($optionsSupp['title']) ? $optionsSupp['title'] : '';
			$args = $optionsSupp;
			if (isset($optionsSupp['title'])) unset($args['title']);

			if (isset($optionsSupp['files'])) {
				$files = explode(',', $optionsSupp['files']);
				unset($args['files']);
			}
		}
		return array('title' => $titre, 'args' => $args, 'files' => $files);
	}

	public static function arg2arrayCustom($data) {
		$dataArray = explode('|', $data);
		$result = array();
		foreach ($dataArray as $item) {
			$arg = explode('=', trim($item), 2);
			if (count($arg) == 2) {
				$result[trim($arg[0])] = trim($arg[1]);
			}
		}
		return $result;
	}

	public function getWidgetTemplateCode($_version = 'dashboard', $_clean = true, $_widgetName = '') {

		if ($_version != 'scenario') return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);

		if ($this->getDisplay('title_with_list', '') != 1) return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);

		$template = getTemplate('core', 'scenario', 'cmd.action.message_with_choice', 'JeedomConnect');

		if (!empty($template)) {
			if (version_compare(jeedom::version(), '4.2.0', '>=')) {
				if (!is_array($template)) return array('template' => $template, 'isCoreWidget' => false);
			} else {
				$replace = array();
				$replace['#listValue#'] = $this->getListOption();
				$html = template_replace($replace, $template);

				return $html;
			}
		}
		return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);
	}

	public function getListOption() {
		$listOption = '';
		if ($this->getConfiguration('listValue', '') != '') {
			$elements = explode(';', $this->getConfiguration('listValue', ''));
			$foundSelect = false;
			foreach ($elements as $element) {
				$coupleArray = explode('|', $element);
				$cmdValue = $this->getCmdValue();
				if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
					if ($cmdValue->execCmd() == $coupleArray[0]) {
						$listOption .= '<option value="' . $coupleArray[0] . '" selected>' . $coupleArray[1] . '</option>';
						$foundSelect = true;
					} else {
						$listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
					}
				} else {
					$listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
				}
			}
			if (!$foundSelect) {
				$listOption = '<option value="none" selected>-- A SELECTIONNER --</option>' . $listOption;
			}
		}

		return $listOption;
	}
}
