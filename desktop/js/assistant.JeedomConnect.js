$("#widgetsUL").sortable({axis: "y", cursor: "move", items: ".widgetItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	beforeStop: function(ev, ui) {
		if ($(ui.item).hasClass('widgetGroup') && $(ui.item).parent().hasClass('widgetGroup') ) {
			alert("Déplacement d'un groupe dans un groupe non autorisé !");
			$(this).sortable('cancel');
		}
	},
	update: function( event, ui){ 
		$('#widgetsUL > .widgetItem').each((i, el) => { 
			if ( $( el ).hasClass( "widgetGroup" ) ){
				var groupId = $(el).data('id') ;
				var groupItem = 0 ; 

				$(el).next().find('.widgetItem').each((i2, el2) => { 
					var widgetId = $(el2).data('id') ;
					var widgetIndex = $(el2).data('index') ;
					var widgetParentId = $(el2).data('parentid') ;
					var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
					widgetToMove.index = groupItem ; 
					widgetToMove.parentId = groupId ; 
					groupItem ++;

				} );
				
				var groupToEdit = configData.payload.groups.find(g => g.id == groupId);
				groupToEdit.index = i ; 
				$(el).data('index', i) ;
			}
			else{
				var widgetId = $(el).data('id') ;
				var widgetIndex = $(el).data('index') ;
				var widgetParentId = $(el).data('parentid') ;
				var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
				
				var widgetParentIdNew = $(el).parent().data('id') ;
				
				widgetToMove.index = i ; 
				widgetToMove.parentId = widgetParentIdNew ; 
				$(el).data('index', i) ;
				$(el).data('parentid',widgetParentIdNew) ;
			}

		});
		refreshWidgetsContent();

	} });

$("#summaryUL").sortable({axis: "y", cursor: "move", items: ".summaryItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	update: function( event, ui){ 
			$('#summaryUL > .summaryItem').each((i, el) => { 
					var summaryKey = $(el).data('id') ;
					var summaryToMove = configData.payload.summaries.find(summary => summary.key == summaryKey);
					summaryToMove.index = i;
					}
			);

	} });

$("#roomUL").sortable({axis: "y", cursor: "move", items: ".roomItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	update: function( event, ui){ 
			$('#roomUL > .roomItem').each((i, el) => { 
					var roomId = $(el).data('id') ;
					var roomToMove = configData.payload.rooms.find(room => room.id == roomId);
					roomToMove.index = i;
					}
			);

	} });	

$("#condImgList").sortable({axis: "y", cursor: "move", items: ".condImgItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	update: function( event, ui){ 
			$('#condImgList > .condImgItem').each((i, el) => { 
					var itemIndex = $(el).data('id') ;
					var itemToMove = configData.payload.background.condImages.find(c => c.index == itemIndex);
					itemToMove.index = i;
					}
			);

	} });

$("#bottomUL").sortable({axis: "y", cursor: "move", items: ".bottomItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	update: function( event, ui){ 
			$('#bottomUL > .bottomItem').each((i, el) => { 
					var tabId = $(el).data('id') ;
					var tabToMove = configData.payload.tabs.find(tab => tab.id == tabId);
					tabToMove.index = i;
					}
			);

	} });	

$("#topUL").sortable({axis: "y", cursor: "move", items: ".topItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true, 
	update: function( event, ui){ 
			$('#topUL > .topItem').each((i, el) => { 
					var tabId = $(el).data('id') ;
					var tabToMove = configData.payload.sections.find(tab => tab.id == tabId);
					tabToMove.index = i;
					}
			);

	} });	


