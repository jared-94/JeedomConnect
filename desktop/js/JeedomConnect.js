
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

$('.eqLogicThumbnailContainer').off('click', '.widgetDisplayCard').on('click', '.widgetDisplayCard', function () {

  var eqId = $(this).attr('data-widget_id');
  editWidgetModal(eqId, true, true, true, true);

})

async function editWidgetModal(widgetId, removeAction, exit, duplicate, checkEquipment) {

  if (checkEquipment) {

    var mesEquipments = await getUsedByEquipement(widgetId);
    var inEquipments = mesEquipments.result.names;
  }
  else {
    var inEquipments = undefined;
  }

  var widgetToEdit = allWidgetsDetail.find(w => w.id == widgetId);
  getWidgetModal({ title: "Editer un widget", eqId: widgetId, widget: widgetToEdit, removeAction: removeAction, exit: exit, duplicate: duplicate, inEquipments: inEquipments }, function (result) {
    refreshWidgetDetails();
    if (!exit) refreshWidgetsContent();
  });

}

async function getUsedByEquipement(eqId) {
  const result = await $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      action: 'getWidgetExistance',
      id: eqId
    },
    cache: false,
    dataType: 'json',
    async: false,
  });

  if (result.state != 'ok') {
    $('#div_alert').showAlert({
      message: result.result,
      level: 'danger'
    });
  }

  return result;
}




/**************************************************** */
/**************************************************** */
/**************************************************** */
/**************************************************** */
/**************************************************** */
/**************************************************** */
/**************************************************** */





var allWidgetsDetail;
var roomList;
var roomListOptions;

refreshWidgetDetails();

function refreshWidgetDetails() {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'getWidgetConfigAll'
    },
    cache: false,
    dataType: 'json',
    async: false,
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        allWidgetsDetail = data.result.widgets;
        roomList = data.result.room_details;
        roomListOptions = data.result.room_options;
        sortWidgets();
      }
    }
  });

}

if (typeof allJeedomData === 'undefined') {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'getAllJeedomData'
    },
    cache: false,
    dataType: 'json',
    async: true,
    success: function (data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        allJeedomData = data.result;
      }
    }
  });
}


$.post({
  url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
  data: {
    'action': 'getEquipments'
  },
  dataType: 'json',
  success: function (data) {
    if (data.state != 'ok') {
      $('#div_alert').showAlert({
        message: data.result,
        level: 'danger'
      });
    }
    else {
      allJCEquipments = data.result
    }
  }
});


var widgetsCat = [];//used for widgets option
var cmdCat = []; //used for cmdList
var imgCat = []; //used for img list
var moreInfos = []; //used for moreInfos

var widgetsList = (function () {
  var json = null;
  $.ajax({
    'async': false,
    'global': false,
    'cache': false,
    'url': "plugins/JeedomConnect/core/config/widgetsConfig.json",
    'dataType': "json",
    'success': function (data) {
      var availableWidgets = data.widgets.filter(function (item) {
        if (item.hide !== undefined && item.hide) {
          return false;
        }
        return true;
      });

      data.widgets = availableWidgets

      data.widgets.sort(function (a, b) {
        return a.name.localeCompare(b.name);
      });
      json = data;
    }
  });
  return json;
})();

items = [];
widgetsList.widgets.forEach(item => {
  items.push('<option value="' + item.type + '">' + item.name + '</option>');
});
$("#widgetsList-select").html(items.join(""));
$("#room-input").html(roomListOptions);




function selectCmd(name, type, subtype, value) {
  var cmd = { type: type }
  if (subtype != 'undefined') {
    cmd = { type: type, subType: subtype }
  }
  var obj = $('#room-input option:selected').val();
  obj = (obj == 'none') ? '' : obj;

  jeedom.cmd.getSelectModal({
    object: {
      id: obj
    },
    cmd: cmd
  }, function (result) {
    refreshCmdData(name, result.cmd.id, value);
  })
}





function refreshCmdData(name, id, value) {
  if (name == '' || id == '') return;
  getCmd({
    id: id,
    error: function (error) {
      $('#widget-alert').showAlert({ message: error.result, level: 'danger' });
    },
    success: function (data) {
      $('#widget-alert').hideAlert();

      var configtype = $("#" + name + "-input").attr('configtype');
      var configsubtype = $("#" + name + "-input").attr('configsubtype');

      if (configtype != undefined && configtype != data.result.type) {
        $('#widget-alert').showAlert({
          message: "La commande " + id + " n'est pas de type '" + configtype + "'", level: 'danger'
        });
        return;
      }
      if (configsubtype != "undefined" && configsubtype != data.result.subType) {
        $('#widget-alert').showAlert({
          message: "La commande " + id + " n'a pas le sous-type '" + configsubtype + "'", level: 'danger'
        });
        return;
      }


      $("#" + name + "-input").attr('cmdId', data.result.id);
      $("#" + name + "-input").val('#' + data.result.humanName + '#');
      $("#" + name + "-input").attr('title', '#' + data.result.humanName + '#');
      $("#" + name + "-input").attr('cmdType', data.result.type);
      $("#" + name + "-input").attr('cmdSubType', data.result.subType);
      if (data.result.type == 'action') {
        $("#confirm-div-" + name).css('display', '');
        $("#secure-div-" + name).css('display', '');
        $("#pwd-div-" + name).css('display', '');
      } else {
        $("#confirm-div-" + name).css('display', 'none');
        $("#secure-div-" + name).css('display', 'none');
        $("#pwd-div-" + name).css('display', 'none');
      }
      if (data.result.subType == 'slider' | data.result.subType == 'numeric') {
        $("#" + name + "-minInput").css('display', '');
        $("#" + name + "-maxInput").css('display', '');
        $("#" + name + "-minInput").val(data.result.minValue);
        $("#" + name + "-maxInput").val(data.result.maxValue);
      } else {
        $("#" + name + "-minInput").css('display', 'none');
        $("#" + name + "-maxInput").css('display', 'none');
      }
      if (data.result.subType == 'binary' | data.result.subType == 'numeric') {
        $("#invert-div-" + name).css('display', '');
        //$("#invert-"+name).prop('checked', data.result.invertBinary == '1' ? "checked" : "");
      } else {
        $("#invert-div-" + name).css('display', 'none');
      }
      if (data.result.subType == 'numeric') {
        $("#" + name + "-unitInput").css('display', '');
        $("#" + name + "-unitInput").val(data.result.unit);
      } else {
        $("#" + name + "-unitInput").css('display', 'none');
      }
      if (data.result.subType == 'slider') {
        $("#" + name + "-stepInput").css('display', '');
        $("#" + name + "-stepInput").val(data.result.step);
      }
      else {
        $("#" + name + "-stepInput").css('display', 'none');
      }
      if (value != 'undefined' & data.result.value != '') {
        refreshCmdData(value, data.result.value, 'undefined');
      }
      refreshImgListOption();
      refreshInfoSelect();
    }
  });
}


// TODO
// call twice !?
$("body").on('change', '.subtitleSelected', function (e) {
  if ($(this).val() == 'custom') {
    $("#subtitle-input-value").show();
    $("#subtitle-select").show();
  } else {
    $("#subtitle-input-value").hide();
    $("#subtitle-select").hide();
    $("#subtitle-input-value").val($("#subtitle-input").val());
  }
});

