<?php

namespace JeedomConnectLogic;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectWidget.class.php";

class ConnectLogic implements MessageComponentInterface {

	/**
	 * @var \SplObjectStorage List of unauthenticated clients (waiting for authentication message)
	 */
	private $unauthenticatedClients;

	/**
	 * @var \SplObjectStorage List of authenticated clients (receiving events broadcasts)
	 */
	private $authenticatedClients;

	/**
	 * @var bool Has authenticated clients (need to read events)
	 */
	private $hasAuthenticatedClients;

	/**
	 * @var bool Has unauthenticated clients (need to check authentication delay and maybe close connection)
	 */
	private $hasUnauthenticatedClients;


	/**
	 * Notifier constructor
	 */
	public function __construct($versionJson) {
		$this->unauthenticatedClients = new \SplObjectStorage;
		$this->authenticatedClients = new \SplObjectStorage;
		$this->hasAuthenticatedClients = false;
		$this->hasUnauthenticatedClients = false;
		$this->authDelay = 3;
		$this->pluginVersion = $versionJson->version;
		$this->appRequire = $versionJson->require;
	}


	/**
	 * Process the logic (read events and broadcast to authenticated clients, close authenticated clients)
	 */
	public function process() {
		if ($this->hasUnauthenticatedClients) {
			// Check is there is unauthenticated clients for too long
			\log::add('JeedomConnect', 'debug', 'Close unauthenticated client');
			$current = time();
			foreach ($this->unauthenticatedClients as $client) {
				if ($current - $client->openTimestamp > $this->authDelay) {
					// Client has been connected without authentication for too long, close connection
					\log::add('JeedomConnect', 'warning', "Close unauthenticated client #{$client->resourceId} from IP: {$client->ip}");
					$client->close();
				}
			}
		}

		if ($this->hasAuthenticatedClients) {
			$this->lookForNewConfig();
			$this->sendActions();
			$this->broadcastEvents();
		}
	}


	/**
	 * Update authenticated clients flag
	 */
	private function setAuthenticatedClientsCount() {
		$this->hasAuthenticatedClients = $this->authenticatedClients->count() > 0;
		if (!$this->hasAuthenticatedClients) {
			\log::add('JeedomConnect', 'debug', 'There is no more authenticated client');
		}
	}

	/**
	 * Update unauthenticated clients flag
	 */
	private function setUnauthenticatedClientsCount() {
		$this->hasUnauthenticatedClients = $this->unauthenticatedClients->count() > 0;
		if (!$this->hasUnauthenticatedClients) {
			\log::add('JeedomConnect', 'debug', 'There is no more unauthenticated client');
		}
	}

