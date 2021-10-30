
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
var allWidgetsDetail;
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
        allWidgetsDetail = data.result
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


$('.eqLogicThumbnailContainer').off('click', '.widgetDisplayCard').on('click', '.widgetDisplayCard', function () {

  var eqId = $(this).attr('data-widget_id');
  editWidgetModal(eqId, true, true, true);

})


function editWidgetModal(widgetId, removeAction, exit, duplicate) {
  var widgetToEdit = allWidgetsDetail.find(w => w.id == widgetId);
  getWidgetModal({ title: "Editer un widget", eqId: widgetId, widget: widgetToEdit, removeAction: removeAction, exit: exit, duplicate: duplicate }, function (result) {
    refreshWidgetDetails();
    if (!exit) refreshWidgetsContent();
  });

}


function getWidgetModal(_options, _callback) {
  // console.log("getWidgetModal option recues : ", _options)
  if (!isset(_options)) {
    return;
  }
  $("#widgetModal").dialog('destroy').remove();
  if ($("#widgetModal").length == 0) {
    $('body').append('<div id="widgetModal"></div>');

    $("#widgetModal").dialog({
      title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 1250,
      height: 0.8 * $(window).height()
    });
    jQuery.ajaxSetup({
      async: false
    });
    $('#widgetModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.widgetModal.JeedomConnect');
    jQuery.ajaxSetup({
      async: true
    });
  }
  setWidgetModalData(_options);

  if (_options.removeAction != true) {
    $('.widgetMenu .removeWidget').hide();
    $('.widgetMenu .hideWidget').addClass('roundedRight');
  }

  if ($('#widgetOptions').attr('widget-id') == undefined || $('#widgetOptions').attr('widget-id') == '' || !(_options.duplicate)) {
    $('.widgetMenu .duplicateWidget').hide();
    $('.widgetMenu .saveWidget').addClass('roundedLeft');
  }
  else {
    $('.widgetMenu .duplicateWidget').show();
    $('.widgetMenu .saveWidget').removeClass('roundedLeft');
  }

  if (_options.exit == true) {
    $('.widgetMenu .saveWidget').attr('exit-attr', 'true');
  }

  $("#widgetModal").dialog({ title: _options.title });
  $('#widgetModal').dialog('open');

};




/********************************************** */
/********************************************** */
/********************************************** */


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
  $('body').append('<div id="widgetBulkModal"></div>');
  $('#widgetBulkModal').dialog({
    title: "{{Ajout de widgets en masse}}",
    width: 0.95 * $(window).width(),
    height: 0.8 * $(window).height(),
    closeOnEscape: false
  });
  $('#widgetBulkModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.widgetBulkModal.JeedomConnect').dialog('open');
})


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


$('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').on('change', function () {
  var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
  $('#img_config').attr("src", 'plugins/JeedomConnect/data/qrcodes/' + key + '.png');

});

$("#assistant-btn").click(function () {
  $('#md_modal').dialog({ title: "{{Configuration de l'équipement}}" });
  $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.JeedomConnect&eqLogicId=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$("#notifConfig-btn").click(function () {
  $('#md_modal').dialog({ title: "{{Configuration des notifications}}" });
  $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=notifs.JeedomConnect&eqLogicId=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$('.jeedomConnect').off('click', '#export-btn').on('click', '#export-btn', function () {
  var dt = new Date();
  var dd = String(dt.getDate()).padStart(2, '0');
  var mm = String(dt.getMonth() + 1).padStart(2, '0'); //January is 0!
  var yyyy = dt.getFullYear();

  today = yyyy + mm + dd;
  var time = dt.getHours() + '' + dt.getMinutes() + '' + dt.getSeconds() + '';

  var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
  var a = document.createElement("a");
  //a.href = 'plugins/JeedomConnect/data/configs/' + key + '.json';
  a.href = '/core/php/downloadFile.php?apikey=' + userHash + '&pathfile=/var/www/html/plugins/JeedomConnect/data/configs/' + key + '.json';
  a.download = key + '_' + today + '_' + time + '.json';
  a.click();
  a.remove();
});

$('.jeedomConnect').off('click', '#exportAll-btn').on('click', '#exportAll-btn', function () {
  var apiKey = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();

  var dt = new Date();
  var dd = String(dt.getDate()).padStart(2, '0');
  var mm = String(dt.getMonth() + 1).padStart(2, '0'); //January is 0!
  var yyyy = dt.getFullYear();

  today = yyyy + mm + dd;
  var time = dt.getHours() + '' + dt.getMinutes() + '' + dt.getSeconds() + '';

  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'getConfig',
      'apiKey': apiKey,
      all: true
    },
    dataType: 'json',
    success: function (data) {
      //console.log("roomList ajax received : ", data) ;
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        var a = document.createElement("a");
        //a.href = 'plugins/JeedomConnect/data/configs/' + apiKey + '.json.generated';
        a.href = '/core/php/downloadFile.php?apikey=' + userHash + '&pathfile=/var/www/html/plugins/JeedomConnect/data/configs/' + apiKey + '.json.generated';
        a.download = apiKey + '_' + today + '_' + time + '_GENERATED.json';
        a.click();
        a.remove();
      }
    }
  });


});

$('.jeedomConnect').off('click', '#copy-btn').on('click', '#copy-btn', function () {
  var from = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').text();

  allJCEquipmentsWithoutCurrent = allJCEquipments.filter(function (obj) {
    return obj.id !== from;
  });
  getSimpleModal({ title: "Recopier vers quel(s) appareil(s)", fields: [{ title: "Choix", type: "checkboxes", choices: allJCEquipmentsWithoutCurrent }] }, function (result) {

    $.post({
      url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
      data: {
        'action': 'copyConfig',
        'from': from,
        'to': result.checkboxes
      },
      dataType: 'json',
      success: function (data) {
        console.log("copyConfig ajax received : ", data);
        if (data.state != 'ok') {
          $('#div_alert').showAlert({
            message: data.result,
            level: 'danger'
          });
        }
        else {
          $('#div_alert').showAlert({
            message: "C'est fait !",
            level: 'success'
          });
        }
      }
    });
  });
});

