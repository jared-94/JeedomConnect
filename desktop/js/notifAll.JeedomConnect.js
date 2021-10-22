
$('#bt_saveJcWidgetNotifAll').off('click').on('click', function () {
	save();

});

function save() {
	var checkedVals = $('.notifAllOptions:checkbox:checked').map(function () {
		return this.value;
	}).get();
	console.log('list checked : ', checkedVals);

	$.post({
		url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
		data: {
			'action': 'saveNotifAll',
			'cmdList': checkedVals
		},
		success: function () {
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Configuration sauvegardée', level: 'success' });
		},
		error: function (error) {
			console.log(error);
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Erreur lors de la sauvegarde', level: 'danger' });
		}
	});

}