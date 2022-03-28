<?php

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/JeedomConnect.class.php";
require_once dirname(__FILE__) . "/../class/apiHelper.class.php";

http_response_code(200);
header('Content-Type: multipart/form-data');



$params = $_POST;
$files = $_FILES;

JCLog::debug('[UPLOAD API] Upload files ' . json_encode($params));

$apiKey = $params['apiKey'] ?? null;
$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');
if (!is_object($eqLogic)) {
    throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
}

$userImgPath = __DIR__ . '/../../../../' . config::byKey('userImgPath',   'JeedomConnect');

foreach ($files as $file) {
    $path = $userImgPath . $file['name'];
    JCLog::debug('[UPLOAD API] copy file to : ' . $path);
    move_uploaded_file($file["tmp_name"], $path);
}

echo json_encode(apiHelper::getFiles($userImgPath, true, false));
