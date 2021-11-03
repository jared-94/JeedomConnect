function refreshLine() {
    $('.widgetLine').each((i, el) => {
        var type = $('#widgetBulkList-select option:selected').val();
        var room = $(el).find('select[data-l1key=room] option:selected').val();
        var name = $(el).find('input[data-l1key=name]').val();

        var exist = allWidgetsDetail.some(e => {
            if (e.room === undefined) e.room = 'none';
            return e.type == type && e.name == name && e.room == room
        });

        if (exist) {
            $(el).attr('style', 'background-color: #D75951 !important');
            $(el).find('.checkboxBulk').prop("checked", false);
            $('#checkAll').prop("checked", false);
        }

    });
}

function refreshAddWidgetBulk() {
    widgetsCat = [];
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
                    Object.entries(data.result).forEach(eqLogic => {
                        tbody += `<tr class="widgetLine" data-index="${indexRow}"><td>`
                        tbody += '<input type="checkbox" class="checkbox-inline checkboxBulk" checked/>';
                        tbody += '</td>'
                        tbody += '<td>'
                        tbody += '<select class="room-input cmdAttrib" data-l1key="room" id="room-input" room="' + eqLogic[1].room + '">'
                        tbody += '<option value="none">Sélection  d\'une pièce</option>'
                        tbody += roomListOptions
                        tbody += '</select>'
                        tbody += '</td>'
                        isDisabled = isJcExpert ? '' : 'disabled';
                        widget.options.filter(o => o.required === true || (o.hasOwnProperty('generic_type') && o.generic_type != '')).forEach(option => {
                            var eqLogicInfo = ''
                            tbody += '<td>'
                            if (option.category == "cmd") {
                                var cmd = undefined
                                if (option.hasOwnProperty('generic_type')) {
                                    var cmd = eqLogic[1].cmds.find(c => c.generic_type == option.generic_type)
                                }
                                if (cmd == undefined) {
                                    cmd = {}
                                    cmd.cmdid = ''
                                    cmd.humanName = ''
                                    cmd.cmdtype = option.type
                                    cmd.cmdsubtype = option.subtype || ''
                                    cmd.minValue = option.minValue || ''
                                    cmd.maxValue = option.maxValue || ''
                                    cmd.unit = option.unit || ''
                                }
                                eqLogicInfo += `<div class="input-group"><input class='input-sm form-control roundedLeft cmdAttrib needRefresh' data-l1key="${option.id}" id='${option.id}-input' title='${cmd.humanName} -- id : ${cmd.cmdid}' value='${cmd.humanName}' cmdId='${cmd.cmdid}' cmdType='${cmd.cmdtype}' cmdSubType='${cmd.cmdsubtype}' minValue='${cmd.minValue}' maxValue='${cmd.maxValue}' unit='${cmd.unit}' ${isDisabled}>`
                                eqLogicInfo += '<span class="input-group-btn">'
                                eqLogicInfo += '<a class="btn btn-sm btn-default roundedRight listCmdInfo tooltips" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>'
                                eqLogicInfo += '</span>'
                                eqLogicInfo += '</div>'
                            } else if (option.category == "string") {
                                var value = ''
                                if (option.id == 'name') {
                                    var value = eqLogic[1].name
                                }
                                type = (option.subtype != undefined) ? option.subtype : 'text';
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
                                eqLogic[1].cmds.filter(c => c.generic_type == option.generic_type).forEach(c => {
                                    eqLogicInfo += `<div class="input-group"><input class='input-sm form-control roundedLeft cmdListAttr' data-l1key="${option.id}" id='${option.id}-input' title='${c.humanName} -- id : ${c.cmdid}' value='${c.humanName}' cmdId='${c.cmdid}' cmdName='${c.name}' cmdType='${c.cmdtype}' cmdSubType='${c.cmdsubtype}' minValue='${c.minValue}' maxValue='${c.maxValue}' unit='${c.unit}' ${isDisabled} >`
                                    eqLogicInfo += '<span class="input-group-btn">'
                                    eqLogicInfo += '<a class="btn btn-sm btn-default roundedRight listCmdInfo tooltips" title="Rechercher une commande"><i class="fas fa-list-alt"></i></a>'
                                    eqLogicInfo += '</span>'
                                    eqLogicInfo += '<i class="fas fa-minus-circle pull-right cursor removeParent"></i>'
                                    eqLogicInfo += '</div>'
                                });
                                eqLogicInfo += '</div>';


                            }
                            tbody += eqLogicInfo
                            tbody += '</td>'
                        });
                        tbody += '</tr>'
                        indexRow++;
                    });

                    var actionSecureHtml = `<select style="max-width: 120px;">
                    <option value="none">Aucun</option><option value="confirm">Confirmation</option>
                    <option value="secure">Empreinte</option><option value="pwd">Mot de passe</option>
                    </select><br/>`;
                    var thead = '<thead><tr>'
                    thead += '<th><input type="checkbox" class="checkbox-inline" name="checkboxBulk" id="checkAll" checked/></th>'
                    thead += '<th>Pièce</th>'
                    widget.options.filter(o => o.required === true || (o.hasOwnProperty('generic_type') && o.generic_type != '')).forEach(option => {
                        var required = (option.required) ? " required" : "";
                        var actionSecure = (option.type == 'action') ? actionSecureHtml : "";
                        thead += `<th class='${option.id}${required}'>` + actionSecure + option.name + '</th > '
                    });
                    thead += '</tr></thead>'
                    $("#table_widgets").html(thead + tbody);

                    $("#room-input > option").each(function () {
                        if ($(this).text().includes($(this).parent().attr('room'))) {
                            $(this).prop('selected', true);
                            return;
                        }
                    });

                    refreshLine();
                }
            }
        }
    });



    // //Enable
    // option = `<li><div class='form-group'>
    //     <label class='col-xs-3 '>Actif</label>
    //     <div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="enable-input" checked></div></div></div></li>`;
    // items.push(option);

    // //Room
    // option = `<li><div class='form-group'>
    //   <label class='col-xs-3 ${type == 'room' ? 'required' : ''}'>Pièce</label>
    //   <div class='col-xs-9'><div class='input-group'><select style="width:340px;" id="room-input" value=''>
    //   <option value="none">Sélection  d'une pièce</option>`;
    // option += roomListOptions;

    // if (type == 'room') {
    //     option += `<option value="global">Global</option>`;
    // }
    // option += `</select></div></div></div></li>`;
    // items.push(option);

    // widget.options.forEach(option => {
    //     var required = (option.required) ? "required" : "";
    //     var description = (option.description == undefined) ? '' : option.description;
    //     var curOption = `<li><div class='form-group' id="${option.id}-div">
    //   <label class='col-xs-3  ${required}'   id="${option.id}-label">${option.name}</label>
    //   <div class='col-xs-9' id="${option.id}-div-right">
    //   <div class="description">${description}</div>`;

    //     if (option.category == "cmd") {
    //         isDisabled = isJcExpert ? '' : 'disabled';
    //         curOption += `<table><tr class="cmd">
    //           <td>
    //             <input class='input-sm form-control roundedLeft' style="width:250px;" id="${option.id}-input" value='' cmdId='' cmdType='' cmdSubType='' ${isDisabled}>
    //             <td>
    //                <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une commande' onclick="selectCmd('${option.id}', '${option.type}', '${option.subtype}', '${option.value}');">
    //                   <i class='fas fa-list-alt'></i></a>
    //                   </td>
    //           </td>
    //           <td>
    //               <i class="mdi mdi-minus-circle" id="${option.id}-remove"
    //                     style="color:rgb(185, 58, 62);font-size:16px;margin-right:10px;display:${option.required ? 'none' : ''};" aria-hidden="true" onclick="removeCmd('${option.id}')"></i>
    //           </td>
    //           <td>
    //                   <div style="width:50px;margin-left:5px; display:none;" id="invert-div-${option.id}">
    //                   <i class='fa fa-sync' title="Inverser"></i><input type="checkbox" style="margin-left:5px;" id="invert-${option.id}"></div>
    //           </td>
    //           <td>
    //                 <div style="width:50px;margin-left:5px; display:none;" id="confirm-div-${option.id}">
    //                 <i class='fa fa-question' title="Demander confirmation"></i><input type="checkbox" style="margin-left:5px;" id="confirm-${option.id}"></div>
    //           </td><td>
    //                   <div style="width:50px; display:none;" id="secure-div-${option.id}">
    //                   <i class='fa fa-fingerprint' title="Sécuriser avec empreinte digitale"></i><input type="checkbox" style="margin-left:5px;" id="secure-${option.id}"  ></div>
    //           </td>
    //           <td>
    //                   <div style="width:50px; display:none;" id="pwd-div-${option.id}">
    //                   <i class='mdi mdi-numeric' title="Sécuriser avec un code"></i><input type="checkbox" style="margin-left:5px;" id="pwd-${option.id}"  ></div>
    //           </td>
    //           <td>
    //               <input type="number" style="width:50px; display:none;" id="${option.id}-minInput" value='' placeholder="Min">
    //           </td>
    //           <td>
    //               <input type="number" style="width:50px;margin-left:5px; display:none;" id="${option.id}-maxInput" value='' placeholder="Max">
    //           </td>
    //           <td>
    //               <input type="number" step="0.1" style="width:50px;margin-left:5px; display:none;" id="${option.id}-stepInput" value='1' placeholder="Step">
    //           </td>
    //           <td>
    //               <input style="width:50px; margin-left:5px; display:none;" id="${option.id}-unitInput" value='' placeholder="Unité">
    //           </td></tr></table>
    //                   `;

    //         curOption += "</div></div></li>";

    //     } else if (option.category == "string") {

    //         type = (option.subtype != undefined) ? option.subtype : 'text';
    //         curOption += `<div class='input-group'>
    //       <div style="display:flex"><input type="${type}" style="width:340px;" id="${option.id}-input" value=''>`;
    //         if (option.id == 'name') {
    //             curOption += `
    //             <div class="dropdown" id="name-select">
    //             <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
    //             <i class="fas fa-plus-square"></i> </a>
    //             <ul class="dropdown-menu infos-select" input="${option.id}-input">`;
    //             if (widget.variables) {
    //                 widget.variables.forEach(v => {
    //                     curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
    //                 });
    //             }
    //             curOption += `</ul></div></div>`
    //         }
    //         curOption += `</div></div></div></li>`;
    //     } else if (option.category == "binary") {
    //         curOption += `<div class='input-group'><input type="checkbox" style="width:150px;" id="${option.id}-input"></div>
    //        </div></div></li>`;
    //     } else if (option.category == "stringList") {
    //         curOption += `<div class='input-group'><select style="width:340px;" id="${option.id}-input" onchange="subtitleSelected();">`;
    //         if (!required) {
    //             curOption += `<option value="none">Aucun</option>`;
    //         }
    //         option.choices.forEach(item => {
    //             curOption += `<option value="${item.id}">${item.name}</option>`;
    //         })
    //         if (option.id == "subtitle") {
    //             curOption += `<option value="custom">Personnalisé</option></select>`;
    //             curOption += `<div style="display:flex">
    //                     <input style="width:340px; margin-top:5px; display:none;" id="subtitle-input-value" value='none'>`;

    //             curOption += `
    //           <div class="dropdown" id="subtitle-select" style=" display:none;">
    //           <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
    //           <i class="fas fa-plus-square"></i> </a>
    //           <ul class="dropdown-menu infos-select" input="subtitle-input-value">`;
    //             if (widget.variables) {
    //                 widget.variables.forEach(v => {
    //                     curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
    //                 });
    //             }
    //             curOption += `</ul></div></div>`
    //         } else {
    //             curOption += '</select>';
    //         }
    //         curOption += `</div></div></div></li>`;
    //     } else if (option.category == "img") {
    //         curOption += `
    //             <a class="btn btn-success roundedRight" onclick="imagePicker(this)"><i class="fas fa-check-square">
    //             </i> Choisir </a>
    //             <a data-id="icon-div-${option.id}" id="icon-div-${option.id}" onclick="removeImage(this)"></a>
    //             </div></div></li>`;


    //     } else if (option.category == "widgets") {
    //         var widgetChoices = [];
    //         widgetsList.widgets.forEach(item => {
    //             if (option.whiteList !== undefined) {
    //                 if (option.whiteList.includes(item.type)) {
    //                     widgetChoices.push(item.type);
    //                 }
    //             } else if (option.blackList !== undefined) {
    //                 if (!option.blackList.includes(item.type)) {
    //                     widgetChoices.push(item.type);
    //                 }
    //             } else {
    //                 widgetChoices.push(item.type);
    //             }
    //         })
    //         curOption += `<span class="input-group-btn">
    //             <a class="btn btn-default roundedRight" onclick="addWidgetOption('${widgetChoices.join(".")}')"><i class="fas fa-plus-square">
    //             </i> Ajouter</a></span><div id="widget-option"></div>`;
    //         curOption += `</div></div></li>`;
    //     } else if (option.category == "cmdList") {
    //         curOption += `<span class="input-group-btn">
    //             <a class="btn btn-default roundedRight" onclick="addCmdOption('${JSON.stringify(option.options).replace(/"/g, '&quot;')}')"><i class="fas fa-plus-square">
    //             </i> Ajouter</a></span><div id="cmdList-option" data-cmd-options="${JSON.stringify(option.options).replace(/"/g, '&quot;')}" style='margin-left:-150px;'></div>`;
    //         curOption += `</div></div></li>`;
    //     } else if (option.category == "ifImgs") {
    //         curOption += `<span class="input-group-btn">
    //             <a class="btn btn-default roundedRight" onclick="addImgOption('widget')"><i class="fas fa-plus-square">
    //             </i> Ajouter</a></span><div id="imgList-option"></div>`;
    //         curOption += `</div></div></li>`;
    //     } else if (option.category == "scenario") {
    //         curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' scId='' disabled>
    //   <span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir un scenario' onclick="selectScenario('${option.id}');">
    //   <i class='fas fa-list-alt'></i></a></span></div>
    //     <div id='optionScenario' style='display:none;'>
    //       <div class="input-group input-group-sm" style="width: 100%">
    //           <span class="input-group-addon roundedLeft" style="width: 100px">Tags</span>
    //           <input style="width:100%;" class='input-sm form-control roundedRight title' type="string" id="tags-scenario-input" value="" placeholder="Si nécessaire indiquez des tags" />
    //       </div>
    //     </div>
    //   </div>
    //   </div></li>`;
    //     } else if (option.category == "choicesList") {
    //         curOption += `<div class='input-group'>`;

    //         option.choices.forEach(v => {
    //             curOption += `<label class="checkbox-inline">
    //     <input type="checkbox" class="eqLogicAttr" id="${v.id}-jc-checkbox" />${v.text}
    //     </label>`;
    //         });

    //         curOption += `</div></div></div></li>`;
    //     } else {
    //         return;
    //     }

    //     items.push(curOption);

    // });

    // //Details access
    // option = `<li><div class='form-group'>
    //   <label class='col-xs-3 '>Bloquer vue détails</label>
    //   <div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="blockDetail-input" ></div></div></div></li>`;
    // items.push(option);

    // $("#widgetOptions").html(items.join(""));
    // loadSortable('all');
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
                }
                // else if (option.category == "scenario") {

                //     if ($("#" + option.id + "-input").attr('scId') == '' & option.required) {
                //         throw 'La commande ' + option.name + ' est obligatoire';
                //     }

                //     if ($("#" + option.id + "-input").attr('scId') != '') {
                //         result[option.id] = $("#" + option.id + "-input").attr('scId');

                //         result['options'] = {};
                //         result['options']['scenario_id'] = $("#" + option.id + "-input").attr('scId');
                //         result['options']['action'] = 'start';
                //         if ($('#tags-scenario-input').val() != '') {
                //             getCmdIdFromHumanName({ alert: '#widget-alert', stringData: $('#tags-scenario-input').val() }, function (data, _params) {
                //                 result['options']['tags'] = data;
                //             });
                //         }
                //     }
                // }
                else if (option.category == "string") {
                    if ($(this).find("#" + option.id + "-input").val() == '' & option.required) {
                        throw 'La commande ' + option.name + ' est obligatoire';
                    }
                    result[option.id] = parseString($(this).find("#" + option.id + "-input").val(), infoCmd);
                }
                // else if (option.category == "binary") {
                //     result[option.id] = $("#" + option.id + "-input").is(':checked');
                // }
                // else if (option.category == "color") {
                //     result[option.id] = $("#" + option.id + "-input").val();
                // }
                // else if (option.category == "stringList") {
                //     if ($("#" + option.id + "-input").val() == 'none' & option.required) {
                //         throw 'La commande ' + option.name + ' est obligatoire';
                //     }

                //     if ($("#" + option.id + "-input").val() != 'none') {
                //         result[option.id] = $("#" + option.id + "-input").val();
                //     }
                //     else {
                //         result[option.id] = undefined;
                //     }
                // }
                else if (option.category == "cmdList") {
                    var cmdList = [];
                    var index = 0;

                    $(this).find('.jcCmdList .cmdListAttr').each(function () {
                        cmdList.push({ id: $(this).attr('cmdId'), name: $(this).attr('cmdName'), index: index });
                        index++;
                    });
                    result[option.id] = cmdList;
                }
                //     if (cmdCat.length == 0 & option.required) {
                //         throw 'La commande ' + option.name + ' est obligatoire';
                //     }

                //     // ---- Start cmdCat.forEach
                //     cmdCat.forEach(item => {
                //         if (option.options.hasImage | option.options.hasIcon) {
                //             item.image = htmlToIcon($('.jcCmdListOptions[data-id="icon-' + item.id + '"][data-index="' + item.index + '"]').children().first());
                //             if (item.image == {}) { delete item.image; }
                //         }
                //         if (option.options.type == 'action') {
                //             item['confirm'] = $('.jcCmdListOptions[data-id="confirm-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
                //             item['secure'] = $('.jcCmdListOptions[data-id="secure-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
                //             item['pwd'] = $('.jcCmdListOptions[data-id="pwd-' + item.id + '"][data-index="' + item.index + '"]').is(':checked') || undefined;
                //             item['name'] = $('.jcCmdListOptions[data-id="custom-name-' + item.id + '"][data-index="' + item.index + '"]').val() || "";

                //             if (item.subtype != undefined && item.subtype != 'other') {
                //                 var optionsForSubtype = { 'message': ['title', 'message'], 'slider': ['slider'], 'color': ['color'] };

                //                 item['options'] = {};

                //                 if (item.subtype == 'select') {
                //                     item['options']['select'] = $('.jcCmdListOptions[data-id="select-' + item.id + '"][data-index="' + item.index + '"] option:selected').val();
                //                 }
                //                 else {

                //                     var currentArray = optionsForSubtype[item.subtype];
                //                     currentArray.forEach(key => {
                //                         var tmpData = $('.jcCmdListOptions[data-id="' + key + '-' + item.id + '"][data-index="' + item.index + '"]').val();
                //                         if (tmpData != '') {
                //                             getCmdIdFromHumanName({ alert: '#widget-alert', stringData: tmpData, subtype: key }, function (result, _params) {
                //                                 item['options'][_params.subtype] = result;
                //                             });
                //                         }
                //                         else {
                //                             item['options'][key] = '';
                //                         }
                //                     });

                //                 }

                //             }
                //         }
                //     });
                //     // ---- END cmdCat.forEach
                //     result[option.id] = cmdCat;

                // }
                // else if (option.category == "ifImgs") {
                //     if (imgCat.length == 0 & option.required) {
                //         throw 'La commande ' + option.name + ' est obligatoire';
                //     }

                //     imgCat.forEach(item => {
                //         item.image = htmlToIcon($("#icon-div-" + item.index).children().first());
                //         getCmdIdFromHumanName({ alert: '#widget-alert', stringData: $("#imglist-cond-" + item.index).val() }, function (result, _params) {
                //             item.condition = result;
                //         });
                //     });

                //     result[option.id] = imgCat;
                // }
                // else if (option.category == "img") {
                //     let icon = htmlToIcon($("#icon-div-" + option.id).children().first());
                //     if (icon.source == undefined & option.required) {
                //         throw "L'image est obligatoire";
                //     }
                //     result[option.id] = icon.source != undefined ? icon : undefined;
                // }
                // else if (option.category == "choicesList") {
                //     option.choices.forEach(v => {
                //         result[v.id] = $("#" + v.id + "-jc-checkbox").prop('checked');
                //     });
                // }
            });

            // ----- END forEach ----

            result.type = $("#widgetBulkList-select").val();
            widgetType = $("#widgetBulkList-select").val();
            // result.blockDetail = $("#blockDetail-input").is(':checked');

            // widgetEnable = $('#enable-input').is(":checked");
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
                            loadPage(url)
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