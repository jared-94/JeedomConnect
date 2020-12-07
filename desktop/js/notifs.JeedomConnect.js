function openTab(evt, tabName) {
	if (tabName == "channelsTab") {
		//refreshBottomTabData();
	} else if (tabName == "notifsTab") {
		//refreshWidgetData();
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
  if ($("#simpleModal").length == 0) {
    $('body').append('<div id="simpleModal"></div>');
    $("#simpleModal").dialog({
	  title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 350
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
  $("#simpleModal").dialog('option', 'buttons', {
    "Annuler": function() {
      $(this).dialog("close");
    },
    "Valider": function() {
      var result = {};
	  if (_options.fields.find(i => i.type == "enable")) {
		  result.enable = $("#mod-enable-input").is(':checked');
	  }
	  if (_options.fields.find(i => i.type == "name")) {
		  result.name = $("#mod-name-input").val();
	  }
	  if (_options.fields.find(i => i.type == "icon")) {
		  result.icon = $("#mod-icon-input").val();
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

      if ($.trim(result) != '' && 'function' == typeof(_callback)) {
        _callback(result);
      }
      $(this).dialog('close');
    }
  });
  $('#simpleModal').dialog('open');
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




function getNotifModal(_options, _callback) {
  if (!isset(_options)) {
    return;
  }
  if ($("#notifModal").length == 0) {
    $('body').append('<div id="notifModal"></div>');

    $("#notifModal").dialog({
	  title: _options.title,
      closeText: '',
      autoOpen: false,
      modal: true,
      width: 850,
	  	height: 450
    });
    jQuery.ajaxSetup({
      async: false
    });
    $('#notifModal').load('index.php?v=d&plugin=JeedomConnect&modal=notifs.notifModal.JeedomConnect');
    jQuery.ajaxSetup({
      async: true
    });
  }
  setNotifModalData(_options);
  $("#notifModal").dialog('option', 'buttons', {
    "Annuler": function() {
	  $('#notif-alert').hideAlert();
      $(this).dialog("close");
    },
    "Valider": function() {
      var result = _options.notif || {};
			if ($("#mod-notifName-input").val() == '') {
				$('#notif-alert').showAlert({message: 'La commande Nom est obligatoire', level: 'danger'});
				throw {};
			}
	  	result.name = $("#mod-notifName-input").val();
			result.channel = $("#mod-channel-input").val();
			if ($("#mod-color-input").val() != '') {
				result.color = $("#mod-color-input").val();
			} else {
				result.color = undefined;
			}
			if ($("#mod-img-img").attr("value") != '') {
				result.image = $("#mod-img-img").attr("value");
			} else {
				result.image = undefined;
			}
			if (actionList.length > 0) {
				actionList.forEach(item => {
		      item.name = $("#"+item.id+"-name-input").val();
		    });
				result.actions = actionList;
			} else {
				result.actions = undefined;
			}


      if ('function' == typeof(_callback)) {
          _callback(result);
      }
			$('#notif-alert').hideAlert();
      $(this).dialog('close');


    }
  });
  $('#notifModal').dialog('open');
};
