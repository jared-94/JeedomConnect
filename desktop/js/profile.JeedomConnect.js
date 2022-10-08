var prev_val;
$("#modal_appProfil").on('focus', '#profileAllSelect', function () {
    prev_val = $(this).val();
    console.log('on focus prev_val', prev_val);
}).on('change', '#profileAllSelect', function () {
    var id = $(this).find('option:selected').val();
    var changeExist = $('.infoSave').attr('data-change') == 'true';
    if (changeExist) {
        bootbox.confirm("Des changements n'ont pas été sauvegardés. Continuez ?", function (result) {
            if (!result) {
                // console.log('on change prev_val', prev_val);
                // $(this).val(prev_val);
                $('#profileAllSelect option[value="' + prev_val + '"]').prop('selected', true);
                return;
            }
            else {
                getJcProfile(id);

                $('.infoSave').hide();
                $('.infoSave').attr('data-change', 'false');
            }
        });
    }
    else {
        getJcProfile(id);

        $('.infoSave').hide();
        $('.infoSave').attr('data-change', 'false');
    }

})


async function getJcProfile(id) {
    var data = {
        action: 'getStandardConfig',
        key: id,
    }
    let dataProfile = await asyncAjaxGenericFunction(data);
    // console.log('get profile - dataProfile', dataProfile);
    if (dataProfile.state != 'ok') return;

    // $('#notifAllSelect option[value=' + myKey + ']').remove();
    $('.profilAttr').prop("checked", false);
    $('#accordionProfil').setValues(dataProfile.result.profile, '.profilAttr');
    recalculateSelected();

}

function recalculateSelected() {
    $('.jcMenu').each(function () {
        var nbTotal = $(this).find('.profilAttr').length;
        var nbSelected = $(this).find('.profilAttr:checked').length;

        $(this).find('.countElt').text('(' + nbSelected + ' / ' + nbTotal + ')');
    })
}

// $('.showInfoAppProfil').off('click').on('click', function () {
//     $('.infoAppProfil').show();
// });
$('.showInfoAppProfil').hover(function () {
    if ($('.infoAppProfil').is(":visible")) {
        $('.infoAppProfil').hide();
    }
    else {
        $('.infoAppProfil').show();
    }
});

$('#btn_selectAll').off('click').on('click', function () {
    $('.profilAttr').prop("checked", true);
    saveRequired();
});

$('#btn_deselectAll').off('click').on('click', function () {
    $('.profilAttr').prop("checked", false);
    saveRequired();
});

$('input.profilAttr').off('click').on('click', function () {
    saveRequired();
})

function saveRequired() {
    $('.infoSave').show();
    $('.infoSave').attr('data-change', 'true');
    recalculateSelected();
}

$('#bt_saveJcProfil').off('click').on('click', function () {
    saveJcProfile();
});

async function saveJcProfile() {
    let myKey = $('#profileAllSelect').find('option:selected').val();
    let myName = $('#profileAllSelect').find('option:selected').text();
    var profils = $("#accordionProfil").getValues('.profilAttr')[0];

    // var profils2 = $("#accordionProfil").getValues('.profileAttrList')[0];
    // console.log('profileAttrList', JSON.stringify(profils2));

    var data = {
        action: 'saveStandardConfig',
        key: myKey,
        value: { "name": myName, "profile": profils }
    }
    let dataProfile = await asyncAjaxGenericFunction(data);

    // console.log('save - dataProfile', dataProfile);
    if (dataProfile.state != 'ok') return;

    $('.infoSave').hide();
    $('.infoSave').attr('data-change', 'false');


    var data = {
        action: 'broadcastAppProfilChanged',
        key: myKey,
    }
    let broadcastChanges = await asyncAjaxGenericFunction(data);
    if (dataProfile.state == 'ok') {
        console.log('info partagée ! ');
    }

}


$('#bt_removeJcProfil').off('click').on('click', function () {
    removeJcProfile();
});


async function removeJcProfile() {

    usage = await getAppProfilCount();

    if (usage > 0) {
        bootbox.alert(
            "Suppression impossible : ce profil est actuellement utilisé par " + usage + " équipement" + getPlurial(usage) + "."
        );
        return;
    }
    bootbox.confirm("Etes vous sûr de vouloir supprimer ce profil ?", function (result) {
        if (result) {

            let myKey = $('#profileAllSelect').find('option:selected');
            let myKeyValue = myKey.val();

            var data = {
                action: 'removeStandardConfig',
                key: myKeyValue
            }
            // let dataProfile = await asyncAjaxGenericFunction(data);

            if (dataProfile.state != 'ok') return;

            $.fn.showAlert({
                message: 'Suppression réalisée',
                level: 'success'
            })

            myKey.remove();
            $('#profileAllSelect option:first').prop("selected", true).trigger('change');
        }
    });

}

