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

?>
<style>
  .required:after {
    content:" *";
    color: red;
  }
</style>
<div style="display: none;" id="div_simpleModalAlert"></div>
<form class="form-horizontal" style="overflow: hidden;">
  <div id="simpleModalAlert" style="display:none"></div>
  <ul id="modalOptions" style="padding-left:10px; list-style-type: none;">
    <li id="object-li" style="display:none;">
      <div class='form-group'>
      <label class='col-xs-3 ' >Objet</label>
      <div class='col-xs-9'>
        <select id="object-select" onchange="objectSelected();">
          <option value="none">{{Aucun}}</option>
          <?php
          foreach ((jeeObject::buildTree(null, false)) as $object) {
            echo '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
          }
          ?>
        </select>
      </div>
    </li>
  </ul>
</form>


<script>
function setSimpleModalData(options) {
	items = [];
	options.forEach(option => {
		if (option.type == "enable") {
			var value = option.value ? 'checked' : '';
			enable = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Actif</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-enable-input" ${value}></div></div></div></li>`;
			items.push(enable);
		} else if (option.type == "name") {
			var value = option.value ? option.value : '';
			name = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Nom</label>
			<div class='col-xs-9'><div class='input-group'><input style="width:150px;" id="mod-name-input" value='${value}'></div></div></div></li>`;
			items.push(name);
		} else if (option.type == "icon") {
      let icon = option.value ? iconToHtml(option.value) : '';
			icon = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Icone</label>
			<div class='col-xs-9'><div class='input-group'>
      <a class='btn btn-default btn-sm cursor bt_selectTrigger'
        tooltip='Choisir une icone' onclick="getSimpleIcon();">
      <i class='fas fa-flag'></i> Icône </a>
      <a id="icon-div">${icon} </a>
        </div></div>
      </div></li>`;
	        items.push(icon);
		} else if (option.type == "move") {
			move = `<li><div class='form-group'>
			<label class='col-xs-3 '>Déplacer vers</label>
			<div class='col-xs-9'><div class='input-group'><select style="width:150px;" id="mod-move-input" value=''>`;
		    option.value.forEach(item => {
		      move += `<option value="${item.id}">${item.name}</option>`;
		    });
		    move += `</select></div></div></div></li>`;
		    items.push(move);
		} else if (option.type == "string") {
			items.push(`<li>${option.value}</li>`);
		} else if (option.type == "expanded") {
			var value = option.value ? 'checked' : '';
			expanded = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Développé par défaut</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-expanded-input" ${value}></div></div></div></li>`;
			items.push(expanded);
		} else if (option.type == "widget") {
			widget = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Widget</label>
			<div class='col-xs-9'><div class='input-group'>
			<select style="width:250px;" id="mod-widget-input">`

			// configData.payload.widgets.forEach(item => {
      allWidgetsDetail.forEach(item => {
				if (option.choices.includes(item.type)) {
					let name = getWidgetPath(item.id);
          room = getRoomName(item.room);
          if (room) { name = name +' (' + room + ')'}
					widget += `<option style="width:150px;" value="${item.id}" name="${name}">${name}</option>`;
				}
			})
			widget += `</select></div></div></div></li>`;
			items.push(widget);
		} else if (option.type == "object") {
      $("#object-li").css("display", "block");
      if (option.value) {
        $('#object-select option[value="'+option.value+'"]').prop('selected', true);
      }
    } else if (option.type == "swipeUp" | option.type == "swipeDown") {
      swipe = `<li><div class='form-group'>
			   <label class='col-xs-3' >${option.type == 'swipeUp' ? "Swipe Up" : "Swipe Down"}</label>
			   <div class='col-xs-9'>
          <select id="${option.type}-select" onchange="swipeSelected('${option.type}');">
            <option value='none' ${option.value ? "" : "selected"}>Aucun</option>
            <option value='cmd' ${option.value ? option.value.type == 'cmd' ? "selected" : "": ""}>Exécuter une commande</option>
            <option value='sc' ${option.value ? option.value.type == 'sc' ? "selected" : "": ""}>Lancer un scénario</option>
          </select>
          <div class='input-group' id="${option.type}-cmd-div"
            style="display:${option.value ? option.value.type == 'cmd' ? "''" : "none": "none"};"><input class='input-sm form-control roundedLeft' style="width:260px;"
            id="${option.type}-cmd-input" value=''
            cmdId='${option.value ? option.value.type == 'cmd' ? option.value.id: '': ''}' disabled>
            <a class='btn btn-default btn-sm cursor bt_selectTrigger'
              tooltip='Choisir une commande' onclick="selectSimpleCmd('${option.type}');">
            <i class='fas fa-list-alt'></i></a>
          </div>
          <div class='input-group' id="${option.type}-sc-div"
          style="display:${option.value ? option.value.type == 'sc' ? "''" : "none": "none"};"><input class='input-sm form-control roundedLeft' style="width:260px;"
            id="${option.type}-sc-input" value='' scId='${option.value ? option.value.type == 'sc' ? option.value.id: '': ''}' disabled>
            <a class='btn btn-default btn-sm cursor bt_selectTrigger'
              tooltip='Choisir un scénario' onclick="selectSimpleSc('${option.type}');">
            <i class='fas fa-list-alt'></i></a>
         </div>
         </div></li>`;
			items.push(swipe);
    }
	});

	$("#modalOptions").append(items.join(""));
  refreshSwipe("swipeUp");
  refreshSwipe("swipeDown");

}

function refreshSwipe(type) {
  if ($("#"+type+"-cmd-input").attr('cmdId') != '') {
    getSimpleCmd({ id: $("#"+type+"-cmd-input").attr('cmdId'), success: function (data) {
      $("#"+type+"-cmd-input").val(data.result.humanName);
    }})
  }

  if ($("#"+type+"-sc-input").attr('scId') != '') {
    getSimpleScenarioHumanName({ id: $("#"+type+"-sc-input").attr('scId'), success: function (data) {
      data.forEach(sc => {
        if (sc['id'] == $("#"+type+"-sc-input").attr('scId')) {
          $("#"+type+"-sc-input").val(sc['humanName']);
        }
      })
    }})
  }
}

function objectSelected() {
  $("#mod-name-input").val($("#object-select  option:selected").text());
}

function swipeSelected(type) {
  val = $("#"+type+"-select  option:selected").val();
  if (val == 'cmd') {
    $("#"+type+"-cmd-div").css("display", "");
    $("#"+type+"-sc-div").css("display", "none");
  } else if (val == 'sc') {
    $("#"+type+"-cmd-div").css("display", "none");
    $("#"+type+"-sc-div").css("display", "");
  } else if (val == 'none') {
    $("#"+type+"-cmd-div").css("display", "none");
    $("#"+type+"-sc-div").css("display", "none");
  }
}

function selectSimpleCmd(name) {
  jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function(result) {
    $("#"+name+"-cmd-input").val(result.human);
    $("#"+name+"-cmd-input").attr('cmdId', result.cmd.id);
  })
}

function selectSimpleSc(name) {
  jeedom.scenario.getSelectModal({}, function(result) {
    $("#"+name+"-sc-input").attr('scId', result.id);
    $("#"+name+"-sc-input").val(result.human);
  })
}

function getSimpleScenarioHumanName(_params) {
var params = $.extend({}, jeedom.private.default_params, {}, _params || {});

  var paramsAJAX = jeedom.private.getParamsAJAX(params);
  paramsAJAX.url = 'core/ajax/scenario.ajax.php';
  paramsAJAX.data = {
   action: 'all',
   id: _params.id
  };
  $.ajax(paramsAJAX);
}

function getSimpleCmd({id, error, success}) {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {'action': 'getCmd', 'id': id },
    cache: false,
    success: function( cmdData ) {
      jsonData = JSON.parse(cmdData);
      if (jsonData.state == 'ok') {
        success && success(jsonData);
      } else {
        error && error(jsonData);
      }
    }
  });
}

function getSimpleIcon(name) {
  getIconModal({ title: "Choisir une icône", withIcon: "1", withImg: "0", icon: htmlToIcon($("#icon-div").children().first()) }, (result) => {
    $("#icon-div").html(iconToHtml(result));
  })
}

</script>
