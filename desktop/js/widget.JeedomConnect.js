
/*********** FOR EXPERT MODE *********/

$("#widgetOptions").on('focusin', '.jcCmdListOptionsCommand', function () {
    $(this).attr('data-value-focusin', $(this).val());
})

$("#widgetOptions").on('focusout', '.jcCmdListOptionsCommand', function () {
    var previousData = $(this).attr('data-value-focusin');
    if (previousData != $(this).val()) {

        var elt = $(this);
        var currentId = elt.attr('data-cmd-id');
        var currentIndex = elt.attr('data-index');

        getCmd({
            id: elt.val(),
            error: function (error) {
                $('#widget-alert').showAlert({ message: error.result, level: 'danger' });
            },
            success: function (data) {
                $('#widget-alert').hideAlert();

                // remove options of the previous cmd
                elt.parent().siblings().each(function () {
                    if ($(this).find('.jcCmdListOptions').length !== 0) {
                        $(this).remove();
                    }
                });

                // create options for the new cmd selected
                newItem = { id: data.result.id, subtype: data.result.subType, index: currentIndex, name: data.result.name };
                divListOption = getCmdOptions(newItem);
                elt.parent().parent().append(divListOption);

                // update cmdCat
                cmdCat.forEach(item => {
                    if (item.id == currentId && item.index == currentIndex) {
                        item.id = data.result.id;
                        item.name = data.result.humanName;
                        item.subtype = data.result.subType;
                    }
                })
                saveCmdList();

            }
        });

    }

    $(this).attr('data-value-focusin', '');
})
/**----------- END EXPERT MODE ---------*/

function getWidgetModal(_options, _callback) {
    // console.log("getWidgetModal option recues : ", _options)
    if (!isset(_options)) {
        return;
    }
    $("#widgetModal").dialog('destroy').remove();
    if ($("#widgetModal").length == 0) {
        $('body').append('<div id="widgetModal"></div>');

        $("#widgetModal").dialog({
            title: _options.title,
            closeText: '',
            autoOpen: false,
            modal: true,
            width: 1250,
            height: 0.8 * $(window).height()
        });
        jQuery.ajaxSetup({
            async: false
        });
        $('#widgetModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.widgetModal.JeedomConnect');
        jQuery.ajaxSetup({
            async: true
        });
    }
    setWidgetModalData(_options);

    if (_options.inEquipments !== undefined && _options.inEquipments != '') {

        $('#widgetInclusion').html('<span id="widgetExistInEquipement" style="display:none">' + _options.inEquipments.join(', ') + '</span>');
        $('#widgetInclusion').append('Utilisé dans :<br/><ul>');

        $.each(_options.inEquipments, function (index, value) {
            $("#widgetInclusion").append('<li>' + value + '</li>');
        });
        $("#widgetInclusion").append('</ul>');
        $('#widgetInclusion').css('display', 'block');
    }

    if (_options.removeAction != true) {
        $('.widgetMenu .removeWidget').hide();
        $('.widgetMenu .hideWidget').addClass('roundedRight');
    }

    if ($('#widgetOptions').attr('widget-id') == undefined || $('#widgetOptions').attr('widget-id') == '' || !(_options.duplicate)) {
        $('.widgetMenu .duplicateWidget').hide();
        $('.widgetMenu .saveWidget').addClass('roundedLeft');
    }
    else {
        $('.widgetMenu .duplicateWidget').show();
        $('.widgetMenu .saveWidget').removeClass('roundedLeft');
    }

    if ($('#widgetOptions').attr('widget-id') != undefined && $('#widgetOptions').attr('widget-id') != '') {
        $('.autoFillWidgetCmds').hide();
    }

    if (_options.exit == true) {
        $('.widgetMenu .saveWidget').attr('exit-attr', 'true');
    }

    $("#widgetModal").dialog({ title: _options.title });
    $('#widgetModal').dialog('open');

};



