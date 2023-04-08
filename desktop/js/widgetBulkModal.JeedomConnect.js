function isDarkTheme() {
    if ($('body')[0].hasAttribute('data-theme')) {
        if ($('body').attr('data-theme').endsWith('Dark')) return true;
    }

    return false;
}

function getBackgroundColor() {
    if (isDarkTheme()) return '#626262';

    return '#c6d7ff';
}

$('.alreadyExist').attr('style', function (i, s) {
    return (s || '') + ';background-color: ' + getBackgroundColor() + ' !important';
});

function refreshAddWidgetBulk() {
    $('.alreadyExist').hide();
    widgetsOption = [];
    cmdCat = [];
    imgCat = [];
    var type = $("#widgetBulkList-select").val();
    var widget = widgetsList.widgets.find(i => i.type == type);
    if (widget == undefined) return;
    $("#widgetImg").attr("src", "plugins/JeedomConnect/data/img/" + widget.img);

    $("#widgetDescription").html(widget.description);

    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'getCmdsForWidgetType',
            widget_type: widget.type
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
                    $('.optionWidgetBulk').hide();
                    $('.noGenType').show();
                    $('#table_widgets').empty();
                }
                else {

                    $('.optionWidgetBulk').show();
                    $('.noGenType').hide();
                    var tbody = '<tbody>';
                    var indexRow = 0;
                    var checkAll = 'checked';
                    Object.entries(data.result).forEach(eqLogic => {
                        var cmdsWithGenType = [];
                        tr = '<td>'
                        tr += '<select class="room-input cmdAttrib" data-l1key="room" id="room-input" roomId="' + eqLogic[1].room + '">'
                        tr += '<option value="none">Sélection  d\'une pièce</option>'
                        tr += roomListOptions
                        tr += '</select>'
                        tr += '</td>'
                        isDisabled = isJcExpert ? '' : 'disabled';
                        widget.options.filter(o => o.required === true || (o.hasOwnProperty('generic_type') && o.generic_type != '')).forEach(option => {
                            var eqLogicInfo = ''
                            tr += '<td>'
                            if (option.category == "cmd") {
                                var cmd = undefined
                                if (option.hasOwnProperty('generic_type')) {
                                    var cmd = eqLogic[1][option.id] || undefined;
                                }
                                if (cmd == undefined) {
                                    cmd = {}
                                    cmd.id = ''
                                    cmd.humanName = ''
                                    cmd.type = option.type
                                    cmd.subType = option.subtype || ''
                                    cmd.minValue = option.minValue || ''
                                    cmd.maxValue = option.maxValue || ''
                                    cmd.unit = option.unit || ''
                                } else {
                                    var rc = {
                                        optionId: option.id,
                                        cmdId: cmd.id
                                    }
                                    cmdsWithGenType.push(rc);
                                }
                                eqLogicInfo += `<div class="input-group"><input class='input-sm form-control roundedLeft cmdAttrib needRefresh' data-l1key="${option.id}" id='${option.id}-input' title='${cmd.humanName} -- id : ${cmd.id}' value='${cmd.humanName}' cmdId='${cmd.id}' cmdType='${cmd.type}' cmdSubType='${cmd.subType}' minValue='${cmd.minValue}' maxValue='${cmd.maxValue}' unit='${cmd.unit}' ${isDisabled}>`
                                eqLogicInfo += '<span class="input-group-btn">'
                                eqLogicInfo += '<a class="btn btn-sm btn-default roundedRight listCmdInfo tooltips" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>'
                                eqLogicInfo += '</span>'
                                eqLogicInfo += '</div>'
                            } else if (option.category == "string") {
                                var value = ''
                                if (option.id == 'name') {
                                    var value = eqLogic[1].name
                                }
                                type = (option.subType != undefined) ? option.subType : 'text';
                                eqLogicInfo += `<div class='input-group'><input class='input-sm form-control roundedLeft cmdAttrib' data-l1key="${option.id}" type="${type}" id="${option.id}-input" value='${value}'>`;
                                if (option.id == 'name') {
                                    eqLogicInfo += '<span class="input-group-btn">'
                                    eqLogicInfo += `
                                    <div class="dropdown" id="name-select">
                                    <a class="btn btn-default btn-sm roundedRight dropdown-toggle" data-toggle="dropdown" style="height" >
                                    <i class="fas fa-plus-square"></i> </a>
                                    <ul class="dropdown-menu infos-select" input="${option.id}-input">`;
                                    if (widget.variables) {
                                        widget.variables.forEach(v => {
                                            eqLogicInfo += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
                                        });
                                    }
                                    eqLogicInfo += `</ul></div></div>`
                                }
                                eqLogicInfo += `</div></div></div></li>`;
                            } else if (option.category == "cmdList") {
                                eqLogicInfo += '<div class="jcCmdList">';
                                eqLogic[1].modes.filter(c => c.generic_type == option.generic_type).forEach(c => {
                                    eqLogicInfo += `<div class="input-group"><input class='input-sm form-control roundedLeft cmdListAttr' data-l1key="${option.id}" id='${option.id}-input' title='${c.humanName} -- id : ${c.id}' value='${c.humanName}' cmdId='${c.id}' cmdName='${c.name}' cmdType='${c.type}' cmdSubType='${c.subType}' minValue='${c.minValue}' maxValue='${c.maxValue}' unit='${c.unit}' ${isDisabled} >`
                                    eqLogicInfo += '<span class="input-group-btn">'
                                    eqLogicInfo += '<a class="btn btn-sm btn-default roundedRight listCmdInfo tooltips" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>'
                                    eqLogicInfo += '</span>'
                                    eqLogicInfo += '<i class="fas fa-minus-circle pull-right cursor removeParent"></i>'
                                    eqLogicInfo += '</div>'
                                });
                                eqLogicInfo += '</div>';
                            }
                            tr += eqLogicInfo
                            tr += '</td>'
                        });


                        if (eqLogic[1].alreadyExist || false) {
                            style = 'background-color: ' + getBackgroundColor() + ' !important';
                            cbChecked = '';
                            checkAll = '';
                            $('.alreadyExist').show();
                        } else {
                            style = '';
                            cbChecked = 'checked';
                        }
                        tbody += `<tr class="widgetLine" data-index="${indexRow}" style='${style}'>`
                        tbody += `<td><input type="checkbox" class="checkboxBulk" ${cbChecked}/></td>`
                        tbody += tr
                        tbody += '</tr>'

                        indexRow++;
                    });

                    var actionSecureHtml = `<select style="max-width: 120px;">
                    <option value="none">Aucun</option><option value="confirm">Confirmation</option>
                    <option value="secure">Empreinte</option><option value="pwd">Mot de passe</option>
                    </select><br/>`;
                    var thead = '<thead><tr>'
                    thead += `<th><input type="checkbox" class="checkbox-inline" name="checkboxBulk" id="checkAll" ${checkAll}/></th>`
                    thead += '<th>Pièce</th>'
                    widget.options.filter(o => o.required === true || (o.hasOwnProperty('generic_type') && o.generic_type != '')).forEach(option => {
                        var required = (option.required) ? " required" : "";
                        var actionSecure = (option.type == 'action') ? actionSecureHtml : "";
                        thead += `<th class='${option.id}${required}'>` + actionSecure + option.name + '</th > '
                    });
                    thead += '</tr></thead>'
                    $("#table_widgets").html(thead + tbody);

                    $("#room-input > option").each(function () {
                        if ($(this).val() == $(this).parent().attr('roomId')) {
                            $(this).prop('selected', true);
                            return;
                        }
                    });
                }
            }
        }
    });
}

