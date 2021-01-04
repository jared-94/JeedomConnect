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

class JeedomConnect extends eqLogic {

   /*     * *************************Attributs****************************** */

	public static $_initialConfig = array(
		'type' => 'JEEDOM_CONFIG',
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
		file_put_contents($config_file, json_encode($config));
	}

	public function getConfig() {
		$config_file = self::$_config_dir . $this->getConfiguration('apiKey') . ".json";
		$config = file_get_contents($config_file);
		$jsonConfig = json_decode($config, true);
		
		//add cmd configs
		foreach ($jsonConfig['payload']['widgets'] as $index => $widget) {
			foreach ($widget as $item => $value) {
				//if (substr_compare($item, 'Info', strlen($item)-4, 4) === 0) {
					$cmd = cmd::byId($value);
					if (is_object($cmd)) {
						$jsonConfig['payload']['widgets'][$index][$item . 'SubType'] = $cmd->getSubType();
						$jsonConfig['payload']['widgets'][$index][$item . 'MinValue'] = $cmd->getConfiguration('minValue');
						$jsonConfig['payload']['widgets'][$index][$item . 'MaxValue'] = $cmd->getConfiguration('maxValue');
						$jsonConfig['payload']['widgets'][$index][$item . 'Unit'] = $cmd->getUnite();
						$jsonConfig['payload']['widgets'][$index][$item . 'Value'] = $cmd->getValue();
					}
				//}
			}
		}
		return $jsonConfig;
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
			$port = config::byKey('port', 'JeedomConnect', 8090);
			$url = config::byKey('wsAddress', 'JeedomConnect', 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':'. $port);
			$connectData = array(
				'url' => $url,
				'apiKey' => $this->getConfiguration('apiKey')
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
			log::add('JeedomConnect', 'info', "No token defnied. Please connect your device first");
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

	private function getDistance($lat1, $lon1, $lat2, $lon2) {
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
			if ($this->getConfiguration('scenariosEnabled') == '') {
				$this->setConfiguration('scenariosEnabled', '1');
				$this->save();
			}
    }

    public function postUpdate()
    {
    }

    public function preRemove() {
			unlink(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
			unlink(self::$_config_dir . $this->getConfiguration('apiKey') . ".json");
			unlink(self::$_notif_dir . $this->getConfiguration('apiKey') . ".json");
    }

    public function postRemove()
    {
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
			//log::add('JeedomConnect', 'info', json_encode($_options));
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
			$eqLogic->sendNotif($this->getLogicalId(), $data);
		}
	}

}
