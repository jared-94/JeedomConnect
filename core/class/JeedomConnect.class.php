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
require_once dirname(__FILE__) . '/JeedomConnectUtils.class.php';
require_once dirname(__FILE__) . '/JeedomConnectLogs.class.php';

class JeedomConnect extends eqLogic {

	public static $_widgetPossibility = array('custom' => true);
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


	public static function pluginGenericTypes() {
		$generics = array(
			'GEOLOCATION' => array(
				'name' => __('Géolocalisation', __FILE__),
				'familyid' => 'tracking',
				'family' => __('Géolocalisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('other')
			),
			'AC_ON' => array(
				'name' => __('Climatiseur ON', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Action',
				'subtype' => array('other')
			),
			'AC_OFF' => array(
				'name' => __('Climatiseur OFF', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Action',
				'subtype' => array('other')
			),
			'AC_STATE' => array(
				'name' => __('Climatiseur Etat', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('binary')
			),
			'AC_TEMPERATURE' => array(
				'name' => __('Climatiseur Température', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('numeric')
			),
			'AC_SET_TEMPERATURE' => array(
				'name' => __('Climatiseur Consigne Température', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Action',
				'subtype' => array('slider')
			),
			'AC_SET_MODE' => array(
				'name' => __('Climatiseur Mode', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Action',
				'subtype' => array('select')
			),
			'AC_MODE' => array(
				'name' => __('Climatiseur Mode', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('other')
			),
			'AC_SET_FAN_MODE' => array(
				'name' => __('Climatiseur Ventillation Mode', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Action',
				'subtype' => array('select')
			),
			'AC_FAN_MODE' => array(
				'name' => __('Climatiseur Ventillation Mode', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('other')
			),
			'AC_INDOOR_TEMPERATURE' => array(
				'name' => __('Climatiseur Température Intérieur', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('numeric')
			),
			'AC_OUTDOOR_TEMPERATURE' => array(
				'name' => __('Climatiseur Température Extérieur', __FILE__),
				'familyid' => 'ac',
				'family' => __('Climatisation', __FILE__),
				'type' => 'Info',
				'subtype' => array('numeric')
			),
		);
		return $generics;
	}

	public static $_plugin_config_dir = __DIR__ . '/../config/';
	public static $_plugin_info_dir = __DIR__ . '/../../plugin_info/';
	public static $_data_dir = __DIR__ . '/../../data/';
	public static $_config_dir = __DIR__ . '/../../data/configs/';
	public static $_qr_dir = __DIR__ . '/../../data/qrcodes/';
	public static $_notif_dir = __DIR__ . '/../../data/notifs/';
	public static $_backup_dir = __DIR__ . '/../../data/backups/';

	/*     * ***********************Methode static*************************** */

	/*     * ********************** DAEMON MANAGEMENT *************************** */

	public static function deamon_info() {
		$return = array();
		$return['log'] = __CLASS__;
		$return['state'] = 'nok';

		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';

		// $serverExist = count(system::ps('core/php/server.php')) > 0;
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
			}
		}

		if (!self::useWebsocket()) {
			if ($return['state'] == 'ok') self::deamon_stop();
			$return['state'] == 'nok';
			$return['launchable'] = 'nok';
			$return['launchable_message'] = 'Aucun équipement utilise le websocket';
			return $return;
		}

		$return['launchable'] = 'ok';
		$return['last_launch'] = config::byKey('lastDeamonLaunchTime', __CLASS__, __('Inconnue', __FILE__));
		return $return;
	}


	public static function deamon_start() {
		self::deamon_stop();
		JCLog::info('Starting daemon');

		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}

		$daemonLogConfig = config::byKey('daemonLog', __CLASS__, 'parent');
		$daemonLog = ($daemonLogConfig == 'parent') ? log::getLogLevel(__CLASS__) : $daemonLogConfig;


		$path = realpath(dirname(__FILE__) . '/../../resources/JeedomConnectd'); // répertoire du démon à modifier
		$cmd = 'python3 ' . $path . '/JeedomConnectd.py'; // nom du démon à modifier
		$cmd .= ' --loglevel ' . log::convertLogLevel($daemonLog); // log::convertLogLevel(log::getLogLevel(__CLASS__));
		$cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '58090'); // port socket - échange entre le démon en PY et l'api jeedom
		$cmd .= ' --websocketport ' . config::byKey('port', __CLASS__, '8090'); // port d'écoute du démon pour échange avec l'application JC
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/JeedomConnect/core/api/JeedomConnect.api.php'; // chemin de la callback url à modifier (voir ci-dessous)
		$cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__); // l'apikey pour authentifier les échanges suivants
		$cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // et on précise le chemin vers le pid file (ne pas modifier)
		log::add(__CLASS__, 'debug', 'Starting daemon with cmd >>' . $cmd . '<<');
		exec($cmd . ' >> ' . log::getPathToLog('JeedomConnect_daemon') . ' 2>&1 &'); // 'template_daemon' est le nom du log pour votre démon, vous devez nommer votre log en commençant par le pluginid pour que le fichier apparaisse dans la page de config

		$i = 0;
		while ($i < 10) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 10) {
			log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDeamon');
		return true;
	}

	public static function deamon_stop() {
		JCLog::info('Stopping daemon');
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // ne pas modifier
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('JeedomConnectd.py');
		sleep(1);
	}

	public static function sendToDaemon($params) {
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] != 'ok') {
			throw new Exception("Le démon n'est pas démarré");
		}
		$params['jeedomApiKey'] = jeedom::getApiKey(__CLASS__);
		$payLoad = json_encode($params);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', __CLASS__, '58090')); //port par défaut de votre plugin à modifier
		socket_write($socket, $payLoad, strlen($payLoad));
		socket_close($socket);
	}

	/*     * -------------------------------- END DAEMON -------------------------------- */

	public static function useWebsocket() {
		/** @param JeedomConnect $eqLogic */
		$daemonRequired = false;
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			if ($eqLogic->getConfiguration('useWs', false)) {
				$daemonRequired = true;
				break;
			}
		}
		return $daemonRequired;
	}

	/**
	 * Check if websocket is acitvated at least on one equipment, if not and if daemon is started
	 * then displays a message in jeedom console to let the user know that the daemon seems not required
	 * for his current need, and that this functionnality can be disabled. 
	 *
	 * @return void
	 */
	public static function checkDaemon() {
		/**
		 * @param JeedomConnect $eqLogic
		 */
		$daemonRequired = self::useWebsocket();

		if (!$daemonRequired) {
			JCLog::warning("le démon n'est pas nécessaire !");
			$plugin = plugin::byId('JeedomConnect');
			$daemon_info = $plugin->deamon_info();
			$msg = ($daemon_info['state'] == 'ok') ?
				"Il semblerait que le démon du plugin JC soit actif alors que tu n'en as pas besoin puisqu'aucun de tes équipements n'utilise la connexion par websocket.
			 Tu peux donc le stopper." : '';
			$msg .= $daemon_info['auto'] ? ' Tu peux également désactiver la gestion automatique du démon.' : '';

			if ($msg != '') message::add('JeedomConnect',  $msg);
		}

		return;
	}

	public static function removeBackupFiles() {
		return JeedomConnectUtils::removeBackupFiles();
	}

	public static function backup() {
		JeedomConnectWidget::exportWidgetConf();
		JeedomConnectWidget::exportWidgetCustomConf();

		foreach (\JeedomConnect::getAllJCequipment() as $eqLogic) {
			$apiKey = $eqLogic->getLogicalId();

			$bkpDir = self::$_backup_dir . $apiKey;
			if (!is_dir($bkpDir))  @mkdir($bkpDir, 0755, true);

			$configFile = realpath(self::$_config_dir) . '/' . $apiKey . '.json';
			if (file_exists($configFile)) {
				$configFileContent = JeedomConnectUtils::getFileContent($configFile);
				$content = JeedomConnectUtils::addTypeInPayload($configFileContent, 'JC_EXPORT_EQLOGIC_CONFIG');
				file_put_contents($bkpDir . '/config-' . $apiKey . '.json', json_encode($content, JSON_PRETTY_PRINT));
			}

			$notifFile = realpath(self::$_notif_dir) . '/' . $apiKey . '.json';
			if (file_exists($notifFile)) copy($notifFile, $bkpDir . '/notif-' . $apiKey . '.json');
		}
	}

	public static function copyNotifConfig($oldApiKey, $newApiKey) {
		$notif_file = self::$_notif_dir . $oldApiKey . ".json";
		$notif_file_new = self::$_notif_dir . $newApiKey . ".json";

		if (file_exists($notif_file)) {
			JCLog::debug('Copying notification config file');
			copy($notif_file, $notif_file_new);
		}
	}

	public static function copyBackupConfig($oldApiKey, $newApiKey) {
		$backupDir = JeedomConnect::$_backup_dir . $oldApiKey;
		$backupDir_new = JeedomConnect::$_backup_dir . $newApiKey;

		if (is_dir($backupDir)) {
			JCLog::debug('Copying backup config folder');
			JeedomConnectUtils::recurse_copy($backupDir, $backupDir_new);
		}
	}

	public static function copyConfig($from, $to) {

		$config_file_model = self::$_config_dir . $from . ".json";
		if (!file_exists($config_file_model)) {
			JCLog::warning('file ' . $config_file_model . ' does not exist');
			return null;
		}
		$configFile = file_get_contents($config_file_model);

		foreach ($to as $item) {

			if ($item != $from) {

				$config_file_destination = self::$_config_dir . $item . ".json";
				try {
					JCLog::debug('Copying config file from ' . $from . ' to ' . $item);
					file_put_contents($config_file_destination, $configFile);

					/** @var JeedomConnect $eqLogic */
					$eqLogic = eqLogic::byLogicalId($item, 'JeedomConnect');
					if (!is_object($eqLogic)) {
						JCLog::debug('no object found');
						continue;
					}
					$eqLogic->generateNewConfigVersion();
				} catch (Exception $e) {
					JCLog::error('Unable to write file : ' . $e->getMessage());
				}
			}
		}

		return true;
	}

	public function cleanCustomData() {
		$apiKey = $this->getConfiguration('apiKey');
		$customData = config::searchKey('customData::' . $apiKey . '::', 'JeedomConnect');

		if (!empty($customData)) {
			$config = $this->getConfig(true);

			foreach ($customData as $item) {
				$search = array_search($item['value']['widgetId'], array_column($config['payload']['widgets'], 'widgetId'));
				if ($search === false) {
					JCLog::debug('removing custom data (not used anymore) : ' . $item['value']['widgetId']);
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
			JCLog::debug('Saving conf in file : ' . $config_file);
			file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
		} catch (Exception $e) {
			JCLog::error('Unable to write file : ' . $e->getMessage());
		}
	}

	public function getConfig($replace = false, $saveGenerated = false) {

		if ($this->isWidgetMap()) return null;

		if ($this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == '') {
			JCLog::error('¤¤¤¤¤ getConfig for ApiKey EMPTY ! [' . $this->getName() . ']');
			return null;
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		if (!file_exists($config_file_path)) {
			JCLog::warning('file ' . $config_file_path . ' does not exist');
			return null;
		}
		$configFile = file_get_contents($config_file_path);
		$jsonConfig = json_decode($configFile, true);

		if (!$replace) {
			JCLog::trace('¤¤¤¤¤ only send the config file without enrichment for apikey ' . $this->getConfiguration('apiKey'));
			return $jsonConfig;
		}

		$roomIdList = array();
		$widgetList = array();
		$widgetIdInGroup = array();
		$maxIndex = 0;
		foreach ($jsonConfig['payload']['widgets'] as $key => $widget) {
			$widgetData = JeedomConnectWidget::getWidgets($widget['id']);
			$widgetId = $widget['widgetId'] ?? '';
			if (empty($widgetData)) {
				// ajax::error('Erreur - pas d\'équipement trouvé');
				JCLog::debug('Erreur - pas de widget trouvé avec l\'id ' . $widget['id']);
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

				foreach ($widget as $item => $value) {
					if (is_array($value) && array_key_exists('subType', $value) && $value['subType'] == 'select') {
						$choices = self::getChoiceData($value['id']);
						$widget[$item]['choices'] = $choices;
					}
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
					// JCLog::debug( 'the widget ['. $item . '] does not exist in the config file. Adding it.');
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
							foreach ($newWidgetConf as $key => $value) {
								if (is_array($value) && key_exists('subType', $value) && $value['subType'] == 'select') {
									$choices = self::getChoiceData($newWidgetConf[$key]['id']);
									$newWidgetConf[$key]['choices'] = $choices;
								}
							}
						}

						$maxIndex = $maxIndex + 1;
						$jsonConfig['payload']['widgets'][$maxIndex] = $newWidgetConf;

						array_push($widgetList, $newWidgetConf['id']);
					}
				}
			}

			if (count($moreWidget) > 0) JCLog::debug('more widgets children to add -- ' . json_encode($moreWidget));
			$widgetIdInGroup = $moreWidget;
		}


		//add equipement password
		$pwd = $this->getConfiguration('pwdAction', null);
		$jsonConfig['payload']['password'] = $pwd;

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

	/**
	 * @param string $apiKey
	 * @return array
	 */
	public static function getWidgetConfigContent($apiKey = '') {

		$filePath = self::$_config_dir . $apiKey . '.json';
		JCLog::debug('will check for config file : ' . $filePath);

		if (!file_exists($filePath) || $apiKey == '') {
			throw new Exception("No config file found");
		}

		return json_decode(file_get_contents($filePath), true);
	}

	public function getCustomWidget() {

		$myKey = 'customData::' . $this->getConfiguration('apiKey') . '::';
		// JCLog::debug( 'looking for config key : ' .  myKey);

		$allCustomData = config::searchKey($myKey, 'JeedomConnect');

		$final = array();
		foreach ($allCustomData as $item) {
			if (!key_exists('widgetId', $item['value'])) {
				JCLog::error('no widgetId found - skip');
				continue;
			}
			$final[$item['value']['widgetId']] = $item['value'];
		}

		$result = array("widgets" => $final);
		// JCLog::debug( 'resultat : ' .  json_encode($result));
		return $result;
	}

	public function getGeneratedConfigFile() {

		if ($this->getConfiguration('apiKey') == null || $this->getConfiguration('apiKey') == '') {
			JCLog::error('¤¤¤¤¤ getGeneratedConfigFile for ApiKey EMPTY ! [' . $this->getName() . ']');
			return null;
		}

		$cacheConf = cache::byKey('jcConfig' . $this->getConfiguration('apiKey'))->getValue();
		if ($cacheConf != '') {
			return json_decode($cacheConf, true);
		}

		$config_file_path = self::$_config_dir . $this->getConfiguration('apiKey') . ".json.generated";
		if (!file_exists($config_file_path)) {
			JCLog::warning('file ' . $config_file_path . ' does not exist  -- new try to generate one');
			$this->getConfig(true, true);
		}

		try {
			$configFile = file_get_contents($config_file_path);
			$jsonConfig = json_decode($configFile, true);
			return $jsonConfig;
		} catch (Exception $e) {
			JCLog::error('Unable to generate configuration setting : ' . $e->getMessage());
			return null;
		}
	}

	public static function getChoiceData($cmdId) {
		$choice = array();

		$cmd = cmd::byId($cmdId);

		if (!is_object($cmd)) {
			JCLog::warning($cmdId . ' is not a valid cmd Id');
			return $choice;
		}

		$cmdConfig = $cmd->getConfiguration('listValue');

		if ($cmdConfig !=  '') {
			JCLog::trace('value of listValue ' . json_encode($cmdConfig));

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

		JCLog::trace('final choices list => ' . json_encode($choice));
		return $choice;
	}

	public function getWidgetId() {
		$ids = array();

		$conf = $this->getConfig(true);
		if (is_null($conf)) return $ids;

		foreach ($conf['payload']['widgets'] as $item) {
			array_push($ids, $item['id']);
		}

		JCLog::debug(' fx  getWidgetId -- result final ' . json_encode($ids));
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
			JCLog::info('Config file updated for ' . $this->getName() . ':' . json_encode($jsonConfig));
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
					if ($cmd->getLogicalId() == $notif['id'] || strpos(strtolower($cmd->getLogicalId()), 'notifall') !== false) {
						$remove = false;
						break;
					}
				}
				if ($remove) {
					JCLog::debug('remove cmd ' . $cmd->getName());
					$cmd->remove();
				}
			}
		}
	}

	public function addCmd($notif) {
		try {
			$cmdNotif = $this->getCmd(null, $notif['id']);
			if (!is_object($cmdNotif)) {
				JCLog::debug('add new cmd ' . $notif['name']);
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

			$cmdNotif->save();
		} catch (Exception $e) {
			JCLog::error('Notif creation error ' . $e->getMessage());
		}
	}

	public static function fixNotif() {
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			$change = false;
			$config_file = self::$_notif_dir . $eqLogic->getConfiguration('apiKey') . ".json";
			if (!file_exists($config_file)) continue;

			$config = json_decode(file_get_contents($config_file), true);
			// JCLog::debug('FIX NOTIF // config ' . json_encode($config));
			$idCounter = $config['idCounter'];

			//Update cmds
			foreach ($config['notifs'] as $key => $notif) {
				if (!key_exists('id', $notif) || $notif['id'] == '') {
					$change = true;

					JCLog::debug('Notif Id not found : ' . json_encode($notif));
					$newId = 'notif-' . $idCounter;
					$config['notifs'][$key]['id'] = $newId;
					$idCounter++;

					/** @var cmd $cmdNotif */
					$cmdNotif = cmd::byEqLogicIdCmdName($eqLogic->getId(), $notif['name']);
					// if cmd is an object as an action/msg and logicalId is null, then it's the same cmd 
					// --> update the logicalId with the new id set
					if (
						is_object($cmdNotif) &&
						$cmdNotif->getType() == 'action' &&
						$cmdNotif->getSubType() == 'message' &&
						is_null($cmdNotif->getLogicalId())
					) {
						try {
							JCLog::debug('updating cmd with empty logicalId => ' . json_encode(utils::o2a($cmdNotif)));
							$cmdNotif->setLogicalId($newId);
							$cmdNotif->save();
						} catch (Exception $e) {
							JCLog::error('Notif error ' . $e->getMessage());
						}
					} else {
						JCLog::debug('cmd already OK');
					}
				}
			}

			if ($change) {
				$config['idCounter'] = $idCounter;
				file_put_contents($config_file, json_encode($config));

				$data = array(
					"type" => "SET_NOTIFS_CONFIG",
					"payload" => $config
				);

				$eqLogic->sendNotif('defaultNotif', $data);
			}
		}
		config::save('fix::notifID', 'done', 'JeedomConnect');
		JCLog::debug('END fixNotif');
	}

	public static function fixNotifCmdDummy() {
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {

			// remove bad cmd named '{'
			/** @var cmd $cmdDummy */
			$cmdDummy = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), '{');
			if (is_object($cmdDummy)) $cmdDummy->remove();
		}
		config::save('fix::notifCmdDummy', 'done', 'JeedomConnect');
		JCLog::debug('END fixNotifCmdDummy');
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
			// $user = user::all()[0];
			// $this->setConfiguration('userId', $user->getId());
			// $this->save(true);  //save manquant ! info non persistée
			JCLog::error('Aucun utilisateur sélectionné sur [' . $this->getName() . ']');
		}

		$connectData = array(
			'useWs' => $this->getConfiguration('useWs', 0),
			'polling' => $this->getConfiguration('polling', 0),
			'eqName' => $this->getName(),
			'userName' => $user ? $user->getLogin() : null,
			'httpUrl' => config::byKey('httpUrl', 'JeedomConnect', network::getNetworkAccess('external')),
			'internalHttpUrl' => config::byKey('internHttpUrl', 'JeedomConnect', network::getNetworkAccess('internal')),
			'wsAddress' => config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('externalAddr') . ':8090'),
			'internalWsAddress' => config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'),
			'apiKey' => $this->getConfiguration('apiKey'),
			'userHash' => $user ? $user->getHash() : null,
		);

