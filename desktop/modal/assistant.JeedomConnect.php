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

$eqName = $eqLogic->getName();


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
$widgetArray= JeedomConnectWidget::getWidgets();

$listWidget = '';
foreach ($widgetArray as $widget) {

	$img = $widget['img'] ;

	$type = $widget['type'];
	$widgetName = $widget['name'] ;
	$widgetRoom = $widget['roomName'] == 'Aucun' ? '' :  ' (' . $widget['roomName'] . ')' ;
	$id = $widget['id'];

  $widgetTypeAvail[$type] = $widgetsConfigGlobal[$type] ;
	$widgetAvailOptions .= '<option value="'.$id.'" data-widget-id="'.$id.'" data-type="'.$type.'">' . $widgetName . $widgetRoom . ' ['.$id.']</option>' ;

}
asort($widgetTypeAvail);


$summaryConfig = config::byKey('object:summary');

$summaryAvailOptions = '';
foreach ($summaryConfig as $index => $summary) {
  $icon = $summary['icon'] ;
  $icon = '';

  if ( array_key_exists('icon',$summary ) ){
    $matches = array();
    preg_match('/(.*)class=\"(.*)\"(.*)/', $summary['icon'], $matches);

    if (count($matches) > 3){
      list($iconType, $iconImg) = explode(" ", $matches[2], 2);
      $iconType = ($iconType=='icon') ? 'jeedom' : 'fa';
      $iconImg = ($iconType=='fa') ? str_replace('fa-','',$iconImg) : $iconImg;
      $icon = 'data-icon-source="'.$iconType.'" data-icon-name="'.$iconImg.'"';
    }
  }

  $summaryAvailOptions .= '<option value="'.$summary['key'].'" data-key="'.$summary['key'].'" 
                        data-name="'.$summary['name'].'" '.$icon.'
                        >' . $summary['name'] . '</option>' ;

}


?>

<link href="/plugins/JeedomConnect/desktop/css/md/css/materialdesignicons.css" rel="stylesheet">

<div class="container-modal">

<div style="display:none;" id="jc-assistant"></div>

<div id="" class="col-sm-12">
    <legend class="col-sm-3 pull-left">Personnalisation de &gt; <?=$eqName?> &lt;</legend>
    <div class="pull-right">
      <a class="btn btn-success pull-right" onclick="save()"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
      <a class="btn btn-danger pull-right" onclick="resetConfig()"><i class="fa fa-times-circle"></i> {{Réinitialiser}}</a>
    </div>
</div>

<div id="widgetConfContainer" class="col-sm-12">

  <div id="detailMenu" class="col-sm-2 " style="margin-right: 20px;">
    <div class="tab fixed">
      <button class="tablinks" onclick="openTab(event, 'bottomTab')" id="defaultOpen">Menu du bas</button>
      <button class="tablinks" onclick="openTab(event, 'topTab')">Menu du haut</button>
      <button class="tablinks" onclick="openTab(event, 'roomTab')">Pièces</button>
      <button class="tablinks" onclick="openTab(event, 'summaryTab')">Résumés</button>
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
        Vous pouvez ici configurer les menus du bas dans l'application. Choisissez une icône et de préférence un nom court.
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
      Vous pouvez définir ici les pièces qui seront affichées dans le menu pièce (pour les résumés).
      </div>
    </div>
  </div>
 
  <!-- SUMMARY PART -->

  <div id="summaryTab" class="col-sm-12 tabcontent">
    <div  class="col-sm-7">
      <form class="form-horizontal">
        <fieldset>
          <h3>Résumés</h3>
            
            <div class="form-group">
              <label class="col-sm-5 control-label">{{Encore disponible : }}
                <sup>
                    <i class="fas fa-question-circle floatright" title="Non ajouté ci-dessous, mais disponible et paramétré dans la configuration de Jeedom"></i>
                </sup>
              </label>
              <div class="col-sm-7">
                <select id="selSummaryDetail" class="form-control">
                  <option value="none" data-key="none">{{Aucun}}</option>
                  <?php
                  echo $summaryAvailOptions ;
                  ?>
                </select>
              </div>
            </div>


            <div class="input-group " style="display:inline-flex;">
              <span class="input-group-btn">
                <a class="btn btn-success btn-sm " style="margin-top:5px;" id="btn-selectSummary" onclick="selectSummary()"><i class="fa fa-plus-circle"></i> Ajouter ce résumé</a>
                <a class="btn btn-warning btn-sm " style="margin-top:5px;" id="btn-importAllSummary" onclick="importAllSummary()"><i class="fa fa-plus-circle"></i> Importer tous les résumés</a>
              </span>
            </div>

            <ul id="summaryUL" class="tabsLargeUL"></ul>
        </fieldset>
      </form>
    </div>

    <!-- <div class="col-sm-4">
      <div class="alert alert-info">
      Vous pouvez customiser les icônes de vos résumés
      </div>
      <img src="plugins/JeedomConnect/desktop/img/widget.png" />
    </div> -->
  </div>




  <!-- WIDGET PART -->

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
