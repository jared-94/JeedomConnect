$('#bt_uploadImg').fileupload({
    add: function (e, data) {
        let currentPath = $('#bt_uploadImg').attr('data-path');
        data.url = 'core/ajax/jeedom.ajax.php?action=uploadImageIcon&filepath=' + userImgPath;
        data.submit();
    },
    done: function (e, data) {
        if (data.result.state != 'ok') {
            $('#div_iconSelectorAlert').showAlert({
                message: data.result.result,
                level: 'danger'
            });
            return;
        }
        var name = data.result.result.filepath.replace(/^.*[\\\/]/, '');
        div = '<div class="divIconSel divImgSel">';
        div += '<div class="cursor iconSel"><img class="img-responsive" source="user" name="' + name + '" src="' + userImgPath + name + '" /></div>';
        div += '<div class="iconDesc">' + name + '</div>';
        div += '<a class="btn btn-danger btn-xs bt_removeImg" data-realfilepath="' + userImgPath + name + '"><i class="fas fa-trash"></i> {{Supprimer}}</a>';
        div += '</div>';
        $("#div_imageGallery[source='user']").append(div);

        $('#div_iconSelectorAlert').showAlert({
            message: 'Fichier(s) ajouté(s) avec succès',
            level: 'success'
        });
    }
});

$('#tabimg .div_imageGallery').off('click').on('click', '.bt_removeImg', function () {
    $.hideAlert();
    var filepath = $(this).attr('data-realfilepath');
    $imgDivElement = $(this).closest('.divIconSel');
    bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer cette image}} <span style="font-weight: bold ;">' + filepath + '</span> ?', function (result) {
        if (result) {
            jeedom.removeImageIcon({
                filepath: filepath,
                error: function (error) {
                    $('#div_iconSelectorAlert').showAlert({
                        message: error.message,
                        level: 'danger'
                    });
                },
                success: function (data) {
                    $imgDivElement.remove();
                    $('#div_iconSelectorAlert').showAlert({
                        message: 'Fichier supprimé avec succès',
                        level: 'success'
                    });
                }
            })
        }
    })
});

$('#mod_selectIcon').off('click', '.divIconSel').on('click', '.divIconSel', function () {
    $('.divIconSel').removeClass('iconSelected');
    $(this).closest('.divIconSel').addClass('iconSelected');
});

function iconColorDefined(c) {
    $("#mod-color-input").val(c.value);
}

function setIconParams() {
    $("#icon-params-div").show();
    $("#img-params-div").hide();
}

function setImgParams() {
    $("#icon-params-div").hide();
    $("#img-params-div").show();
}

//searching
$('#in_searchIconSelector').on('keyup', function () {

    var search = $(this).value()
    if (search.length == 1) {
        return;
    }
    $('.divIconSel').css({
        'display': ''
    })
    $('.iconCategory').css({
        'display': ''
    })

    if (search != '') {
        search = normTextLower(search)
        $('.iconDesc').each(function () {
            iconName = normTextLower($(this).text())
            if (iconName.indexOf(search) == -1) {
                $(this).closest('.divIconSel').css({
                    'display': 'none'
                })
            }
        })
    }

    var somethingFound = 0
    $('.iconCategory').each(function () {
        var hide = true
        if ($(this).find('.divIconSel:visible').length == 0) {
            $(this).css({
                'display': 'none'
            })
        } else {
            somethingFound += 1
        }
    })
    if (somethingFound == 0) {
        $('.generalCategory').css({
            'display': ''
        })
    }
})

$('#bt_resetSearchIcon').on('click', function () {
    $('#in_searchIconSelector').val('').keyup()
})


$('#mod_selectIcon ul li a').click(function () {
    $('.jcpanel.tab-pane').css('display', 'none');

    var type = $(this).attr('href').replace('#', '');

    $('.tab-pane[source="' + type + '"]').css('display', 'block');

    if (type == 'user') {
        $('.btnImgUserAdd').css('display', 'block');
    } else {
        $('.btnImgUserAdd').css('display', 'none');
    }

    if (['jc', 'user'].indexOf(type) > -1) {
        setImgParams();
    } else {
        setIconParams()
    }

    $('#in_searchIconSelector').keyup();
})


$(function () {
    var buttonSet = $('.ui-dialog[aria-describedby="mod_selectIcon"]').find('.ui-dialog-buttonpane')
    buttonSet.find('#mySearch').remove()
    var mySearch = $('.ui-dialog[aria-describedby="mod_selectIcon"]').find('#mySearch')
    buttonSet.append(mySearch)
    if (selectedIcon.source == 0) {
        $('#mod_selectIcon ul li a').first().click();
    } else {
        $(`#mod_selectIcon ul li a[href="#${selectedIcon.source}"]`).click();
        if (selectedIcon.source == 'user') {
            tmpSrc = (userImgPath || '') + selectedIcon.name;
            $(`.tab-pane[source="${selectedIcon.source}"]`).find(`[src="${decodeURI(tmpSrc)}"]`).closest('.divIconSel').addClass('iconSelected');
        } else {
            $(`.tab-pane[source="${selectedIcon.source}"]`).find(`[name="${decodeURI(selectedIcon.name)}"]`).closest('.divIconSel').addClass('iconSelected');
        }
        setTimeout(function () {
            elem = $('div.divIconSel.iconSelected')
            if (elem.position()) {
                container = $('#mod_selectIcon > .tab-content')
                pos = elem.position().top + container.scrollTop() - container.position().top
                container.animate({
                    scrollTop: pos - 20
                })
            }
        }, 250)
    }

    if (selectedIcon.color != 0) {
        $("#mod-color-picker").val("#" + selectedIcon.color);
        $("#mod-color-input").val("#" + selectedIcon.color);
    }
    if (selectedIcon.shadow === "true") {
        $("#bw-input").prop('checked', true);
    }


});