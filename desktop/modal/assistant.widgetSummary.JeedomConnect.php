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


?>

<div id="alert_JcWidgetSummary"></div>
<a class="btn btn-success btn-sm pull-right" id="bt_saveJcWidgetSummary"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
<a class="btn btn-default btn-sm pull-right" id="bt_updateWidgetSummary" style="display:none;"><i class="fas fa-sync-alt"></i> {{Rafraichir}}</a>
<a class="btn btn-danger btn-sm pull-right" id="bt_removeJcWidgetSummary" style="display:none;"><i class="fas fa-trash-alt"></i> {{Supprimer}}</a>
<span style="color:var(--al-info-color);font-size:11px;">Note : vous pouvez éditer un widget en cliquant sur son id. Les modifications que vous réaliserez sur la nouvelle fenêtre ne seront pas répercutées automatiquement sur cette page.<br/>Pour rafraichir les données, cliquez sur le bouton 'rafraichir' une fois qu'il apparaitra.</span>
<table id="table_JcWidgetSummary" class="table table-bordered table-condensed tablesorter stickyHead">
  <thead>
    <tr>
      <th>{{ID}}</th>
      <th>{{Type}}</th>
      <th data-sorter="select-text">{{Pièce}}</th>
      <th data-sorter="false" data-filter="false">{{Nom}}</th>
      <th data-sorter="false" data-filter="false">{{Sous-titre}}</th>
      <th data-sorter="checkbox" data-filter="false">{{Visible}}</th>
      <th data-sorter="select-text" data-filter="false">{{Affichage forcé}}</th>
      <th data-sorter="checkbox" data-filter="false">{{Titre}}</th>
      <th data-sorter="checkbox" data-filter="false">{{Sous-Titre}}</th>
      <th data-sorter="checkbox" data-filter="false" style="max-width:65px;">{{Statut}}</th>
      <th data-sorter="checkbox" data-filter="false">{{Icon}}</th>
      <th data-sorter="checkbox" data-filter="false">{{Bloquer détails}}</th>
      <th >{{nb Eq}}</th>
      <th data-sorter="false" data-filter="false" style="text-align:center;"><i class="fas fa-trash-alt"></i></th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>


<?php include_file('desktop', 'widgetsummary.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'Assistant.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop/common', 'utils', 'js'); ?>