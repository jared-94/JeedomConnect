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

$existOldFormat = false;
foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {

  $configFile = $eqLogic->getConfig(false)  ;
  if( ! is_null($configFile) && ! array_key_exists('formatVersion', $configFile) ) $existOldFormat = true;
  
}

$pluginVersion = JeedomConnect::getPluginInfo();
$apkLink = $pluginVersion['enrollment'] ;
$beta = $pluginVersion['typeVersion'] == 'beta' ;

?>
<form class="form-horizontal jeedomConnect">
  
  <?php
  if ($beta){
  ?>
  <div style="text-align:center;margin-bottom:10px;">
    <div style="margin-bottom:10px;">
      <span  class="alert alert-success">
        <a href="<?=$apkLink?>"  style="color: white !important;padding: 0px 10px;" target="_blank">S'enregister en tant que bêta-testeur</a>
      </span>
    </div>

    <div>
      <span>Afin d'accèder à l'application dans sa version bêta depuis le Store, vous devez être inscrit comme bêta-testeur</span>
    </div>
  </div>
  <?php
  }
  ?>
  
  <div class="alert alert-info" style="text-align:center;">
    Les paramètres ci-dessous doivent être configurés correctement pour le bon fonctionnement de l'application.<br/>
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
			     <label class="col-lg-6 control-label">{{Activer la connexion par Websocket}}</label>
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

      <br/>
      <!-- CUSTOM ZONE -->
      <div class="alert alert-warning" style="text-align:center;">
          {{Personnalisation}}
			</div>
      
      <div class="form-group">
        <label class="col-lg-6 control-label">{{Chemin pour les images perso}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Chemin où sont stockés vos images personnelles<br/>Indiquez-le SANS la racine de votre installation jeedom [/var/www/html/]<br/>Par exemple, renseignez 'data/img/' pour le répertoire '/var/www/html/data/img/'"></i>
          </sup>
        </label>
        <div class="col-lg-3">
          <input class="configKey form-control" type="string" data-l1key="userImgPath"
					         placeholder="<?=config::byKey('userImgPath', 'JeedomConnect');?>" />
        </div>
      </div>


      <br/>
      <!-- BEGIN DANGER ZONE -->
      <div class="alert alert-danger" style="text-align:center;">
          <i class="fas fa-skull-crossbones"></i>&nbsp;&nbsp;&nbsp;&nbsp;{{Attention vous entrez en zone de Dangers !}}&nbsp;&nbsp;&nbsp;&nbsp;<i class="fas fa-skull-crossbones"></i>
			</div>
      <div class="actions-detail" style="text-align:center;">
        </div>

      <div class="form-group">
        <label class="col-sm-6 control-label">{{Réinitialiser l'ensemble des équipements}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Fait une réinitialisation de l'ensemble des équipements.<br>Vous aurez donc des appareils vierges."></i>
          </sup>
        </label>
        <div class="col-sm-1">
          <a class="btn btn-danger" id="reinitAllEq"><i class="fas fa-exclamation-triangle"></i> {{Réinitialiser}}
          </a>
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-6 control-label">{{Suppprimer l'intégralité des widgets}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Supprime tous les widgets,<br>et réinitialise la configuration de tous les équipements<br>Vos équipements seront vierges et devrez recréer tous vos widgets."></i>
          </sup>
        </label>
        <div class="col-sm-1">
          <a class="btn btn-danger" id="removeAllWidgets"><i class="fas fa-exclamation-triangle"></i> {{Supprimer}}
          </a>
        </div>
      </div>

      <hr>
      <div class="form-group">
        <label class="col-sm-6 control-label">{{Utilisation des widgets}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Permet de savoir dans combien d'équipement, chaque widget est utilisé."></i>
          </sup>
        </label>
        <div class="col-sm-1">
          <a class="btn btn-default" id="listWidget"><i class="fas fa-clipboard-list"></i> {{Lister}}
          </a>
        </div>
        <div class="col-sm-5 filterOption"> N'afficher que les widgets 
          <label class="radio-inline"><input type="radio" name="filter" id="unusedOnly"/> non-utilisés</label>
          <label class="radio-inline"><input type="radio" name="filter" id="unexistingOnly"/> non-existants</label>
          <label class="radio-inline"><input type="radio" name="filter" id="all" checked/> tous</label>
        </div>
      </div>
      <div class="resultListWidget">
      </div>

      <div class="form-group">
        <label class="col-sm-6 control-label">{{Configurations des Widgets}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Permet d'exporter vos widgets"></i>
          </sup>
        </label>
        <div class="col-sm-6 input-group" style="display:inline-flex;">
          <span class="input-group-btn">
            <input type="file" accept=".json" id="importConfig-input" style="display:none;" >
            <a class="btn btn-warning" id="exportWidgetConf"><i class="fa fa-save"></i> {{Exporter}}</a>
            <a class="btn btn-primary" id="importWidgetConf"><i class="fa fa-cloud-upload-alt"></i> {{Importer}}</a>
          </span>
          
        </div>
      </div>

      <hr>
      <div class="form-group" id="migrationDiv">
        <label class="col-sm-6 control-label">{{Migration des configurations}}
          <sup>
              <i class="fas fa-question-circle floatright" title="Permet de migrer vos configurations vers le nouveau format. Nécessaire pour le bon fonctionnement de l'application."></i>
          </sup>
        </label>
        <div class="col-sm-1">
        <?php
        if( $existOldFormat ){
          echo '<a class="btn btn-warning" id="migrateConf" ><i class="fas fa-exclamation-triangle"></i> {{Migrer}}
          </a>' ;
        }
        else{
          echo '<a class="btn btn-success" disabled id="migrateConf" style="cursor:not-allowed!important;" title="Tous vos équipements sont déjà sous le nouveau format" ><i class="fas fa-check-circle"></i> {{Migrer}}
          </a>' ;
        }
        ?>
        </div>
        <div class="col-sm-5 migrationOption">
          <label class="radio-inline"><input type="radio" name="migration" id="all"/> tous</label>
          <label class="radio-inline"><input type="radio" name="migration" id="enableOnly" checked/>Uniquement les équipements actifs</label>
        </div>
      </div>

      
      <!-- END DANGER ZONE -->

    </fieldset>
</form>

<?php include_file('desktop', 'configuration.JeedomConnect', 'js', 'JeedomConnect');?>