function refreshWidgetOption() {
  curOption = "";
  widgetsCat.sort(function (s, t) {
    return s.index - t.index;
  });
  widgetsCat.forEach(item => {
    var name = getWidgetPath(item.id);

    if (item.roomName) {
      roomName = (item.roomName == '') ? '' : ' (' + item.roomName + ')';
    }
    else {
      roomNameTmp = getRoomName(item.room || undefined) || '';
      roomName = (roomNameTmp == '') ? '' : ' (' + roomNameTmp + ')';
    }

    curOption += `<div class='input-group jcWidgetListMovable' data-id="${item.id}">
          <input style="width:240px;" class='input-sm form-control roundedLeft' title="id=${item.id}" id="${item.id}-input" value='${name}${roomName}' disabled>
          <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>

          <!-- <i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidgetOption('${item.id}');"></i>
		      <i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidgetOption('${item.id}');"></i> -->

          <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteWidgetOption('${item.id}');"></i></li>
          </div>`;
  });
  $("#widget-option").html(curOption);
}



function refreshCmdListOption(optionsJson) {
  var options = JSON.parse(optionsJson);

  curOption = "";
  cmdCat.sort(function (s, t) {
    return s.index - t.index;
  });

  var tempIndex = 0;
  cmdCat.forEach(item => {
    isDisabled = isJcExpert ? '' : 'disabled';

    item.index = item.index || tempIndex;
    //open the div
    curOption += `<div class='input-group col-lg-12 jcCmdList' style="display:flex;border:0.5px black solid;margin: 0 5px;" data-id="${item.id}" data-index="${item.index}">`;

    //left part
    curOption += `<div class="col-lg-6 form-group">`;

    curOption += `<div class="input-group input-group-sm" style="width: 100%">
                            <span class="input-group-addon roundedLeft" style="width: 100px">Commande</span>
                            <input style="width:240px;" class='input-sm form-control roundedRight title jcCmdListOptions jcCmdListOptionsCommand' data-cmd-id="${item.id}" data-id="name-${item.id}" data-index="${item.index}" value='' ${isDisabled}>
                        </div>`;

    curOption += getCmdOptions(item);

    curOption += `</div>`;
    // --- END left part --

    ////right part
    curOption += `<div class="col-lg-6">`;
    curOption += `<div class="col-lg-12 form-group">`;

    if (options.type == 'action') {
      curOption += `
                <div class="col-lg-6">
                    <i class='mdi mdi-help-circle-outline'></i><input type="checkbox" style="margin-left:5px;" class="jcCmdListOptions" data-id="confirm-${item.id}" data-index="${item.index}">
                    <i class='mdi mdi-fingerprint'></i><input type="checkbox" style="margin-left:5px;" class="jcCmdListOptions" data-id="secure-${item.id}" data-index="${item.index}"  >
                    <i class='mdi mdi-numeric'></i><input type="checkbox" style="margin-left:5px;" class="jcCmdListOptions" data-id="pwd-${item.id}" data-index="${item.index}"  >
                </div> `;

    }

    curOption += `<div class="col-lg-6" >
                  <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>

                  <!-- <i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" data-id="${item.id}" data-index="${item.index}" onclick="upCmdOption(this,'${optionsJson.replace(/"/g, '&quot;')}');"></i>
                  <i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" data-id="${item.id}" data-index="${item.index}" onclick="downCmdOption(this,'${optionsJson.replace(/"/g, '&quot;')}');"></i> -->

                  <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" data-id="${item.id}" data-index="${item.index}"  onclick="deleteCmdOption(this,'${optionsJson.replace(/"/g, '&quot;')}');"></i>
                </div>`

    curOption += `</div>`;

    if (options.hasIcon | options.hasImage) {
      curOption += `<div class="col-lg-12 form-group">`;
      curOption += `
              <div>
                      <a class="btn btn-success roundedRight imagePicker"><i class="fas fa-check-square">
                      </i> Icône </a>
                      <a class="jcCmdListOptions removeImage" data-id="icon-${item.id}" data-index="${item.index}">${iconToHtml(item.image)}</a>
              </div>`;
      curOption += `</div>`;
    }



    curOption += `</div>`;
    //// ---  END right part
    curOption += `</div>`;

    tempIndex++;


  });
  $("#cmdList-option").html(curOption);
  cmdCat.forEach(item => {
    var confirm = item.confirm ? "checked" : "";
    $('.jcCmdListOptions[data-id="confirm-' + item.id + '"][data-index="' + item.index + '"]').prop('checked', confirm);

    var secure = item.secure ? "checked" : "";
    $('.jcCmdListOptions[data-id="secure-' + item.id + '"][data-index="' + item.index + '"]').prop('checked', secure);

    var pwd = item.pwd ? "checked" : "";
    $('.jcCmdListOptions[data-id="pwd-' + item.id + '"][data-index="' + item.index + '"]').prop('checked', pwd);

    $('.jcCmdListOptions[data-id="custom-name-' + item.id + '"][data-index="' + item.index + '"]').val(item.name || '');

    getCmd({
      id: item.id,
      success: function (data) {
        $('.jcCmdListOptions[data-id="name-' + item.id + '"][data-index="' + item.index + '"]').val("#" + data.result.humanName + "#");
        if (!isIcon(item.image)) {
          item.image = jeedomIconToIcon(data.result.icon);
          $('.jcCmdListOptions[data-id="icon-' + item.id + '"][data-index="' + item.index + '"]').html(iconToHtml(item.image));
        }
      }
    });

    if (['message', 'slider', 'color'].indexOf(item.subtype) > -1 && item.options != null) {
      Object.entries(item.options).forEach(entry => {
        const [key, value] = entry;
        if (value != '') {
          getHumanNameFromCmdId({ alert: '#widget-alert', cmdIdData: value, id: item.id, index: item.index }, function (result, _params) {
            $('.jcCmdListOptions[data-id="' + key + '-' + _params.id + '"][data-index="' + _params.index + '"]').val(result);
          });
        }
      });

    }

  })
}