function openTab(evt, tabName) {
	if (tabName == "bottomTab") {
		refreshBottomTabData();
	} else if (tabName == "topTab") {
		refreshTopTabData();
	} else if (tabName == "roomTab") {
		refreshRoomData();
	} else if (tabName == "summaryTab") {
		refreshSummaryData();
	} else if (tabName == "widgetsTab") {
		refreshWidgetData();
	} else if (tabName == "backgroundTab") {
		refreshBackgroundData();
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
			height: jQuery(window).height() - 150,
      width: jQuery(window).width() - 50 < 1500 ? jQuery(window).width() - 50 : 1500
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
		        _callback(result, _options);
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
				try{
					$('#simpleModalAlert').hide();
					var result = {};
					if (_options.fields.find(i => i.type == "enable")) {
						result.enable = $("#mod-enable-input").is(':checked');
					}
					if (_options.fields.find(i => i.type == "name")) {
						if ($("#mod-name-input").val() == '') {
							throw 'Le nom est obligatoire';
						}
						result.name = $("#mod-name-input").val();
					}
					if (_options.fields.find(i => i.type == "icon")) {
						let icon = htmlToIcon($("#icon-div").children().first());
						if (icon.source == undefined) {
							throw "L'icône est obligatoire";
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
						if ( $("#mod-widget-input").val() == undefined){
							throw 'Choix obligatoire';
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
							result.swipeUp = { type: 'sc', id: $("#swipeUp-sc-input").attr('scId') , tags :$("#swipeUp-sc-tags-input").val() }
						}
					}
					if (_options.fields.find(i => i.type == "swipeDown")) {
						let choice = $("#swipeDown-select option:selected").val();
						if (choice == 'cmd') {
							result.swipeDown = { type: 'cmd', id: $("#swipeDown-cmd-input").attr('cmdId') }
						} else if (choice == 'sc') {
							result.swipeDown = { type: 'sc', id: $("#swipeDown-sc-input").attr('scId'), tags :$("#swipeDown-sc-tags-input").val() }
						}
					}
					if (_options.fields.find(i => i.type == "action")) {
						let choice = $("#action-select option:selected").val();
						if (choice == 'cmd') {
							result.action = { type: 'cmd', id: $("#action-cmd-input").attr('cmdId') }
						} else if (choice == 'sc') {
							result.action = { type: 'sc', id: $("#action-sc-input").attr('scId'), tags :$("#action-sc-tags-input").val() }
						}
					}
					if ($.trim(result) != '' && 'function' == typeof(_callback)) {
						_callback(result);
					}
					$(this).dialog('close');

				} 
				catch (error) {
					$('#div_simpleModalAlert').showAlert({message: error, level: 'danger'});
					console.error(error);
			  	}
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



function htmlToIcon(html) {
	let icon = {};
	icon.source = html.attr("source");
	icon.name = html.attr("name");
	if (icon.source == 'fa') {
		icon.prefix = html.attr("prefix");
	}
	if ((icon.source == 'jeedom' | icon.source == 'md' | icon.source == 'fa') & typeof(html.attr("style")) == "string") {
		icon.color = html.attr("style").split(":")[1];
	}
	if (icon.source == 'jc' | icon.source == 'user') {
		let tags = html.attr("style").split(";");
		if (tags.includes('filter:grayscale(100%)')) {
			icon.shadow = true;
		}
	}

	return icon;
}

function iconToHtml(icon) {
	if (icon == undefined) { return ''; }
	if (typeof(icon) == "string") { //for old config structure
		if (icon.startsWith('user_files')) {
			icon = { source: 'user', name: icon.substring(icon.lastIndexOf("/") + 1) };
		} else if (icon.lastIndexOf(".") > -1) {
			icon = { source: 'jc', name: icon };
		} else {
			icon = { source: 'md', name: icon };
		}
	}
	if (icon.source == 'jeedom') {
		return `<i source="jeedom" name="${icon.name}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="icon ${icon.name}"></i>`;
	} else if (icon.source == 'md') {
		return `<i source="md" name="${icon.name}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="mdi mdi-${icon.name}"></i>`;
	} else if (icon.source == 'fa') {
		return `<i source="fa" name="${icon.name}" prefix="${icon.prefix || 'fa'}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="${icon.prefix || 'fa'} fa-${icon.name}"></i>`;
	} else if (icon.source == 'jc') {
		return `<img source="jc" name="${icon.name}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="plugins/JeedomConnect/data/img/${icon.name}">`;
	} else if (icon.source == 'user') {
		return `<img source="user" name="${icon.name}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="${userImgPath}${icon.name}">`;
	}
	return '';
}

function isIcon(icon) {
	if (icon == undefined) { return false; }
	if (typeof(icon) == "string") { return true; }
	if (typeof(icon) == "object") {
		if (icon.source != undefined & icon.name != undefined) { return true; }
	}
	return false;
}
