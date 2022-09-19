$("#notifAllModal").on('change', '#notifAllSelect', function () {
	$('.notifAllOptions').prop("checked", false);

	let cmdStr = $(this).find('option:selected').attr('data-cmd');
	if (cmdStr === undefined) return;

	let cmdList = cmdStr.split(',');
	// console.log('cmd list =>', cmdList);
	cmdList.forEach(item => {
		$('.notifAllOptions[value=' + item + ']').prop("checked", true);
	})

})

$('#bt_addJcNotifAll').off('click').on('click', function () {
	$('#alert_JcWidgetNotifAll').hideAlert();
	bootbox.prompt("Nom de la nouvelle commande ?", function (result) {
		let inputName = $.trim(result);
		if (inputName == '') {
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Le nom doit être renseigné', level: 'danger' });
			return;
		}

		if ($('#notifAllSelect option[data-text="' + inputName.toLowerCase() + '"]').length > 0) {
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Ce nom existe déjà', level: 'danger' });
			return;
		}

		let optionVal = 'notifAll_' + makeid();
		$('#notifAllSelect').append('<option value="' + optionVal + '" data-text="' + inputName.toLowerCase() + '">' + inputName + '</option>');
		$('#notifAllSelect option[value=' + optionVal + ']').prop("selected", true).trigger('change');

	});
});


$('#bt_saveJcNotifAll').off('click').on('click', function () {
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
			'key': $('#notifAllSelect').find('option:selected').val(),
			'name': $('#notifAllSelect').find('option:selected').text(),
			'cmdList': checkedVals
		},
		success: function () {
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Configuration sauvegardée', level: 'success' });
			$('#notifAllSelect').find('option:selected').attr('data-cmd', checkedVals.join(','));
		},
		error: function (error) {
			console.log(error);
			$('#alert_JcWidgetNotifAll').showAlert({ message: 'Erreur lors de la sauvegarde', level: 'danger' });
		}
	});

}


$(document).ready(
	function () {
		$('#notifAllSelect option[value=notifAll]').prop("selected", true).trigger('change');;
	}
);