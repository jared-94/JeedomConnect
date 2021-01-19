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
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$eqLogic = eqLogic::byId(init('eqLogicId'));
sendVarToJS('apiKey', $eqLogic->getConfiguration('apiKey'));


include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect');
include_file('desktop', 'configManager', 'js', 'JeedomConnect');
include_file('desktop', 'assistant.JeedomConnect', 'css', 'JeedomConnect');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/5.5.55/css/materialdesignicons.min.css">

<div class="container-modal">

<div style="display:none;" id="jc-assistant"></div>

<a class="btn btn-success pull-right" onclick="save()"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
<a class="btn btn-danger pull-right" onclick="resetConfig()"><i class="fa fa-times-circle"></i> {{Réinitialiser}}</a>
<legend> {{Configuration Jeedom Connect}}</legend>
<div class="tab fixed">
  <button class="tablinks" onclick="openTab(event, 'bottomTab')" id="defaultOpen">Menu du bas</button>
  <button class="tablinks" onclick="openTab(event, 'topTab')">Menu du haut</button>
  <button class="tablinks" onclick="openTab(event, 'roomTab')">Pièces</button>
  <button class="tablinks" onclick="openTab(event, 'widgetsTab')">Widgets</button>
</div>


<div id="bottomTab" class="tabcontent">
  <div class="leftContent">
    <h3>Menu du bas</h3>
    <a class="btn btn-success btn-sm " style="margin-top:5px;" onclick="addBottomTabModal()"><i class="fa fa-plus-circle"></i> Ajouter un menu</a>
    <ul id="bottomUL" class="tabsUL"></ul>
  </div>
  <div class="rightContent">
    <div class="alert alert-info">
	Vous pouvez ici configurer les menus du bas dans l'application. Choisissez de préférence un nom court et indiquez le nom EXACT d'une icone trouvée sur <br>
	<a href="https://materialdesignicons.com/" target="_blank">https://materialdesignicons.com/</a><br/>
  ou sur<br/>
  <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">https://fontawesome.com/</a>
	</div>
  <img src="plugins/JeedomConnect/desktop/img/bottom_tab.png" />
  </div>
</div>

<div id="topTab" class="tabcontent">
  <div class="leftContent">
    <h3>Menu du haut</h3>
    Sous menu de :
    <select name="topTabParents" id="topTabParents-select" style="width:300px" onchange="refreshTopTabContent()"></select>
    <br>
    <a class="btn btn-success btn-sm " style="margin-top:5px;" onclick="addTopTabModal()"><i class="fa fa-plus-circle"></i> Ajouter un menu</a>
    <ul id="topUL" class="tabsUL"></ul>
  </div>
  <div class="rightContent">
    <div class="alert alert-info">
	Chaque menu du bas peut avoir une liste de menu haut.
	</div>
  <img src="plugins/JeedomConnect/desktop/img/top_tab.png" />
  </div>
</div>

<div id="roomTab" class="tabcontent">
  <div class="leftContent">
    <h3>Pièces</h3>
    <a class="btn btn-success btn-sm " style="margin-top:5px;" onclick="addRoomModal()"><i class="fa fa-plus-circle"></i> Ajouter une pièce</a>
    <ul id="roomUL" class="tabsUL"></ul>
  </div>
  <div class="rightContent">
    <div class="alert alert-info">
	Vous pouvez définir ici des noms de pièces auxquelles seront attachés vos widgets.
	</div>
  </div>
</div>

<div id="widgetsTab" class="tabcontent">
  <div class="leftContent">
    <h3>Widgets</h3>
    Emplacement des widgets :
    <select name="widgetsParents" id="widgetsParents-select" style="width:300px" onchange="refreshWidgetsContent()"></select>
    <br>
    <a class="btn btn-success btn-sm " style="margin-top:5px;" id="btn-addWidget" onclick="addWidgetModal()"><i class="fa fa-plus-circle"></i> Ajouter un widget</a>
    <a class="btn btn-success btn-sm " style="margin-top:5px;margin-left:10px;" onclick="addGroupModal()"><i class="fa fa-plus-circle"></i> Ajouter un groupe</a>
    <ul id="widgetsUL" class="tabsLargeUL"></ul>
  </div>
  <div class="rightContent">
    <div class="alert alert-info">
	Vous pouvez ajouter des widgets, ainsi que des groupes pour les classer.
	</div>
  <img src="plugins/JeedomConnect/desktop/img/widget.png" />
  </div>
</div>

</div>