function saveCmdList() {
  cmdCat.forEach(item => {
    item.image = htmlToIcon($('.jcCmdListOptions[data-id="icon-' + item.id + '"][data-index="' + item.index + '"]').children().first());
    item['confirm'] = $('.jcCmdListOptions[data-id="confirm-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;

    item['secure'] = $('.jcCmdListOptions[data-id="secure-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;

    item['pwd'] = $('.jcCmdListOptions[data-id="pwd-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;

    item['options'] = {};
    if (item.subtype == 'message') {
      item['options']['title'] = $('.jcCmdListOptions[data-id="title-' + item.id + '"][data-index="' + item.index + '"]').val();
      item['options']['message'] = $('.jcCmdListOptions[data-id="message-' + item.id + '"][data-index="' + item.index + '"]').val();
    }
    else if (item.subtype == 'select') {
      item['options'][item.subtype] = $('.jcCmdListOptions[data-id="select-' + item.id + '"][data-index="' + item.index + '"] option:selected').val();
    }
    else {
      item['options'][item.subtype] = $('.jcCmdListOptions[data-id="' + item.subtype + '-' + item.id + '"][data-index="' + item.index + '"]').val();
    }

  });
}

function addCmdOption(optionsJson) {
  saveCmdList();
  var options = JSON.parse(optionsJson);
  var cmd = {};
  if (options.type) {
    cmd = { type: options.type }
  }
  if (options.subtype) {
    cmd = { type: options.type, subType: options.subtype }
  }
  jeedom.cmd.getSelectModal({ cmd: cmd }, function (result) {
    var name = result.human.replace(/#/g, '');
    name = name.split('[');
    name = name[name.length - 1].replace(/]/g, '');
    var maxIndex = getMaxIndex(cmdCat);
    cmdCat.push({ id: result.cmd.id, name: name, index: maxIndex + 1, subtype: result.cmd.subType });
    refreshCmdListOption(optionsJson);
  })
}

function deleteCmdOption(elm, optionsJson) {
  saveCmdList();

  var id = $(elm).data('id');
  var currentIndex = $(elm).data('index');

  var cmdToDelete = cmdCat.find(i => i.id == id && i.index == currentIndex);
  var index = cmdCat.indexOf(cmdToDelete);
  cmdCat.forEach(item => {
    if (item.index > cmdToDelete.index) {
      item.index = item.index - 1;
    }
  });
  cmdCat.splice(index, 1);
  refreshCmdListOption(optionsJson);
}


//More Infos

function refreshMoreInfos() {
  let div = '';
  moreInfos.forEach((item, i) => {
    item.index = i;
    var unit = item.unit || '';
    div += `<div class='input-group moreInfosItem' style="border-width:1px; border-style:dotted;" id="moreInfo-${item.id}" data-id="${item.id}">
          <input style="width:260px;" class='input-sm form-control roundedLeft' id="${item.id}-input" value='${item.id}' disabled>
          <label style="position:absolute; margin-left:5px; width: 40px;"> Nom : </label>
          <input style="width:80px;position:absolute; margin-left:45px;" id="${item.id}-name-input" value='${item.name}'>
          <label style="position:absolute; margin-left:130px; width: 42px;"> Unité : </label>
          <input style="width:80px;position:absolute; margin-left:175px;" id="${item.id}-unit-input" value='${unit}'>
          <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-left:265px;cursor:grab!important;" aria-hidden="true"></i>
          <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;position:absolute; margin-left:5px;" aria-hidden="true" onclick="deleteMoreInfo('${item.id}');"></i>
          </div>`;
  });
  $("#moreInfos-div").html(div);
  moreInfos.forEach(item => {
    cmdHumanName = getHumanName(item.id);
    if (cmdHumanName != '') {
      $("#" + item.id + "-input").val(cmdHumanName);
      item.human = cmdHumanName;
    }
    else {
      $("#" + item.id + "-input").val("#" + item.id + "#");
    }
  })

  refreshImgListOption();
  refreshInfoSelect();
}

function addMoreCmd() {
  jeedom.cmd.getSelectModal({ cmd: { type: 'info' } }, function (result) {
    getCmdDetail({ id: result.cmd.id, human: result.human }, function (result, _param) {
      moreInfos.push({ type: 'cmd', id: result.id, human: _param.human, name: result.name, unit: result.unite });
      saveImgOption();
      refreshMoreInfos();
    })
  });
}

function deleteMoreInfo(id) {
  var infoToDelete = moreInfos.find(i => i.id == id);
  var index = moreInfos.indexOf(infoToDelete);
  moreInfos.splice(index, 1);
  refreshMoreInfos();
}

// Infos select
function refreshInfoSelect() {
  let infosOptionHtml = '';
  var type = $("#widgetsList-select").val();
  var widget = widgetsList.widgets.find(i => i.type == type);
  if (widget?.variables) {
    widget.variables.forEach(v => {
      infosOptionHtml += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)">
        <a href="#">#${v.name}#</a></li>`;
    });
  }
  $('input[cmdType="info"]').each((i, el) => {
    infosOptionHtml += `<li info="${$("input[id=" + el.id + "]").attr('cmdid')}" onclick="infoSelected('${el.title}', this)">
      <a href="#">${el.title}</a></li>`;
  });
  moreInfos.forEach(i => {
    if (i.human) {
      infosOptionHtml += `<li info="${i.id}" onclick="infoSelected('${i.human}', this)">
      <a href="#">${i.human}</a></li>`;
    }
  });
  $(".infos-select").html(infosOptionHtml);

  refreshStrings();
}

function infoSelected(value, el) {
  let inputId = $(el).parent().attr("input")
  //$("#"+inputId).val( $("#"+inputId).val() + value);
  let input = $("#" + inputId);
  input.val([input.val().slice(0, input[0].selectionStart), value, input.val().slice(input[0].selectionStart)].join(''));
}

function refreshStrings() {
  const infoCmd = moreInfos.slice();
  $('input[cmdType="info"]').each((i, el) => {
    infoCmd.push({ id: $("input[id=" + el.id + "]").attr('cmdid'), human: el.title });
  });
  $("#name-input").val(idToHuman($("#name-input").val(), infoCmd));
  $("#subtitle-input-value").val(idToHuman($("#subtitle-input-value").val(), infoCmd));
}



// Image condition list
function refreshImgListOption(dataType = 'widget') {
  var options = [];


  if (dataType != 'widget') {
    //if summary modal open, then get the summary Config object
    var widget = summaryConfig.find(i => i.type == 'summary');
  }
  else {
    //otherwise use the widget config based on the selected type one
    var type = $("#widgetsList-select").val();
    var widget = widgetsList.widgets.find(i => i.type == type);
  }

  curOption = "";
  //get all info
  $('input[cmdType="info"]').each((i, el) => {
    options.push({ type: 'cmd', id: $("input[id=" + el.id + "]").attr('cmdid'), human: el.title })
  });
  options = options.concat(moreInfos);

  if (widget?.variables) {
    widget.variables.forEach(v => {
      options.push({ type: 'var', id: v.name, human: `#${v.name}#` })
    });
  }

  imgCat.sort(function (s, t) {
    return s.index - t.index;
  });

  imgCat.forEach(item => {
    curOption += `
		<div data-id="${item.index}" class='input-group jcImgListMovable'>
			Si
			<input style="width:385px;height:31px;margin-left:5px" class=' roundedLeft' index="${item.index}" id="imglist-cond-${item.index}" value="${item.condition}"
			 onchange="setCondValue(this, 'imgList')" />`;
    curOption += `
        <div class="dropdown" id="imglist-cond-select" style="display:inline !important;">
        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
        <i class="fas fa-plus-square"></i> </a>
        <ul class="dropdown-menu infos-select" input="imglist-cond-${item.index}">`;
    if (widget.variables) {
      widget.variables.forEach(v => {
        curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
      });
    }
    curOption += `</ul></div>`;
    curOption += `<a class="btn btn-success roundedRight imagePicker" index="${item.index}"><i class="fas fa-plus-square">
    </i> Image </a>
    <a data-id="icon-div" id="icon-div-${item.index}" class="removeImage">${iconToHtml(item.image)}</a>
    <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
    <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteImgOption('${item.index}');"></i>
    </div>` ;


  });
  $("#imgList-option").html(curOption);
  setCondToHuman('imgList');
  refreshInfoSelect();
}

function saveImgOption() {
  imgCat.forEach(item => {
    item.image = htmlToIcon($("#icon-div-" + item.index).children().first());
    item.condition = $("#imglist-cond-" + item.index).val();
  });
}

function addImgOption(dataType) {
  saveImgOption();
  var maxIndex = getMaxIndex(imgCat);
  imgCat.push({ index: maxIndex + 1 });
  refreshImgListOption(dataType);
  if (dataType == 'widget') refreshInfoSelect();
}

function loadSortable(elt) {

  if (elt == 'imgList' || elt == 'all') {
    $("#imgList-option").sortable({
      axis: "y", cursor: "move", items: ".jcImgListMovable", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
      start: function () { saveImgOption(); },
      update: function (event, ui) {
        $('#imgList-option > .jcImgListMovable').each((i, el) => {
          var imgId = $(el).data('id');
          var imgToMove = imgCat.find(i => i.index == parseInt(imgId));
          imgToMove.index = i;
        }
        );
        refreshImgListOption();

      }
    });
  }

  if (elt == 'widgetList' || elt == 'all') {
    $("#widget-option").sortable({
      axis: "y", cursor: "move", items: ".jcWidgetListMovable", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
      update: function (event, ui) {
        $('#widget-option > .jcWidgetListMovable').each((i, el) => {
          var widgetId = $(el).data('id');
          var widgetToMove = widgetsCat.find(i => i.id == parseInt(widgetId));
          widgetToMove.index = i;
        }
        );
        refreshWidgetOption();

      }
    });
  }

  if (elt == 'cmdList' || elt == 'all') {
    $("#cmdList-option").sortable({
      axis: "y", cursor: "move", items: ".jcCmdList", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
      start: function () { saveCmdList(); },
      update: function (event, ui) {
        $('#cmdList-option > .jcCmdList').each((i, el) => {
          var cmdId = $(el).data('id');
          var cmdIndex = $(el).data('index');

          var cmdToMove = cmdCat.find(i => i.id == parseInt(cmdId) && i.index == cmdIndex);
          cmdToMove.index = i;
        }
        );
        var opt = $("#cmdList-option").data('cmd-options');
        refreshCmdListOption(JSON.stringify(opt));

      }
    });
  }

  if (elt == 'moreInfos' || elt == 'all') {
    $("#moreInfos-div").sortable({
      axis: "y", cursor: "move", items: ".moreInfosItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
      update: function (event, ui) {
        moreInfos = [];
        $('#moreInfos-div > .moreInfosItem').each((i, el) => {
          info = {};
          info.id = $(el).data('id');
          info.human = $(el).find("#" + info.id + "-input").val();
          info.name = $(el).find("#" + info.id + "-name-input").val();
          info.unit = $(el).find("#" + info.id + "-unit-input").val();
          info.index = i;
          moreInfos.push(info);
        }
        );
        refreshMoreInfos();
      }
    });

  }

}

function deleteImgOption(id) {
  saveImgOption();
  var imgToDelete = imgCat.find(i => i.index == id);
  var index = imgCat.indexOf(imgToDelete);
  imgCat.splice(index, 1);
  imgCat.forEach(item => {
    if (item.index > imgToDelete.index) {
      item.index = item.index - 1;
    }
  });
  refreshImgListOption();
}


function addWidgetOption(choices) {
  var widgets = choices.split(".");
  getSimpleModal({ title: "Choisir un widget", fields: [{ type: "widget", choices: widgets }] }, function (result) {
    var maxIndex = getMaxIndex(widgetsCat);
    widgetsCat.push({ id: result.widgetId, index: maxIndex + 1, roomName: result.roomName });
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


$(".widgetMenu .saveWidget").click(function () {
  $('#widget-alert').hideAlert();

  try {

    var result = {};
    var widgetConfig = widgetsList.widgets.find(w => w.type == $("#widgetsList-select").val());
    let infoCmd = moreInfos.slice();

    $('input[cmdType="info"]').each((i, el) => {
      infoCmd.push({ id: $("input[id=" + el.id + "]").attr('cmdid'), human: el.title });
    });

    widgetConfig.options.forEach(option => {
      if (option.category == "cmd") {
        if ($("#" + option.id + "-input").attr('cmdId') == '' & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }

        if ($("#" + option.id + "-input").attr('cmdId') != '') {
          result[option.id] = {};
          result[option.id].id = $("#" + option.id + "-input").attr('cmdId');
          result[option.id].type = $("#" + option.id + "-input").attr('cmdType');
          result[option.id].subType = $("#" + option.id + "-input").attr('cmdSubType');
          result[option.id].minValue = $("#" + option.id + "-minInput").val() != '' ? $("#" + option.id + "-minInput").val() : undefined;
          result[option.id].maxValue = $("#" + option.id + "-maxInput").val() != '' ? $("#" + option.id + "-maxInput").val() : undefined;
          result[option.id].step = $("#" + option.id + "-stepInput").val() != '' ? $("#" + option.id + "-stepInput").val() : undefined;
          result[option.id].unit = $("#" + option.id + "-unitInput").val() != '' ? $("#" + option.id + "-unitInput").val() : undefined;
          result[option.id].invert = $("#invert-" + option.id).is(':checked') || undefined;
          result[option.id].confirm = $("#confirm-" + option.id).is(':checked') || undefined;
          result[option.id].secure = $("#secure-" + option.id).is(':checked') || undefined;
          result[option.id].pwd = $("#pwd-" + option.id).is(':checked') || undefined;
          Object.keys(result[option.id]).forEach(key => result[option.id][key] === undefined ? delete result[option.id][key] : {});
        }
        else {
          result[option.id] = undefined;
        }
      }
      else if (option.category == "scenario") {

        if ($("#" + option.id + "-input").attr('scId') == '' & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }

        if ($("#" + option.id + "-input").attr('scId') != '') {
          result[option.id] = $("#" + option.id + "-input").attr('scId');

          result['options'] = {};
          result['options']['scenario_id'] = $("#" + option.id + "-input").attr('scId');
          result['options']['action'] = 'start';
          if ($('#tags-scenario-input').val() != '') {
            getCmdIdFromHumanName({ alert: '#widget-alert', stringData: $('#tags-scenario-input').val() }, function (data, _params) {
              result['options']['tags'] = data;
            });
          }
          result['options'].confirm = $("#confirm-" + option.id).is(':checked') || undefined;
          result['options'].secure = $("#secure-" + option.id).is(':checked') || undefined;
          result['options'].pwd = $("#pwd-" + option.id).is(':checked') || undefined;

        }
      }
      else if (option.category == "string") {
        if ($("#" + option.id + "-input").val() == '' & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }
        result[option.id] = parseString($("#" + option.id + "-input").val(), infoCmd);
      }
      else if (option.category == "binary") {
        result[option.id] = $("#" + option.id + "-input").is(':checked');
      }
      else if (option.category == "color") {
        result[option.id] = $("#" + option.id + "-input").val();
      }
      else if (option.category == "stringList") {
        if ($("#" + option.id + "-input").val() == 'none' & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }

        if ($("#" + option.id + "-input").val() != 'none') {
          if (option.id == 'subtitle') {
            result[option.id] = parseString($("#subtitle-input-value").val(), infoCmd);
          }
          else {
            result[option.id] = $("#" + option.id + "-input").val();
          }
        }
        else {
          result[option.id] = undefined;
        }
      }
      else if (option.category == "widgets") {
        if (widgetsCat.length == 0 & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }
        result[option.id] = widgetsCat;
      }
      else if (option.category == "cmdList") {
        if (cmdCat.length == 0 & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }

        // ---- Start cmdCat.forEach
        cmdCat.forEach(item => {
          if (option.options.hasImage | option.options.hasIcon) {
            item.image = htmlToIcon($('.jcCmdListOptions[data-id="icon-' + item.id + '"][data-index="' + item.index + '"]').children().first());
            if (item.image == {}) { delete item.image; }
          }
          if (option.options.type == 'action') {
            item['confirm'] = $('.jcCmdListOptions[data-id="confirm-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
            item['secure'] = $('.jcCmdListOptions[data-id="secure-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
            item['pwd'] = $('.jcCmdListOptions[data-id="pwd-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
            item['name'] = $('.jcCmdListOptions[data-id="custom-name-' + item.id + '"][data-index="' + item.index + '"]').val() || "";

            if (item.subtype != undefined && item.subtype != 'other') {
              var optionsForSubtype = { 'message': ['title', 'message'], 'slider': ['slider'], 'color': ['color'] };

              item['options'] = {};

              if (item.subtype == 'select') {
                item['options']['select'] = $('.jcCmdListOptions[data-id="select-' + item.id + '"][data-index="' + item.index + '"] option:selected').val();
              }
              else {

                var currentArray = optionsForSubtype[item.subtype];
                currentArray.forEach(key => {
                  var tmpData = $('.jcCmdListOptions[data-id="' + key + '-' + item.id + '"][data-index="' + item.index + '"]').val();
                  if (tmpData != '') {
                    getCmdIdFromHumanName({ alert: '#widget-alert', stringData: tmpData, subtype: key }, function (result, _params) {
                      item['options'][_params.subtype] = result;
                    });
                  }
                  else {
                    item['options'][key] = '';
                  }
                });

              }

            }
          }
        });
        // ---- END cmdCat.forEach
        result[option.id] = cmdCat;

      }
      else if (option.category == "ifImgs") {
        if (imgCat.length == 0 & option.required) {
          throw 'La commande ' + option.name + ' est obligatoire';
        }

        imgCat.forEach(item => {
          item.image = htmlToIcon($("#icon-div-" + item.index).children().first());
          getCmdIdFromHumanName({ alert: '#widget-alert', stringData: $("#imglist-cond-" + item.index).val() }, function (result, _params) {
            item.condition = result;
          });
        });

        result[option.id] = imgCat;
      }
      else if (option.category == "img") {
        let icon = htmlToIcon($("#icon-div-" + option.id).children().first());
        if (icon.source == undefined & option.required) {
          throw "L'image est obligatoire";
        }
        result[option.id] = icon.source != undefined ? icon : undefined;
      }
      else if (option.category == "choicesList") {
        option.choices.forEach(v => {
          result[v.id] = $("#" + v.id + "-jc-checkbox").prop('checked');
        });
      }
    });

    // ----- END forEach ----

    result.type = $("#widgetsList-select").val();
    widgetType = $("#widgetsList-select").val();
    result.blockDetail = $("#blockDetail-input").is(':checked');

    widgetEnable = $('#enable-input').is(":checked");
    result.enable = widgetEnable;

    widgetRoom = $('#room-input :selected').val();
    widgetRoomName = $('#room-input :selected').text();
    if (widgetRoom != 'none') {
      if (widgetRoom == 'global') {
        result.room = 'global';
      }
      else {
        result.room = parseInt(widgetRoom);
      }
    }


    if (moreInfos.length > 0) {
      result.moreInfos = [];
      moreInfos.forEach(info => {
        info.name = $("#" + info.id + "-name-input").val();
        info.unit = $("#" + info.id + "-unit-input").val();
        delete info.human;
        result.moreInfos.push(info);
      });
    }
    toSave = JSON.stringify(result)

    widgetImg = $("#widgetImg").attr("src");

    widgetName = $("#name-input").val();
    widgetId = $("#widgetOptions").attr('widget-id');


    if (toSave !== null) {
      $.ajax({
        type: "POST",
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
          action: "saveWidgetConfig",
          eqId: widgetId,
          widgetJC: toSave,
          imgPath: widgetImg
        },
        dataType: 'json',
        error: function (error) {
          $('#div_alert').showAlert({ message: error.message, level: 'danger' });
        },
        success: function (data) {
          if (data.state != 'ok') {
            $('#div_alert').showAlert({
              message: data.result,
              level: 'danger'
            });
          }
          else {
            if ($('.widgetMenu .saveWidget').attr('exit-attr') == 'true') {
              var vars = getUrlVars()
              var url = 'index.php?'
              delete vars['id']
              delete vars['saveSuccessFull']
              delete vars['removeSuccessFull']
              vars['saveSuccessFull'] = "1";

              url = getCustomParamUrl(url, vars);
              modifyWithoutSave = false
              loadPage(url)
            }
            else {

              if ($("#selWidgetDetail").length > 0) {
                refreshWidgetDetails();
                refreshWidgetsContent();

                //if it's a new widget
                if (widgetId == undefined || widgetId == '') {

                  if (widgetRoomName != '') widgetRoomName = ' (' + widgetRoomName.replace(/(?:^[\s\u00a0]+)|(?:[\s\u00a0]+$)/g, '') + ')';
                  widgetId = parseInt(data.result.id);
                  $('#selWidgetDetail')
                    .append($("<option></option>")
                      .attr("value", widgetId)
                      .attr("data-widget-id", widgetId)
                      .attr("data-type", widgetType)
                      .text(widgetName + widgetRoomName));
                }
                else {  //if it's just an update
                  $("#selWidgetDetail option[data-widget-id=" + widgetId + "]").text(widgetName);
                }
              }
              $("#widgetModal").dialog('destroy').remove();
            }
          }
        }
      })

    }
  } catch (error) {
    $('#widget-alert').showAlert({ message: error, level: 'danger' });
    console.error(error);
  }

});



$(".widgetMenu .hideWidget").click(function () {
  $("#widgetModal").dialog('destroy').remove();
});

$(".widgetMenu .duplicateWidget").click(function () {

  $('#widget-alert').hideAlert();

  $('#widgetOptions').attr('widget-id', '');

  $(this).hide()
  $('.widgetMenu .removeWidget').hide()
  showAutoFillWidgetCmds();
  $('#widget-alert').showAlert({ message: 'Vous êtes sur le widget dupliqué, réalisez (ou non) vos modifications. Dans tous les cas, pensez à sauvegarder !', level: 'success' });
  // $('.widgetMenu .saveWidget').attr('exit-attr', 'true');

});

$(".widgetMenu .removeWidget").click(function () {
  var warning = "<i source='md' name='alert-outline' style='color:#ff0000' class='mdi mdi-alert-outline'></i>";

  if (itemId == undefined) {
    var widgetId = $("#widgetOptions").attr('widget-id');
  }
  else {
    var widgetId = itemId;
  }


  var allName = $('#widgetExistInEquipement').text();

  var msg = '';
  if (allName.length == 0 || allName == '' || allName == undefined) {
    msg = '(Ce widget n\'est utilisé dans aucun équipement)';
  }
  else {
    var count = (allName.match(/,/g) || []).length;
    if (count == 0) {
      var eq = 'de l\'équipement';
    }
    else {
      var eq = 'des équipements';
    }

    msg = warning + '  La suppression retirera ce widget ' + eq + ' suivant : ' + allName + '  ' + warning;
  }

  getSimpleModal({
    title: "Confirmation", fields: [{
      type: "string",
      value: "Voulez-vous supprimer ce widget ?<br/><br/>" + msg
    }]
  }, function (result) {
    $('#widget-alert').hideAlert();
    widgetId = $("#widgetOptions").attr('widget-id');

    $.ajax({
      type: "POST",
      url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
      data: {
        action: "removeWidgetConfig",
        eqId: widgetId
      },
      dataType: 'json',
      error: function (error) {
        $('#div_alert').showAlert({ message: error.message, level: 'danger' });
      },
      success: function (data) {
        if (data.state != 'ok') {
          $('#div_alert').showAlert({
            message: data.result,
            level: 'danger'
          });
        }
        else {
          var vars = getUrlVars()
          var url = 'index.php?'
          for (var i in vars) {
            if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
              url += i + '=' + vars[i].replace('#', '') + '&'
            }
          }
          modifyWithoutSave = false
          url += '&saveSuccessFull=1'
          loadPage(url)
        }
      }
    })
  });

});


function getWidgetPath(id) {
  //console.log(" getWidgetPath id :  " + id ) ;
  //console.log(" getWidgetPath ==== tous les widgets ===> " , allWidgetsDetail) ;
  var widget = allWidgetsDetail.find(w => w.id == id);
  var name = (' ' + widget.name).slice(1);

  if (widget.parentId === undefined || widget.parentId == null || typeof configData === 'undefined') {
    return name;
  }
  var id = (' ' + widget.parentId.toString()).slice(1);
  parent = configData.payload.groups.find(i => i.id == id);
  if (parent) {
    name = parent.name + " / " + name;
    if (parent.parentId === undefined || parent.parentId == null) {
      return name;
    }
    id = (' ' + parent.parentId.toString()).slice(1);
  }
  parent2 = configData.payload.sections.find(i => i.id == id);
  if (parent2) {
    name = parent2.name + " / " + name;
    if (parent2.parentId === undefined || parent2.parentId == null) {
      return name;
    }
    id = (' ' + parent2.parentId.toString()).slice(1);
  }
  parent3 = configData.payload.tabs.find(i => i.id == id);
  if (parent3) {
    name = parent3.name + " / " + name;
  }
  return name;
}

function getRoomName(id) {
  if (id == 'global') { return 'Global'; }
  const room = roomList.find(r => r.id == id);
  if (room) {
    return room.name;
  } else {
    return undefined;
  }
}



function getCmdDetail(_params, _callback) {
  if (typeof _params.alert == 'undefined') {
    _params.alert = '#div_alert';
  }
  var paramsRequired = ['id'];
  var paramsSpecifics = {
    global: false,
    success: function (result) {

      if ('function' == typeof (_callback)) {
        _callback(result, _params);
      }

    }
  };

  try {
    jeedom.private.checkParamsRequired(_params || {}, paramsRequired);
  } catch (e) {
    (_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e);
    $(_params.alert).showAlert({ message: e.message, level: 'danger' });
    return;
  }
  var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {});
  var paramsAJAX = jeedom.private.getParamsAJAX(params);
  paramsAJAX.url = 'core/ajax/cmd.ajax.php';
  paramsAJAX.data = {
    action: 'getCmd',
    id: _params.id,
  };
  $.ajax(paramsAJAX);
};


function selectInfoCmd(elt, conf) {
  let input = $(elt);
  jeedom.cmd.getSelectModal({ cmd: { type: 'info' } }, function (result) {
    input.val([input.val().slice(0, input[0].selectionStart), result.human, input.val().slice(input[0].selectionStart)].join(''));
    setCondValue(input, conf);
  })
}

function setCondValue(elm, confArr) {
  if (confArr == 'bg') {
    conf = configData.payload.background.condBackgrounds
  }
  else if (confArr == 'batteries') {
    conf = configData.payload.batteries.condImages
  }
  else {
    conf = imgCat
  }

  var curCond = conf.find(c => c.index == $(elm).attr('index'));
  let res = $(elm).val()
  const match = res.match(/#.*?#/g);
  if (match) {
    match.forEach(item => {
      $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
          action: 'humanReadableToCmd',
          human: item
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
          if (data.state == 'ok') {
            res = res.replace(item, data.result)
          }
        }
      });
    });
  }
  curCond.condition = res;
}

function setCondToHuman(confArr) {
  if (confArr == 'bg') {
    conf = configData.payload.background.condBackgrounds;
    idName = 'bg-cond-input-';
  }
  else if (confArr == 'batteries') {
    conf = configData.payload.batteries.condImages;
    idName = 'batteries-cond-input-';
  }
  else {
    conf = imgCat;
    idName = 'imglist-cond-';
  }

  conf.forEach(cond => {
    let input = $("#" + idName + cond.index);
    let value = cond.condition ? cond.condition.slice() : '';
    const match = value.match(/#.*?#/g);
    if (match) {
      match.forEach(item => {
        $.post({
          url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
          data: {
            action: 'cmdToHumanReadable',
            strWithCmdId: item
          },
          cache: false,
          dataType: 'json',
          async: false,
          success: function (data) {
            if (data.state == 'ok') {
              value = value.replace(item, data.result);
            }
          }
        });
      });
    }
    input.val(value);
  });
}

$('body').off('click', '.removeParent').on('click', '.removeParent', function () {
  $(this).parent().remove();
});


/**
 * *************
 * TOP ACTION BUTTON
 * *************
 */


$('.eqLogicAction[data-action=addWidget]').off('click').on('click', function () {
  getWidgetModal({ title: "Configuration du widget", removeAction: false, exit: true });
})

$('.eqLogicAction[data-action=showError]').off('click').on('click', function () {

  var hide = ($('#spanWidgetErreur').text() == 'Erreur') ? true : false;
  if (hide) {
    $('.widgetDisplayCard').not(".hasError").hide();
  }
  else {
    $('.widgetDisplayCard').show();
  }


  var typeSelected = $('#widgetTypeSelect').val();

  if (typeSelected != 'none') {
    $('.widgetDisplayCard').not("[data-widget_type=" + typeSelected + "]").hide();
  }


  $('.eqLogicThumbnailContainer').packery();

  if (hide) {
    $('#spanWidgetErreur').text('Tous');
    $('.eqLogicAction[data-action=showError]').css('color', 'grey');
  }
  else {
    $('#spanWidgetErreur').text('Erreur');
    $('.eqLogicAction[data-action=showError]').css('color', 'red');
  }
  updateWidgetCount()

})

$('.eqLogicAction[data-action=addWidgetBulk]').off('click').on('click', function () {
  $("#widgetModal").dialog('destroy').remove();
  $('body').append('<div id="widgetModal"></div>');
  $('#widgetModal').dialog({
    title: "{{Ajout de widgets en masse}}",
    width: 0.95 * $(window).width(),
    height: 0.8 * $(window).height(),
    modal: true,
    closeText: ''
  });
  $('#widgetModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.widgetBulkModal.JeedomConnect').dialog('open');
})


$('.eqLogicAction[data-action=showCommunity]').off('click').on('click', function () {
  // $('.pluginInfo').toggle("slide", { direction: "right" }, 1000);
  getSimpleModal({
    title: "Forum",
    width: 0.5 * $(window).width(),
    fields: [{
      type: "string",
      value: $('.txtInfoPlugin').html()
    },
    {
      type: "string",
      id: "infoPluginModal",
      value: $('.infoPlugin').html()
    }],
    buttons: {
      "Fermer": function () {
        $('#simpleModalAlert').hide();
        $(this).dialog("close");
      },
      "Copier": function () {
        copyDivToClipboard('#infoPluginModal', true)
      }
    }
  }, function (result) { });


});

$('.eqLogicAction[data-action=showSummary]').off('click').on('click', function () {
  $('body').append('<div id="widgetSummaryModal"></div>');
  $('#widgetSummaryModal').dialog({
    title: "{{Synthèse globale des widgets}}",
    autoOpen: false,
    modal: true,
    closeText: '',
    width: 0.9 * $(window).width(),
    height: 0.8 * $(window).height(),
    closeOnEscape: false,
    // open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); },
    close: function (ev, ui) { check_before_closing(); }
  });
  $('#widgetSummaryModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.widgetSummary.JeedomConnect').dialog('open');
})

$('.eqLogicAction[data-action=showEquipmentSummary]').off('click').on('click', function () {
  $('body').append('<div id="equipmentSummaryModal"></div>');
  $('#equipmentSummaryModal').dialog({
    title: "{{Synthèse des équipements JC}}",
    autoOpen: false,
    modal: true,
    closeText: '',
    width: 0.9 * $(window).width(),
    height: 0.8 * $(window).height(),
    closeOnEscape: false,
    // open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); },
    close: function (ev, ui) { check_before_closing(); }
  });
  $('#equipmentSummaryModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.equipmentSummary.JeedomConnect').dialog('open');
})

$('.eqLogicAction[data-action=showNotifAll]').off('click').on('click', function () {
  $('body').append('<div id="notifAllModal"></div>');
  $('#notifAllModal').dialog({
    title: "{{Configuration de la notification de \"tous\" les équipements}}",
    autoOpen: false,
    modal: true,
    closeText: '',
    width: 0.7 * $(window).width(),
    height: 0.8 * $(window).height(),
    closeOnEscape: false
  });
  $('#notifAllModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.notifAll.JeedomConnect').dialog('open');
})

// Start Generic Types
// HACK Remove when gentype config in plugin is not needed anymore
function gotoGenTypeConfig() {
  $('#md_modal').dialog({ title: "{{Objets / Pièces}}" });
  $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=gentype.objects').dialog('open');
}

$('.eqLogicAction[data-action=gotoGenTypeConfig]').off('click').on('click', function () {
  gotoGenTypeConfig();
})
// End Generic Types

// ------------- END TOP ACTION BUTTON



/**
 * *************
 * SEARCH, ORDER & FILTER BAR
 * *************
 */

$('#widgetsList-div').on('change', function () {
  updateWidgetCount()
})

function updateWidgetCount() {

  var nbVisible = $('.widgetDisplayCard:visible').length;
  var nbTotal = $('.widgetDisplayCard').length;

  var text = (nbTotal != nbVisible) ? nbVisible + "/" + nbTotal : nbTotal;

  $('#coundWidget').text("(" + text + ")");
}

$('#widgetTypeSelect').on('change', function () {
  var typeSelected = this.value;

  $('.widgetDisplayCard').show();
  if (typeSelected != 'none') {
    $('.widgetDisplayCard').not("[data-widget_type=" + typeSelected + "]").hide();
  }

  var widgetSearch = $("#in_searchWidget").val().trim();
  if (widgetSearch != '') {
    $('#in_searchWidget').keyup()
  }

  $('.eqLogicThumbnailContainer').packery();

  updateWidgetCount();

});

$('#eraseFilterChoice').off('click').on('click', function () {
  var vars = getUrlVars()
  var url = 'index.php?'
  for (var i in vars) {
    if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull' &&
      i != 'jcOrderBy' && i != 'jcSearch' && i != 'jcFilter') {
      url += i + '=' + vars[i].replace('#', '') + '&'
    }
  }

  loadPage(url)
})

if ($('#in_searchWidget').val() != '') {
  $('#in_searchWidget').keyup();
}

$('#bt_resetSearchWidget').on('click', function () {
  $('#in_searchWidget').val('').keyup()
})

$('.updateOrderWidget').on('change', function () {

  var type = $(this).val();

  var vars = getUrlVars()
  var url = 'index.php?'

  url = getCustomParamUrl(url, vars);

  loadPage(url)

});

function getCustomParamUrl(url, vars) {

  for (var i in vars) {
    if (i != 'jcOrderBy' && i != 'jcFilter' && i != 'jcSearch') {
      url += i + '=' + vars[i].replace('#', '') + '&'
    }
  }

  var widgetFilter = $("#widgetTypeSelect option:selected").val();
  if (widgetFilter != 'none') {
    url += '&jcFilter=' + widgetFilter
  }

  var widgetOrder = $("#widgetOrder option:selected").val();
  if (widgetOrder != 'none') {
    url += '&jcOrderBy=' + widgetOrder
  }

  var widgetSearch = $("#in_searchWidget").val().trim();
  if (widgetSearch != '') {
    url += '&jcSearch=' + widgetSearch
  }

  return url;
}

function sortWidgets() {
  if (jcOrderBy === undefined) jcOrderBy = 'object';

  allWidgetsDetail.sort(SortByName);
  if (jcOrderBy == 'object') {
    allWidgetsDetail.sort(SortByRoom);
  } else if (jcOrderBy == 'type') {
    allWidgetsDetail.sort(SortByType);
  }
}

function SortByName(a, b) {
  var aName = a.name?.toLowerCase();
  var bName = b.name?.toLowerCase();
  return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
}

function SortByType(a, b) {
  var aType = a.type.toLowerCase();
  var bType = b.type.toLowerCase();
  return ((aType < bType) ? -1 : ((aType > bType) ? 1 : 0));
}

function SortByRoom(a, b) {

  var aObj = ('room' in a) ? roomList.find(r => r.id == a.room) || undefined : undefined;
  var bObj = ('room' in b) ? roomList.find(r => r.id == b.room) || undefined : undefined;

  var aRoom = (aObj != undefined) ? aObj.name.toLowerCase() : 'AAAucun';
  var bRoom = (bObj != undefined) ? bObj.name.toLowerCase() : 'AAAucun';

  return ((aRoom < bRoom) ? -1 : ((aRoom > bRoom) ? 1 : 0));
}

$('#in_searchWidget').off('keyup').keyup(function () {
  var search = $(this).value()
  var widgetFilter = $("#widgetTypeSelect option:selected").val();

  if (search == '') {
    if (widgetFilter == 'none') {
      $('.widgetDisplayCard').show()
    }
    else {
      $('.widgetDisplayCard').each(function () {
        widgetType = $(this).attr('data-widget_type');
        if (widgetFilter == widgetType) {
          $(this).closest('.widgetDisplayCard').show()
        }
      })
    }
    $('.eqLogicThumbnailContainer').packery()
    updateWidgetCount();
    return;
  }


  $('.widgetDisplayCard').hide()
  search = normTextLower(search)
  var text
  var widgetId

  $('.widgetDisplayCard').each(function () {
    text = normTextLower($(this).children('.name').text())
    widgetId = normTextLower($(this).attr('data-widget_id'))
    widgetType = $(this).attr('data-widget_type');
    if (text.indexOf(search) >= 0 || widgetId.indexOf(search) >= 0) {
      if (widgetFilter == 'none' || widgetFilter == widgetType) {
        $(this).closest('.widgetDisplayCard').show()
      }
    }
  })
  $('.eqLogicThumbnailContainer').packery()
  updateWidgetCount();
})

// ------------- END SEARCH & FILTER BAR

/**
 * *************
 * COMMUNITY INFO
 * *************
 */

function copyDivToClipboard(myInput, addBacktick = false) {
  var initialText = $(myInput).html();
  if (addBacktick) {
    $(myInput).html('```<br/>' + initialText.replaceAll('<b>', '').replaceAll('</b>', '') + '```');
  }
  var range = document.createRange();
  range.selectNode($(myInput).get(0));
  window.getSelection().removeAllRanges(); // clear current selection
  window.getSelection().addRange(range); // to select text
  document.execCommand("copy");
  window.getSelection().removeAllRanges();// to deselect
  $('#div_simpleModalAlert').showAlert({
    message: 'Infos copiées',
    level: 'success'
  });
  if (addBacktick) {
    $(myInput).html(initialText);
  }
}
// ------------- END COMMUNITY INFO


/**
 * *************
 * WARNING COMMUNITY INFO
 * *************
 */

function displayJCWarning() {
  var color = [
    'background-color: #fc59cc !important;',
    'background-color: #933ed6 !important;',
    'background-color: rgb(27,161,242)!important;',
    'background-color: orange !important;'
  ];
  shuffle(color);

  var varButton = [
    {
      text: "Ok mais j'ai pas lu",
      open: function () {
        $(this).attr('style', color[0]);
      },
      click: function () {
        warningResponse($(this), false);
      }
    },
    {
      text: "Je clique sans lire",
      open: function () {
        $(this).attr('style', color[1]);
      },
      click: function () {
        warningResponse($(this), false);
      }
    },
    {
      text: "J'ai lu et je le ferai. Promis juré",
      open: function () {
        $(this).attr('style', color[2]);
      },
      click: function () {
        $.post({
          url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
          data: {
            'action': 'incrementWarning'
          },
          cache: false,
          dataType: 'json',
          async: true,
        });
        warningResponse($(this), true);
      }
    },
    {
      text: "J'ai jamais besoin d'aide je suis trop balaise",
      open: function () {
        $(this).attr('style', color[3]);
      },
      click: function () {
        warningResponse($(this), false);
      }
    }
  ];


  shuffle(varButton);

  getSimpleModal({
    title: "Important - JeedomConnect - A lire",
    width: 0.5 * $(window).width(),
    hideCloseButton: true,
    hideActionButton: true,
    fields: [{
      type: "string",
      value: $('.displayJCWarning').html()
    }],
    buttons: varButton
  }, function (result) { });


};

function warningResponse(elt, good) {
  JCwarningAlreadyDisplayed = true;
  $(elt).dialog("close");
  $.fn.showAlert({
    message: good ? 'Merci. On compte sur toi !' : 'Mauvais réponse ... on se revoit bientot !',
    level: good ? 'success' : 'warning'
  });
  $(".displayJCWarning").remove();
}


/**
 * *************
 * ASSISTANT BUTTON
 * *************
 */

$(".btnAssistant").click(function (event) {
  var id = $(this).closest('.eqLogicDisplayCard').data('eqlogic_id');
  openAssistantWidgetModal(id, event);
});

function openAssistantWidgetModal(id, event) {
  if (event !== undefined) event.stopPropagation();
  $('#md_modal').dialog({ title: "{{Configuration de l'équipement}}" });
  $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.JeedomConnect&eqLogicId=' + id).dialog('open');
}


$(".btnNotification").click(function (event) {
  var id = $(this).closest('.eqLogicDisplayCard').data('eqlogic_id');
  openAssistantNotificationModal(id, event);
});

function openAssistantNotificationModal(id, event) {
  if (event !== undefined) event.stopPropagation();
  $('#md_modal').dialog({ title: "{{Configuration des notifications}}" });
  $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=notifs.JeedomConnect&eqLogicId=' + id).dialog('open');
}

// ------------- END ASSISTANT BUTTON


/**
 * *************
 * COMMAND TAB
 * *************
 */

$("#commandtab").sortable({
  axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true
});

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td style="min-width:50px;width:70px;">';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += '</td>';
  tr += '<td style="min-width:300px;width:350px;">';
  tr += '<div class="row">';
  tr += '<div class="col-xs-7">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
  tr += '<option value="">{{Aucune}}</option>';
  tr += '</select>';
  tr += '</div>';
  tr += '<div class="col-xs-5">';
  tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
  tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
  tr += '</div>';
  tr += '</div>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="type" style="display:none;" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td style="min-width:120px;width:140px;">';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></div>';
  tr += '</td>';
  tr += '<td style="min-width:180px;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
  tr += '</td>';
  tr += '<td >';
  tr += '<input style="min-width:30px;width:50px;" class="cmdAttr" data-l1key="order" placeholder="{{Ordre affichage}}"/> ';
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="display:none"></i>';
  tr += '</td>';
  tr += '</tr>';

  $('#table_cmd tbody.cmd_' + _cmd.type).append(tr);
  var tr = $('#table_cmd tbody.cmd_' + _cmd.type + ' tr').last();

  jeedom.eqLogic.builSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: { type: 'info' },
    error: function (error) {
      $('#div_alert').showAlert({ message: error.message, level: 'danger' });
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result);
      tr.setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType(tr, init(_cmd.subType));
    }
  });
}

// ------------- END COMMAND TAB


/**
 * *************
 * DOCUMENT READY
 * *************
 */

$(document).ready(
  function () {
    if ($(".displayJCWarning").length != 0 && (typeof JCwarningAlreadyDisplayed === 'undefined')) {
      displayJCWarning();
    }
  }
);


updateWidgetCount();