function setWidgetModalData(options) {
    $('#summaryModal').dialog('destroy').remove();

    refreshAddWidgets();

    if (options.widget !== undefined) {
        $('#widgetsList-select option[value="' + options.widget.type + '"]').prop('selected', true);
        refreshAddWidgets();
        //Enable
        var enable = options.widget.enable ? "checked" : "";
        $("#enable-input").prop('checked', enable);
        var blockDetail = options.widget.blockDetail ? "checked" : "";
        $("#blockDetail-input").prop('checked', blockDetail);

        //Room
        if (options.widget.room !== undefined) {
            $('#room-input option[value="' + options.widget.room + '"]').prop('selected', true);
        }

        moreInfos = options.widget.moreInfos || [];
        refreshMoreInfos();

        $("#widgetOptions").attr('widget-id', options.eqId ?? '');

        var widgetConfig = widgetsList.widgets.find(i => i.type == options.widget.type);
        //  console.log("widgetsList => ", widgetsList) ;
        //  console.log("widgetConfig => ", widgetConfig) ;
        widgetConfig.options.forEach(option => {
            if (option.category == "string" & options.widget[option.id] !== undefined) {
                $("#" + option.id + "-input").val(options.widget[option.id]);
            } else if (option.category == "binary" & options.widget[option.id] !== undefined) {
                $("#" + option.id + "-input").prop('checked', options.widget[option.id] ? "checked" : "");
            } else if (option.category == "color" & options.widget[option.id] !== undefined) {
                $("#" + option.id + "-input").val(options.widget[option.id]);
                $("#" + option.id + "-picker").val(options.widget[option.id]);
            } else if (option.category == "cmd" & options.widget[option.id] !== undefined) {
                $("#" + option.id + "-input").attr('cmdId', options.widget[option.id].id);
                cmdHumanName = getHumanName(options.widget[option.id].id)
                if (cmdHumanName != '') {
                    $("#" + option.id + "-input").val(cmdHumanName);
                    $("#" + option.id + "-input").attr('title', cmdHumanName);
                    refreshImgListOption();
                    refreshInfoSelect();
                }
                else {
                    $("#" + option.id + "-input").val('#' + options.widget[option.id].id + '#');
                }
                $("#" + option.id + "-input").attr('cmdType', options.widget[option.id].type);
                $("#" + option.id + "-input").attr('cmdSubType', options.widget[option.id].subType);
                if (options.widget[option.id].type == 'action') {
                    $("#confirm-div-" + option.id).css('display', '');
                    $("#secure-div-" + option.id).css('display', '');
                    $("#pwd-div-" + option.id).css('display', '');
                    $("#confirm-" + option.id).prop('checked', options.widget[option.id].confirm ? "checked" : "");
                    $("#secure-" + option.id).prop('checked', options.widget[option.id].secure ? "checked" : "");
                    $("#pwd-" + option.id).prop('checked', options.widget[option.id].pwd ? "checked" : "");
                } else {
                    $("#confirm-div-" + option.id).css('display', 'none');
                    $("#secure-div-" + option.id).css('display', 'none');
                    $("#pwd-div-" + option.id).css('display', 'none');
                }
                if (options.widget[option.id].subType == 'slider' | options.widget[option.id].subType == 'numeric') {
                    $("#" + option.id + "-minInput").css('display', '');
                    $("#" + option.id + "-maxInput").css('display', '');
                    $("#" + option.id + "-minInput").val(options.widget[option.id].minValue);
                    $("#" + option.id + "-maxInput").val(options.widget[option.id].maxValue);
                } else {
                    $("#" + option.id + "-minInput").css('display', 'none');
                    $("#" + option.id + "-maxInput").css('display', 'none');
                }
                if (options.widget[option.id].subType == 'binary' | options.widget[option.id].subType == 'numeric') {
                    $("#invert-div-" + option.id).css('display', '');
                    $("#invert-" + option.id).prop('checked', options.widget[option.id].invert ? "checked" : "");
                } else {
                    $("#invert-div-" + option.id).css('display', 'none');
                }
                if (options.widget[option.id].subType == 'numeric') {
                    $("#" + option.id + "-unitInput").css('display', '');
                    $("#" + option.id + "-unitInput").val(options.widget[option.id].unit);
                } else {
                    $("#" + option.id + "-unitInput").css('display', 'none');
                }
                if (options.widget[option.id].subType == 'slider') {
                    $("#" + option.id + "-stepInput").css('display', '');
                    $("#" + option.id + "-stepInput").val(options.widget[option.id].step);
                }
                else {
                    $("#" + option.id + "-stepInput").css('display', 'none');
                }

            } else if (option.category == "scenario" & options.widget[option.id] !== undefined) {
                getScenarioHumanName({
                    id: options.widget[option.id],
                    error: function (error) { },
                    success: function (data) {
                        data.forEach(sc => {
                            if (sc['id'] == options.widget[option.id]) {
                                $("#" + option.id + "-input").attr('scId', options.widget[option.id]);
                                $("#" + option.id + "-input").val(sc['humanName']);
                            }
                        })
                    }
                });
                $('#optionScenario').css('display', 'block');

                if (options.widget['options'] !== undefined &&
                    options.widget['options']['tags'] !== undefined &&
                    options.widget['options']['tags'] != '') {
                    getHumanNameFromCmdId({ alert: '#widget-alert', cmdIdData: options.widget['options']['tags'] }, function (result, _params) {
                        $('#tags-scenario-input').val(result);
                    });
                }
                if (options.widget['options'] !== undefined) {
                    $("#confirm-" + option.id).prop('checked', options.widget['options'].confirm ? "checked" : "");
                    $("#secure-" + option.id).prop('checked', options.widget['options'].secure ? "checked" : "");
                    $("#pwd-" + option.id).prop('checked', options.widget['options'].pwd ? "checked" : "");
                }

            } else if (option.category == "stringList" & options.widget[option.id] !== undefined) {
                var selectedChoice = option.choices.find(s => s.id == options.widget[option.id]);
                if (selectedChoice !== undefined) {
                    $('#' + option.id + '-input option[value="' + options.widget[option.id] + '"]').prop('selected', true);
                    if (option.id == "subtitle") {
                        $("#subtitle-input-value").val(selectedChoice.id)
                    }
                } else if (option.id == "subtitle" & options.widget.subtitle !== undefined) {
                    $('#subtitle-input option[value="custom"]').prop('selected', true);
                    $("#subtitle-input-value").val(options.widget.subtitle)
                    $("#subtitle-input-value").css('display', 'block');
                    $("#subtitle-select").show();
                }
            } else if (option.category == "widgets" & options.widget[option.id] !== undefined) {
                widgetsCat = options.widget[option.id];
                refreshWidgetOption();
            } else if (option.category == "cmdList" & options.widget[option.id] !== undefined) {
                cmdCat = options.widget[option.id];
                refreshCmdListOption(JSON.stringify(option.options));
            } else if (option.category == "ifImgs" & options.widget[option.id] !== undefined) {
                imgCat = options.widget[option.id];
                refreshImgListOption();
            } else if (option.category == "choicesList") {
                option.choices.forEach(v => {
                    $("#" + v.id + "-jc-checkbox").prop('checked', options.widget[v.id] ? "checked" : "");
                });
            } else if (option.category == "img" & options.widget[option.id] !== undefined) {
                $("#icon-div-" + option.id).html(iconToHtml(options.widget[option.id]));
            }
        });
    }
    refreshStrings();
    loadSortable('all');
}