$("#import-btn").click(function () {
  $("#import-input").click();
});

$("#import-input").change(function () {
  var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
  if ($(this).prop('files').length > 0) {
    file = $(this).prop('files')[0];
    var reader = new FileReader();
    reader.onload = (function (theFile) {
      return function (e) {
        config = e.target.result;
        $.post({
          url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
          data: { 'action': 'saveConfig', 'config': config, 'apiKey': key },
          success: function (r) {
            if (JSON.parse(r).state == 'error') {
              $('#div_alert').showAlert({ message: "Erreur lors de l'importation", level: 'danger' });
            } else {
              $('#div_alert').showAlert({ message: 'Configuration importée avec succès', level: 'success' });
            }
          },
          error: function (error) {
            console.log(error);
            $('#div_alert').showAlert({ message: "Erreur lors de l'importation", level: 'danger' });
          }
        });
      };
    })(file);
    reader.readAsText(file);
    $(this).prop("value", "");
  }
});


$("#qrcode-regenerate").click(function () {
  var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'generateQRcode',
      'id': $('.eqLogicAttr[data-l1key=id]').value()
    },
    success: function () {
      $('#img_config').attr("src", 'plugins/JeedomConnect/data/qrcodes/' + key + '.png?' + new Date().getTime());
    },
    error: function (error) {
      console.log("error while generating qr code ", error)
    }
  });
});

$("#removeDevice").click(function () {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'removeDevice',
      'id': $('.eqLogicAttr[data-l1key=id]').value()
    },
    success: function () {
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=deviceName]').html('');
    },
    error: function (error) {
      console.log("error to remove device ", error);
    }
  });
});


$("#butCol").click(function () {
  $("#hidCol").toggle("slow");
  document.getElementById("listCol").classList.toggle('col-lg-12');
  document.getElementById("listCol").classList.toggle('col-lg-10');
});


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

$('#bt_resetSearchWidget').on('click', function () {
  $('#in_searchWidget').val('').keyup()
  updateWidgetCount();
})


$("#commandtab").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true });

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
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td style="min-width:120px;width:140px;">';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></div> ';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></div> ';
  tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></div>';
  tr += '</td>';
  tr += '<td style="min-width:180px;">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="display:none"></i>';
  tr += '</td>';
  tr += '</tr>';
  $('#table_cmd tbody').append(tr);
  var tr = $('#table_cmd tbody tr').last();
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


/*
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}};
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  if (init(_cmd.type) == 'info') {
    var disabled = (init(_cmd.configuration.virtualAction) == '1') ? 'disabled' : '';
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de l\'info}}"></td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="subType" value="' + init(_cmd.subType) + '" disabled style="margin-bottom : 5px;" />';
    tr += '</td><td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    if (_cmd.subType == "binary") {
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="display" data-l2key="invertBinary" />{{Inverser}}</label></span>';
    }
    if (_cmd.subType == "numeric") {
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 40%;display : inline-block;"> ';
        tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 40%;display : inline-block;">';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    /*if (isset(_cmd.type)) {
    $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
  }
  jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));*/
/*}

if (init(_cmd.type) == 'action') {
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<div class="row">';
  tr += '<div class="col-lg-6">';
  tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icone</a>';
  tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
  tr += '</div>';
  tr += '<div class="col-lg-6">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
  tr += '</div>';
  tr += '</div>';
  tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="width: 180px;display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
  tr += '<option value="">Aucune</option>';
  tr += '</select>';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
  tr += '<input class="cmdAttr form-control type input-sm" data-l1key="subType" value="' + init(_cmd.subType) + '" disabled style="margin-bottom : 5px;" />';
  tr += '</td>';
  tr += '<td>';
  tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
  tr += '</td>';
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
  tr += '</tr>';

  $('#table_cmd tbody').append(tr);
  //$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
  var tr = $('#table_cmd tbody tr:last');
  jeedom.eqLogic.builSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: {type: 'info'},
    error: function (error) {
      $('#div_alert').showAlert({message: error.message, level: 'danger'});
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result);
      tr.setValues(_cmd, '.cmdAttr');
      jeedom.cmd.changeType(tr, init(_cmd.subType));
    }
  });

}
}
*/



////////////////////////////////////////////////////////////
///////////////  ACTION SUR FORM WIDGET ////////////////////
////////////////////////////////////////////////////////////

//used for widgets option
var widgetsCat = [];
//used for cmdList
var cmdCat = [];
//used for img list
var imgCat = [];
var widgetsList = (function () {
  var json = null;
  $.ajax({
    'async': false,
    'global': false,
    'cache': false,
    'url': "plugins/JeedomConnect/core/config/widgetsConfig.json",
    'dataType': "json",
    'success': function (data) {
      data.widgets.sort(function (a, b) {
        return a.name.localeCompare(b.name);
      });
      json = data;
    }
  });
  return json;
})();

//used for moreInfos
var moreInfos = [];

var roomList;
var roomListOptions;
getRoomList()

function getRoomList() {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      'action': 'getJeedomObject'
    },
    cache: false,
    dataType: 'json',
    success: function (data) {
      //console.log("roomList ajax received : ", data) ;
      if (data.state != 'ok') {
        $('#div_alert').showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        roomList = data.result.details;
        roomListOptions = data.result.options;
        // console.log("roomList  : ", roomList);
        // console.log("roomListOptions  : ", roomListOptions);
      }
    }
  });
}


items = [];
widgetsList.widgets.forEach(item => {
  items.push('<option value="' + item.type + '">' + item.name + '</option>');
});
$("#widgetsList-select").html(items.join(""));
$("#room-input").html(roomListOptions);


