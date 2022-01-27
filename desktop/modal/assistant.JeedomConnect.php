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

/** @var \eqLogic */
$eqLogic = eqLogic::byId(init('eqLogicId'));
sendVarToJS('apiKey', $eqLogic->getConfiguration('apiKey'));

$eqName = $eqLogic->getName();


include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect');
include_file('desktop', 'configManager', 'js', 'JeedomConnect');
include_file('desktop', 'assistant.JeedomConnect', 'css', 'JeedomConnect');

/** @var array<JeedomConnect> $eqLogics */
$eqLogics = eqLogic::byType('JeedomConnect');

$widgetsConfigJsonFile = file_get_contents('plugins/JeedomConnect/core/config/widgetsConfig.json');

$widgetsConfigFile = json_decode($widgetsConfigJsonFile, true);
$widgetsConfigGlobal = [];
foreach ($widgetsConfigFile['widgets'] as $widget) {
  $widgetsConfigGlobal[$widget['type']] = $widget['name'];
}

//  prepare list of Widget Already Created
$widgetAvailOptions = '';
$widgetTypeAvail = [];
$widgetRoomAvail = [];
$widgetArray = JeedomConnectWidget::getWidgets();

$orderBy = config::byKey('jcOrderByDefault', 'JeedomConnect', 'object');
switch ($orderBy) {
  case 'name':
    $widgetName = array_column($widgetArray, 'name');
    array_multisort($widgetName, SORT_ASC, $widgetArray);
    break;

  case 'type':
    $widgetType = array_column($widgetArray, 'type');
    $widgetName = array_column($widgetArray, 'name');
    array_multisort($widgetType, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
    break;

  default:
    break;
}

$listWidget = '';
foreach ($widgetArray as $widget) {

  $img = $widget['img'];

  $type = $widget['type'];
  $widgetName = $widget['name'];
  $widgetRoom = $widget['roomName'] == 'Aucun' ? '' :  ' (' . $widget['roomName'] . ')';
  $id = $widget['id'];

  $widgetTypeAvail[$type] = $widgetsConfigGlobal[$type];

  $widgetRoomAvail[$widget['roomName']] = $widget['roomName'];

  $widgetAvailOptions .= '<option value="' . $id . '" data-widget-id="' . $id . '" data-type="' . $type . '" data-room-name="' . $widget['roomName'] . '">' . $widgetName . $widgetRoom . ' [' . $id . ']</option>';
}
asort($widgetTypeAvail);
asort($widgetRoomAvail);


$summaryConfig = config::byKey('object:summary');

$summaryAvailOptions = '';
foreach ($summaryConfig as $index => $summary) {
  $icon = $summary['icon'];
  $icon = '';

  if (array_key_exists('icon', $summary)) {
    $matches = array();
    preg_match('/(.*)class=\"(.*)\"(.*)/', $summary['icon'], $matches);

    if (count($matches) > 3) {
      list($iconType, $iconImg) = explode(" ", $matches[2], 2);
      $iconType = ($iconType == 'icon') ? 'jeedom' : 'fa';
      $iconImg = ($iconType == 'fa') ? str_replace('fa-', '', $iconImg) : $iconImg;

      preg_match('/(.*) icon_(.*)/', $iconImg, $matchesbis);
      $color = '';
      if (count($matchesbis) > 2) {
        switch ($matchesbis[2]) {
          case 'blue':
            $color = 'data-icon-color="#0000FF"';
            break;
          case 'yellow':
            $color = 'data-icon-color="#FFFF00"';
            break;
          case 'orange':
            $color = 'data-icon-color="#FFA500"';
            break;
          case 'red':
            $color = 'data-icon-color="#FF0000"';
            break;
          case 'green':
            $color = 'data-icon-color="#008000"';
            break;
          default:
            $color = '';
            break;
        }
        $iconImg = trim(str_replace('icon_' . $matchesbis[2], '', $iconImg));
      }

      $icon = 'data-icon-source="' . $iconType . '" data-icon-name="' . $iconImg . '" ' . $color;
    }
  }

  $summaryAvailOptions .= '<option value="' . $summary['key'] . '" data-key="' . $summary['key'] . '"
                        data-name="' . $summary['name'] . '" ' . $icon . '
                        >' . $summary['name'] . '</option>';
}


?>

<link href="/plugins/JeedomConnect/desktop/css/md/css/materialdesignicons.css" rel="stylesheet">

<div class="container-modal">

  <div style="display:none;" id="jc-assistant"></div>

  <div id="" class="col-sm-12">
    <legend class="col-sm-3 pull-left">Personnalisation de &gt; <?= $eqName ?> &lt;</legend>
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
        <button class="tablinks" onclick="openTab(event, 'backgroundTab')">Fond d'écran</button>
        <button class="tablinks" onclick="openTab(event, 'weatherTab')">Météo</button>
        <button class="tablinks" onclick="openTab(event, 'batteryTab')">Batteries</button>
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
        <div class="col-sm-7">
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
                    echo $summaryAvailOptions;
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
        <div class="col-sm-7">
          <form class="form-horizontal">
            <fieldset>
              <h3>Widgets</h3>
              <div class="form-group">
                <label class="col-sm-5 control-label">{{Emplacement des widgets}}</label>
                <div class="col-sm-7">
                  <select name="widgetsParents" id="widgetsParents-select" style="width:300px" onchange="refreshWidgetsContent()"></select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-5 control-label">{{Pièce}}</label>
                <div class="col-sm-7">
                  <select id="selWidgetRoom" class="form-control">
                    <option value="all">{{Toutes}}</option>
                    <?php
                    foreach ($widgetRoomAvail as $key => $value) {
                      echo '<option value="' . $key . '">' . $widgetRoomAvail[$key] . '</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-5 control-label">{{Type de Widget}}</label>
                <div class="col-sm-7">
                  <select id="selWidgetType" class="form-control">
                    <option value="all">{{Tous}}</option>
                    <?php
                    foreach ($widgetTypeAvail as $key => $value) {
                      echo '<option value="' . $key . '">' . $widgetTypeAvail[$key] . '</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>


              <div class="form-group">
                <label class="col-sm-5 control-label">{{Widget}}</label>
                <div class="col-sm-7">
                  <select id="selWidgetDetail" class="form-control">
                    <option value="none" data-widget-id="none">{{Aucun}}</option>
                    <?php
                    echo $widgetAvailOptions;
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-5 control-label"></label>
                <div class="col-sm-7">
                  <input class="form-control" type="checkbox" id="hideExist"> {{Masquer les éléments déjà présents}}
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

      <!-- BACKGROUND PART -->

      <div id="backgroundTab" class="col-sm-12 tabcontent" style="width: 100%;">
        <!--    START LEFT PANEL --->
        <div class="col-sm-8">
          <form class="form-horizontal">
            <fieldset>
              <h3>Fond d'écran</h3>

              <div class="form-group">
                <label class="col-sm-5 control-label">{{Image si aucune condition vérifiée}}</label>
                <div class="col-sm-7">
                  <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une image' onclick="getBgImg();">
                    <i class='fas fa-flag'></i> Image
                  </a>
                  <a id="bg-icon-div" onclick='removeBgImg();'> </a>
                </div>
              </div>


              <div class="form-group">
                <label class="col-sm-5 control-label">{{Image sous conditions}}</label>
                <div class="col-sm-7">
                  <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Ajouter une condition' onclick="addCondImg();">
                    <i class='fas fa-plus'></i> Ajouter
                  </a>
                </div>
              </div>

              <div id="condImgList"></div>
            </fieldset>
          </form>
        </div>
        <!--    END LEFT PANEL --->

        <!--    START RIGHT PANEL --->
        <div class="col-sm-4">
          <div class="alert alert-info">
            Vous pouvez ici configurer le fond d'écran de l'application. Utilisez de préférence des images adaptées à la taille de votre écran.
          </div>

          <div class="alert alert-info">
            Pour les conditions, utilisez les opérateurs ==, !=, <,>, <=,>=, &&, ||
          </div>

          <div class="alert alert-info">
            Variables disponibles :
            <ul>
              <li><b>#bottomTabId#</b> : id du menu bas en cours (visible en survolant la souris sur les menus bas)</li>
              <li><b>#topTabId#</b> : id du menu haut en cours (visible en survolant la souris sur les menus haut)</li>
              <li><b>#screenId#</b> : id de la page en cours. Valeurs possibles :
                <ul>
                  <li>1 : Page principale</li>
                  <li>2 : Page Pièces</li>
                  <li>3 : Page Notifications</li>
                  <li>4 : Page scénarios</li>
                  <li>5 : Page Préférences</li>
                  <li>6 : Page Applications</li>
                  <li>7 : Page Batteries</li>
                  <li>8 : Page Santé</li>
                  <li>9 : Page MaJ Plugins</li>
                  <li>10: Page détails</li>
                </ul>
              </li>
              <li><b>#roomId#</b> : id de la pièce en cours lorsqu'on est dans la page Pièces. Utilisez 0 pour le premier onglet Pièces</li>
            </ul>
          </div>

        </div>
        <!--    END RIGHT PANEL --->
      </div>

      <!-- WEATHER PART -->

      <div id="weatherTab" class="tabcontent">
        <div class="leftContent">
          <h3>Météo</h3>

          <div class="input-group " style="width: 90%">
            Equipement
            <input class="roundedLeft" style="margin-left:5px; width:400px" id="weather-input" value="" disabled>
            <a class="btn btn-default listEquipementInfo" tooltip="Sélectionner un équipement" onclick="getWeatherEq();"><i class="fas fa-list-alt"></i></a>
            <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="removeWeatherEq();"></i>
          </div>

        </div>
        <div class="rightContent">
          <div class="alert alert-info">
            Vous pouvez ici configurer la météo. Seul le plugin officiel Weather est compatible.
          </div>
        </div>
      </div>


      <!-- BATTERY PART -->

      <div id="batteryTab" class="col-sm-12 tabcontent" style="width: 100%;">
        <!--    START LEFT PANEL --->
        <div class="col-sm-8">
          <form class="form-horizontal">
            <fieldset>
              <h3>Batteries</h3>

              <div class="form-group">
                <label class="col-sm-5 control-label">{{Image sous conditions}}</label>
                <div class="col-sm-7">
                  <a class='btn btn-default btn-sm cursor bt_BatteryImgSelection' tooltip='Ajouter une condition' onclick="addBatteryCondImg();">
                    <i class='fas fa-plus'></i> Ajouter
                  </a>
                </div>
              </div>

              <div id="batteryImgList"></div>
            </fieldset>
          </form>
        </div>
        <!--    END LEFT PANEL --->

        <!--    START RIGHT PANEL --->
        <div class="col-sm-4">
          <div class="alert alert-info">
            Vous pouvez configurer ici les conditions et icones à utiliser pour afficher les équipements ayant des batteries.
          </div>

          <div class="alert alert-info">
            Pour les conditions, utilisez les opérateurs ==, !=, <,>, <=,>=, &&, ||
          </div>

          <div class="alert alert-info">
            Variables disponibles :
            <ul>
              <li><b>#plugin#</b> : le nom du plugin (id)</li>
              <li><b>#battery#</b> : pourcentage de la batterie restante</li>
              <li><b>#level#</b> : niveau de criticité. Valeurs possibles :
                <ul>
                  <li>good</li>
                  <li>warning</li>
                  <li>critical</li>
                </ul>
              </li>
            </ul>
          </div>

        </div>
        <!--    END RIGHT  --->
      </div>
      <!--    END BATTERY PANEL --->



    </div>
  </div>