		$dataFree = JeedomConnectUtils::hideSensitiveData(json_encode($connectData), 'send');
		JCLog::debug('Generate qrcode with data ' . $dataFree);

		require_once dirname(__FILE__) . '/../php/phpqrcode.php';
		try {
			$filepath = self::$_qr_dir . $this->getConfiguration('apiKey') . '.png';
			QRcode::png(json_encode($connectData), $filepath);

			if (config::byKey('withQrCode', 'JeedomConnect', true)) {
				// Start DRAWING LOGO IN QRCODE
				$QR = imagecreatefrompng($filepath);

				// START TO DRAW THE IMAGE ON THE QR CODE
				$logopath = dirname(__FILE__) . '/../../data/img/JeedomConnect_icon2.png';
				$logo = imagecreatefromstring(file_get_contents($logopath));
				$QR_width = imagesx($QR);
				$QR_height = imagesy($QR);

				$logo_width = imagesx($logo);
				$logo_height = imagesy($logo);

				// Scale logo to fit in the QR Code
				$logo_qr_width = $QR_width / 5;
				$scale = $logo_width / $logo_qr_width;
				$logo_qr_height = $logo_height / $scale;

				imagecopyresampled($QR, $logo, $QR_width / 2.5, $QR_height / 2.5, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

				// Save QR code again, but with logo on it
				imagepng($QR, $filepath);
			}
		} catch (Exception $e) {
			JCLog::error('Unable to generate a QR code : ' . $e->getMessage());
		}
	}

