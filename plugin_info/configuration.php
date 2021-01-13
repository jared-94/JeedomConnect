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
  <div class="alert alert-success" style="text-align:center;">
  <a href="https://github.com/jared-94/JeedomConnect/releases/latest"
    style="color: white !important;" target="_blank">Télécharger la dernière version de l'application pour Android</a>
  </div>
  <div class="alert alert-info" style="text-align:center;">
    Les paramètres c-dessous doivent être configurés correctement pour le bon fonctionnement de l'application.<br/>
    Les paramètres liés au websocket ne sont nécessaires que si vous l'activez.
    Si vous n'utilisez pas le websocket, vous pouvez désactiver le démon.<br/>
    Après tout changement ici, veuillez redémarrer l'application.
  </div>
    <fieldset>
      <div class="form-group">
          <label class="col-lg-6 control-label">{{Adresse http externe}}</label>
          <div class="col-lg-3">
              <input class="configKey form-control" type="string" data-l1key="httpUrl"
					         placeholder="<?php echo network::getNetworkAccess('external'); ?>" />
          </div>
      </div>
      <div class="form-group">
          <label class="col-lg-6 control-label">{{Adresse http interne}}</label>
          <div class="col-lg-3">
              <input class="configKey form-control" type="string" data-l1key="internHttpUrl"
					         placeholder="<?php echo network::getNetworkAccess('internal'); ?>" />
          </div>
      </div>
      <div class="alert alert-info" style="text-align:center;">
        La connexion par Websocket nécessite une configuration supplémentaire sur votre réseau, au moins pour un accès extérieur.
			</div>
      <div class="form-group">
			     <label class="col-lg-6 control-label">{{Activer la connexion par Websocket }}</label>
			     <div class="col-sm-1">
				         <input type="checkbox" class="configKey form-control" data-l1key="useWs"/>
			     </div>
		  </div>
      <div class="form-group">
          <label class="col-lg-6 control-label">{{Port d'écoute du websocket}}</label>
          <div class="col-lg-1">
              <input class="configKey form-control" type="number" data-l1key="port" placeholder="8090" />
          </div>
      </div>
		  <div class="form-group">
          <label class="col-lg-6 control-label">{{Adresse externe websocket}}</label>
          <div class="col-lg-3">
              <input class="configKey form-control" type="string" data-l1key="wsAddress"
					         placeholder="<?php echo 'ws://' . config::byKey('externalAddr') . ':8090'; ?>" />
          </div>
      </div>
      <div class="form-group">
          <label class="col-lg-6 control-label">{{Adresse interne websocket}}</label>
          <div class="col-lg-3">
              <input class="configKey form-control" type="string" data-l1key="internWsAddress"
					         placeholder="<?php echo 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'; ?>" />
          </div>
      </div>
    </fieldset>
</form>
