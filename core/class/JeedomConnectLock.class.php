<?php

use Psr\Log\LoggerInterface;

class JeedomConnectLock {
	private static $_plugin_id = 'JeedomConnect';

	/**
	 * @var LoggerInterface
	 */
	private $_logger;

	private $_key;
	private $_file = null;
	private $_own = false;


	function __construct($key) {
		$this->_logger = log::getLogger(self::$_plugin_id);

		$this->_key = $key;

		$this->_logger->debug("create lock {$this->_key}");
		$lockFile = jeedom::getTmpFolder(self::$_plugin_id) . "/{$this->_key}.lockfile";
		$this->_file = fopen($lockFile, 'w+');
	}

	function __destruct() {
		if ($this->_own == true)
			$this->unlock();
	}

	function Lock($timeout = 3) {
		$a = 0;
		do {
			if (flock($this->_file, LOCK_EX)) {
				ftruncate($this->_file, 0);
				fwrite($this->_file, "Locked\n");
				fflush($this->_file);

				$this->_own = true;
				$this->_logger->debug("lock {$this->_key}");
				return true;
			}
			sleep(1);
			++$a;
		} while ($a <= $timeout);

		return false;
	}

	function unlock() {
		if ($this->_own) {
			if (!flock($this->_file, LOCK_UN)) {
				return false;
			}
			ftruncate($this->_file, 0);
			fwrite($this->_file, "Unlocked\n");
			fflush($this->_file);
			$this->_own = false;
			$this->_logger->debug("unlock {$this->_key}");
		}
		return true;
	}
}
