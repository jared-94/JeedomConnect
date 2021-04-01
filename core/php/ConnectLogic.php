<?php
namespace JeedomConnectLogic;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once dirname(__FILE__) . "/../class/apiHelper.class.php";

class ConnectLogic implements MessageComponentInterface
{

		private $configList = array();
		private $apiKeyList = array();

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
    public function __construct($versionJson) {
			foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
				$apiKey = $eqLogic->getConfiguration('apiKey');
				if ( $apiKey !=  ''){
					\log::add('JeedomConnect', 'debug', 'checking apiKey : ' . $apiKey );
					array_push($this->apiKeyList, $apiKey);
					$this->configList[$apiKey] = $eqLogic->getConfig(true);
				}
			}
      $this->unauthenticatedClients = new \SplObjectStorage;
      $this->authenticatedClients = new \SplObjectStorage;
      $this->hasAuthenticatedClients = false;
      $this->hasUnauthenticatedClients = false;
      $this->authDelay = 2;
			$this->pluginVersion = $versionJson->version;
			$this->appRequire = $versionJson->require;
      $this->lastReadTimestamp = time();
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
			if (version_compare($objectMsg->appVersion, $this->appRequire, "<")) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad version requierement");
				$result = array(
					'type' => 'APP_VERSION_ERROR',
					'payload' => \JeedomConnect::getPluginInfo() );
				$conn->send(json_encode($result));
				$conn->close();
				return;
			}
			if (version_compare($this->pluginVersion, $objectMsg->pluginRequire, "<")) {
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

			$user = \user::byId($eqLogic->getConfiguration('userId'));
			if ($user == null) {
				$user = \user::all()[0];
				$eqLogic->setConfiguration('userId', $user->getId());
				$eqLogic->save();
			}

			//check config format version
			if( ! array_key_exists('formatVersion', $this->configList[$objectMsg->apiKey]) ) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad bad format version");
				$result = array( 'type' => 'FORMAT_VERSION_ERROR' );
				$conn->send(json_encode($result));
				$conn->close();
	      return;
			}

      $conn->apiKey = $objectMsg->apiKey;
			$conn->eqLogic = $eqLogic;
      $this->authenticatedClients->attach($conn);
      $this->hasAuthenticatedClients = true;
      \log::add('JeedomConnect', 'info', "#{$conn->resourceId} is authenticated with api Key '{$conn->apiKey}'");
			$result = array(
				'type' => 'WELCOME',
				'payload' => array(
					'pluginVersion' => $this->pluginVersion,
					'userHash' => $user->getHash(),
					'configVersion' => $this->configList[$objectMsg->apiKey]['payload']['configVersion'],
					'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
					'pluginConfig' => \apiHelper::getPluginConfig(),
					'cmdInfo' => \apiHelper::getCmdInfoData($this->configList[$conn->apiKey]),
					'scInfo' => \apiHelper::getScenarioData($this->configList[$conn->apiKey]),
					'objInfo' => \apiHelper::getObjectData($this->configList[$conn->apiKey])
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
        \log::add('JeedomConnect', 'debug', "New connection: #{$conn->resourceId} from IP: {$conn->ip}");
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
		if (!array_key_exists('type', $msg)) { return; }
		switch ($msg['type']) {
			case 'CMD_EXEC':
				$cmd = \cmd::byId($msg['payload']['id']);
				if (!is_object($cmd)) {
					\log::add('JeedomConnect', 'error', "Can't find command");
					return;
				}
				if ($msg['payload']['options'] == null) {
					$cmd->execCmd();
				} else {
					$cmd->execCmd($option = $msg['payload']['options']);
				}
				break;
			case 'SC_EXEC':
				// $sc = \scenario::byId($msg['payload']['id']);
				// $sc->launch();
				$options = array();
				if (isset($msg['payload']['options'])) {
					$options = $msg['payload']['options'];
				}
				\scenarioExpression::createAndExec('action', 'scenario', $options);
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
			case 'GET_PLUGIN_CONFIG':
				$conf = array(
		      'type' => 'PLUGIN_CONFIG',
		      'payload' => array(
		        'useWs' => \config::byKey('useWs', 'JeedomConnect', false),
		        'httpUrl' => \config::byKey('httpUrl', 'JeedomConnect', \network::getNetworkAccess('external')),
		        'internalHttpUrl' => \config::byKey('internHttpUrl', 'JeedomConnect', \network::getNetworkAccess('internal')),
		        'wsAddress' => \config::byKey('wsAddress', 'JeedomConnect', 'ws://' . \config::byKey('externalAddr') . ':8090'),
		        'internalWsAddress' => \config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . \config::byKey('internalAddr', 'core', 'localhost') . ':8090')
		     ));
				\log::add('JeedomConnect', 'debug', "Send : ".json_encode($conf));
				$from->send(json_encode($conf));
				break;
			case 'GET_CONFIG':
				$result = $this->configList[$from->apiKey];
				//$result['payload']['summaryConfig'] = \config::byKey('object:summary');
				\log::add('JeedomConnect', 'debug', "Send : ".json_encode($result));
				$from->send(json_encode($result));
				break;
			case 'GET_CMD_INFO':
				$this->sendCmdInfo($from);
				break;
			case 'GET_SC_INFO':
				$this->sendScenarioInfo($from);
				break;
			case 'GET_ALL_SC':
				$this->sendScenarioInfo($from, true);
				break;
			case 'UNSUBSCRIBE_SC':
				$from->sendAllSc = false;
				break;
			case 'GET_HISTORY':
				$from->send(json_encode(\apiHelper::getHistory($msg['payload']['id'], $msg['payload']['options'])));
				break;
			case 'GET_FILES':
				$from->send(json_encode(\apiHelper::getFiles($msg['payload']['folder']) ));
				break;
			case 'REMOVE_FILE':
				$from->send(json_encode(\apiHelper::removeFile($msg['payload']['file']) ));
				break;
			case 'ADD_GEOFENCE':
				$this->addGeofence($from, $msg['payload']['geofence']);
				break;
			case 'REMOVE_GEOFENCE':
				$this->removeGeofence($from, $msg['payload']['geofence']);
				break;
			case 'GET_GEOFENCES':
				$this->sendGeofences($from);
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
					// $this->configList[$apiKey] = $eqLogic->getConfig(true);
					$this->configList[$apiKey] = $eqLogic->getGeneratedConfigFile();
					//$this->configList[$apiKey]['payload']['configVersion'] = $configVersion;
					array_push($this->apiKeyList, $apiKey);
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
				$this->configList[$apiKey] = $eqLogic->getConfig(true);
			}
		}
		//Remove deleted configs
		foreach ($this->apiKeyList as $i => $key) {
			if (!in_array($key, $eqLogicKeys)) {
				\log::add('JeedomConnect', 'info', "Remove device with key ".$key);
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
		foreach ($this->authenticatedClients as $client) {
			$config = $this->configList[$client->apiKey];
			$eventsRes = \apiHelper::getEvents($events, $config);

			foreach ($eventsRes as $res) {
				if (count($res['payload']) > 0) {
					\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : ".json_encode($res));
					$client->send(json_encode($res));
				}
			}
		}
	}

	public function sendSummaries($client) {
		$objIds = \apiHelper::getObjectData($this->configList[$client->apiKey]);
		$result = array(
			'type' => 'SET_CMD_INFO',
			'payload' => array()
		);
		foreach (\jeeObject::all() as $object) {
			if (in_array($object->getId(), $objIds)) {
				$summary = array(
					'id' => $object->getId(),
					'summary' => $object->getSummary()
				);
				array_push($result['payload'], $summary);
			}
		}
		\log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
		$client->send(json_encode($result));
	}

	public function sendCmdInfo($client) {
		$result = array(
			'type' => 'SET_CMD_INFO',
			'payload' => \apiHelper::getCmdInfoData($this->configList[$client->apiKey])
		);
		\log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
		$client->send(json_encode($result));
	}

	public function sendScenarioInfo($client, $all=false) {
		$client->sendAllSc = $all;
		$scIds = \apiHelper::getScenarioList($this->configList[$client->apiKey]);
		$result = array(
			'type' => $all ? 'SET_ALL_SC' : 'SET_SC_INFO',
			'payload' => array()
		);

		foreach (\scenario::all() as $sc) {
			if (in_array($sc->getId(), $scIds) || $all) {
				$state = $sc->getCache(array('state', 'lastLaunch'));
				$sc_info = array(
					'id' => $sc->getId(),
					'name' => $sc->getName(),
					'object' => $sc->getObject() == null ? 'Aucun' : $sc->getObject()->getName(),
					'group' => $sc->getGroup() == '' ? 'Aucun' : $sc->getGroup(),
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

	public function addGeofence($client, $geofence) {
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$eqLogic->addGeofenceCmd($geofence);
	}

	public function removeGeofence($client, $geofence) {
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$eqLogic->removeGeofenceCmd($geofence);
	}

	public function sendGeofences($client) {
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$result = array(
			'type' => 'SET_GEOFENCES',
			'payload' => array(
				'geofences' => array()
			)
		);
		foreach ($eqLogic->getCmd('info') as $cmd) {
			if (substr( $cmd->getLogicalId(), 0, 8 ) === "geofence") {
				array_push($result['payload']['geofences'], array(
					'identifier' => substr( $cmd->getLogicalId(), 9 ),
					'extras' => array(
		        'name' => $cmd->getName()
		      ),
		      'radius' => $cmd->getConfiguration('radius'),
		      'latitude' => $cmd->getConfiguration('latitude'),
		      'longitude' => $cmd->getConfiguration('longitude'),
		      'notifyOnEntry' => true,
		      'notifyOnExit' => true
				));
			}
		}
		if (count($result['payload']['geofences']) > 0) {
			$client->send(json_encode($result));
		}
	}

}