function setWidgetModalData(options) {
  $('#summaryModal').dialog('destroy').remove();

  refreshAddWidgets();

  if (options.widget !== undefined) {
    $('#widgetsList-select option[value="' + options.widget.type + '"]').prop('selected', true);
    refreshAddWidgets();
    //Enable
    var enable = options.widget.enable ? "checked" : "";
    $("#enable-input").prop('checked', enable);
    var blockDetail = options.widget.blockDetail ? "checked" : "";
    $("#blockDetail-input").prop('checked', blockDetail);

    //Room
    if (options.widget.room !== undefined) {
      $('#room-input option[value="' + options.widget.room + '"]').prop('selected', true);
    }

    moreInfos = options.widget.moreInfos || [];
    refreshMoreInfos();

    $("#widgetOptions").attr('widget-id', options.eqId ?? '');

    var widgetConfig = widgetsList.widgets.find(i => i.type == options.widget.type);
    //  console.log("widgetsList => ", widgetsList) ;
    //  console.log("widgetConfig => ", widgetConfig) ;
    widgetConfig.options.forEach(option => {
      if (option.category == "string" & options.widget[option.id] !== undefined) {
        $("#" + option.id + "-input").val(options.widget[option.id]);
      } else if (option.category == "binary" & options.widget[option.id] !== undefined) {
        $("#" + option.id + "-input").prop('checked', options.widget[option.id] ? "checked" : "");
      } else if (option.category == "color" & options.widget[option.id] !== undefined) {
        $("#" + option.id + "-input").val(options.widget[option.id]);
        $("#" + option.id + "-picker").val(options.widget[option.id]);
      } else if (option.category == "cmd" & options.widget[option.id] !== undefined) {
        $("#" + option.id + "-input").attr('cmdId', options.widget[option.id].id);
        getHumanName({
          id: options.widget[option.id].id,
          error: function (error) { },
          success: function (data) {
            $("#" + option.id + "-input").val(data);
            $("#" + option.id + "-input").attr('title', data);
            refreshImgListOption();
            refreshInfoSelect();
          }
        });
        $("#" + option.id + "-input").attr('cmdType', options.widget[option.id].type);
        $("#" + option.id + "-input").attr('cmdSubType', options.widget[option.id].subType);
        if (options.widget[option.id].type == 'action') {
          $("#confirm-div-" + option.id).css('display', '');
          $("#secure-div-" + option.id).css('display', '');
          $("#pwd-div-" + option.id).css('display', '');
          $("#confirm-" + option.id).prop('checked', options.widget[option.id].confirm ? "checked" : "");
          $("#secure-" + option.id).prop('checked', options.widget[option.id].secure ? "checked" : "");
          $("#pwd-" + option.id).prop('checked', options.widget[option.id].pwd ? "checked" : "");
        } else {
          $("#confirm-div-" + option.id).css('display', 'none');
          $("#secure-div-" + option.id).css('display', 'none');
          $("#pwd-div-" + option.id).css('display', 'none');
        }
        if (options.widget[option.id].subType == 'slider' | options.widget[option.id].subType == 'numeric') {
          $("#" + option.id + "-minInput").css('display', '');
          $("#" + option.id + "-maxInput").css('display', '');
          $("#" + option.id + "-minInput").val(options.widget[option.id].minValue);
          $("#" + option.id + "-maxInput").val(options.widget[option.id].maxValue);
        } else {
          $("#" + option.id + "-minInput").css('display', 'none');
          $("#" + option.id + "-maxInput").css('display', 'none');
        }
        if (options.widget[option.id].subType == 'binary' | options.widget[option.id].subType == 'numeric') {
          $("#invert-div-" + option.id).css('display', '');
          $("#invert-" + option.id).prop('checked', options.widget[option.id].invert ? "checked" : "");
        } else {
          $("#invert-div-" + option.id).css('display', 'none');
        }
        if (options.widget[option.id].subType == 'numeric') {
          $("#" + option.id + "-unitInput").css('display', '');
          $("#" + option.id + "-unitInput").val(options.widget[option.id].unit);
        } else {
          $("#" + option.id + "-unitInput").css('display', 'none');
        }
        if (options.widget[option.id].subType == 'slider') {
          $("#" + option.id + "-stepInput").css('display', '');
          $("#" + option.id + "-stepInput").val(options.widget[option.id].step);
        }
        else {
          $("#" + option.id + "-stepInput").css('display', 'none');
        }

      } else if (option.category == "scenario" & options.widget[option.id] !== undefined) {
        getScenarioHumanName({
          id: options.widget[option.id],
          error: function (error) { },
          success: function (data) {
            data.forEach(sc => {
              if (sc['id'] == options.widget[option.id]) {
                $("#" + option.id + "-input").attr('scId', options.widget[option.id]);
                $("#" + option.id + "-input").val(sc['humanName']);
              }
            })
          }
        });
        $('#optionScenario').css('display', 'block');

        if (options.widget['options'] !== undefined &&
          options.widget['options']['tags'] !== undefined &&
          options.widget['options']['tags'] != '') {
          getHumanNameFromCmdId({ alert: '#widget-alert', cmdIdData: options.widget['options']['tags'] }, function (result, _params) {
            $('#tags-scenario-input').val(result);
          });
        }
      } else if (option.category == "stringList" & options.widget[option.id] !== undefined) {
        var selectedChoice = option.choices.find(s => s.id == options.widget[option.id]);
        if (selectedChoice !== undefined) {
          $('#' + option.id + '-input option[value="' + options.widget[option.id] + '"]').prop('selected', true);
          if (option.id == "subtitle") {
            $("#subtitle-input-value").val(selectedChoice.id)
          }
        } else if (option.id == "subtitle" & options.widget.subtitle !== undefined) {
          $('#subtitle-input option[value="custom"]').prop('selected', true);
          $("#subtitle-input-value").val(options.widget.subtitle)
          $("#subtitle-input-value").css('display', 'block');
          $("#subtitle-select").show();
        }
      } else if (option.category == "widgets" & options.widget[option.id] !== undefined) {
        widgetsCat = options.widget[option.id];
        refreshWidgetOption();
      } else if (option.category == "cmdList" & options.widget[option.id] !== undefined) {
        cmdCat = options.widget[option.id];
        refreshCmdListOption(JSON.stringify(option.options));
      } else if (option.category == "ifImgs" & options.widget[option.id] !== undefined) {
        imgCat = options.widget[option.id];
        refreshImgListOption();
      } else if (option.category == "choicesList") {
        option.choices.forEach(v => {
          $("#" + v.id + "-jc-checkbox").prop('checked', options.widget[v.id] ? "checked" : "");
        });
      } else if (option.category == "img" & options.widget[option.id] !== undefined) {
        $("#icon-div-" + option.id).html(iconToHtml(options.widget[option.id]));
      }
    });
  }
  refreshStrings();
  loadSortable('all');
}