$('#widgetsList-select').change(function () {
    refreshAddWidgets();
})


function refreshAddWidgets() {
    widgetsCat = [];
    cmdCat = [];
    imgCat = [];
    moreInfos = [];
    var type = $("#widgetsList-select").val();
    var widget = widgetsList.widgets.find(i => i.type == type);
    showAutoFillWidgetCmds();

    (type == 'jc') ? $('.searchForJCeq').show() : $('.searchForJCeq').hide();

    $("#widgetImg").attr("src", "plugins/JeedomConnect/data/img/" + widget.img);

    $("#widgetDescription").html(widget.description);

    if (widget.variables) {
        let varDescr = `Variables disponibles : <ul style="padding-left: 15px;">`;
        widget.variables.forEach(v => {
            varDescr += `<li>#${v.name}# : ${v.descr}</li>`;
        });
        varDescr += '</ul>';
        $("#widgetVariables").html(varDescr);
        $("#widgetVariables").show();
    } else {
        $("#widgetVariables").hide();
    }

    var items = [];

    //Enable
    option = `<li><div class='form-group'>
  	<label class='col-xs-3 '>Actif</label>
  	<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="enable-input" checked></div></div></div></li>`;
    items.push(option);

    //Room
    option = `<li><div class='form-group'>
    <label class='col-xs-3 ${type == 'room' ? 'required' : ''}'>Pièce</label>
    <div class='col-xs-9'><div class='input-group'><select style="width:340px;" id="room-input" value=''>
    <option value="none">Sélection  d'une pièce</option>`;
    option += roomListOptions;

    if (type == 'room') {
        option += `<option value="global">Global</option>`;
    }
    option += `</select></div></div></div></li>`;
    items.push(option);

    widget.options.forEach(option => {
        var required = (option.required) ? "required" : "";
        var description = (option.description == undefined) ? '' : option.description;
        var curOption = `<li><div class='form-group' id="${option.id}-div">
    <label class='col-xs-3  ${required}'   id="${option.id}-label">${option.name}</label>
    <div class='col-xs-9' id="${option.id}-div-right">
    <div class="description">${description}</div>`;

        if (option.category == "cmd") {
            isDisabled = isJcExpert ? '' : 'disabled';
            curOption += `<table><tr class="cmd">
            <td>
              <input class='input-sm form-control roundedLeft needRefresh' style="width:250px;" id="${option.id}-input" value='' cmdId='' cmdType='' cmdSubType='' ${isDisabled} configtype='${option.type}' configsubtype='${option.subtype}' configlink='${option.value}'>
              <td>
                 <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une commande' onclick="selectCmd('${option.id}', '${option.type}', '${option.subtype}', '${option.value}');">
                    <i class='fas fa-list-alt'></i></a>
                    </td>
            </td>
            <td>
                <i class="mdi mdi-minus-circle removeCmd" id="${option.id}-remove"
                      style="color:rgb(185, 58, 62);font-size:16px;margin-right:10px;display:${option.required ? 'none' : 'block'};" aria-hidden="true" data-cmdid="${option.id}-input"></i>
            </td>
            <td>
                    <div style="width:50px;margin-left:5px; display:none;" id="invert-div-${option.id}">
                    <i class='fa fa-sync' title="Inverser"></i><input type="checkbox" style="margin-left:5px;" id="invert-${option.id}"></div>
            </td>
            <td>
                  <div style="width:50px;margin-left:5px; display:none;" id="confirm-div-${option.id}">
                  <i class='fa fa-question' title="Demander confirmation"></i><input type="checkbox" style="margin-left:5px;" id="confirm-${option.id}"></div>
            </td>
            <td>
                    <div style="width:50px; display:none;" id="secure-div-${option.id}">
                    <i class='fa fa-fingerprint' title="Sécuriser avec empreinte digitale"></i><input type="checkbox" style="margin-left:5px;" id="secure-${option.id}"  ></div>
            </td>
            <td>
                    <div style="width:50px; display:none;" id="pwd-div-${option.id}">
                    <i class='mdi mdi-numeric' title="Sécuriser avec un code"></i><input type="checkbox" style="margin-left:5px;" id="pwd-${option.id}"  ></div>
            </td>
            <td>
                <input type="number" style="width:50px; display:none;" id="${option.id}-minInput" value='' placeholder="Min">
            </td>
            <td>
                <input type="number" style="width:50px;margin-left:5px; display:none;" id="${option.id}-maxInput" value='' placeholder="Max">
            </td>
            <td>
                <input type="number" step="0.1" style="width:50px;margin-left:5px; display:none;" id="${option.id}-stepInput" value='1' placeholder="Step">
            </td>
            <td>
                <input style="width:50px; margin-left:5px; display:none;" id="${option.id}-unitInput" value='' placeholder="Unité">
            </td></tr></table>
                    `;

            curOption += "</div></div></li>";

        } else if (option.category == "string") {
            type = (option.subtype != undefined) ? option.subtype : 'text';
            if (option.subtype == "multiline") {
                curOption += `<div class='input-group'>
        <div style="display:flex"><textarea style="width:340px;" id="${option.id}-input" value=''></textarea>`;
            } else {
                curOption += `<div class='input-group'>
        <div style="display:flex"><input type="${type}" style="width:340px;" id="${option.id}-input" value=''>`;
            }

            if (option.id == 'name') {
                curOption += `
              <div class="dropdown" id="name-select">
              <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
              <i class="fas fa-plus-square"></i> </a>
              <ul class="dropdown-menu infos-select" input="${option.id}-input">`;
                if (widget.variables) {
                    widget.variables.forEach(v => {
                        curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
                    });
                }
                curOption += `</ul></div></div>`
            }
            curOption += `</div></div></div></li>`;
        } else if (option.category == "binary") {
            curOption += `<div class='input-group'><input type="checkbox" style="width:150px;" id="${option.id}-input"></div>
         </div></div></li>`;
        } else if (option.category == "color") {
            curOption += `<div class='input-group'><input style="width:200px;" id="${option.id}-input" class="inputJCColor" >
      <input type="color" id="${option.id}-picker"  class="changeJCColor">
      </div>
         </div></div></li>`;
        } else if (option.category == "stringList") {
            var classSub = (option.id == "subtitle") ? "subtitleSelected" : "";
            curOption += '<div class="input-group"><select style="width:340px;" id="${option.id}-input" class="' + classSub + '">';
            if (!required) {
                curOption += `<option value="none">Aucun</option>`;
            }
            option.choices.forEach(item => {
                curOption += `<option value="${item.id}">${item.name}</option>`;
            })
            if (option.id == "subtitle") {
                curOption += `<option value="custom">Personnalisé</option></select>`;
                curOption += `<div style="display:flex">
  					<textarea style="width:340px; margin-top:5px; display:none;" id="subtitle-input-value" value='none'></textarea>`;

                curOption += `
            <div class="dropdown" id="subtitle-select" style=" display:none;">
            <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
            <i class="fas fa-plus-square"></i> </a>
            <ul class="dropdown-menu infos-select" input="subtitle-input-value">`;
                if (widget.variables) {
                    widget.variables.forEach(v => {
                        curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
                    });
                }
                curOption += `</ul></div></div>`
            } else {
                curOption += '</select>';
            }
            curOption += `</div></div></div></li>`;
        } else if (option.category == "img") {
            curOption += `
              <a class="btn btn-success roundedRight imagePicker"><i class="fas fa-check-square">
              </i> Choisir </a>
              <a data-id="icon-div-${option.id}" id="icon-div-${option.id}" class="removeImage"></a>
              </div></div></li>`;


        } else if (option.category == "widgets") {
            var widgetChoices = [];
            widgetsList.widgets.forEach(item => {
                if (option.whiteList !== undefined) {
                    if (option.whiteList.includes(item.type)) {
                        widgetChoices.push(item.type);
                    }
                } else if (option.blackList !== undefined) {
                    if (!option.blackList.includes(item.type)) {
                        widgetChoices.push(item.type);
                    }
                } else {
                    widgetChoices.push(item.type);
                }
            })
            curOption += `<span class="input-group-btn">
              <a class="btn btn-default roundedRight" onclick="addWidgetOption('${widgetChoices.join(".")}')"><i class="fas fa-plus-square">
              </i> Ajouter</a></span><div id="widget-option"></div>`;
            curOption += `</div></div></li>`;
        } else if (option.category == "cmdList") {
            curOption += `<span class="input-group-btn">
              <a class="btn btn-default roundedRight" onclick="addCmdOption('${JSON.stringify(option.options).replace(/"/g, '&quot;')}')"><i class="fas fa-plus-square">
              </i> Ajouter</a></span><div id="cmdList-option" data-cmd-options="${JSON.stringify(option.options).replace(/"/g, '&quot;')}" style='margin-left:-150px;'></div>`;
            curOption += `</div></div></li>`;
        } else if (option.category == "ifImgs") {
            curOption += `<span class="input-group-btn">
              <a class="btn btn-default roundedRight" onclick="addImgOption('widget')"><i class="fas fa-plus-square">
              </i> Ajouter</a></span><div id="imgList-option"></div>`;
            curOption += `</div></div></li>`;
        } else if (option.category == "scenario") {
            curOption += `<div class='input-group'><input class='input-sm form-control roundedLeft' id="${option.id}-input" value='' scId='' disabled>
    <span class='input-group-btn'><a class='btn btn-default btn-sm cursor bt_selectTrigger selectScenario' tooltip='Choisir un scenario' data-related="${option.id}-input">
    <i class='fas fa-list-alt'></i></a></span></div>
      <div id='optionScenario' style='display:none;'>
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Tags</span>
            <input style="width:100%;" class='input-sm form-control roundedRight title' type="string" id="tags-scenario-input" value="" placeholder="Si nécessaire indiquez des tags" />
        </div>
        <div class="" style="width: 100%;display: flex;">
          <div class="input-group input-group-sm">
            <span class="input-group-addon roundedLeft" style="width: 100px">Sécurité</span>
          </div>
          <div style="padding-left: 10px;">
            <label class="radio-inline"><input type="radio" name="secure-radio-${option.id}" id="confirm-${option.id}" ><i class='fa fa-question' title="Demander confirmation"></i></label>
            <label class="radio-inline"><input type="radio" name="secure-radio-${option.id}" id="secure-${option.id}"  ><i class='fa fa-fingerprint' title="Sécuriser avec empreinte digitale"></i></label>
            <label class="radio-inline"><input type="radio" name="secure-radio-${option.id}" id="pwd-${option.id}"     ><i class='mdi mdi-numeric' title="Sécuriser avec un code"></i></label>
            <label class="radio-inline"><input type="radio" name="secure-radio-${option.id}" id="none-${option.id}"  checked   >Aucun</label>
          </div>
        </div>
      </div>


    </div>
    </div></li>`;
        } else if (option.category == "choicesList") {
            curOption += `<div class='input-group'>`;

            option.choices.forEach(v => {
                curOption += `<label class="checkbox-inline">
      <input type="checkbox" class="eqLogicAttr" id="${v.id}-jc-checkbox" />${v.text}
      </label>`;
            });

            curOption += `</div></div></div></li>`;
        } else {
            return;
        }

        items.push(curOption);

    });

    //More infos
    if (!["widgets-summary", "room", "favorites"].includes(widget.type)) {
        moreDiv = `<li><div class='form-group'>
      <label class='col-xs-3 '>Ajouter des infos</label>
      <div class='col-xs-9'>
      <div class="description">Permet d'ajouter des infos utilisables dans les images sous conditions, nom et/ou sous-titre</div>
      <div class='input-group'>
      <span class="input-group-btn">
        <a class="btn btn-default roundedRight" onclick="addMoreCmd()"><i class="fas fa-plus-square">
        </i> Ajouter une commande</a>
          </span>
          </div>
        <div id="moreInfos-div"></div>
      </div></div></li>`;
        items.push(moreDiv);
    }

    //Details access
    option = `<li><div class='form-group'>
    <label class='col-xs-3 '>Bloquer vue détails</label>
    <div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="blockDetail-input" ></div></div></div></li>`;
    items.push(option);

    $("#widgetOptions").html(items.join(""));
    loadSortable('all');
}


