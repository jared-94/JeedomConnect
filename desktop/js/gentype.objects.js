// HACK delete file when gentype config in plugin is not needed anymore

//searching
$('#in_searchObject').keyup(function () {
    var search = $(this).value()
    if (search == '') {
        $('.objectDisplayCard').show()
        $('.objectListContainer').packery()
        return
    }
    search = jeedomUtils.normTextLower(search)

    $('.objectDisplayCard').hide()
    var text
    $('.objectDisplayCard .name').each(function () {
        text = jeedomUtils.normTextLower($(this).text())
        if (text.indexOf(search) >= 0) {
            $(this).closest('.objectDisplayCard').show()
        }
    })
    $('.objectListContainer').packery()
})

$('#bt_resetObjectSearch').on('click', function () {
    $('#in_searchObject').val('').keyup()
})

$(".objectListContainer").delegate('.objectDisplayCard', 'click', function () {
    $('#md_modal').dialog({ title: "{{Objets / Pièces > commandes}}" });
    $('#md_modal').load('index.php?v=d&plugin=JeedomConnect&modal=gentype.object.cmds&objectId=' + $(this).attr("data-object_id")).dialog('open');
});

$('#md_modal').unbind('dialogresize').bind('dialogresize', function (event, ui) {
    $('.objectListContainer').packery()
});

$('#bt_resetObjectSearch').click()