function refreshAddWidgets() {
  widgetsCat = [];
  cmdCat = [];
  imgCat = [];
  moreInfos = [];
  var type = $("#widgetsList-select").val();
  var widget = widgetsList.widgets.find(i => i.type == type);
  $("#widgetImg").attr("src", "plugins/JeedomConnect/data/img/" + widget.img);

  $("#widgetDescription").html(widget.description);

  if (widget.variables) {
    let varDescr = `Variables disponibles : <ul style="padding-left: 15px;">`;
    widget.variables.forEach(v => {
      varDescr += `<li>#${v.name}# : ${v.descr}</li>`;
    });
    varDescr += '</ul>';
    $("#widgetVariables").html(varDescr);
    $("#widgetVariables").show();
  } else {
    $("#widgetVariables").hide();
  }

  var items = [];

  //Enable
  option = `<li><div class='form-group'>
  	<label class='col-xs-3 '>Actif</label>
  	<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="enable-input" checked></div></div></div></li>`;
  items.push(option);

  //Room
  option = `<li><div class='form-group'>
    <label class='col-xs-3 ${type == 'room' ? 'required' : ''}'>Pièce</label>
    <div class='col-xs-9'><div class='input-group'><select style="width:340px;" id="room-input" value=''>
    <option value="none">Sélection  d'une pièce</option>`;
  option += roomListOptions;

  if (type == 'room') {
    option += `<option value="global">Global</option>`;
  }
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
      isDisabled = isJcExpert ? '' : 'disabled';
      curOption += `<table><tr class="cmd">
            <td>
              <input class='input-sm form-control roundedLeft needRefresh' style="width:250px;" id="${option.id}-input" value='' cmdId='' cmdType='' cmdSubType='' ${isDisabled} configtype='${option.type}' configsubtype='${option.subtype}' configlink='${option.value}'>
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
                    <div style="width:50px; display:none;" id="pwd-div-${option.id}">
                    <i class='mdi mdi-numeric' title="Sécuriser avec un code"></i><input type="checkbox" style="margin-left:5px;" id="pwd-${option.id}"  ></div>
            </td>
            <td>
                <input type="number" style="width:50px; display:none;" id="${option.id}-minInput" value='' placeholder="Min">
            </td>
            <td>
                <input type="number" style="width:50px;margin-left:5px; display:none;" id="${option.id}-maxInput" value='' placeholder="Max">
            </td>
            <td>
                <input type="number" step="0.1" style="width:50px;margin-left:5px; display:none;" id="${option.id}-stepInput" value='1' placeholder="Step">
            </td>
            <td>
                <input style="width:50px; margin-left:5px; display:none;" id="${option.id}-unitInput" value='' placeholder="Unité">
            </td></tr></table>
                    `;

      curOption += "</div></div></li>";

    } else if (option.category == "string") {

      type = (option.subtype != undefined) ? option.subtype : 'text';
      curOption += `<div class='input-group'>
        <div style="display:flex"><input type="${type}" style="width:340px;" id="${option.id}-input" value=''>`;
      if (option.id == 'name') {
        curOption += `
              <div class="dropdown" id="name-select">
              <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
              <i class="fas fa-plus-square"></i> </a>
              <ul class="dropdown-menu infos-select" input="${option.id}-input">`;
        if (widget.variables) {
          widget.variables.forEach(v => {
            curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
          });
        }
        curOption += `</ul></div></div>`
      }
      curOption += `</div></div></div></li>`;
    } else if (option.category == "binary") {
      curOption += `<div class='input-group'><input type="checkbox" style="width:150px;" id="${option.id}-input"></div>
         </div></div></li>`;
    } else if (option.category == "color") {
      curOption += `<div class='input-group'><input style="width:200px;" id="${option.id}-input" >
      <input type="color" id="${option.id}-picker"  onchange="colorDefined(this, '${option.id}')">
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
        curOption += `<option value="custom">Personnalisé</option></select>`;
        curOption += `<div style="display:flex">
  					<textarea style="width:340px; margin-top:5px; display:none;" id="subtitle-input-value" value='none'></textarea>`;

        curOption += `
            <div class="dropdown" id="subtitle-select" style=" display:none;">
            <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
            <i class="fas fa-plus-square"></i> </a>
            <ul class="dropdown-menu infos-select" input="subtitle-input-value">`;
        if (widget.variables) {
          widget.variables.forEach(v => {
            curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
          });
        }
        curOption += `</ul></div></div>`
      } else {
        curOption += '</select>';
      }
      curOption += `</div></div></div></li>`;
    } else if (option.category == "img") {
      curOption += `
              <a class="btn btn-success roundedRight" onclick="imagePicker(this)"><i class="fas fa-check-square">
              </i> Choisir </a>
              <a data-id="icon-div-${option.id}" id="icon-div-${option.id}" onclick="removeImage(this)"></a>
              </div></div></li>`;


    } else if (option.category == "widgets") {
      var widgetChoices = [];
      widgetsList.widgets.forEach(item => {
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
              </i> Ajouter</a></span><div id="cmdList-option" data-cmd-options="${JSON.stringify(option.options).replace(/"/g, '&quot;')}" style='margin-left:-150px;'></div>`;
      curOption += `</div></div></li>`;
    } else if (option.category == "ifImgs") {
      curOption += `<span class="input-group-btn">
              <a class="btn btn-default roundedRight" onclick="addImgOption('widget')"><i class="fas fa-plus-square">
              </i> Ajouter</a></span><div id="imgList-option"></div>`;
      curOption += `</div></div></li>`;
    } else if (option.category == "scenario") {
      curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' scId='' disabled>
    <span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir un scenario' onclick="selectScenario('${option.id}');">
    <i class='fas fa-list-alt'></i></a></span></div>
      <div id='optionScenario' style='display:none;'>
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Tags</span>
            <input style="width:100%;" class='input-sm form-control roundedRight title' type="string" id="tags-scenario-input" value="" placeholder="Si nécessaire indiquez des tags" />
        </div>
      </div>
    </div>
    </div></li>`;
    } else if (option.category == "choicesList") {
      curOption += `<div class='input-group'>`;

      option.choices.forEach(v => {
        curOption += `<label class="checkbox-inline">
      <input type="checkbox" class="eqLogicAttr" id="${v.id}-jc-checkbox" />${v.text}
      </label>`;
      });

      curOption += `</div></div></div></li>`;
    } else {
      return;
    }

    items.push(curOption);

  });

  //More infos
  if (!["widgets-summary", "room", "favorites"].includes(widget.type)) {
    moreDiv = `<li><div class='form-group'>
      <label class='col-xs-3 '>Ajouter des infos</label>
      <div class='col-xs-9'>
      <div class="description">Permet d'ajouter des infos utilisables dans les images sous conditions</div>
      <div class='input-group'>
      <span class="input-group-btn">
        <a class="btn btn-default roundedRight" onclick="addMoreCmd()"><i class="fas fa-plus-square">
        </i> Ajouter une commande</a>
          </span>
          </div>
        <div id="moreInfos-div"></div>
      </div></div></li>`;
    items.push(moreDiv);
  }

  //Details access
  option = `<li><div class='form-group'>
    <label class='col-xs-3 '>Bloquer vue détails</label>
    <div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="blockDetail-input" ></div></div></div></li>`;
  items.push(option);

  $("#widgetOptions").html(items.join(""));
  loadSortable('all');
}


function imagePicker(elm) {
  var newElt = $(elm).nextAll("a[data-id^='icon-']:first");

  getIconModal({ title: "Choisir une icône ou une image", withIcon: "1", withImg: "1", icon: htmlToIcon(newElt.children().first()), elt: newElt }, (result, _params) => {
    $(_params.elt).html(iconToHtml(result));
  });
}


function removeImage(elm) {
  $(elm).empty();
}



function removeCmd(id) {
  $("#" + id + "-input").attr('value', '');
  $("#" + id + "-input").val('');
  $("#" + id + "-input").attr('cmdId', '');
}

function selectCmd(name, type, subtype, value) {
  var cmd = { type: type }
  if (subtype != 'undefined') {
    cmd = { type: type, subType: subtype }
  }
  jeedom.cmd.getSelectModal({ cmd: cmd }, function (result) {
    refreshCmdData(name, result.cmd.id, value);
  })
}

$("#widgetOptions").on('change', '.needRefresh', function () {
  var info = $(this).val();
  var name = $(this).attr('id').replace('-input', '');
  var configlink = $(this).attr('configlink') || 'undefined';
  refreshCmdData(name, info, configlink);

});



function refreshCmdData(name, id, value) {
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





function selectScenario(name) {
  jeedom.scenario.getSelectModal({}, function (result) {
    $("#" + name + "-input").attr('value', result.human);
    $("#" + name + "-input").val(result.human);
    $("#" + name + "-input").attr('scId', result.id);
    if ($("#name-input").val() == "") {
      getScenarioHumanName({
        id: name,
        error: function (error) { },
        success: function (data) {
          data.forEach(sc => {
            if (sc['id'] == result.id) {
              $("#name-input").val(sc.name);
            }
          })
        }
      });
      $("#name-input").val(result.name);

      previousData = $('#tags-scenario-input').val() || '';
      $('#tags-scenario-input').val(previousData);
      $('#optionScenario').css('display', 'block');
    }
  })
}

function subtitleSelected() {
  if ($("#subtitle-input").val() == 'custom') {
    $("#subtitle-input-value").show();
    $("#subtitle-select").show();
  } else {
    $("#subtitle-input-value").hide();
    $("#subtitle-select").hide();
    $("#subtitle-input-value").val($("#subtitle-input").val());
  }
}

function refreshWidgetOption() {
  curOption = "";
  widgetsCat.sort(function (s, t) {
    return s.index - t.index;
  });
  widgetsCat.forEach(item => {
    var name = getWidgetPath(item.id);
    curOption += `<div class='input-group jcWidgetListMovable' data-id="${item.id}">
          <input style="width:240px;" class='input-sm form-control roundedLeft' title="id=${item.id}" id="${item.id}-input" value='${name}' disabled>
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
  cmdCat.forEach(item => {
    isDisabled = isJcExpert ? '' : 'disabled';
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
                      <a class="btn btn-success roundedRight" onclick="imagePicker(this)"><i class="fas fa-check-square">
                      </i> Icône </a>
                      <a class="jcCmdListOptions" data-id="icon-${item.id}" data-index="${item.index}" onclick="removeImage(this)">${iconToHtml(item.image)}</a>
              </div>`;
      curOption += `</div>`;
    }



    curOption += `</div>`;
    //// ---  END right part
    curOption += `</div>`;


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


$("#widgetOptions").on('focusin', '.jcCmdListOptionsCommand', function () {
  $(this).attr('data-value-focusin', $(this).val());
})

$("#widgetOptions").on('focusout', '.jcCmdListOptionsCommand', function () {
  var previousData = $(this).attr('data-value-focusin');
  if (previousData != $(this).val()) {

    var elt = $(this);
    var currentId = elt.attr('data-cmd-id');
    var currentIndex = elt.attr('data-index');

    getCmd({
      id: elt.val(),
      error: function (error) {
        $('#widget-alert').showAlert({ message: error.result, level: 'danger' });
      },
      success: function (data) {
        $('#widget-alert').hideAlert();

        // remove options of the previous cmd
        elt.parent().siblings().each(function () {
          if ($(this).find('.jcCmdListOptions').length !== 0) {
            $(this).remove();
          }
        });

        // create options for the new cmd selected
        newItem = { id: data.result.id, subtype: data.result.subType, index: currentIndex, name: data.result.name };
        divListOption = getCmdOptions(newItem);
        elt.parent().parent().append(divListOption);

        // update cmdCat
        cmdCat.forEach(item => {
          console.log('item in CmdCat :', item);
          if (item.id == currentId && item.index == currentIndex) {
            console.log('found cmdCat !');
            item.id = data.result.id;
            item.name = data.result.humanName;
            item.subtype = data.result.subType;
          }
        })
        saveCmdList();

      }
    });

  }

  $(this).attr('data-value-focusin', '');
})


function getCmdOptions(item) {

  curOption = '';

  var d = new Date().getTime();
  var rand = Math.floor(Math.random() * 10000) * Math.floor(Math.random() * 10000);
  var timestamp = d + '' + rand;
  var customUid = 'cmd' + item.id + '____' + timestamp + '____';

  // create dynamic var
  if ('options' in item) {
    for (const [key, value] of Object.entries(item.options)) {
      eval('var option' + key.charAt(0).toUpperCase() + key.slice(1) + '= "' + value + '";');
    }
  }


  if (item.subtype == 'select') {
    var optionSelect = optionSelect || '';

    getCmdDetail({ id: item.id, item: item, optionSelect: optionSelect }, function (_result, _param) {

      var selectOptions = [];
      $.each(_result.configuration.listValue.split(';'), function (key, val) {
        var myData = val.split('|');
        if (myData.length == 1) {
          var value = text = myData[0];
        }
        else {
          var value = myData[0];
          var text = myData[1];
        }
        selectOptions.push(`<option value="${value}">${text}</option>`);
      });

      $('.jcCmdListOptions[data-uid=' + customUid + '][data-l1key=options][data-l2key=select]').html(selectOptions.join(""));

      var optionSelect = _param.optionSelect || '';

      $('.jcCmdListOptions[data-uid=' + customUid + '][data-l1key=options][data-l2key=select]').value(optionSelect);


    });

    curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Choix</span>
            <select class="form-control input-sm jcCmdListOptions roundedRight" data-l1key="options" data-l2key="select" data-uid="${customUid}" data-id="select-${item.id}" data-index="${item.index}">
            </select>
        </div>
       `;

  }


  if (item.subtype == 'message') {

    var optionTitle = optionTitle || '';
    var optionMessage = optionMessage || '';

    curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Titre</span>
            <input style="width:240px;" class='input-sm form-control roundedRight title jcCmdListOptions' type="string" data-id="title-${item.id}" data-index="${item.index}" value="${optionTitle}" />
        </div>
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Message</span>
            <textarea class="message form-control ta_autosize jcCmdListOptions" data-l1key="options" data-l2key="message" rows="1" style="resize:vertical;"  data-id="message-${item.id}" data-index="${item.index}" data-uid="${customUid}">${optionMessage}</textarea>
            <span class="input-group-addon hasBtn roundedRight">
              <button class="btn btn-default roundedRight listEquipementInfo" type="button" tooltip="Sélectionner la commande" data-cmd_id="${item.id}" data-index="${item.index}" data-uid="${customUid}" ><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').on('click', function() {
              jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
                $('.jcCmdListOptions[data-l1key=options][data-l2key=message][data-uid=${customUid}]').atCaret('insert', result.human);
              });
          });

        </script>
        </div>`;
  }

  if (item.subtype == 'slider') {
    var optionSlider = optionSlider || '';

    curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Valeur</span>
            <textarea class="message form-control ta_autosize jcCmdListOptions" data-l1key="options" data-l2key="message" rows="1" style="resize:vertical;"  data-id="slider-${item.id}" data-index="${item.index}" data-uid="${customUid}">${optionSlider}</textarea>
            <span class="input-group-addon hasBtn roundedRight">
              <button class="btn btn-default roundedRight listEquipementInfo" type="button" tooltip="Sélectionner la commande" data-cmd_id="${item.id}" data-index="${item.index}" data-uid="${customUid}"><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').on('click', function() {
              jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
                $('.jcCmdListOptions[data-l1key=options][data-l2key=message][data-uid=${customUid}]').atCaret('insert', result.human);
              });
          });
        </script>
        </div>`;
  }

  if (item.subtype == 'color') {
    var optionColor = optionColor || '';

    curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Couleur</span>
            <input type="color" class="form-control input-sm cursor colorChooser" data-uid="${customUid}" style="width: 20%; display: inline-block;" value="${optionColor}">
            <input class="jcCmdListOptions form-control input-sm" data-l1key="options" data-l2key="color"  data-uid="${customUid}" style="width: 80%; display: inline-block;" placeholder="Couleur (hexa)" data-id="color-${item.id}" data-index="${item.index}" value="${optionColor}">
            <span class="input-group-btn">
              <button class="btn btn-default listEquipementInfo roundedRight" type="button" tooltip="Sélectionner la commande" data-uid="${customUid}" data-index="${item.index}" data-cmd_id="${item.id}"><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').off('click').on('click', function () {
            var el = $(this);
            jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
              $('.jcCmdListOptions[data-uid=${customUid}][data-l1key=options][data-l2key=color]').value(result.human);
            });
          });
          $('.colorChooser[data-uid=${customUid}]').off('change').on('change', function () {
            $('.jcCmdListOptions[data-uid=${customUid}][data-l1key=options][data-l2key=color]').value($(this).value())
          });
        </script>
        </div>`;
  }

  curOption += `<div class="input-group input-group-sm" style="width: 100%">
                            <span class="input-group-addon roundedLeft" style="width: 100px">Nom</span>
                            <input style="width:240px;" class='input-sm form-control roundedRight title jcCmdListOptions' data-id="custom-name-${item.id}" data-index="${item.index}" value='${item.name || ""}' >
                        </div>`;


  return curOption;


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

/*
function upCmdOption(elm, optionsJson) {
  saveCmdList();

  var id = $(elm).data('id');
  var currentIndex = $(elm).data('index');

  var cmdToMove = cmdCat.find(i => i.id == parseInt(id) && i.index == currentIndex);
  var index = parseInt(cmdToMove.index);
  if (index == 0) {
    return;
  }
  var otherCmd = cmdCat.find(i => i.index == index - 1);
  cmdToMove.index = index - 1;
  otherCmd.index = index;
  refreshCmdListOption(optionsJson);
}

function downCmdOption(elm, optionsJson) {
  saveCmdList();

  var id = $(elm).data('id');
  var currentIndex = $(elm).data('index');

  var cmdToMove = cmdCat.find(i => i.id == parseInt(id) && i.index == currentIndex );
  var index = parseInt(cmdToMove.index);
  if (index == getMaxIndex(cmdCat)) {
    return;
  }
  var otherCmd = cmdCat.find(i => i.index == index + 1);
  cmdToMove.index = index + 1;
  otherCmd.index = index;
  refreshCmdListOption(optionsJson);
}
*/

//More Infos

function refreshMoreInfos() {
  let div = '';
  moreInfos.forEach(item => {
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
    getHumanName({
      id: item.id,
      success: function (data) {
        $("#" + item.id + "-input").val(data);
      }
    });
  });
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
  if (widget.variables) {
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
    infosOptionHtml += `<li info="${i.id}" onclick="infoSelected('${i.human}', this)">
      <a href="#">${i.human}</a></li>`;
  });
  $(".infos-select").html(infosOptionHtml);

  refreshStrings();
}

function colorDefined(c, id) {
  console.log('id', id)
  $("#" + id + "-input").val(c.value);
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

function idToHuman(string, infos) {
  let result = string;
  if (typeof (string) != "string") { return string; }
  const match = string.match(/#.*?#/g);
  if (!match) { return string; }
  match.forEach(item => {
    const info = infos.find(i => i.id == item.replace(/\#/g, ""));
    if (info && info.human != '') {
      result = result.replace(item, info.human);
    }
  });
  return result;
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

  if (widget.variables) {
    widget.variables.forEach(v => {
      options.push({ type: 'var', id: v.name, human: `#${v.name}#` })
    });
  }

  imgCat.sort(function (s, t) {
    return s.index - t.index;
  });

  imgCat.forEach(item => {
    /*
    curOption += `
    <div data-id="${item.index}" class='input-group jcImgListMovable'>
      Si
      <input style="width:385px;height:31px;margin-left:5px" class=' roundedLeft' index="${item.index}" id="cond-input-${item.index}" value="${item.condition}"
       onchange="setCondValue(this, 'imgList')" />
       <a class='btn btn-default btn-sm cursor bt_selectTrigger' style=";margin-right:10px;" tooltip='Ajouter une commande' onclick="selectInfoCmd('#cond-input-${item.index}', 'imgList');">
                    <i class='fas fa-list-alt'></i></a>
      <a class="btn btn-success roundedRight" index="${item.index}" onclick="imagePicker(this)"><i class="fas fa-plus-square">
      </i> Image </a>
      <a data-id="icon-div" id="icon-div-${item.index}" onclick="removeImage(this)">${iconToHtml(item.image)}</a>
      <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
      <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteImgOption('${item.index}');"></i>
      </div>
    `;
    */
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
    curOption += `<a class="btn btn-success roundedRight" index="${item.index}" onclick="imagePicker(this)"><i class="fas fa-plus-square">
    </i> Image </a>
    <a data-id="icon-div" id="icon-div-${item.index}" onclick="removeImage(this)">${iconToHtml(item.image)}</a>
    <i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
    <i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteImgOption('${item.index}');"></i>
    </div>` ;


  });
  $("#imgList-option").html(curOption);
  setCondToHuman('imgList');
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