$("body").on('click', '#widgetModal .imagePicker', function () {
    var newElt = $(this).nextAll("a[data-id^='icon-']:first");

    getIconModal({ title: "Choisir une icône ou une image", withIcon: "1", withImg: "1", icon: htmlToIcon(newElt.children().first()), elt: newElt }, (result, _params) => {
        $(_params.elt).html(iconToHtml(result));
    });
});


$("body").on('click', '#widgetModal .removeImage', function () {
    $(this).empty();
});

$('body').off('click', '#widgetModal .removeCmd').on('click', '#widgetModal .removeCmd', function () {
    console.log("entre");
    var id = $(this).data('cmdid');
    $('#' + id).attr('value', '');
    $('#' + id).val('');
    $('#' + id).attr('cmdId', '');
});

$("body").on('click', '.selectScenario', function () {
    var related = $(this).data('related');
    jeedom.scenario.getSelectModal({}, function (result) {
        $("#" + related).attr('value', result.human);
        $("#" + related).val(result.human);
        $("#" + related).attr('scId', result.id);
        if ($("#name-input").val() == "") {
            getScenarioHumanName({
                error: function (error) { },
                success: function (data) {
                    data.forEach(sc => {
                        if (sc['id'] == result.id) {
                            $("#name-input").val(sc.name);
                        }
                    })
                }
            });
            $("#name-input").val(result.name);

            previousData = $('#tags-scenario-input').val() || '';
            $('#tags-scenario-input').val(previousData);
            $('#optionScenario').css('display', 'block');
        }
    })
});

