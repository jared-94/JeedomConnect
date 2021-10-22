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


$alreadyChecked = config::byKey('notifAll', 'JeedomConnect', array());

$txt = '';
foreach (eqLogic::byType('JeedomConnect') as $eqLogic) {
  $tmpTxt = '';
  foreach ($eqLogic->getCmd('action') as $cmd) {
    if (strpos(strtolower($cmd->getLogicalId()), 'notif') !== false) {
      if ($cmd->getLogicalId() == 'notifall') continue;

      $checked = in_array($cmd->getId(), $alreadyChecked) ? 'checked' : '';
      $tmpTxt .= '<label class="checkbox-inline"><input type="checkbox" class="notifAllOptions" value="' . $cmd->getId() . '"  ' . $checked . '/> ' . $cmd->getName() . '</label> ';
    }
  }

  if ($tmpTxt != '') {
    $txt .= '<div class="col-lg-7"><legend>' . $eqLogic->getName() . '</legend><div class="form-group">';
    $txt .= '<div class="col-sm-7">' . $tmpTxt . '</div>';
    $txt .= '</div></div>';
  }
}




?>

<div id="alert_JcWidgetNotifAll"></div>
<a class="btn btn-success btn-sm pull-right" id="bt_saveJcWidgetNotifAll"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
<span style="color:var(--al-info-color);font-size:11px;">Note : tous les équipements que vous cocherez ici seront notifiés dès lors que vous utiliserez une commande 'Notifier les appareils JC'.</span>
<div id="table_JcWidgetNotifAll">
  <?= $txt; ?>

</div>


<?php include_file('desktop', 'notifAll.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop/common', 'utils', 'js'); ?>