/*
function upImgOption(id) {
  saveImgOption();
  var imgToMove = imgCat.find(i => i.index == parseInt(id));
  var index = parseInt(imgToMove.index);
  if (index == 0) {
    return;
  }
  var otherImg = imgCat.find(i => i.index == index - 1);
  imgToMove.index = index - 1;
  otherImg.index = index;
  refreshImgListOption();
}

function downImgOption(id) {
  saveImgOption();
  var imgToMove = imgCat.find(i => i.index == parseInt(id));
  var index = parseInt(imgToMove.index);
  if (index == getMaxIndex(imgCat)) {
    return;
  }
  var otherImg = imgCat.find(i => i.index == index + 1);
  imgToMove.index = index + 1;
  otherImg.index = index;
  refreshImgListOption();
}
*/

function addWidgetOption(choices) {
  var widgets = choices.split(".");
  getSimpleModal({ title: "Choisir un widget", fields: [{ type: "widget", choices: widgets }] }, function (result) {
    var maxIndex = getMaxIndex(widgetsCat);
    widgetsCat.push({ id: result.widgetId, index: maxIndex + 1 });
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

/*
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
*/

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


function getCmdIdFromHumanName(_params, _callback) {
  if (typeof _params.alert == 'undefined') {
    _params.alert = '#div_alert';
  }

  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      action: 'humanReadableToCmd',
      human: _params.stringData
    },
    cache: false,
    dataType: 'json',
    async: false,
    success: function (data) {
      if (data.state != 'ok') {
        $(_params.alert).showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        if ('function' == typeof (_callback)) {
          _callback(data.result, _params);
        }
      }
    }
  });
}

