<?php


require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function JeedomConnect_pre_update() {

    $deamon_info = JeedomConnect::deamon_info();
    if ($deamon_info['state'] == 'ok') {
        JeedomConnect::deamon_stop();
    }
}