async function getAppProfilCount() {
    let myKey = $('#profileAllSelect').find('option:selected').val();

    var data = {
        action: 'getAppProfilCount',
        appProfil: myKey
    }
    let dataProfile = await asyncAjaxGenericFunction(data);

    if (dataProfile.state != 'ok') return;

    return dataProfile.result;

}


$('#bt_createJcProfil').off('click').on('click', function () {
    $.fn.hideAlert();

    bootbox.prompt("Nom du nouveau profile ?", function (result) {
        if (result == null) return; // cancel


        let inputName = $.trim(result);
        if (inputName == '') {
            $.fn.showAlert({ message: 'Le nom doit être renseigné', level: 'danger' });
            return;
        }

        if ($('#profileAllSelect option[data-text="' + inputName.toLowerCase() + '"]').length > 0) {
            $.fn.showAlert({ message: 'Ce nom existe déjà', level: 'danger' });
            return;
        }

        let optionVal = 'profile_' + makeid();
        $('#profileAllSelect').append('<option value="' + optionVal + '" data-text="' + inputName.toLowerCase() + '">' + inputName + '</option>');
        $('#profileAllSelect option[value=' + optionVal + ']').prop("selected", true).trigger('change');

    });
});



$('#bt_editJcProfil').off('click').on('click', function () {
    $.fn.hideAlert();

    let currentName = $('#profileAllSelect').find('option:selected').text();
    bootbox.prompt({
        title: "Nouveau nom pour cette commande ?",
        value: currentName,
        callback: function (result) {
            if (result == null) return; //if cancelled

            let inputName = $.trim(result);

            if (currentName == inputName) return;

            if (inputName == '') {
                $.fn.showAlert({ message: 'Le nom doit être renseigné', level: 'danger' });
                return;
            }

            if ($('#profileAllSelect option[data-text="' + inputName.toLowerCase() + '"]').length > 0) {
                $.fn.showAlert({ message: 'Ce nom existe déjà', level: 'danger' });
                return;
            }

            let key = $('#profileAllSelect').find('option:selected').val();
            editJcProfile(key, currentName, inputName);

        }
    });
})

async function editJcProfile(key, oldData, newData) {
    var data = {
        action: 'editStandardConfig',
        key: key,
        newValue: newData,
        object: "name"
    }
    let dataEdit = await asyncAjaxGenericFunction(data);

    if (dataEdit.state != 'ok') return;
    $.fn.showAlert({ message: 'Elément édité', level: 'success' });

    $('#profileAllSelect option[value=' + key + ']').text(newData);
    $('#profileAllSelect option[value=' + key + ']').attr('data-text', newData.toLowerCase())

}


// SEARCH bar
$('#in_searchObject').keyup(function () {
    var search = $(this).value()
    if (search == '') {
        $('.jcMenu').show();
        $('.cmdLine').show()
        $('#bt_closeAll').click();
        return
    }
    search = normTextLower(search)
    $('#bt_openAll').click();
    $('.jcMenu').hide()
    $('.cmdLine').hide()
    var text
    $('.jcMenu').each(function () {
        $(this).find('.cmdLine').each(function () {
            console.log('on est ici')
            text = normTextLower($(this).find('.profileName').text())
            code = normTextLower($(this).find('input.profilAttr').attr('data-l3key'));
            console.log('looking for', text, code);
            if (text.indexOf(search) >= 0 || code.indexOf(search) >= 0) {
                $(this).closest('.jcMenu').show()
                $(this).show()
                return;
            }
        });
    })
    // $('.jcMenuContainer').packery()
})

$('#bt_resetObjectSearch').on('click', function () {
    $('#in_searchObject').val('').keyup()
})

$('#bt_resetObjectSearch').click();


$('#bt_openAll').off('click').on('click', function () {
    $(".accordion-toggle[aria-expanded='false']").each(function () {
        $(this).click()
    })
})
$('#bt_closeAll').off('click').on('click', function () {
    $(".accordion-toggle[aria-expanded='true']").each(function () {
        $(this).click()
    })
})

//// READY

$(document).ready(
    function () {
        $('#profileAllSelect option:first').prop("selected", true).trigger('change');
    }
);