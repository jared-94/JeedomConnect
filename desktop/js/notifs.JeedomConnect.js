$("#notifsUL").sortable({
	axis: "y", cursor: "move", items: ".notifItem", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true,
	update: function (event, ui) {
		$('#notifsUL > .notifItem').each((i, el) => {
			var notifId = $(el).data('id');
			var notifToMove = notifData.notifs.find(i => i.id == notifId);
			notifToMove.index = i;
		}
		);
	}
});


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

function getIconModal(_options, _callback) {
	$("#mod_selectIcon").dialog('destroy').remove();
	if ($("#mod_selectIcon").length == 0) {
		$('body').append('<div id="mod_selectIcon"></div>');
		$("#mod_selectIcon").dialog({
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
					if (result.source == 'jc' | result.source == 'user') {
						result.shadow = $("#bw-input").is(':checked');
					}

					if ($.trim(result) != '' && 'function' == typeof (_callback)) {
						_callback(result);
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
	$("#simpleModal").dialog({
		title: _options.title, buttons: {
			"Annuler": function () {
				$(this).dialog("close");
			},
			Save: {
				text: "Valider",
				id: "saveSimple",
				click: function () {
					try {
						var result = {};
						if (_options.fields.find(i => i.type == "enable")) {
							result.enable = $("#mod-enable-input").is(':checked');
						}
						if (_options.fields.find(i => i.type == "name")) {
							if ($("#mod-name-input").val() == '') {
								throw 'La nom est obligatoire';
							}
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
	});
	$('#simpleModal').dialog('open');
	$('#simpleModal').keydown(function (e) {
		if (e.which == 13) {
			$('#saveSimple').click();
			return false;
		}
	})
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
	$("#notifModal").dialog({
		title: _options.title, buttons: {
			"Annuler": function () {
				$('#notif-alert').hideAlert();
				$(this).dialog("close");
			},
			"Valider": function () {
				try {
					var result = _options.notif || {};
					if ($("#mod-notifName-input").val() == '') {
						throw 'La commande Nom est obligatoire';
					}
					result.name = $("#mod-notifName-input").val();
					result.channel = $("#mod-channel-input").val();
					result.update = $("#update-input").is(':checked')
					result.critical = $("#critical-input").is(':checked');
					result.criticalVolume = parseFloat($("#criticalVolume-input").val());
					if ($("#mod-color-input").val() != '') {
						result.color = $("#mod-color-input").val();
					}
					else {
						result.color = undefined;
					}
					result.image = htmlToIcon($("#icon-div").children().first());
					if (result.image == {}) { delete result.image; }

					if (actionList.length > 0) {
						actionList.forEach(item => {
							item.name = $("#" + item.id + "-name-input").val();
						});
						result.actions = actionList;
					}
					else {
						result.actions = undefined;
					}

					if ('function' == typeof (_callback)) {
						_callback(result);
					}

					$('#notif-alert').hideAlert();
					$(this).dialog('close');
				}
				catch (error) {
					$('#notif-alert').showAlert({ message: error, level: 'danger' });
					console.error(error);
				}

			}
		}
	});
	$('#notifModal').dialog('open');
};