	public function registerDevice($id, $name) {
		$this->setConfiguration('deviceId', $id);
		$this->setConfiguration('deviceName', $name);
		$this->save(true);
	}

	public function removeDevice() {
		$this->setConfiguration('deviceId', '');
		$this->setConfiguration('deviceName', '');
		$this->setConfiguration('platformOs', '');
		$this->save(true);
	}

	public function registerToken($token) {
		$this->setConfiguration('token', $token);
		$this->save(true);
	}

	public function sendNotif($notifId, $data) {
		if ($this->getConfiguration('token') == null) {
			JCLog::info("No token defined. Please connect your device first");
			return;
		}

		$postData = array(
			'to' => $this->getConfiguration('token')
		);
		if ($this->getConfiguration('platformOs') == 'android') {
			$postData['priority'] = 'high';
		}

		foreach ($this->getNotifs()['notifs'] as $notif) {
			if ($notif['id'] == $notifId) {
				unset($notif['name']);
				// JCLog::info(" add notif setup data // BEFORE ==> " . json_encode($notif));
				if (key_exists('actions', $notif)) {
					foreach ($notif['actions'] as $key => $value) {
						$value['name'] = str_replace("'", "&#039;", $value['name']);
						$notif['actions'][$key] = $value;
					}
				}
				// JCLog::info(" add notif setup data // AFTER ==> " . json_encode($notif));
				$data["payload"] = array_merge($data["payload"], $notif);
			}
		}
		$data["payload"]["time"] = time();
		if ($data["type"] == "DISPLAY_NOTIF") {
			$data = JeedomConnectUtils::getNotifData($data, $this);
		}
		$postData["data"] = $data;

		if ($this->getConfiguration('platformOs') == 'ios' && $data["type"] == "DISPLAY_NOTIF") {
			$postData = JeedomConnectUtils::getIosPostData($postData, $data);
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
			default:
				JCLog::error("Error while detecting system architecture. " . php_uname("m") . " detected");
				return;
		}


		$binFile =  __DIR__ . "/../../resources/" . $sendBin;
		if (!is_executable($binFile)) {
			chmod($binFile, 0555);
		}
		$cmd = $binFile . " -data='" . json_encode($postData, JSON_HEX_APOS) . "' 2>&1";
		JCLog::info("Send notification with data " . json_encode($postData["data"]));
		$output = shell_exec($cmd);
		if (is_null($output) || empty($output)) {
			JCLog::error("Error while sending notification");
			return;
		} else {
			JCLog::debug("Send output : " . $output);
		}
	}

