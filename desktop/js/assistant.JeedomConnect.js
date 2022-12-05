$("#widgetsUL").sortable({
	axis: "y", cursor: "move", items: ".widgetItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	beforeStop: function (ev, ui) {
		if ($(ui.item).hasClass('widgetGroup') && $(ui.item).parent().hasClass('widgetGroup')) {
			alert("Déplacement d'un groupe dans un groupe non autorisé !");
			$(this).sortable('cancel');
		}
	},
	update: function (event, ui) {
		$('#widgetsUL > .widgetItem').each((i, el) => {
			if ($(el).hasClass("widgetGroup")) {
				var groupId = $(el).data('id');
				var groupItem = 0;

				$(el).next().find('.widgetItem').each((i2, el2) => {
					var widgetId = $(el2).data('id');
					var widgetIndex = $(el2).data('index');
					var widgetParentId = $(el2).data('parentid');
					var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
					widgetToMove.index = groupItem;
					widgetToMove.parentId = groupId;
					groupItem++;

				});

				var groupToEdit = configData.payload.groups.find(g => g.id == groupId);
				groupToEdit.index = i;
				$(el).data('index', i);
			}
			else {
				var widgetId = $(el).data('id');
				var widgetIndex = $(el).data('index');
				var widgetParentId = $(el).data('parentid');
				var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);

				var widgetParentIdNew = $(el).parent().data('id');

				widgetToMove.index = i;
				widgetToMove.parentId = widgetParentIdNew;
				$(el).data('index', i);
				$(el).data('parentid', widgetParentIdNew);
			}

		});
		refreshWidgetsContent();

	}
});

$("#summaryUL").sortable({
	axis: "y", cursor: "move", items: ".summaryItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#summaryUL > .summaryItem').each((i, el) => {
			var summaryKey = $(el).data('id');
			var summaryToMove = configData.payload.summaries.find(summary => summary.key == summaryKey);
			summaryToMove.index = i;
		}
		);

	}
});

$("#roomUL").sortable({
	axis: "y", cursor: "move", items: ".roomItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#roomUL > .roomItem').each((i, el) => {
			var roomId = $(el).data('id');
			var roomToMove = configData.payload.rooms.find(room => room.id == roomId);
			roomToMove.index = i;
		}
		);

	}
});

$("#condImgList").sortable({
	axis: "y", cursor: "move", items: ".condImgItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#condImgList > .condImgItem').each((i, el) => {
			var itemIndex = $(el).data('id');
			var itemToMove = configData.payload.background.condBackgrounds.find(c => c.index == itemIndex);
			itemToMove.index = i;
		}
		);

	}
});

$("#batteryImgList").sortable({
	axis: "y", cursor: "move", items: ".condImgItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#batteryImgList > .condImgItem').each((i, el) => {
			var itemIndex = $(el).data('id');
			var itemToMove = configData.payload.batteries.condImages.find(c => c.index == itemIndex);
			itemToMove.index = i;
		}
		);

	}
});

$("#bottomUL").sortable({
	axis: "y", cursor: "move", items: ".bottomItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#bottomUL > .bottomItem').each((i, el) => {
			var tabId = $(el).data('id');
			var tabToMove = configData.payload.tabs.find(tab => tab.id == tabId);
			tabToMove.index = i;
		}
		);

	}
});

