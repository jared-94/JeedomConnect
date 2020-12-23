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
  #widgetImg {
	  display:block;
	  margin-left:auto;
	  margin-right:auto;
	  width: 100px;
	  margin-bottom:25px;
	  margin-top:15px;
  }
  .description {
	  color:var(--al-info-color);
	  font-size:11px;
  }
</style>

<div class="" style="margin:auto; width:800px; height:350px;">
  <div style="display:none;" id="notif-alert"></div>
    <h3 style="margin-left:25px;">Options de la notification</h3><br>
	<div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
	<form class="form-horizontal" style="overflow: hidden;">
	  <ul id="notifOptions" style="padding-left:10px; list-style-type: none;">
	  </ul>
	</form>
 </div>

 <script>
 var actionList = [];

  function setNotifModalData(options) {
  	items = [];
    actionList = [];
    var notif = options.notif;
    //Name
    var value = notif ? notif.name : '';
    var nameHtml = `<li><div class='form-group'>
    <label class='col-xs-3  required' >Nom</label>
    <div class='col-xs-9'><div class='input-group'><input style="width:250px;" id="mod-notifName-input" value='${value}'></div></div></div></li>`;
    items.push(nameHtml);

    //Channel
    var channelValue = notif ? notif.channel : '';
    var channelsHtml = `<li><div class='form-group'>
    <label class='col-xs-3 required'>Canal</label>
    <div class='col-xs-9'><div class='input-group'><select style="width:150px;" id="mod-channel-input" value=''>`;
    notifData.channels.forEach(item => {
        channelsHtml += `<option value="${item.id}">${item.name}</option>`;
    });
    channelsHtml += `</select></div></div></div></li>`;
    items.push(channelsHtml);

    //Update
    var updateValue = notif ? notif.update ? "checked" : "" : "";
    var updateHtml = `<li><div class='form-group'>
    <label class='col-xs-3'>Mettre à jour l'existante</label>
    <div class='col-xs-9'>
      <div class="description">Si une autre notification de cette catégorie existe, son contenu sera mis à jour</div>
    <div class='input-group'>
      <input type="checkbox" style="width:150px;" id="update-input" ${updateValue}>
    </div></div></div></li>`;

    items.push(updateHtml);

    //Color
    var colorValue = notif ? notif.color || '' : '';
    var colorHtml = `<li><div class='form-group'>
    <label class='col-xs-3 ' >Couleur</label>
    <div class='col-xs-9'>
      <div class='input-group'><input style="width:200px;" id="mod-color-input" value='${colorValue}'>
      <input type="color" id="mod-color-picker" value='${colorValue}' onchange="colorDefined(this)">
      </div></div>
    </div></li>`;
    items.push(colorHtml);

    //Integrated image
    var imageHtml = `<li><div class='form-group'>
      <label class='col-xs-3 ' >Image</label>
        <div class='col-xs-9'>
        <span class="input-group-btn">
            <img id="mod-img-img" src="" style="width:30px; height:30px; margin-top:-15px; display:none;" />
            <i class="mdi mdi-minus-circle" id="mod-img-remove" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;display:none;" aria-hidden="true" onclick="removeImage()"></i>
            <a class="btn btn-success roundedRight" onclick="imagePicker()"><i class="fas fa-check-square">
            </i> Choisir </a>
        </span></div></div></li>`;

    items.push(imageHtml);

    //Actions
    var actionsHtml = `<li><div class='form-group'>
      <label class='col-xs-3 ' >Actions</label>
        <div class='col-xs-9'>
        <div class="description">Configurez jusqu'à 3 actions. Elles seront ignorées en cas d'utilisation de Ask</div>
        <span class="input-group-btn">
            <a class="btn btn-default roundedRight" onclick="addCmd()"><i class="fas fa-plus-square">
            </i> Ajouter une commande</a>
            <a class="btn btn-default roundedRight" onclick="addScenario()"><i class="fas fa-plus-square">
            </i> Ajouter un scénario</a>
            </span><div id="actionList"></div>
            </span></div></div></li>`;

    items.push(actionsHtml);


    $("#notifOptions").html(items.join(""));
    if (notif) {
      $('#mod-channel-input option[value="'+notif.channel+'"]').prop('selected', true);
      actionList = notif.actions || [];
      if (notif.image) {
        $("#mod-img-img").attr("value", notif.image);
        $("#mod-img-img").attr("src", "plugins/JeedomConnect/data/img/"+notif.image);
        $("#mod-img-img").css("display", "");
        $("#mod-img-remove").css("display", "");
      }
    }

    refreshActionList();
  }



	function imagePicker() {
		getImageModal({title: "Choisir une image", selected: $("#mod-img-img").attr("value") } , function(result) {
			$("#mod-img-img").attr("value", result);
			$("#mod-img-img").attr("src", "plugins/JeedomConnect/data/img/"+result);
			$("#mod-img-img").css("display", "");
			$("#mod-img-remove").css("display", "");
		});
	}

	function removeImage() {
		$("#mod-img-img").attr("src", "");
		$("#mod-img-img").attr("value", "");
		$("#mod-img-img").css("display", "none");
		$("#mod-img-remove").css("display", "none");
	}

  function colorDefined(c) {
    $("#mod-color-input").val(c.value);
  }

  function addCmd() {
    if (actionList.length > 2) {
      return;
    }
    actionList.forEach(item => {
      item.name = $("#"+item.id+"-name-input").val();
    });
		jeedom.cmd.getSelectModal({cmd:{type: 'action', subType: 'other'}}, function(result) {
      var name = result.human.replace(/#/g, '');
      name = name.split('[');
      name = name[name.length-1].replace(/]/g, '');
      var maxIndex = getMaxIndex(actionList);
      actionList.push({type: 'cmd', id: result.cmd.id, name:name, index: maxIndex+1 });
      refreshActionList()
		})
	}

  function addScenario() {
    if (actionList.length > 2) {
      return;
    }
    actionList.forEach(item => {
      item.name = $("#"+item.id+"-name-input").val();
    });
    jeedom.scenario.getSelectModal({}, function(result) {
      var name = result.human.replace(/#/g, '');
      name = name.split('[');
      name = name[name.length-1].replace(/]/g, '');
      var maxIndex = getMaxIndex(actionList);
      actionList.push({type: 'scenario', id: result.id, name:name, index: maxIndex+1 });
      refreshActionList();
		})
	}

  function refreshActionList() {
    actionList.sort(function(s,t) {
			return s.index - t.index;
		});
    var html = '';
		actionList.forEach(item => {
      html += `<div class='input-group' style="border-width:1px; border-style:dotted;" id="actionList-${item.id}">
						<input style="width:240px;" class='input-sm form-control roundedLeft' id="${item.id}-input" value='' disabled>
            <i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upAction('${item.id}');"></i>
      			<i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downAction('${item.id}');"></i>
      			<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteAction('${item.id}');"></i>
            <div class='input-group'><label class="xs-col-3">Nom : </label><input style="width:170px;" id="${item.id}-name-input" value="${item.name || ''}"></div>
            </div></div>`;
      if (item.type == "cmd") {
        getHumanName({
          id: item.id,
          error: function (error) {},
          success: function (data) {
            $("#"+item.id+"-input").val(data);
          }
        });
      } else if (item.type == "scenario") {
        getScenarioHumanName({
					id: item.id,
					error: function (error) {},
					success: function (data) {
						data.forEach(sc => {
							if (sc['id'] == item.id) {
								$("#"+item.id+"-input").val(sc.humanName);
							}
						})
					}
				});
      }

    });
    $("#actionList").html(html);
  }




  function deleteAction(id) {
		var actionToDelete = actionList.find(i => i.id == id);
		var index = actionList.indexOf(actionToDelete);
		actionList.forEach(item => {
		if (item.index > actionToDelete.index) {
			item.index = item.index - 1;
		}
		});
		actionList.splice(index, 1);
		refreshActionList();
	}

  function upAction(id) {
    actionList.forEach(item => {
      item.name = $("#"+item.id+"-name-input").val();
    });
		var actionToMove = actionList.find(i => i.id == parseInt(id));
		var index = parseInt(actionToMove.index);
		if (index == 0) {
			return;
		}
		var otherAction = actionList.find(i => i.index == index - 1);
		actionToMove.index = index - 1;
		otherAction.index = index;
		refreshActionList();
	}

	function downAction(id) {
    actionList.forEach(item => {
      item.name = $("#"+item.id+"-name-input").val();
    });
		var actionToMove = actionList.find(i => i.id == parseInt(id));
		var index = parseInt(actionToMove.index);
		if (index == getMaxIndex(actionList)) {
			return;
		}
		var otherAction = actionList.find(i => i.index == index + 1);
		actionToMove.index = index + 1;
		otherAction.index = index;
		refreshActionList();
	}



   function getHumanName(_params) {
	 var params = $.extend({}, jeedom.private.default_params, {}, _params || {});

     var paramsAJAX = jeedom.private.getParamsAJAX(params);
     paramsAJAX.url = 'core/ajax/cmd.ajax.php';
     paramsAJAX.data = {
      action: 'getHumanCmdName',
      id: _params.id
     };
     $.ajax(paramsAJAX);
   }

   function getScenarioHumanName(_params) {
	 var params = $.extend({}, jeedom.private.default_params, {}, _params || {});

     var paramsAJAX = jeedom.private.getParamsAJAX(params);
     paramsAJAX.url = 'core/ajax/scenario.ajax.php';
     paramsAJAX.data = {
      action: 'all',
      id: _params.id
     };
     $.ajax(paramsAJAX);
   }



 </script>