function getHumanNameFromCmdId(_params, _callback) {
  if (typeof _params.alert == 'undefined') {
    _params.alert = '#div_alert';
  }

  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      action: 'cmdToHumanReadable',
      strWithCmdId: _params.cmdIdData
    },
    cache: false,
    dataType: 'json',
    async: false,
    success: function (data) {
      if (data.state != 'ok') {
        $(_params.alert).showAlert({
          message: data.result,
          level: 'danger'
        });
      }
      else {
        if ('function' == typeof (_callback)) {
          _callback(data.result, _params);
        }
      }
    }
  });
}

function getCmd({ id, error, success }) {
  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: { 'action': 'getCmd', 'id': id },
    cache: false,
    success: function (cmdData) {
      jsonData = JSON.parse(cmdData);
      if (jsonData.state == 'ok') {
        success && success(jsonData);
      } else {
        error && error(jsonData);
      }
    }
  });
}

function jeedomIconToIcon(html) {
  if (html.startsWith("<i ")) {
    let tag1 = html.split("\"")[1].split(" ")[0];
    let tag2 = html.split("\"")[1].split(" ")[1];
    if (tag1 == 'icon') {
      return { source: 'jeedom', name: tag2 };
    } else if (tag1.startsWith('fa')) {
      return { source: 'fa', prefix: tag1, name: tag2.replace("fa-", "") };
    }
  }
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


function saveWidget() {
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

}

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

function hideWidget() {
  $("#widgetModal").dialog('destroy').remove();
}

function duplicateWidget() {
  $('#widget-alert').hideAlert();

  $('#widgetOptions').attr('widget-id', '');

  $('.widgetMenu .duplicateWidget').hide()
  $('.widgetMenu .removeWidget').hide()
  $('#widget-alert').showAlert({ message: 'Vous êtes sur le widget dupliqué, réalisez (ou non) vos modifications. Dans tous les cas, pensez à sauvegarder !', level: 'success' });
  // $('.widgetMenu .saveWidget').attr('exit-attr', 'true');

}

function removeWidget(itemId) {
  var warning = "<i source='md' name='alert-outline' style='color:#ff0000' class='mdi mdi-alert-outline'></i>";

  if (itemId == undefined) {
    var widgetId = $("#widgetOptions").attr('widget-id');
  }
  else {
    var widgetId = itemId;
  }

  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      action: 'getWidgetExistance',
      id: widgetId
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
        var allName = data.result.names;

        var msg = '';
        if (allName.length == 0 || allName == '' || allName == undefined) {
          msg = '(Ce widget n\'est utilisé dans aucun équipement)';
        }
        else {
          if (allName.length == 1) {
            var eq = 'de l\'équipement';
          }
          else {
            var eq = 'des équipements';
          }

          msg = warning + '  La suppression retirera ce widget ' + eq + ' suivant : ' + allName.join(', ') + '  ' + warning;
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

      }
    }
  });

}


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