	public function addGeofenceCmd($geofence, $coordinates) {
		JCLog::debug("Add or update geofence cmd : " . json_encode($geofence));

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

		$this->setCoordinates($coordinates['latitude'], $coordinates['longitude'], $coordinates['altitude'], '', '', time());
	}

	public function removeGeofenceCmd($geofence) {
		JCLog::debug("Remove geofence cmd : " . json_encode($geofence));

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
		JCLog::debug("[setGeofencesByCoordinates] " . $lat . ' -- ' . $lgt);
		foreach (cmd::byEqLogicId($this->getId()) as $cmd) {
			if (strpos(strtolower($cmd->getLogicalId()), 'geofence') !== false) {
				$dist = JeedomConnectUtils::getDistance($lat, $lgt, $cmd->getConfiguration('latitude'), $cmd->getConfiguration('longitude'));
				if ($dist < $cmd->getConfiguration('radius')) {
					if ($cmd->execCmd() != 1) {
						JCLog::debug("Set 1 for geofence " . $cmd->getName());
						$cmd->event(1, date('Y-m-d H:i:s', $timestamp));
					}
				} else {
					if ($cmd->execCmd() !== 0) {
						JCLog::debug("Set 0 for geofence " . $cmd->getName());
						$cmd->event(0, date('Y-m-d H:i:s', $timestamp));
					}
				}
			}
		}

		$distToDefault = JeedomConnectUtils::getDistance($lat, $lgt);
		$cmdDistance = $this->getCmd(null, 'distance');
		if (is_object($cmdDistance)) $cmdDistance->event($distToDefault);
	}