$("#widgetOptions").on('change', '.needRefresh', function () {
    var info = $(this).val();
    var name = $(this).attr('id').replace('-input', '');
    var configlink = $(this).attr('configlink') || 'undefined';
    refreshCmdData(name, info, configlink);

});




function getCmdOptions(item) {

    curOption = '';

    var d = new Date().getTime();
    var rand = Math.floor(Math.random() * 10000) * Math.floor(Math.random() * 10000);
    var timestamp = d + '' + rand;
    var customUid = 'cmd' + item.id + '____' + timestamp + '____';

    // create dynamic var
    if ('options' in item) {
        for (const [key, value] of Object.entries(item.options)) {
            eval('var option' + key.charAt(0).toUpperCase() + key.slice(1) + '= "' + value + '";');
        }
    }


    if (item.subtype == 'select') {
        var optionSelect = optionSelect || '';

        getCmdDetail({ id: item.id, item: item, optionSelect: optionSelect }, function (_result, _param) {

            var selectOptions = [];
            $.each(_result.configuration.listValue.split(';'), function (key, val) {
                var myData = val.split('|');
                if (myData.length == 1) {
                    var value = text = myData[0];
                }
                else {
                    var value = myData[0];
                    var text = myData[1];
                }
                selectOptions.push(`<option value="${value}">${text}</option>`);
            });

            $('.jcCmdListOptions[data-uid=' + customUid + '][data-l1key=options][data-l2key=select]').html(selectOptions.join(""));

            var optionSelect = _param.optionSelect || '';

            $('.jcCmdListOptions[data-uid=' + customUid + '][data-l1key=options][data-l2key=select]').value(optionSelect);


        });

        curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Choix</span>
            <select class="form-control input-sm jcCmdListOptions roundedRight" data-l1key="options" data-l2key="select" data-uid="${customUid}" data-id="select-${item.id}" data-index="${item.index}">
            </select>
        </div>
       `;

    }


    if (item.subtype == 'message') {

        var optionTitle = optionTitle || '';
        var optionMessage = optionMessage || '';

        curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Titre</span>
            <input style="width:240px;" class='input-sm form-control roundedRight title jcCmdListOptions' type="string" data-id="title-${item.id}" data-index="${item.index}" value="${optionTitle}" />
        </div>
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Message</span>
            <textarea class="message form-control ta_autosize jcCmdListOptions" data-l1key="options" data-l2key="message" rows="1" style="resize:vertical;"  data-id="message-${item.id}" data-index="${item.index}" data-uid="${customUid}">${optionMessage}</textarea>
            <span class="input-group-addon hasBtn roundedRight">
              <button class="btn btn-default roundedRight listEquipementInfo" type="button" tooltip="Sélectionner la commande" data-cmd_id="${item.id}" data-index="${item.index}" data-uid="${customUid}" ><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').on('click', function() {
              jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
                $('.jcCmdListOptions[data-l1key=options][data-l2key=message][data-uid=${customUid}]').atCaret('insert', result.human);
              });
          });

        </script>
        </div>`;
    }

    if (item.subtype == 'slider') {
        var optionSlider = optionSlider || '';

        curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Valeur</span>
            <textarea class="message form-control ta_autosize jcCmdListOptions" data-l1key="options" data-l2key="message" rows="1" style="resize:vertical;"  data-id="slider-${item.id}" data-index="${item.index}" data-uid="${customUid}">${optionSlider}</textarea>
            <span class="input-group-addon hasBtn roundedRight">
              <button class="btn btn-default roundedRight listEquipementInfo" type="button" tooltip="Sélectionner la commande" data-cmd_id="${item.id}" data-index="${item.index}" data-uid="${customUid}"><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').on('click', function() {
              jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
                $('.jcCmdListOptions[data-l1key=options][data-l2key=message][data-uid=${customUid}]').atCaret('insert', result.human);
              });
          });
        </script>
        </div>`;
    }

    if (item.subtype == 'color') {
        var optionColor = optionColor || '';

        curOption = `
        <div class="input-group input-group-sm" style="width: 100%">
            <span class="input-group-addon roundedLeft" style="width: 100px">Couleur</span>
            <input type="color" class="form-control input-sm cursor colorChooser" data-uid="${customUid}" style="width: 20%; display: inline-block;" value="${optionColor}">
            <input class="jcCmdListOptions form-control input-sm" data-l1key="options" data-l2key="color"  data-uid="${customUid}" style="width: 80%; display: inline-block;" placeholder="Couleur (hexa)" data-id="color-${item.id}" data-index="${item.index}" value="${optionColor}">
            <span class="input-group-btn">
              <button class="btn btn-default listEquipementInfo roundedRight" type="button" tooltip="Sélectionner la commande" data-uid="${customUid}" data-index="${item.index}" data-cmd_id="${item.id}"><i class="fas fa-list-alt"></i></button>
            </span>

        <script>
          $('.listEquipementInfo[data-uid=${customUid}]').off('click').on('click', function () {
            var el = $(this);
            jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
              $('.jcCmdListOptions[data-uid=${customUid}][data-l1key=options][data-l2key=color]').value(result.human);
            });
          });
          $('.colorChooser[data-uid=${customUid}]').off('change').on('change', function () {
            $('.jcCmdListOptions[data-uid=${customUid}][data-l1key=options][data-l2key=color]').value($(this).value())
          });
        </script>
        </div>`;
    }

    curOption += `<div class="input-group input-group-sm" style="width: 100%">
                            <span class="input-group-addon roundedLeft" style="width: 100px">Nom</span>
                            <input style="width:240px;" class='input-sm form-control roundedRight title jcCmdListOptions' data-id="custom-name-${item.id}" data-index="${item.index}" value='${item.name || ""}' >
                        </div>`;


    return curOption;


}