	/**
	 * Authenticate client
	 *
	 * @param \Ratchet\ConnectionInterface $conn Connection to authenticate
	 */
	private function authenticate(ConnectionInterface $conn, $msg) {
		// Remove client from unauthenticated clients list
		$this->unauthenticatedClients->detach($conn);
		$this->setUnauthenticatedClientsCount();
		// Parse message
		$objectMsg = json_decode($msg);
		if ($objectMsg === null || !property_exists($objectMsg, 'apiKey') || !property_exists($objectMsg, 'userHash') || !property_exists($objectMsg, 'deviceId') || !property_exists($objectMsg, 'token')) {
			\log::add('JeedomConnect', 'warning', "Authentication failed (invalid message) for client #{$conn->resourceId} from IP: {$conn->ip}");
			$conn->close();
			return;
		}

		$eqLogic = \eqLogic::byLogicalId($objectMsg->apiKey, 'JeedomConnect');

		if (!is_object($eqLogic)) {
			// Invalid API key
			\log::add('JeedomConnect', 'warning', "Authentication failed (invalid credentials) for client #{$conn->resourceId} from IP: {$conn->ip}");
			$result = array('type' => 'BAD_KEY');
			$conn->send(json_encode($result));
			$conn->close();
			return;
		} else {
			$config = $eqLogic->getGeneratedConfigFile();
			if ($eqLogic->getConfiguration('deviceId') == '') {
				\log::add('JeedomConnect', 'info', "Register new device {$objectMsg->deviceName}");
				$eqLogic->registerDevice($objectMsg->deviceId, $objectMsg->deviceName);
			}
			$eqLogic->registerToken($objectMsg->token);

			//check registered device
			if ($eqLogic->getConfiguration('deviceId') != $objectMsg->deviceId) {
				\log::add('JeedomConnect', 'warning', "Authentication failed (invalid device) for client #{$conn->resourceId} from IP: {$conn->ip}");
				$result = array('type' => 'BAD_DEVICE');
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			//check version requierement
			if (version_compare($objectMsg->appVersion, $this->appRequire, "<")) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad version requierement");
				$result = array(
					'type' => 'APP_VERSION_ERROR',
					'payload' => \JeedomConnect::getPluginInfo()
				);
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}
			if (version_compare($this->pluginVersion, $objectMsg->pluginRequire, "<")) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad plugin requierement");
				$result = array('type' => 'PLUGIN_VERSION_ERROR');
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			//close previous connection from the same client
			foreach ($this->authenticatedClients as $client) {
				if ($client->apiKey == $objectMsg->apiKey) {
					\log::add('JeedomConnect', 'debug', 'Disconnect previous connection client ' . ${$client->resourceId});
					$client->close();
				}
			}

			$user = \user::byId($eqLogic->getConfiguration('userId'));
			if ($user == null) {
				$user = \user::all()[0];
				$eqLogic->setConfiguration('userId', $user->getId());
				$eqLogic->save();
			}

			$userConnected = \user::byHash($objectMsg->userHash);
			if (!is_object($userConnected)) $userConnected = $user;

			//check config content
			if (is_null($config)) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : empty config file");
				$result = array('type' => 'EMPTY_CONFIG_FILE');
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			//check config format version
			if (!array_key_exists('formatVersion', $config)) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad format version");
				$result = array('type' => 'FORMAT_VERSION_ERROR');
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			$conn->apiKey = $objectMsg->apiKey;
			$conn->sessionId = rand(0, 1000);
			$conn->configVersion = $config['payload']['configVersion'];
			$conn->lastReadTimestamp = time();
			$this->authenticatedClients->attach($conn);
			$this->hasAuthenticatedClients = true;
			$eqLogic->setConfiguration('platformOs', $objectMsg->platformOs);
			$eqLogic->setConfiguration('appVersion', $objectMsg->appVersion ?? '#NA#');
			$eqLogic->setConfiguration('polling', $objectMsg->polling ?? '0');
			$eqLogic->setConfiguration('sessionId', $conn->sessionId);
			$eqLogic->setConfiguration('connected', 1);
			$eqLogic->setConfiguration('scAll', 0);
			$eqLogic->setConfiguration('appState', 'active');
			$eqLogic->save();
			\log::add('JeedomConnect', 'info', "#{$conn->resourceId} is authenticated with api Key '{$conn->apiKey}'");
			$result = array(
				'type' => 'WELCOME',
				'payload' => array(
					'pluginVersion' => $this->pluginVersion,
					'useWs' => $eqLogic->getConfiguration('useWs', 0),
					'userHash' => $userConnected->getHash(),
					'userId' => $userConnected->getId(),
					'userName' => $userConnected->getLogin(),
					'userProfil' => $userConnected->getProfils(),
					'configVersion' => $config['payload']['configVersion'],
					'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
					'webviewEnabled' => $eqLogic->getConfiguration('webviewEnabled') == '1',
					'editEnabled' => $eqLogic->getConfiguration('editEnabled') == '1',
					'pluginConfig' => \apiHelper::getPluginConfig($eqLogic, false),
					'cmdInfo' => \apiHelper::getCmdInfoData($config, false),
					'scInfo' => \apiHelper::getScenarioData($config, false, false),
					'objInfo' => \apiHelper::getObjectData($config, false),
					'links' => \JeedomConnectUtils::getLinks()
				)
			);
			\log::add('JeedomConnect', 'info', "Send " . json_encode($result));
			$conn->send(json_encode($result));
		}
	}

