<?php

namespace JeedomConnectLogic;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnect.class.php";
// require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";
// require_once dirname(__FILE__) . "/../class/JeedomConnectWidget.class.php";

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
			\JCLog::debug('Close unauthenticated client');
			$current = time();
			foreach ($this->unauthenticatedClients as $client) {
				if ($current - $client->openTimestamp > $this->authDelay) {
					// Client has been connected without authentication for too long, close connection
					\JCLog::warning("Close unauthenticated client #{$client->resourceId} from IP: {$client->ip}");
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
			\JCLog::debug('There is no more authenticated client');
		}
	}

	/**
	 * Update unauthenticated clients flag
	 */
	private function setUnauthenticatedClientsCount() {
		$this->hasUnauthenticatedClients = $this->unauthenticatedClients->count() > 0;
		if (!$this->hasUnauthenticatedClients) {
			\JCLog::debug('There is no more unauthenticated client');
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
			\JCLog::warning("Authentication failed (invalid message) for client #{$conn->resourceId} from IP: {$conn->ip}");
			$conn->close();
			return;
		}

		/** @var \JeedomConnect */
		$eqLogic = \eqLogic::byLogicalId($objectMsg->apiKey, 'JeedomConnect');

		if (!is_object($eqLogic)) {
			// Invalid API key

			// check if new apiKey has beend generated
			$hasNewApiKey = \apiHelper::isApiKeyRegenerated($objectMsg->apiKey);
			if (!$hasNewApiKey) {
				\JCLog::warning("Authentication failed (invalid credentials) for client #{$conn->resourceId} from IP: {$conn->ip}");
				$result = array('type' => 'BAD_KEY');
				$conn->send(json_encode($result));
				$conn->close();
				return;
			} else {
				$msg = \apiHelper::getApiKeyRegenerated($objectMsg->apiKey);
				\JCLog::debug('[WS] no answer for CONNECT || Sending new apiKey info-> ' . json_encode($msg));
				$conn->send(json_encode($msg));
			}
		} else {
			$param = (array) $objectMsg;

			foreach ($this->authenticatedClients as $client) {
				if ($client->apiKey == $param['apiKey']) {
					\JCLog::debug('Disconnect previous connection client ' . ${$client->resourceId});
					$client->close();
				}
			}


			$connexion = \apiHelper::dispatch('WS', 'CONNECT', $eqLogic, $param ?? array(), $param['apiKey']);
			$logs = \JeedomConnectUtils::hideSensitiveData(json_encode($connexion), 'send');
			\JCLog::debug('[WS] Send CONNECT -> ' . $logs);
			// \JCLog::debug('[WS] Send CONNECT -> ' . json_encode($connexion));
			if (
				isset($connexion['type']) &&
				in_array($connexion['type'], array(
					'BAD_DEVICE', 'EQUIPMENT_DISABLE',
					'APP_VERSION_ERROR', 'PLUGIN_VERSION_ERROR',
					'EMPTY_CONFIG_FILE', 'FORMAT_VERSION_ERROR'
				))
			) {
				$conn->send(json_encode($connexion));
				$conn->close();
				return;
			}

			$config = $eqLogic->getGeneratedConfigFile();

			$conn->apiKey = $param['apiKey'];
			$conn->sessionId = rand(0, 1000);
			$conn->configVersion = $config['payload']['configVersion'];
			$conn->lastReadTimestamp = time();
			$conn->lastHistoricReadTimestamp = time();
			$this->authenticatedClients->attach($conn);
			$this->hasAuthenticatedClients = true;

			$eqLogic->setConfiguration('sessionId', $conn->sessionId);
			$eqLogic->save(true);

			$conn->send(json_encode($connexion));
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
		\JCLog::debug("New connection: #{$conn->resourceId} from IP: {$conn->ip}");
	}

	/**
	 * Callback for incoming message from client (try to authenticate unauthenticated client)
	 *
	 * @param \Ratchet\ConnectionInterface $from Connection sending message
	 * @param string $msg Data received from the client
	 */
	public function onMessage(ConnectionInterface $from, $msg) {
		// \JCLog::debug("[WS] Incoming message from #{$from->resourceId} : {$msg}");
		$logData = \JeedomConnectUtils::hideSensitiveData($msg, 'receive');
		\JCLog::debug("[WS] Incoming message from #{$from->resourceId} : {$logData}");

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

			$result = \apiHelper::dispatch('WS', $msg['type'], $eqLogic, $msg['payload']  ?? array(), $from->apiKey ?? null);

			if (!is_null($result)) {
				if (isset($msg['messageId'])) $result['messageId'] = $msg['messageId'];

				// if (!in_array($msg['type'], \apiHelper::$_skipLog)) \JCLog::debug('[WS] Send ' . $msg['type'] . ' -> ' . json_encode($result));
				if (!in_array($msg['type'], \apiHelper::$_skipLog)) {
					$logData = \JeedomConnectUtils::hideSensitiveData(json_encode($result), 'send');
					\JCLog::debug('[WS] Send ' . $msg['type'] . ' -> ' . $logData);
				}

				return $from->send(json_encode($result));
			}
		} catch (\Exception $e) {
			$from->send(json_encode(\apiHelper::raiseException($e->getMessage(), $msg['type'])));
		}
	}

	/**
	 * Callback for connection close (remove client from lists)
	 *
	 * @param \Ratchet\ConnectionInterface $conn Connection closing
	 */
	public function onClose(ConnectionInterface $conn) {
		// Remove client from lists
		\JCLog::info("Connection #{$conn->resourceId} ({$conn->apiKey}) has disconnected");
		/** @var \JeedomConnect */
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
				$eqLogic->setConfiguration('appState', 'background');
				$eqLogic->save(true);
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
		\JCLog::error("An error has occurred: {$e->getMessage()}");
		/** @var \JeedomConnect */
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
				$eqLogic->setConfiguration('appState', 'background');
				$eqLogic->save(true);
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
			/** @var \JeedomConnect */
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if (!is_object($eqLogic)) {
				\JCLog::warning('eq not found - lookForNewConfig');
				$client->close();
				continue;
			}
			if ($eqLogic->getConfiguration('appState', '') != 'active') {
				continue;
			}
			$newConfig = \apiHelper::lookForNewConfig($eqLogic, $client->configVersion);
			if ($newConfig != false) {
				\JCLog::debug("send new config to #{$client->resourceId} with api key " . $client->apiKey);
				$client->configVersion = $newConfig['payload']['configVersion'];
				$client->send(json_encode(\apiHelper::getCmdInfoData($eqLogic->getGeneratedConfigFile())));
				$client->send(json_encode(\apiHelper::getScenarioData($eqLogic->getGeneratedConfigFile(), false, true)));
				$client->send(json_encode(array('type' => 'JEEDOM_CONFIG', 'payload' => $newConfig)));
			}
		}
	}

	private function sendActions() {
		foreach ($this->authenticatedClients as $client) {
			/** @var \JeedomConnect */
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if (!is_object($eqLogic)) {
				\JCLog::warning('eq not found - sendActions');
				$client->close();
				continue;
			}
			$actions = \JeedomConnectActions::getAllActions($client->apiKey);
			//\JCLog::debug("get action  ".json_encode($actions));
			if (count($actions) > 0) {
				$result = array(
					'type' => 'ACTIONS',
					'payload' => array()
				);
				foreach ($actions as $action) {
					array_push($result['payload'], $action['value']['payload']);
				}
				\JCLog::debug("send action to #{$client->resourceId}  " . json_encode($result));
				$client->send(json_encode($result));
				\JeedomConnectActions::removeActions($actions);
			}
		}
	}

	private function broadcastEvents() {
		foreach ($this->authenticatedClients as $client) {
			/** @var \JeedomConnect */
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			if (!is_object($eqLogic)) {
				\JCLog::warning('eq not found - broadcastEvents');
				$client->close();
				continue;
			}
			if ($eqLogic->getConfiguration('appState', '') != 'active') {
				continue;
			}

			$eventsRes = \apiHelper::getEventsFull($eqLogic, $client->lastReadTimestamp, $client->lastHistoricReadTimestamp);
			$client->lastReadTimestamp = $eventsRes[0]['payload'];
			$client->lastHistoricReadTimestamp = $eventsRes[1]['payload'];

			foreach ($eventsRes as $res) {
				if (key_exists('payload', $res) && is_array($res['payload']) && count($res['payload']) > 0) {
					\JCLog::debug("Broadcast to {$client->resourceId} : " . json_encode($res));
					$client->send(json_encode($res));
				}
			}
		}
	}
}
