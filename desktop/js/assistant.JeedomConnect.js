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
			  	result.name = $("#mod-name-input").val();
		  	}
		  	if (_options.fields.find(i => i.type == "icon")) {
					result.icon = { name: $("#mod-icon-input").val().trim(), source: $("#icon-source-input").val()}
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
  if ($("#widgetModal").length == 0) {
    $('body').append('<div id="widgetModal"></div>');

    $("#widgetModal").dialog({
	  title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 1050,
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
			result[option.id] = $("#"+option.id+"-input").val();
		} else if (option.category == "binary") {
			result[option.id] = $("#"+option.id+"-input").is(':checked');
		} else if (option.category == "stringList") {
			if ($("#"+option.id+"-input").val() == 'none' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			if ($("#"+option.id+"-input").val() != 'none') {
				if (option.id == 'subtitle') {
					result[option.id] = $("#subtitle-input-value").val();
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
				if (option.options.hasImage) {
					item.image = $("#cmdList-"+item.id+" img").first().attr("value");
					if (item.image == "") { delete item.image; }
				}
				if (option.options.hasIcon) {
					item.icon = { name: $("#"+item.id+"-icon-input").val().trim(), source: $("#"+item.id+"-icon-source-input").val()}
					if (item.icon.name == "") { delete item.icon; }
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
	      item.image = $("#imgList-"+item.index+" img").first().attr("value");
	      item.info = $("#info-"+item.index).val();
	      item.operator = $("#operator-"+item.index).val();
	      item.value = $("#"+item.index+"-value").val();
	    });
			result[option.id] = imgCat;
		}	else if (option.category == "img") {
			if ($("#"+option.id).attr("value") == '' & option.required) {
				$('#widget-alert').showAlert({message: 'La commande '+option.name+' est obligatoire', level: 'danger'});
				throw {};
			}
			if ($("#"+option.id).attr("value") != "") {
				result[option.id] = $("#"+option.id).attr("value");
			} else {
				result[option.id] = undefined;
			}
		}
	  });

	  result.type = $("#widgetsList-select").val();
	  if ($("#room-input").val() != 'none') {
		  result.room = $("#room-input").val();
	  }
	  result.enable = $("#enable-input").is(':checked');
		result.blockDetail = $("#blockDetail-input").is(':checked');

        if ('function' == typeof(_callback)) {
          _callback(result);
        }
		$('#widget-alert').hideAlert();
        $(this).dialog('close');


    }
  }});
  $('#widgetModal').dialog('open');
};