	/**
	 * Callback for connection open (add to unauthenticated clients list)
	 *
	 * @param \Ratchet\ConnectionInterface $conn Connection to authenticate
	 */
	public function onOpen(ConnectionInterface $conn) {
		// Add some useful informations
		$conn->openTimestamp = time();
		$conn->apiKey = '?';
		if ($conn->httpRequest->hasHeader('X-Forwarded-For')) {
			$conn->ip = $conn->httpRequest->getHeader('X-Forwarded-For')[0];
		} else {
			$conn->ip = '?';
		}
		// Add client to unauthenticated clients list for handling his unauthentication
		$this->unauthenticatedClients->attach($conn);
		$this->hasUnauthenticatedClients = true;
		\log::add('JeedomConnect', 'debug', "New connection: #{$conn->resourceId} from IP: {$conn->ip}");
	}

	/**
	 * Callback for incoming message from client (try to authenticate unauthenticated client)
	 *
	 * @param \Ratchet\ConnectionInterface $from Connection sending message
	 * @param string $msg Data received from the client
	 */
	public function onMessage(ConnectionInterface $from, $msg) {
		\log::add('JeedomConnect', 'debug', "Incoming message from #{$from->resourceId} : {$msg}");
		if ($this->unauthenticatedClients->contains($from)) {
			// this is a message from an unauthenticated client, check if it contains credentials
			$this->authenticate($from, $msg);
		}

		$msg = json_decode($msg, true);
		if ($msg == null) {
			return;
		}
		if (!array_key_exists('type', $msg)) {
			return;
		}
		try {
			$eqLogic = isset($from->apiKey) ?  \eqLogic::byLogicalId($from->apiKey, 'JeedomConnect') : null;
			switch ($msg['type']) {
				case 'CMD_EXEC':
					\apiHelper::execCmd($msg['payload']['id'], $msg['payload']['options'] ?? null);
					break;
				case 'CMDLIST_EXEC':
					\apiHelper::execMultipleCmd($msg['payload']['cmdList']);
					break;
				case 'SC_EXEC':
					\apiHelper::execSc($msg['payload']['id'], $msg['payload']['options']);
					break;
				case 'SC_STOP':
					\apiHelper::stopSc($msg['payload']['id']);
					break;
				case 'SC_SET_ACTIVE':
					\apiHelper::setActiveSc($msg['payload']['id'], $msg['payload']['active']);
					break;
				case 'GET_PLUGIN_CONFIG':
					$conf = \apiHelper::getPluginConfig($eqLogic);
					\log::add('JeedomConnect', 'debug', "Send : " . json_encode($conf));
					$from->send(json_encode($conf));
					break;
				case 'GET_CONFIG':
					$config = $eqLogic->getGeneratedConfigFile();
					\log::add('JeedomConnect', 'debug', "Send : " . json_encode($config));
					$from->send(json_encode($config));
					break;
				case 'GET_BATTERIES':
					$config = \apiHelper::getBatteries();
					$from->send(json_encode($config));
					break;
				case 'GET_CMD_INFO':
					$result = \apiHelper::getCmdInfoData($eqLogic->getGeneratedConfigFile());
					$from->send(json_encode($result));
					break;
				case 'GET_OBJ_INFO':
					$result = \apiHelper::getObjectData($eqLogic->getGeneratedConfigFile());
					$from->send(json_encode($result));
					break;
				case 'GET_SC_INFO':
					$result = \apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile(), false, true);
					$from->send(json_encode($result));
					break;
				case 'GET_ALL_SC':
					$result = \apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile(), true, true);
					$from->send(json_encode($result));
					break;
				case 'GET_INFO':
					$result = \apiHelper::getAllInformations($eqLogic);
					$result['messageId'] = $msg['messageId'];
					\log::add('JeedomConnect', 'info', '[WS] Send info ' . json_encode($result));
					$from->send(json_encode($result));
					break;
				case 'GET_JEEDOM_DATA':
					$result = \apiHelper::getFullJeedomData();
					\log::add('JeedomConnect', 'debug', "Send : " . json_encode($result));
					$from->send(json_encode($result));
					break;
				case 'GET_WIDGET_DATA':
					$result = \apiHelper::getWidgetData();
					$from->send(json_encode($result));
					break;
				case 'GET_WIDGET_WITH_GEN_TYPE':
					$result = \apiHelper::getWidgetFromGenType($msg['payload']['widget_type'], $msg['payload']['eqId'] ?? null);
					$result['messageId'] = $msg['messageId'];
					$from->send(json_encode($result));
					break;
				case 'GET_PLUGINS_UPDATE':
					$pluginUpdate = \apiHelper::getPluginsUpdate();
					$from->send(json_encode($pluginUpdate));
					break;
				case 'DO_PLUGIN_UPDATE':
					$result = \apiHelper::doUpdate($msg['payload']['pluginId']);
					$from->send(json_encode(array('result' => $result)));
					break;
				case 'GET_JEEDOM_GLOBAL_HEALTH':
					$health = \apiHelper::getJeedomHealthDetails($from->apiKey);
					$from->send(json_encode($health));
					break;
				case 'DAEMON_PLUGIN_RESTART':
					$result = \apiHelper::restartDaemon($msg['payload']['userId'], $msg['payload']['pluginId']);
					$from->send(json_encode(array('result' => $result)));
					break;
				case 'DAEMON_PLUGIN_STOP':
					$result = \apiHelper::stopDaemon($msg['payload']['userId'], $msg['payload']['pluginId']);
					$from->send(json_encode(array('result' => $result)));
					break;
				case 'UNSUBSCRIBE_SC':
					$eqLogic->setConfiguration('scAll', 0);
					$eqLogic->save();
					break;
				case 'GET_HISTORY':
					$from->send(json_encode(\apiHelper::getHistory($msg['payload']['id'], $msg['payload']['options'])));
					break;
				case 'GET_FILES':
					$from->send(json_encode(\apiHelper::getFiles($msg['payload']['folder'], $msg['payload']['recursive'])));
					break;
				case 'REMOVE_FILE':
					$from->send(json_encode(\apiHelper::removeFile($msg['payload']['file'])));
					break;
				case 'SET_BATTERY':
					\apiHelper::saveBatteryEquipment($from->apiKey, $msg['payload']['level']);
					break;
				case 'SET_WIDGET':
					\apiHelper::setWidget($msg['payload']['widget']);
					break;
				case 'ADD_WIDGETS':
					\apiHelper::addWidgets($eqLogic, $msg['payload']['widgets'], $msg['payload']['parentId'], $msg['payload']['index']);
					break;
				case 'REMOVE_WIDGET':
					\apiHelper::removeWidget($eqLogic, $msg['payload']['widgetId']);
					break;
				case 'MOVE_WIDGET':
					\apiHelper::moveWidget($eqLogic, $msg['payload']['widgetId'], $msg['payload']['destinationId'], $msg['payload']['destinationIndex']);
					break;
				case 'SET_CUSTOM_WIDGETS':
					\apiHelper::setCustomWidgetList($eqLogic, $msg['payload']['customWidgetList']);
					break;
				case 'SET_GROUP':
					\apiHelper::setGroup($eqLogic, $msg['payload']['group']);
					break;
				case 'REMOVE_GROUP':
					\apiHelper::removeGroup($eqLogic, $msg['payload']['id']);
					break;
				case 'ADD_GROUP':
					\apiHelper::addGroup($eqLogic, $msg['payload']['group']);
					break;
				case 'MOVE_GROUP':
					\apiHelper::moveGroup($eqLogic, $msg['payload']['groupId'], $msg['payload']['destinationId'], $msg['payload']['destinationIndex']);
					break;
				case 'REMOVE_GLOBAL_WIDGET':
					\apiHelper::removeGlobalWidget($msg['payload']['id']);
					break;
				case 'ADD_GLOBAL_WIDGETS':
					$result = \apiHelper::addGlobalWidgets($msg['payload']['widgets']);
					$result['messageId'] = $msg['messageId'];
					$from->send(json_encode($result));
					break;
				case 'SET_BOTTOM_TABS':
					\apiHelper::setBottomTabList($eqLogic, $msg['payload']['tabs'], $msg['payload']['migrate'], $msg['payload']['idCounter']);
					break;
				case 'REMOVE_BOTTOM_TAB':
					\apiHelper::removeBottomTab($eqLogic, $msg['payload']['id']);
					break;
				case 'SET_TOP_TABS':
					\apiHelper::setTopTabList($eqLogic, $msg['payload']['tabs'], $msg['payload']['migrate'], $msg['payload']['idCounter']);
					break;
				case 'REMOVE_TOP_TAB':
					\apiHelper::removeTopTab($eqLogic, $msg['payload']['id']);
					break;
				case 'MOVE_TOP_TAB':
					\apiHelper::moveTopTab($eqLogic, $msg['payload']['sectionId'], $msg['payload']['destinationId']);
					break;
				case 'SET_PAGE_DATA':
					\apiHelper::setPageData($eqLogic, $msg['payload']['rootData'], $msg['payload']['idCounter']);
					break;
				case 'SET_ROOMS':
					\apiHelper::setRooms($eqLogic, $msg['payload']['rooms']);
					break;
				case 'SET_SUMMARIES':
					\apiHelper::setSummaries($eqLogic, $msg['payload']['summaries']);
					break;
				case 'SET_BACKGROUNDS':
					\apiHelper::setBackgrounds($eqLogic, $msg['payload']['backgrounds']);
					break;
				case 'SET_APP_CONFIG':
					\apiHelper::setAppConfig($from->apiKey, $msg['payload']['config']);
					break;
				case 'GET_APP_CONFIG':
					$from->send(json_encode(\apiHelper::getAppConfig($from->apiKey, $msg['payload']['configId'])));
					break;
				case 'ADD_GEOFENCE':
					\apiHelper::addGeofence($eqLogic, $msg['payload']['geofence']);
					break;
				case 'REMOVE_GEOFENCE':
					\apiHelper::removeGeofence($eqLogic, $msg['payload']['geofence']);
					break;
				case 'GET_GEOFENCES':
					$result = \apiHelper::getGeofencesData($eqLogic);
					if (count($result['payload']['geofences']) > 0) {
						\log::add('JeedomConnect', 'info', 'Send ' . json_encode($result));
						$from->send(json_encode($result));
					}
					break;
				case 'GET_NOTIFS_CONFIG':
					$result = \apiHelper::getNotifConfig($eqLogic);
					$from->send(json_encode($result));
					break;

				default:
					$from->send(json_encode(\apiHelper::raiseException($msg['type'], '- method not recognized')));
					break;
			}
		} catch (\Exception $e) {
			$from->send(json_encode(\apiHelper::raiseException($msg['type'], '- ' . $e->getMessage())));
		}
	}

	/**
	 * Callback for connection close (remove client from lists)
	 *
	 * @param \Ratchet\ConnectionInterface $conn Connection closing
	 */
	public function onClose(ConnectionInterface $conn) {
		// Remove client from lists
		\log::add('JeedomConnect', 'info', "Connection #{$conn->resourceId} ({$conn->apiKey}) has disconnected");
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
				$eqLogic->setConfiguration('appState', 'background');
				$eqLogic->save();
			}
		}
		$this->unauthenticatedClients->detach($conn);
		$this->authenticatedClients->detach($conn);
		$this->setAuthenticatedClientsCount();
		$this->setUnauthenticatedClientsCount();
	}

	/**
	 * Callback for connection error (remove client from lists)
	 *
	 * @param \Ratchet\ConnectionInterface $conn Connection in error
	 * @param \Exception $e Exception encountered
	 */
	public function onError(ConnectionInterface $conn, \Exception $e) {
		\log::add('JeedomConnect', 'error', "An error has occurred: {$e->getMessage()}");
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
				$eqLogic->setConfiguration('appState', 'background');
				$eqLogic->save();
			}
		}
		$conn->close();
		// Remove client from lists
		$this->unauthenticatedClients->detach($conn);
		$this->authenticatedClients->detach($conn);
		$this->setAuthenticatedClientsCount();
		$this->setUnauthenticatedClientsCount();
	}


	public function lookForNewConfig() {
		foreach ($this->authenticatedClients as $client) {
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if ($eqLogic->getConfiguration('appState', '') != 'active') {
				continue;
			}
			$newConfig = \apiHelper::lookForNewConfig($eqLogic, $client->configVersion);
			if ($newConfig != false) {
				$config = $newConfig;
				\log::add('JeedomConnect', 'debug', "send new config to #{$client->resourceId} with api key " . $client->apiKey);
				$client->configVersion = $newConfig['payload']['configVersion'];
				$client->send(json_encode(\apiHelper::getCmdInfoData($eqLogic->getGeneratedConfigFile())));
				$client->send(json_encode(\apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile(), false, true)));
				$client->send(json_encode($newConfig));
			}
		}
	}

	private function sendActions() {
		foreach ($this->authenticatedClients as $client) {
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if ($eqLogic->getConfiguration('appState', '') != 'active') {
				continue;
			}
			$actions = \JeedomConnectActions::getAllActions($client->apiKey);
			//\log::add('JeedomConnect', 'debug', "get action  ".json_encode($actions));
			if (count($actions) > 0) {
				$result = array(
					'type' => 'ACTIONS',
					'payload' => array()
				);
				foreach ($actions as $action) {
					array_push($result['payload'], $action['value']['payload']);
				}
				\log::add('JeedomConnect', 'debug', "send action to #{$client->resourceId}  " . json_encode($result));
				$client->send(json_encode($result));
				\JeedomConnectActions::removeActions($actions);
			}
		}
	}

	private function broadcastEvents() {
		foreach ($this->authenticatedClients as $client) {
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if ($eqLogic->getConfiguration('appState', '') != 'active') {
				continue;
			}
			$events = \event::changes($client->lastReadTimestamp);
			$client->lastReadTimestamp = time();
			$config = $eqLogic->getGeneratedConfigFile();
			if (count($events['result']) == 0) {
				// \log::add('JeedomConnect', 'debug', '--- nothing in cache (' . count($events['result']) . ')');
				return;
			} elseif (count($events['result']) < 250) {
				// \log::add('JeedomConnect', 'debug', '--- using cache (' . count($events['result']) . ')');
				$eventsRes = \apiHelper::getEvents($events, $config, $eqLogic->getConfiguration('scAll', 0) == 1);
			} else {
				// \log::add('JeedomConnect', 'debug', '*****  too many items, refresh all (' . count($events['result']) . ')');
				$eventsRes = \apiHelper::getCmdInfoData($config, false);
			}

			foreach ($eventsRes as $res) {
				if (count($res['payload']) > 0) {
					\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : " . json_encode($res));
					$client->send(json_encode($res));
				}
			}
		}
	}
}
