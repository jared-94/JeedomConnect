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
require_once dirname(__FILE__) . '/../../3rdparty/vendor/autoload.php';

use Endroid\QrCode\QrCode;

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
	
	public static $_data_dir = __DIR__ . '/../../data/';
	public static $_config_dir = __DIR__ . '/../../data/configs/';
	public static $_qr_dir = __DIR__ . '/../../data/qrcodes/';

    /*     * ***********************Methode static*************************** */

    public static function dependancy_info() {
        return array(
			'log' => __CLASS__ . '_update',
			'progress_file' => jeedom::getTmpFolder('JeedomConnect') . '/dependance',
			'state' => self::packagesOk() ? 'ok' : 'nok'
		);        
    }

    public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');        
		return array(
			'script' => dirname(__FILE__) . '/../../3rdparty/install.sh ' . jeedom::getTmpFolder('JeedomConnect') . '/dependance', 
			'log' => log::getPathToLog(__CLASS__ . '_update')
		);
    }
	
	public static function packagesOk() {
		$resource_path = realpath(__DIR__ . '/../../3rdparty');
        if (!file_exists($resource_path.'/vendor/cboden/ratchet') or !file_exists($resource_path.'/vendor/endroid/qr-code')) {
			return false;
        }
		return true;
	}
	

    public static function deamon_info() {
        $return = array();

        $status = trim(shell_exec('systemctl is-active jeedom-connect'));
        $return['state'] = ($status === 'active') ? 'ok' : 'nok';

        $return['launchable'] = 'ok';
        if (!file_exists('/etc/systemd/system/jeedom-connect.service')) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le démon n\'est pas installé ', __FILE__);
        }
        return $return;
    }
    
    public static function deamon_start($_debug = false) {
        log::add('JeedomConnect', 'info', 'Starting daemon');
        exec(system::getCmdSudo() . 'systemctl restart jeedom-connect');
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
        exec(system::getCmdSudo() . 'systemctl stop jeedom-connect');
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
			$qrCode = new QrCode(json_encode($connectData));
			$qrCode->writeFile(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
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
    }

    public function preSave()
    {			
    }

    public function postSave()
    {		
    }

    public function preUpdate()
    {
    }

    public function postUpdate()
    {
    }

    public function preRemove()
    {
		unlink(self::$_qr_dir . $this->getConfiguration('apiKey') . '.png');
		unlink(self::$_config_dir . $this->getConfiguration('apiKey') . ".json");
    }

    public function postRemove()
    {
    }


   
}

class JeedomConnectCmd extends cmd {
	
	public function execute($_options = array()) {
	}
	
}
