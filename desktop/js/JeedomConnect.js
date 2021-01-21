
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


$('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').on('change', function () {
	var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
	$('#img_config').attr("src", 'plugins/JeedomConnect/data/qrcodes/'+key+'.png');

});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=deviceName]').on('change', function () {
	var device = $('.eqLogicAttr[data-l1key=configuration][data-l2key=deviceName]').html();
	if (device != '') {
		$("#removeDevice").css("display", "");
	} else {
		$("#removeDevice").css("display", "none");
	}
});

$("#assistant-btn").click(function(){
    $('#md_modal').dialog({title: "{{Configuration de l'équipement}}"});
    $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.JeedomConnect&eqLogicId='+$('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$("#notifConfig-btn").click(function(){
    $('#md_modal').dialog({title: "{{Configuration des notifications}}"});
    $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=notifs.JeedomConnect&eqLogicId='+$('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$("#export-btn").click(function() {
	var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
	var a = document.createElement("a");
	a.href = 'plugins/JeedomConnect/data/configs/'+key+'.json';
	a.download = key+'.json';
	a.click();
	a.remove();
});

$("#import-btn").click(function() {
	$("#import-input").click();
});

$("#import-input").change(function() {
	var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
	if($(this).prop('files').length > 0)
    {
        file =$(this).prop('files')[0];
		var reader = new FileReader();
		reader.onload = (function (theFile) {
			return function (e) {
				config = e.target.result;
				$.post({
					url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
					data: {'action': 'saveConfig', 'config': config, 'apiKey': key },
					success: function (r) {
						if (JSON.parse(r).state == 'error') {
							$('#div_alert').showAlert({message: "Erreur lors de l'importation", level: 'danger'});
						} else {
							$('#div_alert').showAlert({message: 'Configuration importée avec succès', level: 'success'});
						}						
					},
					error: function (error) {
						console.log(error);
						$('#div_alert').showAlert({message: "Erreur lors de l'importation", level: 'danger'});
					}
				});
			};
		})(file);
		reader.readAsText(file);
    }
});


$("#qrcode-regenerate").click(function() {
	var key = $('.eqLogicAttr[data-l1key=configuration][data-l2key=apiKey]').value();
	$.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {'action': 'generateQRcode', 'id': $('.eqLogicAttr[data-l1key=id]').value() },
            success: function () {
               $('#img_config').attr("src", 'plugins/JeedomConnect/data/qrcodes/' + key + '.png?'+ new Date().getTime());
            },
            error: function (error) {
			 console.log("error while generating qr code")
            }
    });
});

$("#removeDevice").click(function() {
	$.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {'action': 'removeDevice', 'id': $('.eqLogicAttr[data-l1key=id]').value() },
            success: function () {
			   $('.eqLogicAttr[data-l1key=configuration][data-l2key=deviceName]').html('');
            },
            error: function (error) {
			 console.log("error");
            }
    });
});


 $("#butCol").click(function(){
   $("#hidCol").toggle("slow");
   document.getElementById("listCol").classList.toggle('col-lg-12');
   document.getElementById("listCol").classList.toggle('col-lg-10');
 });

 $(".li_eqLogic").on('click', function (event) {
   if (event.ctrlKey) {
     var type = $('body').attr('data-page')
     var url = '/index.php?v=d&m='+type+'&p='+type+'&id='+$(this).attr('data-eqlogic_id')
     window.open(url).focus()
   } else {
     jeedom.eqLogic.cache.getCmd = Array();
     if ($('.eqLogicThumbnailDisplay').html() != undefined) {
       $('.eqLogicThumbnailDisplay').hide();
     }
     $('.eqLogic').hide();
     if ('function' == typeof (prePrintEqLogic)) {
       prePrintEqLogic($(this).attr('data-eqLogic_id'));
     }
     if (isset($(this).attr('data-eqLogic_type')) && isset($('.' + $(this).attr('data-eqLogic_type')))) {
       $('.' + $(this).attr('data-eqLogic_type')).show();
     } else {
       $('.eqLogic').show();
     }
     $(this).addClass('active');
     $('.nav-tabs a:not(.eqLogicAction)').first().click()
     $.showLoading()
     jeedom.eqLogic.print({
       type: isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
       id: $(this).attr('data-eqLogic_id'),
       status : 1,
       error: function (error) {
         $.hideLoading();
         $('#div_alert').showAlert({message: error.message, level: 'danger'});
       },
       success: function (data) {
         $('body .eqLogicAttr').value('');
         if(isset(data) && isset(data.timeout) && data.timeout == 0){
           data.timeout = '';
         }
         $('body').setValues(data, '.eqLogicAttr');
         if ('function' == typeof (printEqLogic)) {
           printEqLogic(data);
         }
         if ('function' == typeof (addCmdToTable)) {
           $('.cmd').remove();
           for (var i in data.cmd) {
             addCmdToTable(data.cmd[i]);
           }
         }
         $('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
           jeedom.cmd.changeType($(this).closest('.cmd'));
         });

         $('body').delegate('.cmd .cmdAttr[data-l1key=subType]', 'change', function () {
           jeedom.cmd.changeSubType($(this).closest('.cmd'));
         });
         addOrUpdateUrl('id',data.id);
         $.hideLoading();
         modifyWithoutSave = false;
         setTimeout(function(){
           modifyWithoutSave = false;
         },1000)
       }
     });
   }
   return false;
 });


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
}

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