	public function preInsert() {
		if ($this->isWidgetMap()) return;

		if ($this->getConfiguration('apiKey') == '') {
			$this->setConfiguration('apiKey', JeedomConnectUtils::generateApiKey());
			$this->setLogicalId($this->getConfiguration('apiKey'));
			$this->generateQRCode();
		}
	}

	public function postInsert() {
		if ($this->isWidgetMap()) return;

		$this->setIsEnable(1);
		if ($this->getConfiguration('configVersion') == '') {
			$this->setConfiguration('configVersion', 0);
		}
		$this->save(true);

		$this->saveConfig(self::$_initialConfig);
		$this->saveNotifs(self::$_notifConfig);
	}

	public function preSave() {
	}

	public function postSave() {
		if ($this->isWidgetMap()) return;

		if ($this->getConfiguration('qrRefresh')) {
			$this->generateQRCode();
			$this->setConfiguration('qrRefresh',  0);
			$this->save(true);
		}

		if ($this->getConfiguration('pwdChanged') == 'true') {
			$confStd = $this->getConfig();
			$configVersion = $confStd['payload']['configVersion'] + 1;
			JCLog::debug(' saving new conf after password changed -- updating configVersion to ' . $configVersion);

			//update configVersion in the file
			$confStd['payload']['configVersion'] =  $configVersion;
			$this->saveConfig($confStd);

			//update configVersion in the equipment configuration
			$this->setConfiguration('configVersion', $configVersion);
			$this->setConfiguration('pwdChanged',  'false');
			$this->save(true);

			$this->getConfig(true, true);
		}

		if ($this->getConfiguration('hideBattery')) {
			// JCLog::debug( 'hiding battery : -2');
			$this->batteryStatus(100); //force 100 to remove any ongoing warning or danger notification
			$this->setStatus("battery"); //and for status to null to remove the info

		}
	}

	public function preUpdate() {
		if ($this->isWidgetMap()) return;

		$save = false;

		if ($this->getConfiguration('scenariosEnabled') == '') {
			$this->setConfiguration('scenariosEnabled', '1');
			$save = true;
		}
		if ($this->getConfiguration('webviewEnabled') == '') {
			$this->setConfiguration('webviewEnabled', '1');
			$save = true;
		}
		if ($this->getConfiguration('editEnabled') == '') {
			$this->setConfiguration('editEnabled', '1');
			$save = true;
		}

		if ($save) $this->save(true);
	}

	public function postUpdate() {
		if ($this->isWidgetMap()) return;

		$this->createCommands('all');
		if ($this->getConfiguration('platformOs') != '') {
			$this->createCommands($this->getConfiguration('platformOs'));
		}
	}

	public function preRemove() {
		if ($this->isWidgetMap()) return;

		$apiKey = $this->getConfiguration('apiKey');
		self::removeAllData($apiKey);
	}

	public function isWidgetMap() {
		return ($this->getConfiguration('jceqtype') == "map");
	}


