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

<div  style="margin:auto;">
  <div style="display:none;" id="widget-alert"></div>
  <div style="float:left; width:200px; height:400px; position:fixed;">
      <h3>Choix du widget</h3>
    <select name="widgetsList" id="widgetsList-select"  onchange="refreshAddWidgets();">
    </select>
	<img id="widgetImg" />
	<div class="alert alert-info" id="widgetDescription">
	</div>
  </div>
  <div style="margin-left:210px; height:inherit; width:500px; border-left: 1px solid #ccc;">
    <h3 style="margin-left:25px;">Options du widget</h3><br>
	<div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
	<form class="form-horizontal" style="overflow: hidden; min-width: 800px;">
	  <ul id="widgetOptions" style="padding-left:10px; list-style-type: none;">

	  </ul>
	</form>
  </div>
 </div>

 <script>
	//used for widgets option
	var widgetsCat = [];
  //used for cmdList
  var cmdCat = [];
  //used for img list
  var imgCat = [];

	function setWidgetModalData(options) {
		refreshAddWidgets();
		if (options.widget !== undefined) {
			 $('#widgetsList-select option[value="'+options.widget.type+'"]').prop('selected', true);
			 refreshAddWidgets();
			 //Enable
			 var enable = options.widget.enable ? "checked": "";
			 $("#enable-input").prop('checked', enable);
       var blockDetail = options.widget.blockDetail ? "checked": "";
			 $("#blockDetail-input").prop('checked', blockDetail);
			 //Room
			 if (options.widget.room !== undefined & configData.payload.rooms.find(r => r.id == options.widget.room) !== undefined) {
				$('#room-input option[value="'+options.widget.room+'"]').prop('selected', true);
			 }

			 var widgetConfig = widgetsList.widgets.find(i => i.type == options.widget.type);
			 widgetConfig.options.forEach(option => {
				 if (option.category == "string" & options.widget[option.id] !== undefined ) {
					 $("#"+option.id+"-input").val(options.widget[option.id]);
				 } else if (option.category == "cmd" & options.widget[option.id] !== undefined) {
           $("#"+option.id+"-input").attr('cmdId', options.widget[option.id].id);
           getHumanName({
             id: options.widget[option.id].id,
             error: function (error) {},
             success: function (data) {
               $("#"+option.id+"-input").val(data);
             }
           });
           $("#"+option.id+"-input").attr('cmdType', options.widget[option.id].type);
           $("#"+option.id+"-input").attr('cmdSubType', options.widget[option.id].subType);
           if (options.widget[option.id].type == 'action') {
             $("#confirm-div-"+option.id).css('display', '');
             $("#secure-div-"+option.id).css('display', '');
             $("#confirm-"+option.id).prop('checked', options.widget[option.id].confirm ? "checked" : "");
             $("#secure-"+option.id).prop('checked', options.widget[option.id].secure ? "checked" : "");
           } else {
             $("#confirm-div-"+option.id).css('display', 'none');
             $("#secure-div-"+option.id).css('display', 'none');
           }
           if (options.widget[option.id].subType == 'slider' | options.widget[option.id].subType == 'numeric') {
             $("#"+option.id+"-minInput").css('display', '');
             $("#"+option.id+"-maxInput").css('display', '');
             $("#"+option.id+"-minInput").val(options.widget[option.id].minValue);
             $("#"+option.id+"-maxInput").val(options.widget[option.id].maxValue);
           } else {
             $("#"+option.id+"-minInput").css('display', 'none');
             $("#"+option.id+"-maxInput").css('display', 'none');
           }
           if (options.widget[option.id].subType == 'binary' | options.widget[option.id].subType == 'numeric') {
             $("#invert-div-"+option.id).css('display', '');
             $("#invert-"+option.id).prop('checked', options.widget[option.id].invert ? "checked" : "");
           } else {
               $("#invert-div-"+option.id).css('display', 'none');
           }
           if (options.widget[option.id].subType == 'numeric') {
             $("#"+option.id+"-unitInput").css('display', '');
             $("#"+option.id+"-unitInput").val(options.widget[option.id].unit);
           } else {
               $("#"+option.id+"-unitInput").css('display', 'none');
           }

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
					 widgetsCat = options.widget[option.id];
					 refreshWidgetOption();
				 } else if (option.category == "cmdList" & options.widget[option.id] !== undefined) {
					 cmdCat = options.widget[option.id];
					 refreshCmdListOption(JSON.stringify(option.options));
				 } else if (option.category == "ifImgs" & options.widget[option.id] !== undefined) {
					 imgCat = options.widget[option.id];
					 refreshImgListOption(JSON.stringify(option.infos));
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
    cmdCat = [];
    imgCat = [];
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
		  option += `<option value="${item.id}">${item.name}</option>`;
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
				curOption += `<table><tr class="cmd">
              <td>
                <input class='input-sm form-control roundedLeft' style="width:250px;" id="${option.id}-input" value='' cmdId='' cmdType='' cmdSubType='' disabled>
                <td>
                   <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une commande' onclick="selectCmd('${option.id}', '${option.type}', '${option.subtype}', '${option.value}');">
			                <i class='fas fa-list-alt'></i></a>
                      </td>
              </td>
              <td>
                  <i class="mdi mdi-minus-circle" id="${option.id}-remove"
                        style="color:rgb(185, 58, 62);font-size:16px;margin-right:10px;display:${option.required ? 'none' : ''};" aria-hidden="true" onclick="removeCmd('${option.id}')"></i>
              </td>
              <td>
                      <div style="width:50px;margin-left:5px; display:none;" id="invert-div-${option.id}">
                      <i class='fa fa-sync' title="Inverser"></i><input type="checkbox" style="margin-left:5px;" id="invert-${option.id}"></div>
              </td>
              <td>
                    <div style="width:50px;margin-left:5px; display:none;" id="confirm-div-${option.id}">
                    <i class='fa fa-question' title="Demander confirmation"></i><input type="checkbox" style="margin-left:5px;" id="confirm-${option.id}"></div>
              </td><td>
                      <div style="width:50px; display:none;" id="secure-div-${option.id}">
                      <i class='fa fa-fingerprint' title="Sécuriser avec empreinte digitale"></i><input type="checkbox" style="margin-left:5px;" id="secure-${option.id}"  ></div>
              </td>
              <td>
                  <input style="width:50px; display:none;" id="${option.id}-minInput" value='' placeholder="Min">
                </td><td>
                  <input style="width:50px;margin-left:5px; display:none;" id="${option.id}-maxInput" value='' placeholder="Max">
              </td><td>
                  <input style="width:50px; margin-left:5px; display:none;" id="${option.id}-unitInput" value='' placeholder="Unité">
                </td></tr></table>
                      `;

			 curOption += "</div></div></li>";

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
								<img id="${option.id}" src="" style="width:30px; height:30px; margin-top:-15px; display:none;" />
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
      } else if (option.category == "cmdList") {
        curOption += `<span class="input-group-btn">
								<a class="btn btn-default roundedRight" onclick="addCmdOption('${JSON.stringify(option.options).replace(/"/g, '&quot;')}')"><i class="fas fa-plus-square">
								</i> Ajouter</a></span><div id="cmdList-option"></div>`;
				curOption += `</div></div></li>`;
      } else if (option.category == "ifImgs") {
        curOption += `<span class="input-group-btn">
								<a class="btn btn-default roundedRight" onclick="addImgOption('${JSON.stringify(option.infos).replace(/"/g, '&quot;')}')"><i class="fas fa-plus-square">
								</i> Ajouter</a></span><div id="imgList-option"></div>`;
				curOption += `</div></div></li>`;
      } else if (option.category == "scenario") {
				curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' scId='' disabled>
			<span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir un scenario' onclick="selectScenario('${option.id}');">
			<i class='fas fa-list-alt'></i></a></span></div>
			</div>
			</div></li>`;
    } else {
      return;
    }


			items.push(curOption);


		});
    //Details access
		option = `<li><div class='form-group'>
			<label class='col-xs-3 '>Bloquer vue détails</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="blockDetail-input" ></div></div></div></li>`;
		items.push(option);

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

  function removeCmd(id) {
    $("#"+id+"-input").attr('value', '');
    $("#"+id+"-input").val('');
    $("#"+id+"-input").attr('cmdId', '');
  }

	function selectCmd(name, type, subtype, value) {
		var cmd =  {type: type }
		if (subtype != 'undefined') {
			cmd = {type: type, subType: subtype}
		}
		jeedom.cmd.getSelectModal({cmd: cmd}, function(result) {
      refreshCmdData(name, result.cmd.id, value);
		})
	}

  function refreshCmdData(name, id, value) {
    getCmd({
     id: id,
     error: function (error) {},
     success: function (data) {
       $("#"+name+"-input").attr('cmdId', data.result.id);
       $("#"+name+"-input").val(data.result.humanName);
       $("#"+name+"-input").attr('cmdType', data.result.type);
       $("#"+name+"-input").attr('cmdSubType', data.result.subType);
       if (data.result.type == 'action') {
         $("#confirm-div-"+name).css('display', '');
         $("#secure-div-"+name).css('display', '');
       } else {
         $("#confirm-div-"+name).css('display', 'none');
         $("#secure-div-"+name).css('display', 'none');
       }
       if (data.result.subType == 'slider' | data.result.subType == 'numeric') {
         $("#"+name+"-minInput").css('display', '');
         $("#"+name+"-maxInput").css('display', '');
         $("#"+name+"-minInput").val(data.result.minValue);
         $("#"+name+"-maxInput").val(data.result.maxValue);
       } else {
         $("#"+name+"-minInput").css('display', 'none');
         $("#"+name+"-maxInput").css('display', 'none');
       }
       if (data.result.subType == 'binary' | data.result.subType == 'numeric') {
         $("#invert-div-"+name).css('display', '');
         //$("#invert-"+name).prop('checked', data.result.invertBinary == '1' ? "checked" : "");
       } else {
           $("#invert-div-"+name).css('display', 'none');
       }
       if (data.result.subType == 'numeric') {
         $("#"+name+"-unitInput").css('display', '');
         $("#"+name+"-unitInput").val(data.result.unit);
       } else {
           $("#"+name+"-unitInput").css('display', 'none');
       }
       if (value != 'undefined' & data.result.value != '') {
         refreshCmdData(value, data.result.value, 'undefined');
       }


     }
    });
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



  function refreshCmdListOption(optionsJson) {
    var options = JSON.parse(optionsJson);
		curOption = "";
    cmdCat.sort(function(s,t) {
			return s.index - t.index;
		});
		cmdCat.forEach(item => {
			curOption += `<div class='input-group' style="border-width:1px; border-style:dotted;" id="cmdList-${item.id}">
						<input style="width:240px;" class='input-sm form-control roundedLeft' id="${item.id}-input" value='' disabled>
            <i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upCmdOption('${item.id}','${optionsJson.replace(/"/g, '&quot;')}');"></i>
      			<i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downCmdOption('${item.id}','${optionsJson.replace(/"/g, '&quot;')}');"></i>
      			<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteCmdOption('${item.id}','${optionsJson.replace(/"/g, '&quot;')}');"></i>`
      if (options.type == 'action') {
          curOption += `<div style="text-align:end;">
            <i class='mdi mdi-help-circle-outline'></i><input type="checkbox" style="margin-left:5px;" id="confirm-${item.id}">
            <i class='mdi mdi-fingerprint'></i><input type="checkbox" style="margin-left:5px;" id="secure-${item.id}"  ></div>
            </li><div style="margin-top:-20px;">`;

      }
      if (options.hasIcon) {
        var value = item.icon ? typeof(item.icon) == 'string' ? item.icon : item.icon.name : '';
        var source = item.icon ? typeof(item.icon) == 'string' ? 'md' : item.icon.source : 'md';
        curOption += `<div class='input-group'><label class="xs-col-3">Icône : </label>
          <input style="width:120px;" id="${item.id}-icon-input" value="${value}">
          <select style="width:50px;margin-left:5px;" id="${item.id}-icon-source-input" value=''>
            <option value="md" ${source == 'md' && 'selected'}>MD</option>
            <option value="fa" ${source == 'fa' && 'selected'}>FA</option>
            </select>
          </div>`;
      }
      if (options.hasImage) {
        curOption += `
        <div class='input-group'> <label class="xs-col-3">Image : </label><span class="input-group-btn">
                <img id="${item.id}" src="${item.image ? 'plugins/JeedomConnect/data/img/'+item.image : ''}" style="width:30px; height:30px;margin-top:-15px; display:${item.image ? '': 'none'};" value="${item.image || ''}" />
                <i class="mdi mdi-minus-circle" id="${item.id}-remove" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;display:${item.image ? '': 'none'};" aria-hidden="true" onclick="removeImage('${item.id}')"></i>
                <a class="btn btn-success roundedRight" onclick="imagePicker('${item.id}')"><i class="fas fa-check-square">
                </i> Choisir </a>
                </span> </div>`;
      }
			curOption += '</div>'
      if (item.type == 'action') {
        curOption += '</div>'
      }
      getHumanName({
        id: item.id,
        error: function (error) {},
        success: function (data) {
          $("#"+item.id+"-input").val(data);
        }
      });
		});
		$("#cmdList-option").html(curOption);
    cmdCat.forEach(item => {
    var confirm = item.confirm ? "checked": "";
    $("#confirm-"+item.id).prop('checked', confirm);
    var secure = item.secure ? "checked": "";
    $("#secure-"+item.id).prop('checked', secure);
  })
	}

  function addCmdOption(optionsJson) {
    var options = JSON.parse(optionsJson);
    var cmd = {};
    if (options.type) {
      cmd =  {type: options.type }
    }
		if (options.subtype) {
			cmd = {type: options.type, subType: options.subtype}
		}
		jeedom.cmd.getSelectModal({cmd:cmd}, function(result) {
      var name = result.human.replace(/#/g, '');
      name = name.split('[');
      name = name[name.length-1].replace(/]/g, '');
      var maxIndex = getMaxIndex(cmdCat);
      cmdCat.push({id: result.cmd.id, name:name, index: maxIndex+1 });
      refreshCmdListOption(optionsJson)
		})
	}

  function deleteCmdOption(id, optionsJson) {
		var cmdToDelete = cmdCat.find(i => i.id == id);
		var index = cmdCat.indexOf(cmdToDelete);
		cmdCat.forEach(item => {
		if (item.index > cmdToDelete.index) {
			item.index = item.index - 1;
		}
		});
		cmdCat.splice(index, 1);
		refreshCmdListOption(optionsJson);
	}

  function upCmdOption(id, optionsJson) {
    cmdCat.forEach(item => {
      item.image = $("#cmdList-"+item.id+" img").first().attr("value");
      item.icon = $("#"+item.id+"-icon-input").val();
    });
		var cmdToMove = cmdCat.find(i => i.id == parseInt(id));
		var index = parseInt(cmdToMove.index);
		if (index == 0) {
			return;
		}
		var otherCmd = cmdCat.find(i => i.index == index - 1);
		cmdToMove.index = index - 1;
		otherCmd.index = index;
		refreshCmdListOption(optionsJson);
	}

	function downCmdOption(id, optionsJson) {
    cmdCat.forEach(item => {
      item.image = $("#cmdList-"+item.id+" img").first().attr("value");
      item.icon = $("#"+item.id+"-icon-input").val();
    });
		var cmdToMove = cmdCat.find(i => i.id == parseInt(id));
		var index = parseInt(cmdToMove.index);
		if (index == getMaxIndex(cmdCat)) {
			return;
		}
		var otherCmd = cmdCat.find(i => i.index == index + 1);
		cmdToMove.index = index + 1;
		otherCmd.index = index;
		refreshCmdListOption(optionsJson);
	}

// Image list
  function refreshImgListOption(optionsJson) {
    var options = JSON.parse(optionsJson);
    var type = $("#widgetsList-select").val();
		var widget = widgetsList.widgets.find(i => i.type == type);
		curOption = "";
    imgCat.sort(function(s,t) {
			return s.index - t.index;
		});
		imgCat.forEach(item => {
			curOption += `<div class='input-group' style="border-width:1px; border-style:dotted;" id="imgList-${item.index}">
      Si :<select id="info-${item.index}" style="width:100px;height:31px;margin-left:5px;">`;
      options.forEach(info => {
        var infoName = widget.options.find(o => o.id == info).name;
        curOption += `<option value="${info}" ${item.info == info && "selected"}>${infoName}</option>`;
      });
      curOption += `</select> <select id="operator-${item.index}" style="width:50px;height:31px; margin-left:5px;">
        <option value="=" ${item.operator == "=" && "selected"}>=</option>
        <option value="<" ${item.operator == "<" && "selected"}><</option>
        <option value=">" ${item.operator == ">" && "selected"}>></option>
        <option value="!=" ${item.operator == "!=" && "selected"}>!=</option> </select>`;

      curOption +=`<input style="width:150px;height:31px;margin-left:5px;" class=' roundedLeft' id="${item.index}-value" value='${item.value || ''}' >`
      curOption += `
        <div class='input-group'> <label class="xs-col-3">Image : </label><span class="input-group-btn">
                <img id="${item.index}" src="${item.image ? 'plugins/JeedomConnect/data/img/'+item.image : ''}" style="width:30px; height:30px;margin-top:-15px; display:${item.image ? '': 'none'};" value="${item.image || ''}" />
                <i class="mdi mdi-minus-circle" id="${item.index}-remove" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;display:${item.image ? '': 'none'};" aria-hidden="true" onclick="removeImage('${item.index}')"></i>
                <a class="btn btn-success roundedRight" onclick="imagePicker('${item.index}')"><i class="fas fa-check-square">
                </i> Choisir </a>
                </span>
                <i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upImgOption('${item.index}','${optionsJson.replace(/"/g, '&quot;')}');"></i>
          			<i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downImgOption('${item.index}','${optionsJson.replace(/"/g, '&quot;')}');"></i>
          			<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteImgOption('${item.index}','${optionsJson.replace(/"/g, '&quot;')}');"></i>
                </div>`;

			curOption += '</div>'
		});
		$("#imgList-option").html(curOption);
	}

  function addImgOption(infos) {
    imgCat.forEach(item => {
      item.image = $("#imgList-"+item.index+" img").first().attr("value");
      item.info = $("#info-"+item.index).val();
      item.operator = $("#operator-"+item.index).val();
      item.value = $("#"+item.index+"-value").val();
    });
    var maxIndex = getMaxIndex(imgCat);
    imgCat.push({index: maxIndex+1 });
    refreshImgListOption(infos);
  }

  function deleteImgOption(id, optionsJson) {
		var imgToDelete = imgCat.find(i => i.index == id);
		var index = imgCat.indexOf(imgToDelete);
    imgCat.splice(index, 1);
		imgCat.forEach(item => {
		if (item.index > imgToDelete.index) {
			item.index = item.index - 1;
		}
		});

		refreshImgListOption(optionsJson);
	}

  function upImgOption(id, optionsJson) {
    imgCat.forEach(item => {
      item.image = $("#imgList-"+item.index+" img").first().attr("value");
      item.info = $("#info-"+item.index).val();
      item.operator = $("#operator-"+item.index).val();
      item.value = $("#"+item.index+"-value").val();
    });
		var imgToMove = imgCat.find(i => i.index == parseInt(id));
		var index = parseInt(imgToMove.index);
		if (index == 0) {
			return;
		}
		var otherImg = imgCat.find(i => i.index == index - 1);
		imgToMove.index = index - 1;
		otherImg.index = index;
		refreshImgListOption(optionsJson);
	}

	function downImgOption(id, optionsJson) {
    imgCat.forEach(item => {
      item.image = $("#imgList-"+item.index+" img").first().attr("value");
      item.info = $("#info-"+item.index).val();
      item.operator = $("#operator-"+item.index).val();
      item.value = $("#"+item.index+"-value").val();
    });
		var imgToMove = imgCat.find(i => i.index == parseInt(id));
		var index = parseInt(imgToMove.index);
		if (index == getMaxIndex(imgCat)) {
			return;
		}
		var otherImg = imgCat.find(i => i.index == index + 1);
		imgToMove.index = index + 1;
		otherImg.index = index;
		refreshImgListOption(optionsJson);
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

   function getCmd({id, error, success}) {
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
