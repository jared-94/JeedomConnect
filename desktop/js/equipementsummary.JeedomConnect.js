$('#equipmentSummaryModal').off('click', '#bt_removeJcEquipmentSummary').on('click', '#bt_removeJcEquipmentSummary', function () {

    var searchIDs = $('#table_JcEquipmentSummary .removeEquipment:checked').map(function () {
        return $(this).attr('data-eqId');
    }).get();
    var count = searchIDs.length;
    if (count > 0) {

        if (count == 1) {
            msg = '  Voulez vous supprimer cet équipement ? ';
        }
        else {
            msg = '  Voulez vous supprimer ces ' + count + ' équipements ? '
        }

        getSimpleModal({
            title: "Confirmation", fields: [{
                type: "string",
                value: msg
            }]
        }, function (result) {
            $('#alert_JcEquipmentSummary').hideAlert();

            $.ajax({
                type: "POST",
                url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
                data: {
                    action: "removeEquipmentConfig",
                    eqIds: searchIDs
                },
                dataType: 'json',
                error: function (error) {
                    $('#alert_JcEquipmentSummary').showAlert({ message: error.message, level: 'danger' });
                },
                success: function (data) {
                    if (data.state != 'ok') {
                        $('#alert_JcEquipmentSummary').showAlert({
                            message: data.result,
                            level: 'danger'
                        });
                    }
                    else {
                        searchIDs.forEach(function (item) {
                            console.log(item);
                            $('.tr_object[data-equipment_id=' + item + ']').remove();
                        });
                        $('#equipmentSummaryModal').attr('data-has-changes', true);
                    }
                }
            })

            $('#alert_JcEquipmentSummary').showAlert({
                message: 'Suppression effectuée',
                level: 'success'
            });
            $('#bt_removeJcEquipmentSummary').css('display', 'none');
        });
    }


});

$('#bt_saveJcEquipmentSummary').off('click').on('click', function () {
    $('#div_alert').hideAlert();
    $('#alert_JcEquipmentSummary').hideAlert();

    myData = $('#table_JcEquipmentSummary .tr_object[data-changed=true]').getValues('.objectAttr');
    console.log('myData', myData);

    if (myData.length > 0) {
        $.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {
                action: 'updateEquipmentMass',
                equipementsObj: myData
            },
            cache: false,
            dataType: 'json',
            async: false,
            success: function (data) {
                if (data.state != 'ok') {
                    $('#alert_JcEquipmentSummary').showAlert({
                        message: data.result,
                        level: 'danger'
                    });
                }
                else {
                    $('#alert_JcEquipmentSummary').showAlert({
                        message: 'Sauvegarde effectuée',
                        level: 'success'
                    });
                    $('#equipmentSummaryModal').attr('data-has-changes', true);
                    $('.tr_object').attr('data-changed', false);
                    $('span[data-l1key=eqId]')
                        .addClass('label-info')
                        .removeClass('label-warning');
                }

            }
        });
    }
    else {
        $('#alert_JcEquipmentSummary').showAlert({
            message: 'Aucun changement',
            level: 'warning'
        });
    }

});


$("#table_JcEquipmentSummary").on('change', ' .objectAttr', function () {
    var id = $(this).closest('.tr_object').attr('data-equipment_id');
    updateToBeDone(id);
});

function updateToBeDone(eqId) {
    $tr = $('.tr_object[data-equipment_id=' + eqId + ']');
    $tr.attr('data-changed', true);
    $tr.find('span[data-l1key=eqId]')
        .removeClass('label-info')
        .addClass('label-warning');
}


$('#equipmentSummaryModal').off('click', '.removeEquipment').on('click', '.removeEquipment', function () {

    var myData = $('#table_JcEquipmentSummary .removeEquipment:checked');
    var count = myData.length;
    if (count > 0) {
        $('#bt_removeJcEquipmentSummary').css('display', '');
    }
    else {
        $('#bt_removeJcEquipmentSummary').css('display', 'none');
    }
});


function check_before_closing() {
    var hasChanges = $('#equipmentSummaryModal').attr('data-has-changes');
    if (hasChanges == 'true') {
        var vars = getUrlVars()
        var url = 'index.php?'
        delete vars['id']
        delete vars['saveSuccessFull']
        delete vars['removeSuccessFull']

        url = getCustomParamUrl(url, vars);
        modifyWithoutSave = false
        loadPage(url);
    }
    $("#equipmentSummaryModal").dialog('destroy').remove();
}

