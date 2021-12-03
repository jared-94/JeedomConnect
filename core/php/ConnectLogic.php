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
			$param = (array) $objectMsg;

			foreach ($this->authenticatedClients as $client) {
				if ($client->apiKey == $param['apiKey']) {
					\log::add('JeedomConnect', 'debug', 'Disconnect previous connection client ' . ${$client->resourceId});
					$client->close();
				}
			}


			$connexion = \apiHelper::dispatch('WS', 'CONNECT', $eqLogic, $param, $param['apiKey']);

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
			$this->authenticatedClients->attach($conn);
			$this->hasAuthenticatedClients = true;

			$eqLogic->setConfiguration('sessionId', $conn->sessionId);
			$eqLogic->save();

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
		\log::add('JeedomConnect', 'debug', "New connection: #{$conn->resourceId} from IP: {$conn->ip}");
	}

	/**
	 * Callback for incoming message from client (try to authenticate unauthenticated client)
	 *
	 * @param \Ratchet\ConnectionInterface $from Connection sending message
	 * @param string $msg Data received from the client
	 */
	public function onMessage(ConnectionInterface $from, $msg) {
		\log::add('JeedomConnect', 'debug', "[WS] Incoming message from #{$from->resourceId} : {$msg}");
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

			$result = \apiHelper::dispatch('WS', $msg['type'], $eqLogic, $msg['payload']  ?? null, $from->apiKey ?? null);

			if (!is_null($result)) {
				// if (in_array($msg['type'], array())) {
				if (isset($msg['messageId'])) $result['messageId'] = $msg['messageId'];
				// }
				\log::add('JeedomConnect', 'debug', '[WS] Send ' . $msg['type'] . ' -> ' . json_encode($result));
				return $from->send(json_encode($result));
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

			$eventsRes = \apiHelper::getEventsFull($eqLogic, $client->lastReadTimestamp);
			$client->lastReadTimestamp = time();

			foreach ($eventsRes as $res) {
				if (count($res['payload']) > 0) {
					\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : " . json_encode($res));
					$client->send(json_encode($res));
				}
			}
		}
	}
}
