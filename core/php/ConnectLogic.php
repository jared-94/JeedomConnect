<?php
namespace JeedomConnectLogic;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";

class ConnectLogic implements MessageComponentInterface
{

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
      	$this->unauthenticatedClients = new \SplObjectStorage;
      	$this->authenticatedClients = new \SplObjectStorage;
      	$this->hasAuthenticatedClients = false;
      	$this->hasUnauthenticatedClients = false;
      	$this->authDelay = 1;
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
			
    	if ($this->hasAuthenticatedClients) {
			$this->lookForNewConfig();
			$this->sendActions();
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

		$eqLogic = \eqLogic::byLogicalId($objectMsg->apiKey, 'JeedomConnect');

        if (!is_object($eqLogic)) {
            // Invalid API key
            \log::add('JeedomConnect', 'warning', "Authentication failed (invalid credentials) for client #{$conn->resourceId} from IP: {$conn->ip}");
			$result = array( 'type' => 'BAD_KEY' );
			$conn->send(json_encode($result));
            $conn->close();
			return;
        } else {
            if (!$this->hasAuthenticatedClients) {
                // It is the first client, we store current timestamp for fetching events since this moment
                $this->lastReadTimestamp = time();
            }

			
			$config = $eqLogic->getGeneratedConfigFile();
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

			//check config content
			if( is_null($config) ) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : empty config file");
				$result = array( 'type' => 'EMPTY_CONFIG_FILE' );
				$conn->send(json_encode($result));
				$conn->close();
	      		return;
			}

			//check config format version
			if( ! array_key_exists('formatVersion', $config) ) {
				\log::add('JeedomConnect', 'warning', "Failed to connect #{$conn->resourceId} : bad format version");
				$result = array( 'type' => 'FORMAT_VERSION_ERROR' );
				$conn->send(json_encode($result));
				$conn->close();
	      		return;
			}

			$conn->apiKey = $objectMsg->apiKey;
			$conn->sessionId = rand(0,1000);
			$conn->configVersion = $config['payload']['configVersion'];
			$this->authenticatedClients->attach($conn);
			$this->hasAuthenticatedClients = true;
			$eqLogic->setConfiguration('platformOs', $objectMsg->platformOs);
			$eqLogic->setConfiguration('sessionId', $conn->sessionId);
			$eqLogic->setConfiguration('connected', 1);
			$eqLogic->setConfiguration('scAll', 0);
			$eqLogic->save();
			\log::add('JeedomConnect', 'info', "#{$conn->resourceId} is authenticated with api Key '{$conn->apiKey}'");
			$result = array(
				'type' => 'WELCOME',
				'payload' => array(
					'pluginVersion' => $this->pluginVersion,
					'useWs' => $eqLogic->getConfiguration('useWs', 0),
					'userHash' => $user->getHash(),
					'configVersion' => $config['payload']['configVersion'],
					'scenariosEnabled' => $eqLogic->getConfiguration('scenariosEnabled') == '1',
					'pluginConfig' => \apiHelper::getPluginConfig(),
					'cmdInfo' => \apiHelper::getCmdInfoData($config),
					'scInfo' => \apiHelper::getScenarioData($config),
					'objInfo' => \apiHelper::getObjectData($config)
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
				\apiHelper::execCmd($msg['payload']['id'], $msg['payload']['options']);
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
				$eqLogic = \eqLogic::byLogicalId($from->apiKey, 'JeedomConnect');
				$conf = array(
		      		'type' => 'PLUGIN_CONFIG',
		      		'payload' => array(
		        		'useWs' => $eqLogic->getConfiguration('useWs', 0),
		        		'httpUrl' => \config::byKey('httpUrl', 'JeedomConnect', \network::getNetworkAccess('external')),
		        		'internalHttpUrl' => \config::byKey('internHttpUrl', 'JeedomConnect', \network::getNetworkAccess('internal')),
		        		'wsAddress' => \config::byKey('wsAddress', 'JeedomConnect', 'ws://' . \config::byKey('externalAddr') . ':8090'),
		        		'internalWsAddress' => \config::byKey('internWsAddress', 'JeedomConnect', 'ws://' . \config::byKey('internalAddr', 'core', 'localhost') . ':8090')
		    	 ));
				\log::add('JeedomConnect', 'debug', "Send : ".json_encode($conf));
				$from->send(json_encode($conf));
				break;
			case 'GET_CONFIG':
				$eqLogic = \eqLogic::byLogicalId($from->apiKey, 'JeedomConnect');
				$config = $eqLogic->getGeneratedConfigFile();
				\log::add('JeedomConnect', 'debug', "Send : ".json_encode($config));
				$from->send(json_encode($config));
				break;
			case 'GET_BATTERIES':
				$config = \apiHelper::getBatteries();
				$from->send(json_encode($config));
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
				$eqLogic = \eqLogic::byLogicalId($from->apiKey, 'JeedomConnect');
				$eqLogic->setConfiguration('scAll', 0);
				$eqLogic->save();
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
			case 'SET_BATTERY':
				$eqLogic = \eqLogic::byLogicalId($from->apiKey, 'JeedomConnect');
				if (is_object($eqLogic)) {					
					$batteryCmd = $eqLogic->getCmd(null, 'battery');
					if (is_object($batteryCmd)){
				  		$batteryCmd->event($msg['payload']['level'], date('Y-m-d H:i:s'));
					}
				}				 
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
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
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
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        \log::add('JeedomConnect', 'error', "An error has occurred: {$e->getMessage()}");
		$eqLogic = \eqLogic::byLogicalId($conn->apiKey, 'JeedomConnect');
		if (is_object($eqLogic)) {
			if ($eqLogic->getConfiguration('sessionId', 0) == $conn->sessionId) {
				$eqLogic->setConfiguration('connected', 0);
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
			$newConfig = \apiHelper::lookForNewConfig($eqLogic, $client->configVersion);
  			if ($newConfig != false) {
    			$config = $newConfig;
				\log::add('JeedomConnect', 'debug', "send new config to #{$client->resourceId} with api key ".$client->apiKey);
				$client->configVersion = $newConfig['payload']['configVersion'];
				$client->send(json_encode($newConfig));
				$this->sendCmdInfo($client);
				$this->sendScenarioInfo($client);
			}
		}
	}

	private function sendActions() {
		foreach ($this->authenticatedClients as $client) {
			$actions = \JeedomConnectActions::getAllAction($client->apiKey);
			//\log::add('JeedomConnect', 'debug', "get action  ".json_encode($actions));
			if (count($actions) > 0) {
				$result = array(
					'type' => 'ACTIONS',
					'payload' => array()
				);
				foreach ($actions as $action) {
					array_push($result['payload'], $action['value']['payload']);
				}
				\log::add('JeedomConnect', 'debug', "send action to #{$client->resourceId}  ".json_encode($result));
				$client->send(json_encode($result));
				\JeedomConnectActions::removeAllAction($actions);
			}
		}
		
	}

	private function broadcastEvents($events) {
		foreach ($this->authenticatedClients as $client) {
			$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
			$config = $eqLogic->getGeneratedConfigFile();
			$eventsRes = \apiHelper::getEvents($events, $config, $eqLogic->getConfiguration('scAll', 0) == 1);

			foreach ($eventsRes as $res) {
				if (count($res['payload']) > 0) {
					//\log::add('JeedomConnect', 'debug', "Broadcast to {$client->resourceId} : ".json_encode($res));
					$client->send(json_encode($res));
				}
			}
		}
	}

	public function sendSummaries($client) {
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$config = $eqLogic->getGeneratedConfigFile();
		$objIds = \apiHelper::getObjectData($config);
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
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$config = $eqLogic->getGeneratedConfigFile();
		$result = array(
			'type' => 'SET_CMD_INFO',
			'payload' => \apiHelper::getCmdInfoData($config)
		);
		\log::add('JeedomConnect', 'info', 'Send '.json_encode($result));
		$client->send(json_encode($result));
	}

	public function sendScenarioInfo($client, $scAll=false) {
		$eqLogic = \eqLogic::byLogicalId($client->apiKey, 'JeedomConnect');
		$config = $eqLogic->getGeneratedConfigFile();
		if ($scAll) {
			$eqLogic->setConfiguration('scAll', 1);
			$eqLogic->save();
		}
		$result = array(
			'type' => $scAll ? 'SET_ALL_SC' : 'SET_SC_INFO',
			'payload' => \apiHelper::getScenarioData($config, true)
		);
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
