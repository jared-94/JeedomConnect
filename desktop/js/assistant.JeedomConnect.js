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
    $('#iconModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.iconModal.JeedomConnect');
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
				icon = icon.replace(/"/g, "'")
				var result = {};
				result.html = icon;

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
		$('#simpleModalAlert').hide();
      	$(this).dialog("close");
    },
    Save: {
			text: "Valider",
			id: "saveSimple",
			click: function() {
				$('#simpleModalAlert').hide();
				var result = {};
				if (_options.fields.find(i => i.type == "enable")) {
					result.enable = $("#mod-enable-input").is(':checked');
				}
				if (_options.fields.find(i => i.type == "name")) {
					result.name = $("#mod-name-input").val();
				}
				if (_options.fields.find(i => i.type == "icon")) {
						//result.icon = { name: $("#mod-icon-input").val().trim(), source: $("#icon-source-input").val()}
						result.icon = htmlToIcon($("#icon-div").html());
				}
				if (_options.fields.find(i => i.type == "move")) {
					result.moveToId = $("#mod-move-input").val();
				}
				if (_options.fields.find(i => i.type == "expanded")) {
					result.expanded = $("#mod-expanded-input").is(':checked');
				}
				if (_options.fields.find(i => i.type == "widget")) {
					if ( $("#mod-widget-input").val() == undefined){
						$('#simpleModalAlert').showAlert({message: 'Choix obligatoire', level: 'danger'});
						return;
					}
					result.widgetId = $("#mod-widget-input").val();
					result.widgetName = $("#mod-widget-input option:selected").text();
				}
				if (_options.fields.find(i => i.type == "object")) {
					result.object = $("#object-select  option:selected").val();
					result.name = $("#object-select  option:selected").text();
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


$('#selWidgetType').on('change', function() {
	$( '#selWidgetDetail option' ).show();
	//console.log("filter on type >>" + this.value);
	var typeSelected = this.value ;

	$( '#selWidgetDetail option' ).not( "[data-type=" + typeSelected + "]" ).hide();
	
});