function saveWidgetBulk() {
    $('#widget-alert').hideAlert();

    try {
        var widgetConfig = widgetsList.widgets.find(w => w.type == $("#widgetBulkList-select").val());
        var calls = [];
        // $('#table_widgets tbody .widgetLine').each(function () {
        var rowToSave = $('#table_widgets tbody .widgetLine')
            .filter(function () {
                return ($(this).find('.checkboxBulk').is(':checked')) // dealing only with checked rows !
            });

        if (rowToSave.length == 0) {
            $('#widget-alert').showAlert({ message: 'Aucune ligne sélectionnée !', level: 'warning' });
            return;
        }

        rowToSave.each(function () {
            var result = {};
            let infoCmd = [];
            widgetConfig.options.forEach(option => {
                if (option.category == "cmd") {
                    var cmdElement = $(this).find("#" + option.id + "-input");
                    if (cmdElement.attr('cmdId') == '' & option.required) {
                        throw 'La commande ' + option.name + ' est obligatoire';
                    }

                    if (cmdElement.attr('cmdId') != undefined & cmdElement.attr('cmdId') != '') {
                        var secureOpt = $(this).closest('table').find("thead ." + cmdElement.attr('data-l1key') + " option:selected").val();

                        result[option.id] = {};
                        result[option.id].id = cmdElement.attr('cmdId');
                        result[option.id].type = cmdElement.attr('cmdType');
                        result[option.id].subType = cmdElement.attr('cmdSubType');
                        result[option.id].minValue = cmdElement.attr('minValue') != '' ? cmdElement.attr('minValue') : undefined;
                        result[option.id].maxValue = cmdElement.attr('maxValue') != '' ? cmdElement.attr('maxValue') : undefined;
                        result[option.id].unit = cmdElement.attr('unit') != '' ? cmdElement.attr('unit') : undefined;
                        // result[option.id].invert = $("#invert-" + option.id).is(':checked') || undefined;
                        if (cmdElement.attr('cmdType') == 'action') {
                            result[option.id].confirm = (secureOpt == 'confirm') || undefined;
                            result[option.id].secure = (secureOpt == 'secure') || undefined;
                            result[option.id].pwd = (secureOpt == 'pwd') || undefined;
                        }
                        Object.keys(result[option.id]).forEach(key => result[option.id][key] === undefined ? delete result[option.id][key] : {});
                    }
                    else {
                        result[option.id] = undefined;
                    }
                } else if (option.category == "string") {
                    if ($(this).find("#" + option.id + "-input").val() == '' & option.required) {
                        throw 'La commande ' + option.name + ' est obligatoire';
                    }
                    result[option.id] = parseString($(this).find("#" + option.id + "-input").val(), infoCmd);
                } else if (option.category == "cmdList") {
                    var cmdList = [];
                    var index = 0;

                    $(this).find('.jcCmdList .cmdListAttr').each(function () {
                        cmdList.push({ id: $(this).attr('cmdId'), name: $(this).attr('cmdName'), index: index });
                        index++;
                    });
                    result[option.id] = cmdList;
                }
            });

            result.type = $("#widgetBulkList-select").val();
            widgetType = $("#widgetBulkList-select").val();

            result.enable = true;

            widgetRoom = $(this).find('#room-input :selected').val();
            widgetRoomName = $(this).find('#room-input :selected').text();
            if (widgetRoom != 'none') {
                if (widgetRoom == 'global') {
                    result.room = 'global';
                }
                else {
                    result.room = parseInt(widgetRoom);
                }
            }

            toSave = JSON.stringify(result)

            widgetImg = $("#widgetImg").attr("src");
            widgetName = $(this).find("#name-input").val();

            if (toSave !== null) {
                calls.push($.ajax({
                    type: "POST",
                    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
                    data: {
                        action: "saveWidgetConfig",
                        widgetJC: toSave,
                        imgPath: widgetImg
                    },
                    dataType: 'json',
                    error: function (error) {
                        $('#widget-alert').showAlert({ message: error.message, level: 'danger' });
                    },
                    success: function (data) {
                        if (data.state != 'ok') {
                            $('#widget-alert').showAlert({
                                message: data.result,
                                level: 'danger'
                            });
                        }
                        else {
                            var vars = getUrlVars()
                            var url = 'index.php?'
                            delete vars['id']
                            delete vars['saveSuccessFull']
                            delete vars['removeSuccessFull']
                            vars['saveSuccessFull'] = "1";
                            url = getCustomParamUrl(url, vars);
                            modifyWithoutSave = false
                            jeedomUtils.loadPage(url)
                        }
                    }
                }));
            }
            else {
                $('#widget-alert').showAlert({ message: 'Rien à sauvergarder', level: 'warning' });
            }
        });
    } catch (error) {
        $('#widget-alert').showAlert({ message: error, level: 'danger' });
        console.error(error);
    }
}

