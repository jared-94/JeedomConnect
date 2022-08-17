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
/** @var JeedomConnect $eqLogic */
foreach (\eqLogic::byType('JeedomConnect') as $eqLogic) {
  $configFile = $eqLogic->getConfig(false);
  if (!is_null($configFile) && !array_key_exists('formatVersion', $configFile)) $existOldFormat = true;
}

$pluginVersion = JeedomConnect::getPluginInfo();
$apkLink = $pluginVersion['enrollment'];
$beta = $pluginVersion['typeVersion'] == 'beta';

$orderBy = config::byKey('jcOrderByDefault', 'JeedomConnect', 'object');
$orderByArray = array(
  "object" => "Pièce",
  "name" => "Nom",
  "type" => "Type"
);

$optionsOrderBy = '';
foreach ($orderByArray as $key => $value) {
  $selected = ($key ==  $orderBy) ? 'selected' : '';
  $optionsOrderBy .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}


$userHash = '';
if (isConnect()) {
  if (isset($_SESSION['user']) && is_object($_SESSION['user'])) {
    $user = user::byId($_SESSION['user']->getId());
    if (is_object($user)) {
      // JCLog::debug('user session:' . $user->getHash());
      $userHash = $user->getHash();
    }
  }
}
sendVarToJS('userHash', $userHash);


