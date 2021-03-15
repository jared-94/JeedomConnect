function openTab(evt, tabName) {
	if (tabName == "bottomTab") {
		refreshBottomTabData();
	} else if (tabName == "topTab") {
		refreshTopTabData();
	} else if (tabName == "roomTab") {
		refreshRoomData();
	} else if (tabName == "widgetsTab") {
		refreshWidgetData();
	}
	var i, tabcontent, tablinks;
	tabcontent = document.getElementsByClassName("tabcontent");
	for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].style.display = "none";
	}
	tablinks = document.getElementsByClassName("tablinks");
	for (i = 0; i < tablinks.length; i++) {
		tablinks[i].className = tablinks[i].className.replace(" active", "");
	}
	document.getElementById(tabName).style.display = "block";
	evt.currentTarget.className += " active";
}

function getIconModal(_options, _callback) {
	$("#iconModal").dialog('destroy').remove();
	if ($("#iconModal").length == 0) {
    $('body').append('<div id="iconModal"></div>');
    $("#iconModal").dialog({
	  	title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
			height: (jQuery(window).height() - 150),
      width: 1500
    });
		jQuery.ajaxSetup({
      async: false
    });
		let params = `&withIcon=${_options.withIcon}&withImg=${_options.withImg}`;
		if (_options.icon.source) {
			params += `&source=${_options.icon.source}`;
		}
		if (_options.icon.name) {
			params += `&name=${encodeURI(_options.icon.name)}`;
		}
		if (_options.icon.color) {
			params += `&color=${_options.icon.color.substring(1)}`;
		}
		if (_options.icon.shadow) {
			params += `&shadow=${_options.icon.shadow}`;
		}

    $('#iconModal').load(`index.php?v=d&plugin=JeedomConnect&modal=assistant.iconModal.JeedomConnect${params}`);
    jQuery.ajaxSetup({
      async: true
    });
	}

	$("#iconModal").dialog({title: _options.title, buttons: {
    "Annuler": function() {
      $(this).dialog("close");
    },
    Save: {
			text: "Valider",
			id: "saveSimple",
			click: function() {
				var icon = $('.iconSelected .iconSel').html();
				if (icon === undefined) {
					$('#div_iconSelectorAlert').showAlert({message: 'Aucune icône sélectionnée', level: 'danger'});
					return;
				}

				let result = {};
				result.source = $('.iconSelected .iconSel').children().first().attr("source");
				result.name = $('.iconSelected .iconSel').children().first().attr("name");
				if (result.source == 'fa') {
					result.prefix = $('.iconSelected .iconSel').children().first().attr("prefix");
				}
				if ((result.source == 'jeedom' | result.source == 'md' | result.source == 'fa') & $("#mod-color-input").val() != '') {
					result.color = $("#mod-color-input").val();
				}
				if (result.source == 'jc' | result.source == 'user') {
					result.shadow = $("#bw-input").is(':checked');
				}

				if ($.trim(result) != '' && 'function' == typeof(_callback)) {
		        _callback(result);
		    }

				$(this).dialog('close');
			}
		}
	}});

	$('#iconModal').dialog('open');

}