function fillWidgetsList() {
    items = [];
    widgetsList.widgets.forEach(item => {
        var hasGenericTypeDefined = false
        item.options.forEach(o => {
            if (o.hasOwnProperty('generic_type')) {
                hasGenericTypeDefined = true;
                return;
            }
        })
        if (hasGenericTypeDefined === true) {
            items.push('<option value="' + item.type + '">' + item.name + '</option>');
        }
    });
    $("#widgetBulkList-select").html(items.join(""));
}

$("#table_widgets").delegate('.listCmdInfo', 'click', function () {
    var el = $(this).closest('div').find('.cmdAttrib');
    var type = el.attr('cmdType');
    var subtype = el.attr('cmdSubType') != '' ? el.attr('cmdSubType') : null;

    jeedom.cmd.getSelectModal({ cmd: { type: type, subType: subtype } }, function (result) {
        el.val(result.human);
        el.change()
    });
});

fillWidgetsList();
refreshAddWidgetBulk();

$('body').off('click', '#checkAll').on('click', '#checkAll', function () {
    $('input:checkbox.checkboxBulk').not(this).prop('checked', this.checked);
});

$('body').off('click', '.checkboxBulk').on('click', '.checkboxBulk', function () {
    $('#checkAll').not(this).prop('checked', false);
});

