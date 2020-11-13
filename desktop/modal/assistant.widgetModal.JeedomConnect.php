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
  <div style="display:none;" id="widget-alert"></div>
  <div style="float:left; width:300px; height:350px; position:fixed;">
      <h3>Choix du widget</h3>
    <select name="widgetsList" id="widgetsList-select"  onchange="refreshAddWidgets();">
    </select>
	<img id="widgetImg" />
	<div class="alert alert-info" id="widgetDescription">
	</div>
  </div>
  <div style="margin-left:310px; height:inherit; width:500px; border-left: 1px solid #ccc;">
    <h3 style="margin-left:25px;">Options du widget</h3><br>
	<div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
	<form class="form-horizontal" style="overflow: hidden;">
	  <ul id="widgetOptions" style="padding-left:10px; list-style-type: none;">
	  
	  </ul>
	</form>
  </div>
 </div>
 
 <script>
	//used for widgets option
	var widgetsCat = [];
 
	function setWidgetModalData(options) {
		refreshAddWidgets();
		if (options.widget !== undefined) {
			 $('#widgetsList-select option[value="'+options.widget.type+'"]').prop('selected', true);
			 refreshAddWidgets();
			 //Enable
			 var enable = options.widget.enable ? "checked": "";
			 $("#enable-input").prop('checked', enable);
			 //Room
			 if (options.widget.room !== undefined & configData.payload.rooms.find(r => r.name == options.widget.room) !== undefined) {
				$('#room-input option[value="'+options.widget.room+'"]').prop('selected', true); 
			 }
			 
			 var widgetConfig = widgetsList.widgets.find(i => i.type == options.widget.type);
			 widgetConfig.options.forEach(option => {
				 if (option.category == "string" & options.widget[option.id] !== undefined ) {
					 $("#"+option.id+"-input").val(options.widget[option.id]);					 
				 } else if (option.category == "cmd" & options.widget[option.id] !== undefined) {
					 getHumanName({
						id: options.widget[option.id],
						error: function (error) {},
						success: function (data) {							
						  $("#"+option.id+"-input").attr('cmdId', options.widget[option.id]);
						  $("#"+option.id+"-input").val(data);
						}
					 });					 
				 } else if (option.category == "scenario" & options.widget[option.id] !== undefined) {
					 getScenarioHumanName({
						id: options.widget[option.id],
						error: function (error) {},
						success: function (data) {
							data.forEach(sc => {
								if (sc['id'] == options.widget[option.id]) {
									$("#"+option.id+"-input").attr('scId', options.widget[option.id]);
									$("#"+option.id+"-input").val(sc['humanName']);									
								}
							})						  
						}
					 });					 
				 } else if (option.category == "stringList" & options.widget[option.id] !== undefined) {
					 var selectedChoice = option.choices.find(s => s.id == options.widget[option.id]);
					 if (selectedChoice !== undefined) {						 
						$('#'+option.id+'-input option[value="'+options.widget[option.id]+'"]').prop('selected', true);
						if (option.id == "subtitle") {							
							$("#subtitle-input-value").val(selectedChoice.id)
						}
					 } else if (option.id == "subtitle" & options.widget.subtitle !== undefined) {
						$('#subtitle-input option[value="custom"]').prop('selected', true);
						$("#subtitle-input-value").val(options.widget.subtitle) 
						$("#subtitle-input-value").css('display', 'block');
					 }				 
				 } else if (option.category == "widgets" & options.widget[option.id] !== undefined) {
					 widgetsCat = options.widget.widgets;
					 refreshWidgetOption();
				 } else if (option.category == "img" & options.widget[option.id] !== undefined ) {
					$("#"+option.id).attr("value", options.widget[option.id]);
					$("#"+option.id).attr("src", "plugins/JeedomConnect/data/img/"+options.widget[option.id]);
					$("#"+option.id).css("display", "");	
					$("#"+option.id+"-remove").css("display", "");					
				 }
			 });
		}
		
		
	}
	
	items = [];
	widgetsList.widgets.forEach(item => {
		items.push('<option value="'+item.type+'">'+item.name+'</option>');
	});
	$("#widgetsList-select").html(items.join(""));
	
	
	function refreshAddWidgets() {
		widgetsCat = []; 
		var type = $("#widgetsList-select").val();
		var widget = widgetsList.widgets.find(i => i.type == type);
		$("#widgetImg").attr("src", "plugins/JeedomConnect/data/img/"+widget.img);
		
		$("#widgetDescription").html(widget.description);
		
		var items = [];
		
		//Enable
		option = `<li><div class='form-group'>
			<label class='col-xs-3 '>Actif</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="enable-input" checked></div></div></div></li>`;
		items.push(option);
		
		//Room
		option = `<li><div class='form-group'>
			<label class='col-xs-3 '>Pièce</label>
			<div class='col-xs-9'><div class='input-group'><select style="width:340px;" id="room-input" value=''>
			<option value="none">Sélection  d'une pièce</option>`;
		configData.payload.rooms.forEach(item => {
		  option += `<option value="${item.name}">${item.name}</option>`;
		});
		option += `</select></div></div></div></li>`;
		items.push(option);
		
			
		widget.options.forEach(option => {
			var required = (option.required) ? "required" : "";
			var description = (option.description == undefined) ? '' : option.description;
			var curOption = `<li><div class='form-group' id="${option.id}-div">
			<label class='col-xs-3  ${required}'   id="${option.id}-label">${option.name}</label>
			<div class='col-xs-9' id="${option.id}-div-right">
			<div class="description">${description}</div>`;
			
			if (option.category == "cmd") {
				curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' cmdId='' disabled>
			<span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une commande' onclick="selectCmd('${option.id}', '${option.type}', '${option.subtype}');">
			<i class='fas fa-list-alt'></i></a></span></div>
			</div>			
			</div></li>`;
			
			} else if (option.category == "string") {
				curOption += `<div class='input-group'><input style="width:340px;" id="${option.id}-input" value=''>
			</div>
			</div></div></li>`;
			} else if (option.category == "stringList") {
				curOption += `<div class='input-group'><select style="width:340px;" id="${option.id}-input" onchange="subtitleSelected();">`;
				if (!required) {
					curOption += `<option value="none">Aucun</option>`;
				}
				option.choices.forEach(item => {
					curOption += `<option value="${item.id}">${item.name}</option>`;
				})
				if (option.id == "subtitle") {
					curOption += `<option value="custom">Personalisé</option>`;
				}
				
				curOption += `</select>
					<input style="width:340px; margin-top:5px; display:none;" id="subtitle-input-value" value='none'>
					</div></div></div></li>`;	
			} else if (option.category == "img") {
				curOption += `<span class="input-group-btn">
								<img id="${option.id}" src="" style="width:30px; height:30px; display:none;" />
								<i class="mdi mdi-minus-circle" id="${option.id}-remove" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;display:none;" aria-hidden="true" onclick="removeImage('${option.id}')"></i>
								<a class="btn btn-success roundedRight" onclick="imagePicker('${option.id}')"><i class="fas fa-check-square">
								</i> Choisir </a>
								</span></div></div></li>`;
								
					
			} else if (option.category == "widgets") {
				var widgetChoices = [];
				widgetsList.widgets.forEach(item =>  {
					if (option.whiteList !== undefined) {
						if (option.whiteList.includes(item.type)) {
							widgetChoices.push(item.type);
						}
					} else if (option.blackList !== undefined) {
						if (!option.blackList.includes(item.type)) {
							widgetChoices.push(item.type);
						}
					} else {
						widgetChoices.push(item.type);
					}
				})
				curOption += `<span class="input-group-btn">
								<a class="btn btn-default roundedRight" onclick="addWidgetOption('${widgetChoices.join(".")}')"><i class="fas fa-plus-square">
								</i> Ajouter</a></span><div id="widget-option"></div>`;			
				curOption += `</div></div></li>`;
			} else if (option.category == "scenario") {
				curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' scId='' disabled>
			<span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir un scenario' onclick="selectScenario('${option.id}');">
			<i class='fas fa-list-alt'></i></a></span></div>
			</div>			
			</div></li>`;
			}
			
			
			items.push(curOption);
			
			
		});
		
		$("#widgetOptions").html(items.join(""));
	}
	
	
	
	function imagePicker(id) {
		getImageModal({title: "Choisir une image", selected: $("#"+id).attr("value") } , function(result) {
			$("#"+id).attr("value", result);
			$("#"+id).attr("src", "plugins/JeedomConnect/data/img/"+result);
			$("#"+id).css("display", "");
			$("#"+id+"-remove").css("display", "");
		});
	}
	
	function removeImage(id) {
		$("#"+id).attr("src", "");
		$("#"+id).attr("value", "");
		$("#"+id).css("display", "none");
		$("#"+id+"-remove").css("display", "none");
	}
	
	
	
	function selectCmd(name, type, subtype) {		
		var cmd =  {type: type }
		if (subtype != 'undefined') {
			cmd = {type: type, subType: subtype}
		}
		jeedom.cmd.getSelectModal({cmd: cmd}, function(result) {
			$("#"+name+"-input").attr('value', result.human);
			$("#"+name+"-input").val(result.human);
			$("#"+name+"-input").attr('cmdId', result.cmd.id);
		})		
	}
	
	function selectScenario(name) {		
		jeedom.scenario.getSelectModal({}, function(result) {
			$("#"+name+"-input").attr('value', result.human);
			$("#"+name+"-input").val(result.human);
			$("#"+name+"-input").attr('scId', result.id);
			if ($("#name-input").val() == "") {
				getScenarioHumanName({
					id: name,
					error: function (error) {},
					success: function (data) {
						data.forEach(sc => {
							if (sc['id'] == result.id) {
								$("#name-input").val(sc.name);									
							}
						})						  
					}
				});
				$("#name-input").val(result.name);
			}
		})		
	}
	
	function subtitleSelected() {
		if ($("#subtitle-input").val() == 'custom') {
			$("#subtitle-input-value").css('display', 'block');
		} else {
			$("#subtitle-input-value").css('display', 'none');
			$("#subtitle-input-value").val($("#subtitle-input").val());
		}
	}
	
	function refreshWidgetOption() {
		curOption = "";
		widgetsCat.sort(function(s,t) {
			return s.index - t.index;
		});
		widgetsCat.forEach(item => {			
			var name = getWidgetPath(item.id);
			curOption += `<div class='input-group'>			
						<input style="width:240px;" class='input-sm form-control roundedLeft' id="${item.id}-input" value='${name}' disabled>
						<i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidgetOption('${item.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidgetOption('${item.id}');"></i>
			<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteWidgetOption('${item.id}');"></i></li>
						</div>`;
		});
		$("#widget-option").html(curOption);
	}
	
	function addWidgetOption(choices) {
		var widgets = choices.split(".");
		getSimpleModal({title: "Choisir un widget", fields:[{type: "widget",choices: widgets}] }, function(result) {
			var maxIndex = getMaxIndex(widgetsCat);
			widgetsCat.push({id: result.widgetId, index: maxIndex+1});
			refreshWidgetOption();
		});
	}
	
	function deleteWidgetOption(id) {
		var widgetToDelete = widgetsCat.find(i => i.id == id);
		var index = widgetsCat.indexOf(widgetToDelete);    
		widgetsCat.forEach(item => {
		if (item.index > widgetToDelete.index) {
			item.index = item.index - 1;
		}
		});
		widgetsCat.splice(index, 1);
		refreshWidgetOption();
	}
	
	function upWidgetOption(id) {
		var widgetToMove = widgetsCat.find(i => i.id == parseInt(id));
		var index = parseInt(widgetToMove.index);
		if (index == 0) {
			return;
		}
		var otherWidget = widgetsCat.find(i => i.index == index - 1);
		widgetToMove.index = index - 1;
		otherWidget.index = index;
		refreshWidgetOption();
	}
	
	function downWidgetOption(id) {
		var widgetToMove = widgetsCat.find(i => i.id == parseInt(id));
		var index = parseInt(widgetToMove.index);
		if (index == getMaxIndex(widgetsCat)) {
			return;
		}
		var otherWidget = widgetsCat.find(i => i.index == index + 1);
		widgetToMove.index = index + 1;
		otherWidget.index = index;
		refreshWidgetOption();
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
 