function getSimpleModal(_options, _callback) {
  if (!isset(_options)) {
    return;
  }
	$("#simpleModal").dialog('destroy').remove();
  if ($("#simpleModal").length == 0) {
    $('body').append('<div id="simpleModal"></div>');
    $("#simpleModal").dialog({
	  	title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 430
    });

    jQuery.ajaxSetup({
      async: false
    });
    $('#simpleModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.simpleModal.JeedomConnect');
    jQuery.ajaxSetup({
      async: true
    });
  }
  setSimpleModalData(_options.fields);
  $("#simpleModal").dialog({title: _options.title, buttons: {
    "Annuler": function() {
      $(this).dialog("close");
    },
    Save: {
			text: "Valider",
			id: "saveSimple",
			click: function() {
	      var result = {};
		  	if (_options.fields.find(i => i.type == "enable")) {
			  	result.enable = $("#mod-enable-input").is(':checked');
		  	}
		  	if (_options.fields.find(i => i.type == "name")) {
					if ($("#mod-name-input").val() == '') {
						$('#div_simpleModalAlert').showAlert({message: 'Le nom est obligatoire', level: 'danger'});
						throw {};
					}
			  	result.name = $("#mod-name-input").val();
		  	}
		  	if (_options.fields.find(i => i.type == "icon")) {
					let icon = htmlToIcon($("#icon-div").children().first());
					if (icon.source == undefined) {
						$('#div_simpleModalAlert').showAlert({message: "L'icône est obligatoire", level: 'danger'});
						throw {};
					}
					result.icon = htmlToIcon($("#icon-div").children().first());
		  	}
		  	if (_options.fields.find(i => i.type == "move")) {
			  	result.moveToId = $("#mod-move-input").val();
		  	}
		  	if (_options.fields.find(i => i.type == "expanded")) {
			  	result.expanded = $("#mod-expanded-input").is(':checked');
		  	}
		  	if (_options.fields.find(i => i.type == "widget")) {
			  	result.widgetId = $("#mod-widget-input").val();
			  	result.widgetName = $("#mod-widget-input option:selected").text();
		  	}
				if (_options.fields.find(i => i.type == "object")) {
			  	result.object = $("#object-select  option:selected").val();
		  	}
				if (_options.fields.find(i => i.type == "swipeUp")) {
					let choice = $("#swipeUp-select option:selected").val();
					if (choice == 'cmd') {
						result.swipeUp = { type: 'cmd', id: $("#swipeUp-cmd-input").attr('cmdId') }
					} else if (choice == 'sc') {
						result.swipeUp = { type: 'sc', id: $("#swipeUp-sc-input").attr('scId') }
					}
		  	}
				if (_options.fields.find(i => i.type == "swipeDown")) {
					let choice = $("#swipeDown-select option:selected").val();
					if (choice == 'cmd') {
						result.swipeDown = { type: 'cmd', id: $("#swipeDown-cmd-input").attr('cmdId') }
					} else if (choice == 'sc') {
						result.swipeDown = { type: 'sc', id: $("#swipeDown-sc-input").attr('scId') }
					}
		  	}
	    if ($.trim(result) != '' && 'function' == typeof(_callback)) {
	        _callback(result);
	    }
	    $(this).dialog('close');

	   }
		} }});

  $('#simpleModal').dialog('open');
	$('#simpleModal').keydown(function(e) { if (e.which == 13) {
		$('#saveSimple').click();
		return false;
	}})
};


function getImageModal(_options, _callback) {
  if (!isset(_options)) {
    return;
  }
  if ($("#imageModal").length == 0) {
    $('body').append('<div id="imageModal"></div>');
    $("#imageModal").dialog({
	  title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 450
    });
    jQuery.ajaxSetup({
      async: false
    });
    $('#imageModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.imageModal.JeedomConnect');
    jQuery.ajaxSetup({
      async: true
    });
  }
  setImageModalData(_options.selected);

  $("#imageModal").dialog({
	  buttons: [{
		text: "Annuler",
		click: function() {
			$(this).dialog("close");
		}
	  },
	  {
		text: "Valider",
		id: "validateImg",
		click: function() {
			var result = $(".selected").attr('id');
			if ($.trim(result) != '' && 'function' == typeof(_callback)) {
				_callback(result);
			}
			$(this).dialog('close');
		}
	  }]
	});



  $('#imageModal').dialog('open');
};




