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

if (!isConnect()) {
  throw new Exception('{{401 - Accès non autorisé}}');
}

require_once dirname(__FILE__) . '/../../core/class/JeedomConnectWidget.class.php';


$allNotifAll = config::searchKey('notifAll', 'JeedomConnect');
$notifOptions = '';
foreach ($allNotifAll as $item) {
  if (in_array($item['key'], array('migration::notifAll', 'migration::notifAll2'))) continue;
  // JCLog::debug('value => ' . json_encode($item['value']));
  $conf = $item['value'];

  $notifOptions .= '<option value="' . $item['key'] . '" data-cmd="' . implode(",", $conf['cmd']) . '"  data-text="' . strtolower($conf['name'])  . '">' . $conf['name'] . '</option>';
}


$txt = '';
$index = 0;
/** @var JeedomConnect $eqLogic */
foreach (JeedomConnect::getAllJCequipment() as $eqLogic) {
  $tmpTxt = '';
  foreach ($eqLogic->getCmd('action') as $cmd) {
    if (
      strpos(strtolower($cmd->getLogicalId()), 'notif') !== false  //si c'est une notif
      &&
      (strpos(strtolower($cmd->getLogicalId()), 'notifall') === false) // mais que ca n'est pas une notifALL
    ) {
      $tmpTxt .= '<label class="checkbox-inline"><input type="checkbox" class="notifAllOptions" value="' . $cmd->getId() . '"/> ' . $cmd->getName() . '</label> ';
    }
  }

  if ($tmpTxt != '') {

    if ($index == 0) {
      $txt .= '<div class="row">';
      $index = 0;
    }

    $txt .= '<div class="col-lg-4"><legend class="underline">' . $eqLogic->getName() . '</legend>';
    $txt .= '<div class="form-group">' . $tmpTxt . '</div>';
    $txt .= '</div>';
    $index++;

    if (($index % 3) == 0) {
      $txt .= '</div>';
      $index = 0;
    }
  }
}




?>

<div id="alert_JcWidgetNotifAll"></div>
<div class="row">
  <div class="input-group pull-right" style="display:inline-flex;">
    <!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
    <a class="btn btn-sm roundedLeft" id="bt_editJcNotifAll"><i class="fa fa-pencil-alt"></i> {{Editer}}
    </a><a class="btn btn-info btn-sm" id="bt_addJcNotifAll"><i class="fa fa-plus-circle"></i> {{Ajouter}}
    </a><a class="btn btn-success btn-sm" id="bt_saveJcNotifAll"><i class="fa fa-check-circle"></i> {{Sauvegarder}}
    </a><a class="btn btn-danger btn-sm roundedRight" id="bt_removeJcNotifAll"><i class="fa fa-minus-circle"></i> {{Supprimer}}
    </a>

  </div>
</div>

<div class="row">
  <div class="form-group">
    <label class="col-sm-3 control-label">
      <legend><i class="fa fa-cogs"></i> {{Configurer la commande :}}</legend>
    </label>
    <div class="col-sm-7 control-label">
      <select id="notifAllSelect" class="JC" style="width:auto">
        <?= $notifOptions; ?>
      </select>

    </div>
  </div>
</div>


<legend><i class="fas fa-comment-dots"></i> {{Notifier les équipements :}}</legend>
<span style="color:var(--al-info-color);font-size:11px;">Note : tous les équipements que vous cocherez ici seront notifiés dès lors que vous utiliserez la commande définie plus haut.</span>
<div id="table_JcWidgetNotifAll">
  <?= $txt; ?>

</div>


<?php include_file('desktop', 'notifAll.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'generic.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop/common', 'utils', 'js'); ?>