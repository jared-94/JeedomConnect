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
try {
	require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
	require_once dirname(__FILE__) . "/../class/JeedomConnectWidget.class.php";

	$apiKey = init('apiKey');
	$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

	/*if (!is_object($eqLogic)) {
		JCLog::debug("Can't find eqLogic");
		throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
	}*/

	$tmpFolder = "/var/www/html/plugins/JeedomConnect/tmp";

	$pathfile = calculPath(urldecode(init('pathfile')));
	$pathfile = (strpos($pathfile, '*') !== false) ? realpath(str_replace('*', '', $pathfile)) . '/*' : realpath($pathfile);

	if ($pathfile === false) {
		JCLog::debug('downloadFile - file not found');
		throw new Exception(__('downloadFile - file not found', __FILE__));
	}

	$rootPath = realpath(__DIR__ . '/../../../../');
	if (strpos($pathfile, $rootPath) === false) {
		$pathfile = $rootPath . '/' . str_replace('..', '', $pathfile);
	}

	if (strpos($pathfile, '*') === false) {
		if (!file_exists($pathfile)) {
			JCLog::debug('downloadFile - file not found');
			throw new Exception(__('downloadFile - file not found', __FILE__));
		}
	} elseif (is_dir(str_replace('*', '', $pathfile))) {
		system('cd ' . dirname($pathfile) . ';tar cfz ' . $tmpFolder . '/archive.tar.gz * > /dev/null 2>&1');
		$pathfile = $tmpFolder . '/archive.tar.gz';
	} else {
		$pattern = array_pop(explode('/', $pathfile));
		system('cd ' . dirname($pathfile) . ';tar cfz ' . $tmpFolder . '/archive.tar.gz ' . $pattern . '> /dev/null 2>&1');
		$pathfile = $tmpFolder . '/archive.tar.gz';
	}
	$path_parts = pathinfo($pathfile);
	if ($path_parts['extension'] == 'pdf') {
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename=' . $path_parts['basename']);
	} else {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $path_parts['basename']);
	}
	readfile($pathfile);
	if (file_exists($tmpFolder . '/archive.tar.gz')) {
		unlink($tmpFolder . '/archive.tar.gz');
	}
	exit;
} catch (Exception $e) {
	echo $e->getMessage();
}