// getRoomList();

function getRoomName(id) {
  if (id == 'global') { return 'Global'; }
  const room = roomList.find(r => r.id == id);
  if (room) {
    return room.name;
  } else {
    return undefined;
  }
}

function getMaxIndex(array) {
  var maxIndex = -1;
  array.forEach(item => {
    if (item.index > maxIndex) {
      maxIndex = item.index;
    }
  });
  return maxIndex;
}


function getMaxId(array, defaut = -1) {
  var maxId = defaut;
  array.forEach(item => {
    if (item.id > maxId) {
      maxId = item.id;
    }
  });
  return maxId;
}

function parseString(string, infos) {
  let result = string;
  if (typeof (string) != "string") { return string; }
  const match = string.match(/#.*?#/g);
  if (!match) { return string; }
  match.forEach(item => {
    const info = infos.find(i => i.human == item);
    if (info) {
      result = result.replace(item, "#" + info.id + "#");
    }
  });
  return result;
}


function updateOrderWidget() {

  var type = $("#widgetOrder").val();

  var vars = getUrlVars()
  var url = 'index.php?'

  url = getCustomParamUrl(url, vars);

  loadPage(url)

}


$('#widgetOrder_NOTWORKING').on('change', function () {
  var type = $("#widgetOrder").val();

  $.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: {
      action: 'orderWidget',
      orderBy: type
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
        // $('.eqLogicThumbnailContainer').hide() ;
        $('#widgetsList-div').html(data.result.widgets);
        // $('.eqLogicThumbnailContainer').show() ;
        $('.eqLogicThumbnailContainer').packery();

      }
    }
  });



});


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


