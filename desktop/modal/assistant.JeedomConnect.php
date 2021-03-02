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

$plugin = plugin::byId('JeedomConnect');
$eqLogics = eqLogic::byType($plugin->getId());


$widgetsConfigJsonFile = file_get_contents('plugins/JeedomConnect/resources/widgetsConfig.json');

$widgetsConfigFile = json_decode($widgetsConfigJsonFile, true);
$widgetsConfigGlobal = [];
foreach ($widgetsConfigFile['widgets'] as $widget ) {
  $widgetsConfigGlobal[$widget['type']]=$widget['name'];
}


//  prepare list of Widget Already Created
$widgetAvailOptions = '';
$widgetTypeAvail = [];
foreach ($eqLogics as $eqLogic) {
  if ($eqLogic->getConfiguration('type','') == 'widget') {
      $conf = $eqLogic->getConfiguration('widgetJC','') ;
      $json = json_decode($conf);
      $type = $json->type;
      $widgetTypeAvail[$type] = $widgetsConfigGlobal[$type] ; 
      $parentObject = $eqLogic->getObject();
      $objName = is_object($parentObject) ? ' (' . $parentObject->getName() . ')' : '';
      $name = preg_replace('/\(JCW(\w+)\)/i', '', $eqLogic->getName() )  . $objName ;
      $widgetAvailOptions .= '<option value="'.$eqLogic->getId().'" data-widget-id="'.$eqLogic->getId().'" data-type="'.$type.'">' . $name . '</option>' ;
  }
}

asort($widgetTypeAvail);


?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/5.5.55/css/materialdesignicons.min.css">



<div class="container-modal">

<div style="display:none;" id="jc-assistant"></div>

<a class="btn btn-success pull-right" onclick="save()"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
<a class="btn btn-danger pull-right" onclick="resetConfig()"><i class="fa fa-times-circle"></i> {{Réinitialiser}}</a>

<div id="widgetConfContainer" class="col-sm-12">
  
  <div id="detailMenu" class="col-sm-2 " style="margin-right: 20px;">
    <legend> {{Configuration Jeedom Connect}}</legend>
    <div class="tab fixed">
      <button class="tablinks" onclick="openTab(event, 'bottomTab')" id="defaultOpen">Menu du bas</button>
      <button class="tablinks" onclick="openTab(event, 'topTab')">Menu du haut</button>
      <button class="tablinks" onclick="openTab(event, 'roomTab')">Pièces</button>
      <button class="tablinks" onclick="openTab(event, 'widgetsTab')">Widgets</button>
    </div>
  </div>



<div id="detailTab" class="col-sm-9 ">

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

  
  <div id="widgetsTab" class="col-sm-12 tabcontent">
    <div  class="col-sm-7">
      <form class="form-horizontal">
        <fieldset>
          <h3>Widgets</h3>
            <div class="form-group">
              <label class="col-sm-5 control-label" >{{Emplacement des widgets}}</label>
              <div class="col-sm-7">
                <select name="widgetsParents" id="widgetsParents-select" style="width:300px" onchange="refreshWidgetsContent()"></select>
              </div>
            </div>    

            <div class="form-group">
              <label class="col-sm-5 control-label" >{{Type de Widget}}</label>
              <div class="col-sm-7">
                <select id="selWidgetType" class="form-control">
                  <option value="">{{Aucun}}</option>
                  <?php
                  foreach ($widgetTypeAvail as $key => $value) {
                    echo '<option value="'.$key.'">'.$widgetTypeAvail[$key].'</option>' ;
                  }
                  ?>
                </select>
              </div>
            </div>


            <div class="form-group">
              <label class="col-sm-5 control-label" >{{Widget}}</label>
              <div class="col-sm-7">
                <select id="selWidgetDetail" class="form-control">
                  <option value="none" data-widget-id="none">{{Aucun}}</option>
                  <?php
                  echo $widgetAvailOptions ;
                  ?>
                </select>
              </div>
            </div>
          
            
            <div class="input-group " style="display:inline-flex;">
              <span class="input-group-btn">
                <!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
                <a class="btn btn-success btn-sm " style="margin-top:5px;" id="btn-selectWidget" onclick="selectWidgetModal()"><i class="fa fa-plus-circle"></i> Ajouter ce widget</a>
                <a class="btn btn-success btn-sm " style="margin-top:5px;margin-left:10px;" onclick="addGroupModal()"><i class="fa fa-plus-circle"></i> Ajouter un groupe</a>
              </span>
            </div>

          
            <ul id="widgetsUL" class="tabsLargeUL"></ul>
        </fieldset>
      </form>
    </div>

    <div class="col-sm-4">
      <div class="alert alert-info">
      Vous pouvez ajouter des widgets, ainsi que des groupes pour les classer.
      </div>
      <img src="plugins/JeedomConnect/desktop/img/widget.png" />
    </div>
  </div>
</div>

</div>