$("body").on('change', '.needRefresh', function () {
    var id = $(this).attr('id');
    var row = $(this).closest('.widgetLine').attr('data-index');
    var cmd = $(this).val();

    if (cmd == '') {

        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('value', '');
        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdId', '');
        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('title', '');
        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('minValue', '');
        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('maxValue', '');
        $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('unit', '');
    }
    else {
        getCmd({
            id: cmd,
            error: function (error) {
                $('#widget-alert').showAlert({ message: error.result, level: 'danger' });
            },
            success: function (data) {
                $('#widget-alert').hideAlert();

                var configtype = $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdType');
                var configsubtype = $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdSubType');

                if (configtype != undefined && configtype != data.result.type) {
                    $('#widget-alert').showAlert({
                        message: "La commande " + cmd + " n'est pas de type '" + configtype + "'", level: 'danger'
                    });
                    return;
                }
                if (configsubtype != "" && configsubtype != data.result.subType) {
                    $('#widget-alert').showAlert({
                        message: "La commande " + cmd + " n'a pas le sous-type '" + configsubtype + "'", level: 'danger'
                    });
                    return;
                }
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('value', '#' + data.result.humanName + '#');
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdId', data.result.id);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('title', `#${data.result.humanName}# -- id : ${data.result.id}`);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdType', data.result.type);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('cmdSubType', data.result.subType);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('minValue', data.result.minValue);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('maxValue', data.result.maxValue);
                $('.widgetLine[data-index=' + row + ']').find(' #' + id).attr('unit', data.result.unit);
            }
        });
    }
});


$('body').off('click', '.removeParent').on('click', '.removeParent', function () {
    $(this).parent().remove();
});