$(document).ready(function () {
    $("#table_JcEquipmentSummary").tablesorter({
        widthFixed: false,
        sortLocaleCompare: true,
        sortList: [[4, 0], [2, 0]], //first room, then name
        theme: 'bootstrap',
        headerTemplate: '{content} {icon}',
        widgets: ["zebra", "filter", "uitheme", 'stickyHeaders'],
        widgetOptions: {
            resizable: true,
            resizable_addLastColumn: true
        }
    })

});


$('#table_JcEquipmentSummary').off('click', '.jcMassAction').on('click', '.jcMassAction', function () {
    var type = $(this).data('jctype');
    var checked = ($(this).data('jcaction') == 'checked');

    $('#table_JcEquipmentSummary > tbody  > tr').each(function (index, tr) {

        id = $(this).data('equipment_id');

        maCell = $(this).find('td input[data-l1key=' + type + ']')
        currentState = maCell.is(':checked');

        if (currentState && !checked) {
            // console.log(id + " => on décoche !");
            maCell.prop('checked', false);
            updateToBeDone(id);
        }
        else if (!currentState && checked) {
            // console.log(id + " => on coche !");
            maCell.prop('checked', true);
            updateToBeDone(id);
        }
        /*
        else do nothing => ((currentState && checked) || (!currentState && !checked)) {
        */

    });
})



$('#table_JcEquipmentSummary').on('mouseenter', '.qrcode-panel', function (e) {
    // Calculate the position of the image tooltip
    x = e.pageX - 300;
    y = e.pageY - 120;

    $('.qrCodeModal').find('img').attr('src', $(this).find('.qrcode-content').data('img'));
    $('.qrCodeModal').find('.jcApiKey').text($(this).find('.qrcode-content').data('apikey'));
    $('.qrCodeModal').find('.jcName').text($(this).find('.qrcode-content').data('name'));

    // Set the z-index of the current item,
    // make sure it's greater than the rest of thumbnail items
    // Set the position and display the image tooltip

    $('#equipmentSummaryModal').css('z-index', '15');
    $(".qrCodeModal-content").css({ 'top': y, 'left': x, 'display': 'block' });

});


$('#table_JcEquipmentSummary').on('mouseleave', '.qrcode-panel', function (e) {

    // Reset the z-index and hide the image tooltip
    $('#equipmentSummaryModal').css('z-index', '1');
    $(".qrCodeModal-content").css('display', 'none');
});

$("#table_JcEquipmentSummary").on('change keyup', 'input[data-l1key=name]', function () {
    $(this).siblings('.eqJcName').text($(this).val());
});

$("#table_JcEquipmentSummary").on('click', '.checkJcConnexionOption', function () {
    var useWs = $(this).closest('.tr_object').find('input[data-l1key=useWs]');
    var polling = $(this).closest('.tr_object').find('input[data-l1key=polling]');

    polling.attr('disabled', false);
    useWs.attr('disabled', false);

    if (useWs.prop("checked")) {
        polling.prop("checked", false);
        polling.attr('disabled', true);
    }
    else {
        if (polling.prop("checked")) {
            useWs.prop("checked", false);
            useWs.attr('disabled', true);
        }
    }

});


$("#table_JcEquipmentSummary").on('click', 'input.objectAttr[data-l1key=NotifAll]', function () {
    var nbChecked = $(this).closest('.checkboxes').find('.objectAttr:checked').length;
    var newTitle = getMultiCheckboxOptionTitle(nbChecked);

    $(this).parents('.multiselect').find('.titleOption').text(newTitle);

});

$("#table_JcEquipmentSummary").on('click', '.selectBox', function () {

    $checkboxes = $(this).parent('.multiselect').find('.checkboxes')
    $(".checkboxes").not($checkboxes).hide();

    var hide = $checkboxes.css('display') == 'none';

    if (hide) {
        $checkboxes.css('display', "block");
    } else {
        $checkboxes.css('display', "none");
    }
});

function getMultiCheckboxOptionTitle(nb) {
    var needPlurial = (nb > 1) ? 's' : '';
    var titleSelect = (nb == 0) ? 'Selectionnez une option' : (nb + ' option' + needPlurial + ' sélectionnée' + needPlurial);
    return titleSelect;
}