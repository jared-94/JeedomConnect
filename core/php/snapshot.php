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

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectWidget.class.php";

$apiKey = init('apiKey');
$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

if (!is_object($eqLogic)) {
	JCLog::debug("Can't find eqLogic");
	throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
}

ob_clean();
header('Content-Type: image/jpeg');

$camWidgetId = init('id');
$widget = JeedomConnectWidget::getConfiguration($camWidgetId, 'widgetJC');
$conf = json_decode($widget, true);
$snapUrl = getUrl($conf);
$username = $conf['username'] ?? null ?: null;
$pwd = $conf['password'] ?? null ?: null;

if (!is_string($snapUrl)) {
	JCLog::debug("Can't find snapshot url");
	throw new Exception(__("Can't find snapshot url", __FILE__), -32699);
}


function getUrl($conf) {

	$url = $conf['snapshotUrl'] ?? '';

	if (isset($conf['snapshotUrlInfo'])) {
		$cmdId = $conf['snapshotUrlInfo']['id'];

		$cmd = cmd::byId($cmdId);
		if (is_object($cmd)) {
			$url = $cmd->execCmd();
			// JCLog::debug('Snapshot will use url comming from cmd info ['.$cmdId.'] => ' . $url);
		}
	}
	// JCLog::debug('url used :' . $url);
	return $url;
}

function getData($url, $username, $pwd) {
	$ch = curl_init();

	$replaceArr = array(
		'#username#' => urlencode($username),
		'#password#' => urlencode($pwd),
	);

	$url = str_replace(array_keys($replaceArr), $replaceArr, $url);

	if (!is_null($username) && !is_null($pwd)) {
		$userPwd = $username . ':' . $pwd;
		curl_setopt($ch, CURLOPT_USERPWD, $userPwd);
		$headers = array(
			'Content-Type:application/json',
			'Authorization: Basic ' . base64_encode($userPwd),
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	$data = curl_exec($ch);
	if (curl_error($ch)) {
		JCLog::debug('Error taking snapshot');
	}
	curl_close($ch);
	return $data;
}


$data = getData($snapUrl, $username, $pwd);

if (!function_exists('imagecreatefromstring')) {
	echo $data;
	exit();
}

$compress = init('compress', 100);
$resize = init('resize', 100);


if ($compress >= 100 && $resize >= 100) {
	echo $data;
	exit();
}
if (empty($data) || $data == false || $data == '') {
	echo $data;
	exit();
}


$source = @imagecreatefromstring($data);
if ($source === false) {
	echo $data;
	exit();
}

if ($resize >= 100) {
	imagejpeg($source, null, $compress);
	exit();
}

$width = imagesx($source);
$height = imagesy($source);
$ratio = $width / $height;
$newwidth = round($width * $resize / 100);
$newheight = round($height * $resize / 100);

$result = imagecreatetruecolor($newwidth, $newheight);
imagecopyresized($result, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
imagejpeg($result, null, $compress);
exit();