$("#topUL").sortable({
	axis: "y", cursor: "move", items: ".topItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#topUL > .topItem').each((i, el) => {
			var tabId = $(el).data('id');
			var tabToMove = configData.payload.sections.find(tab => tab.id == tabId);
			tabToMove.index = i;
		}
		);

	}
});


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
	} else if (tabName == "weatherTab") {
		refreshWeatherData();
	} else if (tabName == "batteryTab") {
		refreshBatteriesData('batteries');
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
	$("#mod_selectIcon").dialog('destroy').remove();
	if ($("#mod_selectIcon").length == 0) {
		$('body').append('<div id="mod_selectIcon"></div>');
		$("#mod_selectIcon").dialog({
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


		$('#mod_selectIcon').load(`index.php?v=d&plugin=JeedomConnect&modal=assistant.iconModal.JeedomConnect${params}`);
		jQuery.ajaxSetup({
			async: true
		});
	}

	$("#mod_selectIcon").dialog({
		title: _options.title, buttons: {
			"Annuler": function () {
				$(this).dialog("close");
			},
			Save: {
				text: "Valider",
				id: "saveSimple",
				click: function () {
					var icon = $('.iconSelected .iconSel').html();
					if (icon === undefined) {
						$('#div_iconSelectorAlert').showAlert({ message: 'Aucune icône sélectionnée', level: 'danger' });
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
					if (result.source == 'jc') {
						result.shadow = $("#bw-input").is(':checked');
					}
					if (result.source == 'user') {
						result.shadow = $("#bw-input").is(':checked');
						tmpSrc = $('.iconSelected .iconSel').children().first().attr("src");
						result.name = tmpSrc.replace(userImgPath, '');
					}

					if ($.trim(result) != '' && 'function' == typeof (_callback)) {
						_callback(result, _options);
					}

					$(this).dialog('close');
				}
			}
		}
	});

	$('#mod_selectIcon').dialog('open');

}

function getSimpleModal(_options, _callback) {
	if (!isset(_options)) {
		return;
	}
	$("#simpleModal").dialog('destroy').remove();

	if ($("#simpleModal").length == 0) {
		var iWidth = (_options.width === undefined) ? 430 : _options.width;
		$('body').append('<div id="simpleModal"></div>');
		$("#simpleModal").dialog({
			title: _options.title,
			closeText: '',
			autoOpen: false,
			modal: true,
			width: iWidth
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
	var genericButton = {
		"Annuler": function () {
			$('#simpleModalAlert').hide();
			$(this).dialog("close");
		},
		Save: {
			text: "Valider",
			id: "saveSimple",
			click: function () {
				try {
					$('#simpleModalAlert').hide();
					var result = {};
					if (_options.fields.find(i => i.type == "enable")) {
						result.enable = $("#mod-enable-input").is(':checked');
					}
					if (_options.fields.find(i => i.type == "room")) {
						if ($("#room-input").val() == undefined) {
							throw 'Choix obligatoire';
						}
						result.roomId = $("#room-input option:selected").val();
					}
					if (_options.fields.find(i => i.type == "checkboxes")) {
						checkedVals = $('.checkboxesSelection:checkbox:checked').map(function () {
							return this.value;
						}).get();
						result.checkboxes = checkedVals;
					}
					if (_options.fields.find(i => i.type == "radios")) {
						result.radio = $('input[name=radio]:checked').attr('id');
						result.radio_name = $('input[name=radio]:checked').parent().text();
					}
					const nameField = _options.fields.find(i => i.type == "name");
					if (nameField) {
						if (nameField.required !== false && $("#mod-name-input").val() == '') {
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
					if (_options.fields.find(i => i.type == "color")) {
						result[_options.fields.find(i => i.type == "color").id] = $("#mod-color-input").val();
					}
					if (_options.fields.find(i => i.type == "widget")) {
						let tmpId = $("#mod-widget-input").val();
						if (tmpId == undefined || tmpId == '' || tmpId == 'none') {
							throw "Aucun élément sélectionné";
						}
						result.widgetId = tmpId;
						result.widgetName = $("#mod-widget-input option:selected").text();
						result.roomName = $("#mod-widget-input option:selected").data('room') || '';
					}
					if (_options.fields.find(i => i.type == "object")) {
						result.object = $("#object-select  option:selected").val();
						result.name = $("#object-select  option:selected").text();
					}
					if (_options.fields.find(i => i.type == "visibilityCond")) {
						visibilityCondData = $('#simpleModal #visibility-cond-input').val();
						if (visibilityCondData != '') {
							getCmdIdFromHumanName({ alert: '#div_simpleModalAlert', stringData: visibilityCondData }, function (cmdResult, _params) {
								result.visibilityCond = cmdResult;
							});
						}
						else {
							result.visibilityCond = '';
						}
					}
					if (_options.fields.find(i => i.type == "advancedGrid")) {
						let choice = $("#advancedGrid-select option:selected").val();
						if (choice == 'standard') {
							result.advancedGrid = false
						} else if (choice == 'sc') {
							result.advancedGrid = true
						} else {
							result.advancedGrid = undefined
						}
					}
					if (_options.fields.find(i => i.type == "swipeUp")) {
						let choice = $("#swipeUp-select option:selected").val();
						if (choice == 'cmd') {
							result.swipeUp = { type: 'cmd', id: $("#swipeUp-cmd-input").attr('cmdId') }
						} else if (choice == 'sc') {
							result.swipeUp = { type: 'sc', id: $("#swipeUp-sc-input").attr('scId'), tags: $("#swipeUp-sc-tags-input").val() }
						}
					}
					if (_options.fields.find(i => i.type == "swipeDown")) {
						let choice = $("#swipeDown-select option:selected").val();
						if (choice == 'cmd') {
							result.swipeDown = { type: 'cmd', id: $("#swipeDown-cmd-input").attr('cmdId') }
						} else if (choice == 'sc') {
							result.swipeDown = { type: 'sc', id: $("#swipeDown-sc-input").attr('scId'), tags: $("#swipeDown-sc-tags-input").val() }
						}
					}
					if (_options.fields.find(i => i.type == "action")) {
						let choice = $("#action-select option:selected").val();
						if (choice == 'cmd') {
							result.action = { type: 'cmd', id: $("#action-cmd-input").attr('cmdId') }
						} else if (choice == 'sc') {
							result.action = { type: 'sc', id: $("#action-sc-input").attr('scId'), tags: $("#action-sc-tags-input").val() }
						}
					}
					if ($.trim(result) != '' && 'function' == typeof (_callback)) {
						_callback(result);
					}
					$(this).dialog('close');

				}
				catch (error) {
					$('#div_simpleModalAlert').showAlert({ message: error, level: 'danger' });
					console.error(error);
				}
			}
		}
	}
	$("#simpleModal").dialog({
		title: _options.title,
		buttons: (_options.buttons === undefined) ? genericButton : _options.buttons,
		closeOnEscape: false,
		open: function () {
			if (_options.hideCloseButton) $(".ui-dialog-titlebar-close").hide();
		},
		close: function () {
			if (_options.hideCloseButton) $(".ui-dialog-titlebar-close").show();
		}
	});

	$('#simpleModal').dialog('open');

	if (_options.hideActionButton) {

		$(".ui-dialog-buttonset").hide();

		var timeleft = 10 * 1000;
		countDown(
			timeleft, // milliseconds
			function (restant) { // called every step to update the visible countdown
				$('.timer').html(restant);
			},
			function () { // what to do after
				$('.timerSpan').hide();
				$(".ui-dialog-buttonset").show();
			}
		);

	}

	$('#simpleModal').keydown(function (e) {
		if (e.which == 13) {
			$('#saveSimple').click();
			return false;
		}
	})
};

function showAutoFillWidgetCmds() {
	$('.autoFillWidgetCmds').hide();
	var type = $("#widgetsList-select").val();
	var widget = widgetsList.widgets.find(i => i.type == type);
	if (widget == undefined) return;

	if (widget.options.filter(o => o.hasOwnProperty('generic_type')).length > 0) {
		$('.autoFillWidgetCmds').show();
	}
}

$('.btnSearchForJCeq').off('click').on('click', function () {

	var widget = widgetsList.widgets.find(i => i.type == 'jc');
	if (widget == undefined) return;

	allJCEquipmentsWithEqId = allJCEquipments.map(item => {
		return {
			id: item.eqId,
			name: item.name
		};
	});
	getSimpleModal({ title: "Importer quel équipement", fields: [{ title: "Choix", type: "radios", choices: allJCEquipmentsWithEqId }] }, function (result) {
		$("#name-input").val(result.radio_name);

		jeedom.eqLogic.getCmd({
			id: result.radio,
			success: function (result) {

				Object.entries(result).forEach(([, cmdItem], index) => {
					widget.options.forEach(option => {
						if (option.category == "cmd" && option.id == cmdItem.logicalId) {
							refreshCmdData(option.id, cmdItem.id, 'undefined');
						}
					});
				});
			}
		})
	});
});



$('.btnAutoFillWidgetCmds').off('click').on('click', function () {
	var type = $("#widgetsList-select").val();
	var widget = widgetsList.widgets.find(i => i.type == type);
	if (widget == undefined) return;

	var obj = $('#room-input option:selected').val();
	obj = (obj == 'none') ? '' : obj;

	jeedom.eqLogic.getSelectModal({
		object: {
			id: obj
		}
	}, function (result) {
		$.post({
			url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
			data: {
				action: 'getCmdsForWidgetType',
				widget_type: widget.type,
				eqLogic_Id: result.id
			},
			cache: false,
			dataType: 'json',
			async: false,
			success: function (data) {
				if (data.state != 'ok') {
					$('#widget-alert').showAlert({
						message: data.result,
						level: 'danger'
					});
				}
				else {
					if (Object.entries(data.result).length == 0) {
						// HACK remove link to genType config when not needed anymore
						$('#widget-alert').showAlert({
							message: "Aucun type générique correspondant au widget définit sur les commandes de l'équipement choisi. <a onclick='gotoGenTypeConfig()'><i class='fas fa-external-link-alt'></i> {{Configurer vos types génériques}}</a>",
							level: 'warning'
						});
					}
					else {

						Object.entries(data.result).forEach(eqLogic => {
							//room
							$("#room-input > option").each(function () {
								if ($(this).val() == eqLogic[1].room) {
									$(this).prop('selected', true);
									return;
								}
							});

							//name
							$("#name-input").val(eqLogic[1].name);

							widget.options.filter(o => o.hasOwnProperty('generic_type')).forEach(option => {
								if (option.category == "cmd") {
									var cmd = eqLogic[1][option.id] || undefined;
									if (cmd != undefined) {
										refreshCmdData(option.id, cmd.id, 'undefined');
									}
								} else if (option.category == "cmdList") {
									cmdCat = [];
									var maxIndex = -1;
									eqLogic[1].modes.filter(c => c.generic_type == option.generic_type).forEach(c => {
										cmdCat.push({ id: c.id, name: c.name, index: ++maxIndex, subtype: c.subType });
									});
									refreshCmdListOption(JSON.stringify(option.options));
								}
							});

						});
					}
				}
			}
		});
	});
});


$('#hideExist').on('change', function () {
	hideWidgetSelect();
});

$('#selWidgetRoom').on('change', function () {
	hideWidgetSelect();
});

$('#selWidgetType').on('change', function () {
	hideWidgetSelect();
});

function hideWidgetSelect() {
	$('#selWidgetDetail option').show();

	// hide 'type widget'
	var typeSelected = $('#selWidgetType option:selected').val();
	if (typeSelected != 'all') {
		$('#selWidgetDetail option').not("[data-type='" + typeSelected + "']").hide();
	}


	var roomSelected = $('#selWidgetRoom option:selected').val();
	if (roomSelected != 'all') {
		$('#selWidgetDetail option').not("[data-room-name='" + roomSelected + "']").hide();
	}

	if ($('#hideExist').is(":checked")) {
		//hide exist
		$('#selWidgetDetail option[data-exist=true]').hide();
	}

	$("#selWidgetDetail").val($("#selWidgetDetail option:first").val());
}



function htmlToIcon(html) {
	let icon = {};
	icon.source = html.attr("source");
	icon.name = html.attr("name");
	if (icon.source == 'fa') {
		icon.prefix = html.attr("prefix");
	}
	if ((icon.source == 'jeedom' | icon.source == 'md' | icon.source == 'fa') & typeof (html.attr("style")) == "string") {
		icon.color = html.attr("style").split(":")[1];
	}
	if (icon.source == 'jc' | icon.source == 'user') {
		let tags = html.attr("style").split(";");
		if (tags.includes('filter:grayscale(100%)')) {
			icon.shadow = true;
		}
	}

	const params = html.attr('iconParams') ? JSON.parse(html.attr('iconParams')) : {};

	return { ...params, ...icon };
}

function iconToHtml(icon) {
	if (icon == undefined) { return ''; }
	if (typeof (icon) == "string") { //for old config structure
		if (icon.startsWith('user_files')) {
			icon = { source: 'user', name: icon.substring(icon.lastIndexOf("/") + 1) };
		} else if (icon.lastIndexOf(".") > -1) {
			icon = { source: 'jc', name: icon };
		} else {
			icon = { source: 'md', name: icon };
		}
	}
	if (icon.source == 'jeedom') {
		return `<i iconParams="${JSON.stringify(icon).replace(/"/g, '&quot;')}" source="jeedom" name="${icon.name}" ${icon.color ? 'style="color:' + icon.color + '"' : ''} class="icon ${icon.name}"></i>`;
	} else if (icon.source == 'md') {
		return `<i iconParams="${JSON.stringify(icon).replace(/"/g, '&quot;')}" source="md" name="${icon.name}" ${icon.color ? 'style="color:' + icon.color + '"' : ''} class="mdi mdi-${icon.name}"></i>`;
	} else if (icon.source == 'fa') {
		return `<i iconParams="${JSON.stringify(icon).replace(/"/g, '&quot;')}" source="fa" name="${icon.name}" prefix="${icon.prefix || 'fa'}" ${icon.color ? 'style="color:' + icon.color + '"' : ''} class="${icon.prefix || 'fa'} fa-${icon.name}"></i>`;
	} else if (icon.source == 'jc') {
		return `<img iconParams="${JSON.stringify(icon).replace(/"/g, '&quot;')}" source="jc" name="${icon.name}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="plugins/JeedomConnect/data/img/${icon.name}">`;
	} else if (icon.source == 'user') {
		return `<img iconParams="${JSON.stringify(icon).replace(/"/g, '&quot;')}" source="user" name="${icon.name.replace(userImgPath, '')}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="${userImgPath}${icon.name}">`;
	}
	return '';
}

function isIcon(icon) {
	if (icon == undefined) { return false; }
	if (typeof (icon) == "string") { return true; }
	if (typeof (icon) == "object") {
		if (icon.source != undefined & icon.name != undefined) { return true; }
	}
	return false;
}