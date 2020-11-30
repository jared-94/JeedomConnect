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

include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}

?>
<form class="form-horizontal">
  <a href="https://github.com/jared-94/JeedomConnect/releases/latest"
    style="font-style:italic" target="_blank">Télécharger la dernière version de l'application pour Android</a>
    <fieldset>
        <div class="form-group">
            <label class="col-lg-6 control-label">{{Port d'écoute du websocket}}</label>
            <div class="col-lg-1">
                <input class="configKey form-control" type="number" data-l1key="port" placeholder="8090" />
            </div>
        </div>
		<div class="form-group">
            <label class="col-lg-6 control-label">{{Adresse http(s) Jeedom}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" type="string" data-l1key="httpUrl"
					placeholder="<?php echo config::byKey('externalProtocol') . config::byKey('externalAddr') . ':' . config::byKey('externalPort', 'core', 80); ?>" />
            </div>
        </div>
		<div class="form-group">
            <label class="col-lg-6 control-label">{{Adresse websocket Jeedom}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" type="string" data-l1key="wsAddress"
					placeholder="<?php echo 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'; ?>" />
            </div>
        </div>
    </fieldset>
</form>
