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
		if (result == null) return; //if cancelled

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

$('#bt_editJcNotifAll').off('click').on('click', function () {
	$('#alert_JcWidgetNotifAll').hideAlert();

	let currentName = $('#notifAllSelect').find('option:selected').text();
	bootbox.prompt({
		title: "Nouveau nom pour cette commande ?",
		value: currentName,
		callback: function (result) {
			if (result == null) return; //if cancelled

			let inputName = $.trim(result);

			if (currentName == inputName) return;

			if (inputName == '') {
				$('#alert_JcWidgetNotifAll').showAlert({ message: 'Le nom doit être renseigné', level: 'danger' });
				return;
			}

			if ($('#notifAllSelect option[data-text="' + inputName.toLowerCase() + '"]').length > 0) {
				$('#alert_JcWidgetNotifAll').showAlert({ message: 'Ce nom existe déjà', level: 'danger' });
				return;
			}

			let key = $('#notifAllSelect').find('option:selected').val();
			editJcNotifAll(key, currentName, inputName);

		}
	});
})

$('#bt_removeJcNotifAll').off('click').on('click', function () {
	removeJcNotifAll();
});

$('#bt_saveJcNotifAll').off('click').on('click', function () {
	saveJcNotifAll();

});

async function editJcNotifAll(key, oldData, newData) {
	var data = {
		action: 'editNotifAll',
		key: key,
		oldName: oldData,
		newName: newData
	}
	let dataEdit = await asyncAjaxGenericFunction(data);

	if (dataEdit.state != 'ok') return;
	$('#alert_JcWidgetNotifAll').showAlert({ message: 'Elément édité', level: 'success' });

	$('#notifAllSelect option[value=' + key + ']').text(newData);
	$('#notifAllSelect option[value=' + key + ']').attr('data-text', newData.toLowerCase())

}


async function removeJcNotifAll() {
	let myKey = $('#notifAllSelect').find('option:selected').val();
	var data = {
		action: 'removeNotifAll',
		key: myKey,
	}
	let dataRemove = await asyncAjaxGenericFunction(data);

	if (dataRemove.state != 'ok') return;

	$('#alert_JcWidgetNotifAll').showAlert({ message: 'Elément supprimé', level: 'success' });

	$('#notifAllSelect option[value=' + myKey + ']').remove();
	$('#notifAllSelect option:first').prop("selected", true).trigger('change');;

}

async function saveJcNotifAll() {
	var checkedVals = $('.notifAllOptions:checkbox:checked').map(function () {
		return this.value;
	}).get();

	if (checkedVals.length == 0) {
		$('#alert_JcWidgetNotifAll').showAlert({ message: 'Aucune notification sélectionée', level: 'warning' });
		return;
	}

	data = {
		'action': 'saveNotifAll',
		'key': $('#notifAllSelect').find('option:selected').val(),
		'name': $('#notifAllSelect').find('option:selected').text(),
		'cmdList': checkedVals
	};
	let saveData = await asyncAjaxGenericFunction(data);

	if (saveData.state != 'ok') return;

	$('#alert_JcWidgetNotifAll').showAlert({ message: 'Configuration sauvegardée', level: 'success' });
	$('#notifAllSelect').find('option:selected').attr('data-cmd', checkedVals.join(','));

}


$(document).ready(
	function () {
		$('#notifAllSelect option[value=notifAll]').prop("selected", true).trigger('change');;
	}
);