	public static function getAllJCequipment() {
		$allEq = array();
		/** @var JeedomConnect $eqLogic */
		foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
			if ($eqLogic->isWidgetMap()) continue;
			$allEq[] = $eqLogic;
		}
		return $allEq;
	}

	/**
	 * ensure userImgPath doesn't start with / and ends with /
	 */
	public static function preConfig_userImgPath($value) {

		$userImgPath = ltrim($value, "/");
		if (substr($userImgPath, -1) != "/") {
			$userImgPath .= "/";
		}
		return $userImgPath;
	}

	public static function removeAllData($apiKey) {
		unlink(self::$_qr_dir . $apiKey . '.png');
		unlink(self::$_config_dir . $apiKey . ".json");
		unlink(self::$_config_dir . $apiKey . ".json.generated");
		unlink(self::$_notif_dir . $apiKey . ".json");
		JeedomConnectUtils::delTree(self::$_backup_dir . $apiKey);

		$allKey = config::searchKey('customData::' . $apiKey, 'JeedomConnect');
		foreach ($allKey as $item) {
			config::remove($item['key'], 'JeedomConnect');
		}
	}

	public function postRemove() {
	}

	public function createCommands(string $type) {
		$configFile = JeedomConnectUtils::getFileContent(self::$_plugin_config_dir . '/params.json');
		$dict = $configFile['dictionary'];
		try {
			if (isset($configFile['cmds'][$type])) {
				$this->createCommandsFromConfigFile($configFile['cmds'][$type], $dict);
			} else {
				JCLog::error($type . ' not found in config');
			}
		} catch (Exception $e) {
			JCLog::error('Cannot save Cmd for this EqLogic -- ' . $e->getMessage());
		}
	}

	public function createCommandsFromConfigFile($commands, $dict) {
		$cmd_updated_by = array();
		foreach ($commands as $cmdData) {
			$cmd = $this->getCmd(null, $cmdData["logicalId"]);

			if (!is_object($cmd)) {
				JCLog::debug('cmd creation  => ' . $cmdData["name"] . ' [' . $cmdData["logicalId"] . ']');

				$cmd = new cmd();
				$cmd->setLogicalId($cmdData["logicalId"]);
				$cmd->setEqLogic_id($this->getId());

				if (isset($cmdData["isVisible"])) {
					$cmd->setIsVisible($cmdData["isVisible"]);
				}

				if (isset($cmdData["isHistorized"])) {
					$cmd->setIsHistorized($cmdData["isHistorized"]);
				}

				if (isset($cmdData["generic_type"])) {
					$cmd->setGeneric_type($cmdData["generic_type"]);
				}

				if (isset($cmdData["unite"])) {
					$cmd->setUnite($cmdData["unite"]);
				}

				if (isset($cmdData["order"])) {
					$cmd->setOrder($cmdData["order"]);
				}
			}

			$cmd->setName(__($cmdData["name"], __FILE__));

			$cmd->setType($cmdData["type"]);
			$cmd->setSubType($cmdData["subtype"]);

			if (isset($cmdData['configuration'])) {
				foreach ($cmdData['configuration'] as $key => $value) {
					if ($key == 'listValueToCreate') {
						$key = 'listValue';
						$value = JeedomConnectUtils::createListOption(explode(";", $value), $dict);
					}
					$cmd->setConfiguration($key, $value);
				}
			}

			if (isset($cmdData['display'])) {
				foreach ($cmdData['display'] as $key => $value) {
					$cmd->setDisplay($key, $value);
				}
			}

			if (isset($cmdData['template'])) {
				foreach ($cmdData['template'] as $key => $value) {
					$cmd->setTemplate($key, $value);
				}
			}

			if (isset($cmdData['updateCmd'])) {
				$cmd_updated_by[$cmdData["logicalId"]] = $cmdData['updateCmd'];
			}

			$cmd->save();
		}

		foreach ($cmd_updated_by as $cmdAction_logicalId => $cmdInfo_logicalId) {
			$cmdAction = $this->getCmd(null, $cmdAction_logicalId);
			$cmdInfo = $this->getCmd(null, $cmdInfo_logicalId);

			if (is_object($cmdAction) && is_object($cmdInfo)) {
				$cmdAction->setValue($cmdInfo->getId());
				$cmdAction->save();
			}
		}
	}

	public static function checkAllEquimentsAndUpdateConfig($widgetId) {
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			$eqLogic->checkEqAndUpdateConfig($widgetId);
		}
	}

	public function checkEqAndUpdateConfig($widgetId) {

		JCLog::debug('Checking if widget ' . $widgetId . ' exist on equipment "' . $this->getName() . '" [' . $this->getConfiguration('apiKey') . ']');

		$conf = $this->getConfig(true);
		$exist = false;

		if (!$conf) {
			JCLog::debug('No config content retrieved');
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
			JCLog::debug($widgetId . ' NOT found in the current equipment');
		}
	}

	public function generateNewConfigVersion($widgetId = 'widgets') {
		$confStd = $this->getConfig();
		$configVersion = $confStd['payload']['configVersion'] + 1;
		JCLog::debug($widgetId . ' found in the current equipment -- updating configVersion to ' . $configVersion);

		//update configVersion in the file
		$confStd['payload']['configVersion'] =  $configVersion;
		$this->saveConfig($confStd);

		//update configVersion in the equipment configuration
		$this->setConfiguration('configVersion', $configVersion);
		$this->save(true);

		JCLog::debug('Renewing the version of the widgets configuration');
		$this->getConfig(true, true);
		return true;
	}

	public static function getWidgetParam($only_name = true, $widget_types = array()) {
		$widgetsConfigJonFile = json_decode(file_get_contents(self::$_plugin_config_dir . 'widgetsConfig.json'), true);
		$count_widget_types = count($widget_types);

		$result = array();
		foreach ($widgetsConfigJonFile['widgets'] as $config) {
			if ($count_widget_types > 0 && !in_array($config['type'], $widget_types)) continue;

			$result[$config['type']] = $only_name ? $config['name'] : $config;
		}
		return $result;
	}

	public function moveWidgetIndex($widgetId, $parentId, $currentIndex, $newIndex) {
		try {
			JCLog::debug('moveWidgetIndex data : ' . $widgetId . ' - ' . $parentId . ' - ' . $currentIndex . ' - ' . $newIndex);
			$conf = $this->getConfig();
			if ($conf) {
				foreach (array('widgets', 'groups') as $type) {
					JCLog::debug('moveWidgetIndex -- dealing with type : ' . $type);
					foreach ($conf['payload'][$type] as $key => $value) {
						// JCLog::debug( 'moveWidgetIndex -- checking widget  : ' . json_encode($conf['payload']['widgets'][$key]) ) ;
						// JCLog::debug( 'moveWidgetIndex -- parentId  : ' . $value['parentId'] ) ;
						// JCLog::debug( 'moveWidgetIndex -- index : ' . $value['index'] ) ;

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
			JCLog::error('Unable to move index : ' . $e->getMessage());
			return false;
		}
	}

	public function removeWidgetConf($idToRemoveList) {

		$remove = false;

		$conf = $this->getConfig();

		JCLog::debug('Removing widget in equipement config file -- ' . json_encode($conf));
		if ($conf) {
			foreach ($conf['payload']['widgets'] as $key => $value) {
				if (in_array($value['id'], $idToRemoveList)) {
					JCLog::debug('Removing that widget item config -- ' . json_encode($value));
					unset($conf['payload']['widgets'][$key]);
					$remove = true;
				}
			}

			if ($remove) {
				$conf['payload']['widgets'] = array_values($conf['payload']['widgets']);
				JCLog::info('Widget ID ' . json_encode($idToRemoveList) . ' has been removed on equipement ' . $this->getName());
				$this->saveConfig($conf);
				$this->cleanCustomData();
				$this->generateNewConfigVersion();
				return;
			} else {
				JCLog::info('Widget ID ' . json_encode($idToRemoveList) . ' not found in equipement ' . $this->getName());
			}
		} else {
			JCLog::warning('No config content retrieved');
		}
		return;
	}

	public function resetConfigFile() {
		JCLog::debug('reseting configuration for equipment "' . $this->getName() . '" [' . $this->getConfiguration('apiKey') . ']');
		$this->saveConfig(self::$_initialConfig);
		$this->cleanCustomData();
	}

	public static function getPluginInfo() {

		$pluginInfo = json_decode(file_get_contents(self::$_plugin_info_dir . 'version.json'), true);
		$branchInfo = array(
			"typeVersion" => JeedomConnectUtils::isBeta(true),
			"enrollment" => "https://jared-94.github.io/JeedomConnectDoc/fr_FR/#qBeta"
		);

		$result = array_merge($pluginInfo, $branchInfo);

		return $result;
	}

	public static function displayMessageInfo() {

		$pluginInfo = self::getPluginInfo();

		$apkVersionRequired = $pluginInfo['require'];
		message::add('JeedomConnect',  'Ce plugin nécessite d\'utiliser l\'application en version minimum : ' . $apkVersionRequired . '. Si nécessaire, pensez à mettre à jour votre application depuis votre Store');

		if ($pluginInfo['typeVersion'] == 'beta') {
			$enrollmentLink = htmlentities('<a href="' . $pluginInfo['enrollment'] . '" target="_blank">en cliquant ici</a>');
			message::add('JeedomConnect',  'Si ça n\'est pas déjà fait, pensez à vous inscrire dans le programme beta-testeur de l\'application sur le Store : ' . $enrollmentLink);
		}
	}


	public function toHtml($_version = 'dashboard') {
		$type = $this->getConfiguration('jceqtype', 'none');
		if ($type != 'map') return parent::toHtml($_version);

		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}

		$version = jeedom::versionAlias($_version);

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'map', 'JeedomConnect')));
	}

	public static function createMapEquipment() {
		$eqMap = eqLogic::byLogicalId('jcmapwidget', 'JeedomConnect');
		if (is_object($eqMap)) return;

		$eqMap = new eqLogic();
		$eqMap->setName('Localisation');
		$eqMap->setLogicalId('jcmapwidget');
		$eqMap->setConfiguration('jceqtype', 'map');
		$eqMap->setIsEnable(1);
		$eqMap->setEqType_name(__CLASS__);
		$eqMap->save();
	}

	/*
	 ************************************************************************
	 ****************** FUNCTION TO UPDATE CONF FILE FORMAT *****************
	 ******************    AND CREATE WIDGET ACCORDINGLY    *****************
	 ************************************************************************
	 */

	public function moveToNewConfig() {
		JCLog::info('starting configuration migration for new format - equipement "' . $this->getName() . '"', '_migration');

		//get the config file brut
		$configFile = $this->getConfig(false);
		JCLog::info('original JSON configFile : ' . json_encode($configFile), '_migration');

		//if the configFile is not defined with the new format
		// ie : exist key formatVersion
		if ($configFile == '') {
			JCLog::warning('no configuration file found', '_migration');
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
			JCLog::info('updating rooms ', '_migration');
			foreach ($configFile['payload']['rooms'] as $key => $room) {
				if (
					array_key_exists('object', $room)
					&& !is_null($room['object'])
				) {

					JCLog::info('working on room "' . $room['name'] . '"', '_migration');

					$roomObject = jeeObject::byId($room['object']);

					if (is_object($roomObject)) {
						// set the name with the Jeedom One
						$currentRoom['name'] = $roomObject->getName();
					} elseif ($room['name'] == 'global') {
						// do nothing
						$currentRoom['name'] = $room['name'];
					} else {
						// if object doesnt exist in jeedom, we remove it
						JCLog::info('Room ' . $room['name'] . ' is not migrated as it is not attached to an existing jeedom object [objectId incorrect]', '_migration');
						continue;
					}

					$existingRooms[$room['id']] = $room['object'];

					// set the main id to the jeedom object id
					$currentRoom['id'] = $room['object'];

					$currentRoom['index'] = $indexRoom;

					JCLog::info('new info -- name : "' . $currentRoom['name'] . '"  -- id : ' . $currentRoom['id'], '_migration');

					//save the new widget data into the original config array
					array_push($newRoomsArray, $currentRoom);

					$indexRoom++;
				} else {
					JCLog::info('Room "' . $room['name'] . '" is not migrated as it is not attached to an existing jeedom object', '_migration');
				}
			}

			$configFile['payload']['rooms'] = $newRoomsArray;


			// manage group, and provide new id
			$groupIndex = 999000;
			$existingGroups = array();
			JCLog::info('Group objects -- BEFORE : ' . json_encode($configFile['payload']['groups']), '_migration');
			foreach ($configFile['payload']['groups'] as $key => $group) {

				$newGroup = $group;
				$existingGroups[$group['id']] = $groupIndex;
				$newGroup['id'] = $groupIndex;

				$configFile['payload']['groups'][$key] = $newGroup;
				$groupIndex += 1;
			}
			JCLog::info('Group objects with new Ids-- AFTER : ' . json_encode($configFile['payload']['groups']), '_migration');


			$widgetsIncluded = array();
			$widgetsMatching = array();

			// for each widget in config file create the associate widget equipment
			foreach ($configFile['payload']['widgets'] as $key => $widget) {
				JCLog::info('starting migration for widget "' . $widget['name'] . '"', '_migration');

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
				$widgetsConfigJonFile = json_decode(file_get_contents(self::$_plugin_config_dir . 'widgetsConfig.json'), true);
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

				JCLog::info('conf saved [DB] for widget ' . json_encode($widget), '_migration');
				JCLog::info('conf saved [file] for widget "' . json_encode($newWidget) . '"', '_migration');
			}

			// for each widget which includes other widgets (group, favourite,..)
			// we need to update the widget ID
			JCLog::info('checking widget included into other widgets', '_migration');
			foreach ($widgetsIncluded as $widget) {
				$widgetJC = JeedomConnectWidget::getConfiguration($widget, 'widgetJC');
				$conf = json_decode($widgetJC, true);
				JCLog::info('working on widget "' . $conf['name'] . '" [id:' . $conf['id'] . ']', '_migration');
				foreach ($conf['widgets'] as $index => $obj) {
					$newObj = array();
					foreach ($obj as $key => $value) {
						if ($key == 'id') {
							$newObj['id'] = $widgetsMatching[$value];
							JCLog::info('replacing widget child id "' . $value . '" with new Id "' . $widgetsMatching[$value] . '"', '_migration');
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
			JCLog::info('final config file : ' . json_encode($configFile), '_migration');

			// make a backup file
			$originalFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json';
			$backupFile = self::$_config_dir . $this->getConfiguration('apiKey') . '.json.bkp';
			copy($originalFile, $backupFile);
			JCLog::info('backup file created -- ' . $backupFile, '_migration');

			// save the file
			file_put_contents($originalFile, json_encode($configFile, JSON_PRETTY_PRINT));
			JCLog::info('new configuration file saved ', '_migration');
		} else {
			JCLog::info('Configuration file already into new format', '_migration');
		}

		return;
	}

	public static function migrationAllNotif() {
		$result = array();
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			foreach ($eqLogic->getCmd() as $cmd) {
				// JCLog::debug( '    | checking cmd : ' . $cmd->getName());
				if ($cmd->getLogicalId() != 'notifall' && strpos(strtolower($cmd->getLogicalId()), 'notif') !== false) {
					if ($cmd->getConfiguration('notifAll', false)) {
						// JCLog::debug( '    ++ adding cmd : ' . $cmd->getId());
						$cmd->setConfiguration('notifAll', '');
						$cmd->save();
						$result[] = $cmd->getId();
					}
				}
			}
		}

		config::save('notifAll', json_encode($result), 'JeedomConnect');
		config::save('migration::notifAll', 'done', 'JeedomConnect');
	}


	public static function migrateAppPref() {
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			$apiKey = $eqLogic->getLogicalId();

			$bkpDir = self::$_backup_dir . $apiKey;
			if (!is_dir($bkpDir)) continue;

			$files = JeedomConnectUtils::getFiles(realpath($bkpDir), false, false);
			foreach ($files as $item) {
				$fileInfo = pathinfo($item['path']);
				rename($item['path'], $bkpDir . '/appPref-' . $fileInfo['basename']);
			}
		}

		config::save('migration::appPref', 'done', 'JeedomConnect');
	}

	public static function migrateCustomData() {
		/** @var JeedomConnect $eqLogic */
		foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
			$apiKey = $eqLogic->getConfiguration('apiKey');
			// JCLog::debug('checking ' . $eqLogic->getName . ' [' . $apiKey . ']', '_mig')

			$customDataOriginal = config::byKey('customData::' . $apiKey, 'JeedomConnect');

			// JCLog::debug('data  ' . json_encode($customDataOriginal), '_mig')

			if (array_key_exists('widgets', $customDataOriginal)) {
				// JCLog::debug('widgets exist ! ', '_mig')
				foreach ($customDataOriginal['widgets'] as $key => $value) {
					// JCLog::debug('key : ' . $key . ' -- value :' . json_encode($value), '_mig')
					config::save('customData::' . $apiKey . '::' . $key, json_encode($value), 'JeedomConnect');
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
			/** @var JeedomConnect $eqLogic */
			foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
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
			JCLog::error('Unable to migrate Img Condition : ' . $e->getMessage());
		}
	}

	public function isConnected() {
		if ($this->getConfiguration('useWs', 0) == 0 && $this->getConfiguration('polling', 0) == 1) {
			return time() - $this->getConfiguration('lastSeen', 0) < 3;
		} else {
			return $this->getConfiguration('connected', 0) == 1;
		}
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

		// JCLog::debug( ' sending notif data ==> ' . json_encode($data));
		$eqLogic->sendNotif($this->getLogicalId(), $data);
	}

	public function execute($_options = array()) {
		if ($this->getType() != 'action') {
			return;
		}
		$eqLogic = $this->getEqLogic();

		// JCLog::debug( 'start for : ' . $this->getLogicalId());

		$logicalId = ($this->getLogicalId() === 'notifall') ? 'notifall' : ((strpos(strtolower($this->getLogicalId()), 'notif') !== false) ? 'notif' : $this->getLogicalId());

		// JCLog::debug( 'will execute action : ' . $logicalId . ' -- with option ' . json_encode($_options));

		switch ($logicalId) {
			case 'notifall':
				$cmdNotif = config::byKey('notifAll', 'JeedomConnect', array());
				$orignalCmdId = $this->getId();
				$timestamp = round(microtime(true) * 10000);
				// JCLog::debug( ' all cmd notif all : ' . json_encode($cmdNotif));

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

				// JCLog::debug( ' ----- running exec notif ! ---------');
				$myData = self::getTitleAndArgs($_options);

				if (key_exists('answer', $_options) && $myData['title'] == $_options['message']) $myData['title'] = null;

				$data = array(
					'type' => 'DISPLAY_NOTIF',
					'payload' => array(
						'cmdId' => $_options['orignalCmdId'] ?? $this->getId(),
						'title' => $myData['title'],
						'message' => $myData['args']['message'] ?? $_options['message'],
						'answer' => $_options['answer'] ?? null,
						'timeout' => $_options['timeout'] ?? null,
						'notificationId' => $_options['notificationId'] ?? round(microtime(true) * 10000),
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
				// JCLog::debug(' notif payload ==> ' . json_encode($data), '_test')
				$eqLogic->sendNotif($this->getLogicalId(), $data);
				break;

			case 'goToPage':
				if ($_options['message'] == '') {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
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
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
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
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				$payload = array(
					'action' => 'launchApp',
					'packageName' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'shellExec':
				if (empty($_options['message'])) {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				$payload = array(
					'action' => 'shellExec',
					'cmd' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'screenOn':
				$payload = array(
					'action' => 'screenOn'
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'screenOff':
				$payload = array(
					'action' => 'screenOff'
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'play_sound':
				if (empty($_options['message'])) {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				$payload = array(
					'action' => 'playSound',
					'sound' => $_options['message']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'tts':
				if (empty($_options['message'])) {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				if (!empty($_options['title'])) {
					if (((string)(int)$_options['title'] !== $_options['title']) || (intval($_options['title']) < 0 || intval($_options['title']) > 100)) {
						JCLog::error('Field "' . $this->getDisplay('title_placeholder', 'Titre') . '" has to contain integer between 0 to 100 [cmdId : ' . $this->getId() . ']');
						return;
					}
				}
				$payload = array(
					'action' => 'tts',
					'message' => $_options['message'],
					'volume' => $_options['title']
				);
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'unlink':
				$eqLogic->removeDevice();
				break;


			case 'update_pref_app':
				if (empty($_options['title'])) {
					JCLog::error('Empty field "' . $this->getDisplay('title_placeholder', 'Titre') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				if ($_options['title'] == 'none') {
					JCLog::error('Please select an action for cmd "' . $this->getName() . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				if (empty($_options['message']) && $_options['message'] != '0') {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
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

				// JCLog::debug( 'payload sent ' . json_encode($payload));
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			case 'send_sms':
				if (empty($_options['title'])) {
					JCLog::error('Empty field "' . $this->getDisplay('title_placeholder', 'Titre') . '" [cmdId : ' . $this->getId() . ']');
					return;
				}
				if (empty($_options['message'])) {
					JCLog::error('Empty field "' . $this->getDisplay('message_placeholder', 'Message') . '" [cmdId : ' . $this->getId() . ']');
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
					JCLog::error('No numero found');
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

				// JCLog::debug('payload sent ' . json_encode($payload), '_test')
				if ($eqLogic->isConnected()) {
					JeedomConnectActions::addAction($payload, $eqLogic->getLogicalId());
				} elseif ($eqLogic->getConfiguration('platformOs') == 'android') {
					$eqLogic->sendNotif($this->getLogicalId(), array('type' => 'ACTIONS', 'payload' => $payload));
				}
				break;

			default:
				JCLog::error('unknow action [' . $logicalId . '] - options :' . json_encode($_options));
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
				$optionsSupp[$key] =  trim($value, '"');
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
