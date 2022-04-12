<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

use JeedomConnectLogic\ConnectLogic;

require_once dirname(__FILE__) . '/../../3rdparty/vendor/autoload.php';
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

try {
    $port = intval(config::byKey('port', 'JeedomConnect', 8090));
    $versionPath = dirname(__FILE__) . '/../../plugin_info/version.json';
    $versionJson = json_decode(file_get_contents($versionPath));

    $connectLogic = new ConnectLogic($versionJson);


    // Create socket server
    $server = IoServer::factory(new HttpServer(new WsServer($connectLogic)), $port);

    // Add the periodic processing
    $server->loop->addPeriodicTimer(
        1,
        function () use ($connectLogic) {
            $connectLogic->process();
        }
    );

    // Run server
    JCLog::info("Listenning on port $port");

    $server->run();
} catch (\Exception $e) {
    JCLog::error('Daemon crash with following error: ' . $e->getMessage());
}
