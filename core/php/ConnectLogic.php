<?php
namespace JeedomConnectLogic;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


class ConnectLogic implements MessageComponentInterface
{

		private $configList = array();
		private $apiKeyList = array();
		private $pluginVersion;
		private $pluginVersion;

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
     * @var int Timestamp of the last events read
     */
    private $lastReadTimestamp;

    /**
     * Notifier constructor
     */
    public function __construct($pluginVersion, $appRequire) {
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$apiKey = $eqLogic->getConfiguration('apiKey');
				array_push($this->apiKeyList, $apiKey);
				$this->configList[$apiKey] = $eqLogic->getConfig();
			}
      $this->unauthenticatedClients = new \SplObjectStorage;
      $this->authenticatedClients = new \SplObjectStorage;
      $this->hasAuthenticatedClients = false;
      $this->hasUnauthenticatedClients = false;
      $this->authDelay = 2;
			$this->pluginVersion = $pluginVersion;
			$this->appRequire = $appRequire;
      $this->lastReadTimestamp = time();
    }


    /**
     * Process the logic (read events and broadcast to authenticated clients, close authenticated clients)
     */
    public function process()
    {
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
		$this->lookForNewConfig();
        if ($this->hasAuthenticatedClients) {
            // Read events from Jeedom
            $events = \event::changes($this->lastReadTimestamp);
			$this->lastReadTimestamp = time();
            $this->broadcastEvents($events);
        }
    }


    /**
     * Update authenticated clients flag
     */
    private function setAuthenticatedClientsCount()
    {
        $this->hasAuthenticatedClients = $this->authenticatedClients->count() > 0;
        if (!$this->hasAuthenticatedClients) {
            \log::add('JeedomConnect', 'debug', 'There is no more authenticated client');
        }
    }

    /**
     * Update unauthenticated clients flag
     */
    private function setUnauthenticatedClientsCount()
    {
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
    private function authenticate(ConnectionInterface $conn, $msg)
    {
        // Remove client from unauthenticated clients list
        $this->unauthenticatedClients->detach($conn);
        $this->setUnauthenticatedClientsCount();
        // Parse message
        $objectMsg = json_decode($msg);
        if ($objectMsg === null || !property_exists($objectMsg, 'apiKey') || !property_exists($objectMsg, 'deviceId') || !property_exists($objectMsg, 'token')) {
            \log::add('JeedomConnect', 'warning', "Authentication failed (invalid message) for client #{$conn->resourceId} from IP: {$conn->ip}");
            $conn->close();
            return;
        }

        if (!in_array($objectMsg->apiKey, $this->apiKeyList)) {
            // Invalid API key
            \log::add('JeedomConnect', 'warning', "Authentication failed (invalid credentials) for client #{$conn->resourceId} from IP: {$conn->ip}");
						$result = array( 'type' => 'BAD_KEY' );
						$conn->send(json_encode($result));
            $conn->close();
        } else {
            if (!$this->hasAuthenticatedClients) {
                // It is the first client, we store current timestamp for fetching events since this moment
                $this->lastReadTimestamp = time();
            }

			$eqLogic = \eqLogic::byLogicalId($objectMsg->apiKey, 'JeedomConnect');
			if ($eqLogic->getConfiguration('deviceId') == '') {
				\log::add('JeedomConnect', 'info', "Register new device {$objectMsg->deviceName}");
				$eqLogic->registerDevice($objectMsg->deviceId, $objectMsg->deviceName);
			}
			$eqLogic->registerToken($objectMsg->token);

			//check registered device
			if ($eqLogic->getConfiguration('deviceId') != $objectMsg->deviceId) {
				\log::add('JeedomConnect', 'warning', "Authentication failed (invalid device) for client #{$conn->resourceId} from IP: {$conn->ip}");
				$result = array( 'type' => 'BAD_DEVICE' );
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			//check version requierement
			if (version_compare($this->appVersion, $objectMsg->appRequire, "<")) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad version requierement");
				$result = array( 'type' => 'APP_VERSION_ERROR' );
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}
			if (version_compare($this->pluginRequire, $objectMsg->pluginVersion, "<")) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad plugin requierement");
				$result = array( 'type' => 'PLUGIN_VERSION_ERROR' );
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}

			//close previous connection from the same client
			foreach ($this->authenticatedClients as $client) {
				if ($client->apiKey == $objectMsg->apiKey) {
					\log::add('JeedomConnect', 'debug', "Disconnect previous connection client ${$client->resourceId}");
					$client->close();
				}
			}


      $conn->apiKey = $objectMsg->apiKey;
      $this->authenticatedClients->attach($conn);
      $this->hasAuthenticatedClients = true;
      \log::add('JeedomConnect', 'info', "#{$conn->resourceId} is authenticated with api Key '{$conn->apiKey}'");
			$result = array(
				'type' => 'WELCOME',
				'payload' => array(
					'pluginVersion' => $this->pluginVersion,
					'configVersion' => $this->configList[$objectMsg->apiKey]['payload']['configVersion'],
					'jeedomURL' => \config::byKey('httpUrl', 'JeedomConnect', \config::byKey('externalProtocol') . \config::byKey('externalAddr'))
				)
			);
			\log::add('JeedomConnect', 'info', "Send ".json_encode($result));
			$conn->send(json_encode($result));
        }
    }

    /**
     * Callback for connection open (add to unauthenticated clients list)
     *
     * @param \Ratchet\ConnectionInterface $conn Connection to authenticate
     */
    public function onOpen(ConnectionInterface $conn)
    {
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
        \log::add('JeedomConnect', 'info', "New connection: #{$conn->resourceId} from IP: {$conn->ip}");
        \log::add('JeedomConnect', 'debug', 'New connection headers: '.json_encode($conn->httpRequest->getHeaders()));
    }

    /**
     * Callback for incoming message from client (try to authenticate unauthenticated client)
     *
     * @param \Ratchet\ConnectionInterface $from Connection sending message
     * @param string $msg Data received from the client
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        \log::add('JeedomConnect', 'debug', "Incoming message from #{$from->resourceId} : {$msg}");
        if ($this->unauthenticatedClients->contains($from)) {
            // this is a message from an unauthenticated client, check if it contains credentials
            $this->authenticate($from, $msg);
        }

		$msg = json_decode($msg, true);
		if ($msg == null) {
			return;
		}
		switch ($msg['type']) {
			case 'CMD_EXEC':
				$cmd = \cmd::byId($msg['payload']['id']);
				if (!is_object($cmd)) {
					\log::add('JeedomConnect', 'error', "Can't find command");
					return;
				}
				$cmd->execCmd($option = $msg['payload']['options']);
				break;
			case 'SC_EXEC':
				$sc = \scenario::byId($msg['payload']['id']);
				$sc->launch();
				break;
			case 'SC_STOP':
				$sc = \scenario::byId($msg['payload']['id']);
				$sc->stop();
				break;
			case 'SC_SET_ACTIVE':
				$sc = \scenario::byId($msg['payload']['id']);
				$sc->setIsActive($msg['payload']['active']);
				$sc->save();
				break;
			case 'GET_CONFIG':
				\log::add('JeedomConnect', 'debug', "Send : ".json_encode($this->configList[$from->apiKey]));
				$from->send(json_encode($this->configList[$from->apiKey]));
				break;
			case 'GET_CMD_INFO':
				$this->sendCmdInfo($from);
				break;
			case 'GET_SC_INFO':
				$this->sendScenarioInfo($from);
				break;
			case 'GET_HISTORY':
				$this->sendHistory($from, $msg['payload']['id'], $msg['payload']['options']);
				break;
		}

    }

    /**
     * Callback for connection close (remove client from lists)
     *
     * @param \Ratchet\ConnectionInterface $conn Connection closing
     */
    public function onClose(ConnectionInterface $conn)
    {
        // Remove client from lists
        \log::add('JeedomConnect', 'info', "Connection #{$conn->resourceId} ({$conn->apiKey}) has disconnected");
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
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        \log::add('JeedomConnect', 'error', "An error has occurred: {$e->getMessage()}");
        $conn->close();
        // Remove client from lists
        $this->unauthenticatedClients->detach($conn);
        $this->authenticatedClients->detach($conn);
        $this->setAuthenticatedClientsCount();
        $this->setUnauthenticatedClientsCount();
    }


	public function lookForNewConfig() {
		$eqLogicKeys = array();
		foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
			$apiKey = $eqLogic->getConfiguration('apiKey');
			array_push($eqLogicKeys, $apiKey);
			if (in_array($apiKey, $this->apiKeyList)) {
				$configVersion = $eqLogic->getConfiguration('configVersion');
				if ($configVersion != $this->configList[$apiKey]['payload']['configVersion']) {
					\log::add('JeedomConnect', 'debug', "New configuration for device ".$apiKey);
					$this->configList[$apiKey] = $eqLogic->getConfig();
					array_push($apiKey, $this->apiKeyList);
					foreach ($this->authenticatedClients as $client) {
						if ($client->apiKey == $apiKey) {
							\log::add('JeedomConnect', 'debug', "send new config to #{$client->resourceId} : ".json_encode($this->configList[$apiKey]));
							$client->send(json_encode($this->configList[$apiKey]));
							$this->sendCmdInfo($client);
							$this->sendScenarioInfo($client);
							return;
						}
					}
				}
			} else {
				\log::add('JeedomConnect', 'info', "New device with key ".$apiKey);
				array_push($this->apiKeyList, $apiKey);
				$this->configList[$apiKey] = $eqLogic->getConfig();
			}
		}
		//Remove deleted configs
		foreach ($this->apiKeyList as $i => $key) {
			if (!in_array($key, $eqLogicKeys)) {
				\log::add('JeedomConnect', 'info', "Remove device with key ".$apiKey);
				foreach ($this->authenticatedClients as $client) {
					if ($client->apiKey == $key) {
						$client->close();
					}
				}
				unset($this->configList[$key]);
				unset($this->apiKeyList[$i]);
			}
		}
	}

	private function broadcastEvents($events) {
		foreach ($this->configList as $key => $config) {
			$result_cmd = array(
				'type' => 'CMD_INFO',
				'payload' => array()
			);
			$infoIds = $this->getInfoCmds($config);
			$result_sc = array(
				'type' => 'SC_INFO',
				'payload' => array()
			);
			$scIds = $this->getScenarioIds($config);

			foreach ($events['result'] as $event) {
				if ($event['name'] == 'scenario::update') {
					if (in_array($event['option']['scenario_id'], $scIds) ) {
						$sc_info = array(
							'id' => $event['option']['scenario_id'],
							'status' => $event['option']['state'],
							'lastLaunch' => strtotime($event['option']['lastLaunch'])
						);
						if (array_key_exists('isActive', $event['option'])) {
							$sc_info['active'] = $event['option']['isActive'];
						}
						array_push($result_sc['payload'], $sc_info);
					}
				}
				if ($event['name'] == 'cmd::update') {
					if (in_array($event['option']['cmd_id'], $infoIds) ) {
						$cmd_info = array(
							'id' => $event['option']['cmd_id'],
							'value' => $event['option']['value'],
							'modified' => strtotime($event['option']['valueDate'])
							);
						array_push($result_cmd['payload'], $cmd_info);
					}
				}
			}
			if (count($result_cmd['payload']) > 0) {
				foreach ($this->authenticatedClients as $client) {
					if ($client->apiKey == $key) {
						\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : ".json_encode($result_cmd));
						$client->send(json_encode($result_cmd));
					}
				}
			}
			if (count($result_sc['payload']) > 0) {
				foreach ($this->authenticatedClients as $client) {
					if ($client->apiKey == $key) {
						\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : ".json_encode($result_sc));
						$client->send(json_encode($result_sc));
					}
				}
			}
		}
	}

	private function getInfoCmds($config) {
		$return = array();
		foreach ($config['payload']['widgets'] as $widget) {
			foreach ($widget as $item => $value) {
				if (substr_compare($item, 'Info', strlen($item)-4, 4) === 0) {
					array_push($return, $value);
				}
			}
		}
		return $return;
	}

	private function getScenarioIds($config) {
		$return = array();
		foreach ($config['payload']['widgets'] as $widget) {
			if ($widget['type'] == 'scenario') {
				array_push($return, $widget['scenarioId']);
			}
		}
		return $return;
	}

	public function sendCmdInfo($client) {
		$cmds = \cmd::byIds($this->getInfoCmds($this->configList[$client->apiKey]));
		$result = array(
			'type' => 'SET_CMD_INFO',
			'payload' => array()
		);
		foreach ($cmds as $cmd) {
			$state = $cmd->getCache(array('valueDate', 'value'));
			$cmd_info = array(
				'id' => $cmd->getId(),
				'value' => $state['value'],
				'modified' => strtotime($state['valueDate'])
			);
			array_push($result['payload'], $cmd_info);
		}
		\log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
		$client->send(json_encode($result));
	}

	public function sendScenarioInfo($client) {
		$scIds = $this->getScenarioIds($this->configList[$client->apiKey]);
		$result = array(
			'type' => 'SET_SC_INFO',
			'payload' => array()
		);

		foreach (\scenario::all() as $sc) {
			if (in_array($sc->getId(), $scIds) ) {
				$state = $sc->getCache(array('state', 'lastLaunch'));
				$sc_info = array(
					'id' => $sc->getId(),
					'name' => $sc->getName(),
					'status' => $state['state'],
					'lastLaunch' => strtotime($state['lastLaunch']),
					'active' => $sc->getIsActive() ? 1 : 0
				);
				array_push($result['payload'], $sc_info);
			}
		}

		\log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
		$client->send(json_encode($result));
	}

	public function sendHistory($client, $id, $options = null) {
		$history = array();
		if ($options == null) {
			$history = \history::all($id);
		} else {
			$startTime = date('Y-m-d H:i:s', $options['startTime']);
			$endTime = date('Y-m-d H:i:s', $options['endTime']);
			\log::add('JeedomConnect', 'info', 'Get history from: '.$startTime.' to '.$endTime);
			$history = \history::all($id, $startTime, $endTime);
		}

		$result = array(
			'type' => 'SET_HISTORY',
			'payload' => array(
				'id' => $id,
				'data' => array()
			)
		);

		foreach ($history as $h) {
			array_push($result['payload']['data'], array(
				'time' => strtotime($h->getDateTime()),
				'value' => $h->getValue()
			));
		}
		\log::add('JeedomConnect', 'info', 'Send history');
		$client->send(json_encode($result));

	}
}