$('body').off('click', '.toggle-password').on('click', '.toggle-password', function () {
  $(this).toggleClass("fa-eye fa-eye-slash");
  var input = $("#actionPwd");
  if (input.attr("type") === "password") {
    input.attr("type", "text");
  } else {
    input.attr("type", "password");
  }

});

var originalPwd = null;
$("#actionPwd").focusin(function () {
  if (originalPwd === null) {
    originalPwd = $(this).val();
  }
});


function saveEqLogic(_eqLogic) {
  if (!isset(_eqLogic.configuration)) {
    _eqLogic.configuration = {};
  }

  currentPwd = $("#actionPwd").val();
  if (originalPwd !== null && originalPwd != currentPwd) {
    _eqLogic.configuration.pwdChanged = 'true';
  }

  return _eqLogic;

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



$(document).ready(function () {
  var widgetSearch = $("#in_searchWidget").val().trim();
  if (widgetSearch != '') {
    $('#in_searchWidget').keyup()
  }

  $('.eqLogicThumbnailContainer').packery();

  updateWidgetCount()
});

$('#widgetsList-div').on('change', function () {
  updateWidgetCount()
})

function updateWidgetCount() {

  var nbVisible = $('.widgetDisplayCard:visible').length;
  var nbTotal = $('.widgetDisplayCard').length;

  var text = (nbTotal != nbVisible) ? nbVisible + "/" + nbTotal : nbTotal;

  $('#coundWidget').text("(" + text + ")");
}


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