?>
<form class="form-horizontal jeedomConnect">

  <?php
  if ($beta) {
  ?>
    <div style="text-align:center;margin-bottom:10px;">
      <div style="margin-bottom:10px;">
        <span class="alert alert-success">
          <a href="<?= $apkLink ?>" style="color: white !important;padding: 0px 10px;" target="_blank">S'enregister en tant que bêta-testeur</a>
        </span>
      </div>

      <div>
        <span>Afin d'accèder à l'application dans sa version bêta depuis le Store, vous devez être inscrit comme bêta-testeur</span>
      </div>
    </div>
  <?php
  }
  ?>

  <div class="customJCObject"></div>
  <div class="alert alert-warning infoRefresh" style="text-align:center;display:none;">
    Pour information : vous êtes en train de modifier des éléments de configuration essentiels au bon fonctionnement de vos équipements JC. <br />
    Les QR-Code de l'ensemble de vos équipements JC seront automatiquement regénérés après la sauvegarde de vos modifications.
  </div>

  <div class="alert alert-info" style="text-align:center;">
    Les paramètres ci-dessous doivent être configurés correctement pour le bon fonctionnement de l'application.<br />
    Les paramètres liés au websocket ne sont nécessaires que si vous l'activez.
    Si vous n'utilisez pas le websocket, vous pouvez désactiver le démon.<br />
    Après tout changement ici, veuillez redémarrer l'application.
  </div>
  <fieldset>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Adresse http externe}}</label>
      <div class="col-lg-3">
        <input class="configKey form-control needJCRefresh" type="string" data-l1key="httpUrl" placeholder="<?php echo network::getNetworkAccess('external'); ?>" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Adresse http interne}}</label>
      <div class="col-lg-3">
        <input class="configKey form-control needJCRefresh" type="string" data-l1key="internHttpUrl" placeholder="<?php echo network::getNetworkAccess('internal'); ?>" />
      </div>
    </div>
    <div class="alert alert-info" style="text-align:center;">
      La connexion par Websocket nécessite une configuration supplémentaire sur votre réseau, au moins pour un accès extérieur.
    </div>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Connexion IPV6}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Protocole de connexion en IPV6"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="ipv6" />
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
        <input class="configKey form-control needJCRefresh" type="string" data-l1key="wsAddress" placeholder="<?php echo 'ws://' . config::byKey('externalAddr') . ':8090'; ?>" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Adresse interne websocket}}</label>
      <div class="col-lg-3">
        <input class="configKey form-control needJCRefresh" type="string" data-l1key="internWsAddress" placeholder="<?php echo 'ws://' . config::byKey('internalAddr', 'core', 'localhost') . ':8090'; ?>" />
      </div>
    </div>

    <br />
    <!-- CUSTOM ZONE -->
    <div class="alert alert-success" style="text-align:center;">
      {{Personnalisation}}
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{Chemin pour les images perso}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Chemin où sont stockés vos images personnelles<br/>Indiquez-le SANS la racine de votre installation jeedom [/var/www/html/]<br/>Par exemple, renseignez 'data/img/' pour le répertoire '/var/www/html/data/img/'"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input class="configKey form-control" type="string" data-l1key="userImgPath" placeholder="<?= config::byKey('userImgPath', 'JeedomConnect'); ?>" />
      </div>
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{Trie des widgets par défaut}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Trie sur la page principale du plugin et dans l'assistant de configuration"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <select class="configKey form-control" data-l1key="jcOrderByDefault">
          <?php
          echo $optionsOrderBy;
          ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{QR Code avec le logo JC}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Insère le logo JC à l'intérieur du qr code de chaque équipement"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="withQrCode" checked />
      </div>
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{QR Code sur la page principale}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Au survol d'un équipement, son QR Code est affiché en haut de la page principale du plugin."></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="showQrCodeMainPage" />
      </div>
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{Activer le mode Expert}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Permet de laisser l'utilisateur modifier les commandes manuellement"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="isExpert" />
      </div>
    </div>

    <div class="form-group">
      <label class="col-lg-6 control-label">{{Création en masse en mode strict}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Impose que tous les types génériques nécessaires soient correctement configurés pour la création de widget en masse mais réduit le nombre d'erreur de l'assistant"></i>
        </sup>
      </label>
      <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="isStrict" checked />
      </div>
    </div>
    <br />

    <!-- LOCALISATION ZONE -->
    <div class="alert alert-success text-align">
      {{Localisation}}
    </div>

    <div class="form-group">
      <div class="description text-center">Point de répère par défaut pour calculer les distances avec les positions de chaque équipement</div>
    </div>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Latitude}}</label>
      <div class="col-lg-3">
        <input class="configKey form-control" type="string" data-l1key="latitude" placeholder="<?= config::bykey('info::latitude', 'core', 'celle de paris :)'); ?>" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-6 control-label">{{Longitude}}</label>
      <div class="col-lg-3">
        <input class="configKey form-control" type="string" data-l1key="longitude" placeholder="<?= config::bykey('info::longitude', 'core', 'celle de paris :)'); ?>" />
      </div>
    </div>

    <br />

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
        <label class="radio-inline"><input type="radio" name="filter" id="unusedOnly" /> non-utilisés</label>
        <label class="radio-inline"><input type="radio" name="filter" id="unexistingOnly" /> non-existants</label>
        <label class="radio-inline"><input type="radio" name="filter" id="all" checked /> tous</label>
      </div>
    </div>
    <div class="resultListWidget">
    </div>

    <div class="form-group">
      <label class="col-sm-6 control-label">{{Exporter}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Permet d'exporter vos widgets ou les personnalisation de vos widgets"></i>
        </sup>
      </label>
      <div class="col-sm-6 input-group" style="display:inline-flex;">
        <span class="input-group-btn">
          <input type="file" accept=".json" id="importConfig-input" style="display:none;">
          <a class="btn btn-warning exportConf" id="exportWidgetConf" data-type="exportWidgets"><i class="fa fa-save"></i> {{Widgets}}</a>
          <a class="btn btn-warning exportConf" id="exportCustomDataWidgetConf" data-type="exportCustomData"><i class="fa fa-save"></i> {{Personnalisation}}</a>
        </span>

      </div>
    </div>

    <div class="form-group">
      <label class="col-sm-6 control-label">{{Importer}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Permet d'importer les configurations de vos wigets et/ou de vos personnalisations de widgets"></i>
        </sup>
      </label>
      <div class="col-sm-6 input-group" style="display:inline-flex;">
        <span class="input-group-btn">
          <input type="file" accept=".json" id="importConfig-input" style="display:none;">
          <a class="btn btn-primary importConf" id="importWidgetConf" data-type="exportWidgets"><i class="fa fa-cloud-upload-alt"></i> {{Importer}}</a>
        </span>

      </div>
    </div>

    <!-- <hr>
    <div class="form-group" id="migrationDiv">
      <label class="col-sm-6 control-label">{{Migration des configurations}}
        <sup>
          <i class="fas fa-question-circle floatright" title="Permet de migrer vos configurations vers le nouveau format. Nécessaire pour le bon fonctionnement de l'application."></i>
        </sup>
      </label>
      <div class="col-sm-1">
        <?php
        if ($existOldFormat) {
          echo '<a class="btn btn-warning" id="migrateConf" ><i class="fas fa-exclamation-triangle"></i> {{Migrer}}
          </a>';
        } else {
          echo '<a class="btn btn-success" disabled id="migrateConf" style="cursor:not-allowed!important;" title="Tous vos équipements sont déjà sous le nouveau format" ><i class="fas fa-check-circle"></i> {{Migrer}}
          </a>';
        }
        ?>
      </div>
      <div class="col-sm-5 migrationOption">
        <label class="radio-inline"><input type="radio" name="migration" id="all" /> tous</label>
        <label class="radio-inline"><input type="radio" name="migration" id="enableOnly" checked />Uniquement les équipements actifs</label>
      </div>
    </div> -->


    <!-- END DANGER ZONE -->

  </fieldset>
</form>

<script>
  var JCdataChange = ''
  $('.needJCRefresh').on('focusin', function() {
    JCdataChange = $(this).val();
    // console.log('focus in', JCdataChange);
  });

  $('.needJCRefresh').on('focusout', function() {
    JCdataChangeOut = $(this).val();
    // console.log('focus out', JCdataChangeOut);
    if (JCdataChange != JCdataChangeOut) {
      $('.customJCObject').attr('data-needrefresh', true);
      $('.infoRefresh').show();
    }
  });

  function JeedomConnect_postSaveConfiguration() {

    if ($('.customJCObject').attr('data-needrefresh') == 'true') {
      $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
          action: 'generateQRcode'
        },
        dataType: 'json'
      });
      $('.infoRefresh').hide();
      $('.customJCObject').removeAttr('data-needrefresh');
    }


    // $.post({
    //   url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    //   data: {
    //     action: 'restartDaemon'
    //   },
    //   dataType: 'json'
    // });
  }
</script>


<?php include_file('desktop', 'configuration.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'generic.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'JeedomConnect', 'css', 'JeedomConnect'); ?>