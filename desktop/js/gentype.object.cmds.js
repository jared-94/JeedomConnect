// HACK delete file when gentype config in plugin is not needed anymore

//searching
$('#in_searchCmd').keyup(function () {
    var search = $(this).value()
    if (search == '') {
        $('.panel-collapse.in').closest('.panel').find('.accordion-toggle').click()
        $('.cmdLine').show()
        return
    }
    search = normTextLower(search)
    $('.panel-collapse').attr('data-show', 0)
    $('.cmdLine').hide()
    var text
    $('.cmdLine .cmdName').each(function () {
        text = normTextLower($(this).text())
        if (text.indexOf(search) >= 0) {
            $(this).closest('.cmdLine').show()
            $(this).closest('.panel-collapse').attr('data-show', 1)
        }
    })
    $('.cmdLine .cmdType').each(function () {
        text = normTextLower($(this).text())
        if (text.indexOf(search) >= 0) {
            $(this).closest('.cmdLine').show()
            $(this).closest('.panel-collapse').attr('data-show', 1)
        }
    })
    $('.panel-collapse[data-show=1]').collapse('show')
    $('.panel-collapse[data-show=0]').collapse('hide')
})

$('#bt_resetCmdSearch').off('click').on('click', function () {
    $('#in_searchCmd').val('').keyup()
})

$('.reinitCmd').off('click').on('click', function () {
    var parentTable = $(this).parents('table').attr('id');
    $('table#' + parentTable).find('select[data-l1key=generic_type]').each(function () {
        $(this).val("");
    })
});

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

$('#bt_return').on('click', function () {
    gotoGenTypeConfig();
});

$('.cmdAttr').on('change', function () {
    $(this).closest('tr').attr('data-change', '1');
});

function saveCmds() {
    var cmds = [];
    $('.tableCmd tr').each(function () {
        if ($(this).attr('data-change') == '1') {
            cmds.push($(this).getValues('.cmdAttr')[0]);
        }
    });

    jeedom.cmd.multiSave({
        cmds: cmds,
        error: function (error) {
            $('.displayMessage').showAlert({ message: error.message, level: 'danger' });
        },
        success: function (data) {
            $('.displayMessage').showAlert({ message: '{{Modifications sauvegardées avec succès}}', level: 'success' });
        }

    });
}

$('#bt_resetCmdSearch').click()