function getWidgetModal(_options, _callback) {
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
	  	height: 0.8*$(window).height()
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
  $("#widgetModal").dialog({title: _options.title, buttons: {
    "Annuler": function() {
	  $('#widget-alert').hideAlert();
      $(this).dialog("close");
    },
    "Valider": function() {
      var result = _options.widget ? _options.widget : {};

	  var widgetConfig = widgetsList.widgets.find(w => w.type == $("#widgetsList-select").val());
		let infoCmd = moreInfos.slice();
		$('input[cmdType="info"]').each((i, el) => {
			infoCmd.push({id: $("input[id="+el.id+"]").attr('cmdid'), human: el.title });
		});

	  widgetConfig.options.forEach(option => {
		if (option.category == "cmd") {
			if ($("#"+option.id+"-input").attr('cmdId') == '' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			if ($("#"+option.id+"-input").attr('cmdId') != '') {
				result[option.id] = {};
				result[option.id].id = $("#"+option.id+"-input").attr('cmdId');
				result[option.id].type = $("#"+option.id+"-input").attr('cmdType');
				result[option.id].subType = $("#"+option.id+"-input").attr('cmdSubType');
				result[option.id].minValue = $("#"+option.id+"-minInput").val() != '' ? $("#"+option.id+"-minInput").val() : undefined;
				result[option.id].maxValue = $("#"+option.id+"-maxInput").val() != '' ? $("#"+option.id+"-maxInput").val() : undefined;
				result[option.id].unit = $("#"+option.id+"-unitInput").val() != '' ? $("#"+option.id+"-unitInput").val() : undefined;
				result[option.id].invert = $("#invert-"+option.id).is(':checked') || undefined;
				result[option.id].confirm = $("#confirm-"+option.id).is(':checked') || undefined;
				result[option.id].secure = $("#secure-"+option.id).is(':checked') || undefined;
				Object.keys(result[option.id]).forEach(key => result[option.id][key] === undefined ? delete result[option.id][key] : {});
			} else {
				result[option.id] = undefined;
			}
		} else if (option.category == "scenario") {
			if ($("#"+option.id+"-input").attr('scId') == '' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			if ($("#"+option.id+"-input").attr('scId') != '') {
				result[option.id] = $("#"+option.id+"-input").attr('scId');
			}
		} else if (option.category == "string") {
			if ($("#"+option.id+"-input").val() == '' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			result[option.id] = parseString($("#"+option.id+"-input").val(), infoCmd);
		} else if (option.category == "binary") {
			result[option.id] = $("#"+option.id+"-input").is(':checked');
		} else if (option.category == "stringList") {
			if ($("#"+option.id+"-input").val() == 'none' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			if ($("#"+option.id+"-input").val() != 'none') {
				if (option.id == 'subtitle') {
					result[option.id] = parseString($("#subtitle-input-value").val(), infoCmd);
				} else {
					result[option.id] = $("#"+option.id+"-input").val();
				}
			} else {
				result[option.id] = undefined;
			}
		} else if (option.category == "widgets") {
			if (widgetsCat.length == 0 & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			result[option.id] = widgetsCat;
		} else if (option.category == "cmdList") {
			if (cmdCat.length == 0 & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			cmdCat.forEach(item => {
				if (option.options.hasImage | option.options.hasIcon) {
					item.image = htmlToIcon($("#icon-div-"+item.id).children().first());
					if (item.image == {}) { delete item.image; }
				}
				if (option.options.type == 'action') {
					item['confirm'] = $("#confirm-"+item.id).is(':checked') || undefined;
					item['secure'] = $("#secure-"+item.id).is(':checked') || undefined;
				}
			});
			result[option.id] = cmdCat;

		} else if (option.category == "ifImgs") {
			if (imgCat.length == 0 & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			imgCat.forEach(item => {
	      item.image = htmlToIcon($("#icon-div-"+item.index).children().first());
	      item.info = { id: $("#info-"+item.index+" option:selected").attr('value'), type: $("#info-"+item.index+" option:selected").attr('type') };
	      item.operator = $("#operator-"+item.index).val();
	      item.value = $("#"+item.index+"-value").val();
	    });
			result[option.id] = imgCat;
		}	else if (option.category == "img") {
			let icon = htmlToIcon($("#icon-div-"+option.id).children().first());
			if (icon.source == undefined & option.required) {
				$('#widget-alert').showAlert({message: "L'image est obligatoire", level: 'danger'});
				throw {};
			}
			result[option.id] = icon.source != undefined ? icon : undefined;

		}
	  });

	  result.type = $("#widgetsList-select").val();
	  if ($("#room-input").val() != 'none') {
		  result.room = $("#room-input").val();
	  }
	  result.enable = $("#enable-input").is(':checked');
		result.blockDetail = $("#blockDetail-input").is(':checked');
		if (moreInfos.length > 0) {
			result.moreInfos = [];
			moreInfos.forEach(info => {
				info.name = $("#"+info.id+"-name-input").val();
				result.moreInfos.push(info);
			});

		}

    if ('function' == typeof(_callback)) {
          _callback(result);
        }
		$('#widget-alert').hideAlert();
        $(this).dialog('close');


    }
  }});
  $('#widgetModal').dialog('open');
};

function parseString(string, infos) {
	let result = string;
  if (typeof(string) != "string") { return string; }
  const match = string.match(/#.*?#/g);
  if (!match) { return string; }
  match.forEach(item => {
    const info = infos.find(i => i.human == item);
    if (info) {
      result = result.replace(item, "#"+info.id+"#");
    }
  });
  return result;
}
