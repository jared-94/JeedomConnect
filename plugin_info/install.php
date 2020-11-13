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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function JeedomConnect_install() {
    // Installing daemon
    log::add('JeedomConnect', 'info', 'Installing daemon');
    exec(system::getCmdSudo() . 'cp '.dirname(__FILE__).'/../resources/jeedom-connect.service /etc/systemd/system/jeedom-connect.service');
    exec(system::getCmdSudo() . 'systemctl daemon-reload');
    exec(system::getCmdSudo() . 'systemctl start jeedom-connect');
    exec(system::getCmdSudo() . 'systemctl enable jeedom-connect');
    $active = trim(shell_exec('systemctl is-active jeedom-connect'));
    $enabled = trim(shell_exec('systemctl is-enabled jeedom-connect'));
    if ($active !== 'active' || $enabled !== 'enabled') {
        log::add('JeedomConnect', 'error', "Daemon is not fully installed ($active / $enabled)");
    } else {
		log::add('JeedomConnect', 'info', "Daemon installed ($active / $enabled)");
	}
}

function JeedomConnect_update() {
    log::add('JeedomConnect', 'info', 'Updating daemon');
    exec(system::getCmdSudo() . 'systemctl restart jeedom-connect');
}

function JeedomConnect_remove() {
    log::add('JeedomConnect', 'info', 'Removing daemon');
    exec(system::getCmdSudo() . 'systemctl disable jeedom-connect');
    exec(system::getCmdSudo() . 'systemctl stop jeedom-connect');
    exec(system::getCmdSudo() . 'rm /etc/systemd/system/jeedom-connect.service');
    exec(system::getCmdSudo() . 'systemctl daemon-reload');
    log::add('JeedomConnect', 'info', "Daemon removed");
}
