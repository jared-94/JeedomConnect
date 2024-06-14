function setSimpleModalData(options) {
  items = [];
  options.forEach(option => {
    if (option.type == "enable") {
      var value = option.value ? 'checked' : '';
      var title = option.title || 'Actif';
      enable = `<li><div class='form-group'>
			<label class='col-xs-3  required' >${title}</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-enable-input" ${value}></div></div></div></li>`;
      items.push(enable);
    } else if (option.type == "description") {
      line = `<li><span class="description italic">${option.text}</span></li>`;
      items.push(line);
    } else if (option.type == "line") {
      line = `<li style="border-bottom: solid 1px;"></li>`;
      items.push(line);
    } else if (option.type == "room") {

      line = `<li><div class='form-group'>
    <label class='col-xs-3 '>Pièce</label>
    <div class='col-xs-9'><div class='input-group'><select style="width:340px;" id="room-input" value=''>
    <option value="none">Sélection  d'une pièce</option>`;

      console.log("compare : " + option.value);
      roomList.forEach(item => {
        console.log("compare : " + option.value + " to " + item.id);
        var checked = (option.value == item.id) ? 'selected' : '';

        line += '<option value="' + item.id + '" ' + checked + '>' + item.space + item.name + '</option>';
      })
      line += `</select></div></div></div></li>`;
      items.push(line);

    } else if (option.type == "checkboxes") {

      checkboxes = `<li><div class='form-group'>
			<label class='col-xs-3  required' >${option.title}</label>
			<div class='col-xs-9'><div class='input-group'>`;
      option.choices.forEach(item => {
        checkboxes += `<input type="checkbox" class="checkboxesSelection" style="width:150px;" value="${item.id}" >${item.name}<br/>`;
      });
      checkboxes += `</div></div></div></li>`;
      items.push(checkboxes);
    } else if (option.type == "radios") {

      radios = `<li><div class='form-group'>
			<label class='col-xs-3  required' >${option.title}</label>
			<div class='col-xs-9'><label class='radio-inline'>`;
      option.choices.forEach(item => {
        radios += `<label><input type="radio" class="radiosSelection" name="radio" style="width:150px;" id="${item.id}"  ${item.selected || ''}> ${item.name}</label><br/>`;
      });
      radios += `</label></div></div></li>`;
      items.push(radios);
    } else if (option.type == "name") {
      var value = option.value ? option.value : '';
      name = `<li><div class='form-group'>
			<label class='col-xs-3  ${option.required !== false ? 'required' : ''}' >Nom</label>
			<div class='col-xs-9'><div class='input-group'><input style="width:150px;" id="mod-name-input" value='${value}'></div></div></div></li>`;
      items.push(name);
    } else if (option.type == "icon") {
      let icon = option.value ? iconToHtml(option.value) : '';
      icon = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Icone</label>
			<div class='col-xs-9'><div class='input-group'>
      <a class='btn btn-default btn-sm cursor bt_selectTrigger'
        tooltip='Choisir une icone' onclick="getSimpleIcon();">
      <i class='fas fa-flag'></i> Icône </a>
      <a id="icon-div">${icon} </a>
        </div></div>
      </div></li>`;
      items.push(icon);
    } else if (option.type == "move") {
      move = `<li><div class='form-group'>
			<label class='col-xs-3 '>Déplacer vers</label>
			<div class='col-xs-9'><div class='input-group'><select style="width:150px;" id="mod-move-input" value=''>`;
      option.value.forEach(item => {
        move += `<option value="${item.id}">${item.name}</option>`;
      });
      move += `</select></div></div></div></li>`;
      items.push(move);
    } else if (option.type == "string") {
      var id = (option.id !== undefined) ? `id="${option.id}"` : '';
      items.push(`<li ${id}>${option.value}</li>`);
    } else if (option.type == "expanded") {
      var value = option.value ? 'checked' : '';
      expanded = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Développé par défaut</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-expanded-input" ${value}></div></div></div></li>`;
      items.push(expanded);
    } else if (option.type == "color") {
      var colorValue = option ? option.value || '' : '';
      expanded = `<li><div class='form-group'>
            <label class='col-xs-3  required' >${option.title}</label>
            <div class='col-xs-9'>
              <div class='input-group'>
                <input type="color" id="mod-color-input" value='${colorValue}' class="inputJCColor changeJCColor">
              </div>
            </div>
          </div></li>`;
      items.push(expanded);
    } else if (option.type == "widget") {

      var widgetTmp = '<option class="JCWidgetOption" value="none" >Select</option>';
      var widgetType = []

      allWidgetsDetail.forEach(item => {
        if (option.choices.includes(item.type)) {
          let name = getWidgetPath(item.id);
          room = getRoomName(item.room) || '';
          if (room && room != '') {
            name = name + ' (' + room + ')'
          }

          widgetTmp += `<option class="JCWidgetOption" style="width:150px;" value="${item.id}" data-type="${item.type}" name="${name}" data-room="${room}">${name} [${item.id}]</option>`;
          widgetType.push(item.type);
        }
      })

      var optionSelect = '<option value="none" selected>Tous</option>';
      widgetsList.widgets.forEach(item => {
        if (option.choices.includes(item.type) && widgetType.includes(item.type)) {
          optionSelect += `<option value="${item.type}">${item.name}</option>`;
        }
      })

      widget = `<li>`;
      if (option.typeFilter) {
        widget += `<div class='form-group'>
            <label class='col-xs-3' >Type</label>
            <div class='col-xs-9'><div class='input-group'>
            <select style="width:250px;" class="refreshWidgetType" data-select="mod-widget-input">`
        widget += optionSelect;
        widget += `</select></div></div></div>`;
      }


      widget += `<div class='form-group'>
			<label class='col-xs-3  required' >Widget</label>
			<div class='col-xs-9'><div class='input-group'>
			<select style="width:250px;" id="mod-widget-input">`
      widget += widgetTmp;
      widget += `</select></div></div></div>`;

      widget += `</li>`;
      items.push(widget);
    } else if (option.type == "object") {
      $("#object-li").css("display", "block");
      if (option.value) {
        $('#object-select option[value="' + option.value + '"]').prop('selected', true);
      }

      //hide all rooms already selected for this equipment
      if ($("#roomUL").length) {
        $('ul#roomUL li').each(function (i) {
          $('#object-select option[value="' + $(this).data("id") + '"]').css('display', 'none');
        });
      }

    } else if (option.type == "visibilityCond") {

      var visibilityCond = option.value || '';
      if (visibilityCond != '') {
        const match = visibilityCond.match(/#.*?#/g);
        if (match) {
          match.forEach(item => {
            getHumanNameFromCmdId({ alert: '#widget-alert', cmdIdData: item }, function (humanResult, _params) {
              visibilityCond = visibilityCond.replace(item, humanResult);
            });
          });
        }
      }

      swipe = `<li><div class='form-group'>
			   <label class='col-xs-3' >Visible sous condition
            <sup>
								<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Permet d'ajouter une condition pour afficher ou masquer cet élément (uniquement si 'actif' est coché)"></i>
						</sup>
         </label>
			   <div class='col-xs-9'>
            <input style="width:385px;" class="roundedLeft" id="visibility-cond-input" value="${visibilityCond}" cmdtype="info" cmdsubtype="undefined" configtype="info" configsubtype="undefined" />
          
          <a class='btn btn-default btn-sm cursor bt_selectTrigger' tooltip='Choisir une commande' onclick="selectCmd('simpleModal #visibility-cond', 'info', 'undefined', 'undefined', true);">
          <i class='fas fa-list-alt'></i></a>
    
         </div>
         </div></li>`;
      items.push(swipe);
    }
    else if (option.type == "advancedGrid") {
      // console.log('current option =>', option);
      swipe = `<li><div class='form-group'>
			   <label class='col-xs-3' >Mode de grille</label>
			   <div class='col-xs-9'>
          <select id="advancedGrid-select">
            <option value='auto' ${option.value === null ? "selected" : ""}>Automatique</option>
            <option value='standard' ${option.value === false ? "selected" : ""}>Standard</option>
            <option value='advanced' ${option.value === true ? "selected" : ""}>Avancé</option>
          </select>
         </div>
         </div></li>`;
      items.push(swipe);
    } else if (option.type == "swipeUp" | option.type == "swipeDown" | option.type == "action") {
      swipe = `<li><div class='form-group'>
			   <label class='col-xs-3' >${option.type == 'swipeUp' ? "Swipe Up" : (option.type == 'swipeDown' ? "Swipe Down" : "Action")}</label>
			   <div class='col-xs-9'>
          <select id="${option.type}-select" onchange="swipeSelected('${option.type}');">
            <option value='none' ${option.value ? "" : "selected"}>Aucun</option>
            <option value='cmd' ${option.value ? option.value.type == 'cmd' ? "selected" : "" : ""}>Exécuter une commande</option>
            <option value='sc' ${option.value ? option.value.type == 'sc' ? "selected" : "" : ""}>Lancer un scénario</option>
          </select>
          <div class='input-group' id="${option.type}-cmd-div"
            style="display:${option.value ? option.value.type == 'cmd' ? "''" : "none" : "none"};"><input class='input-sm form-control roundedLeft' style="width:260px;"
            id="${option.type}-cmd-input" value=''
            cmdId='${option.value ? option.value.type == 'cmd' ? option.value.id : '' : ''}' disabled>
            <a class='btn btn-default btn-sm cursor bt_selectTrigger'
              tooltip='Choisir une commande' onclick="selectSimpleCmd('${option.type}');">
            <i class='fas fa-list-alt'></i></a>
          </div>
          <div class='input-group' id="${option.type}-sc-div"
          style="display:${option.value ? option.value.type == 'sc' ? "''" : "none" : "none"};">
            <input class='input-sm form-control roundedLeft' style="width:260px;"
            id="${option.type}-sc-input" value='' scId='${option.value ? option.value.type == 'sc' ? option.value.id : '' : ''}' disabled>
            <a class='btn btn-default btn-sm cursor bt_selectTrigger'
              tooltip='Choisir un scénario' onclick="selectSimpleSc('${option.type}');">
            <i class='fas fa-list-alt'></i></a>
            <div class="input-group input-group-sm" style="width: 100%">
              <span class="input-group-addon roundedLeft" style="width: 40px">Tags</span>
              <input style="width:100%;" class='input-sm form-control' type="string" id="${option.type}-sc-tags-input" 
                  value="${option.value ? option.value.tags ? option.value.tags : '' : ''}" placeholder="Si nécessaire indiquez des tags" />
            </div>
         </div>
         </div></li>`;
      items.push(swipe);
    }
  });

  $("#modalOptions").append(items.join(""));
  refreshSwipe("swipeUp");
  refreshSwipe("swipeDown");
  refreshSwipe("action");

}

$("body").on('change', '.refreshWidgetType', function (e) {

  var typeSelected = this.value;

  $('.JCWidgetOption').show();
  if (typeSelected != 'none') {
    $('#' + $(this).data('select') + ' option').not("[data-type=" + typeSelected + "]").hide();
    $('#' + $(this).data('select')).prop("selectedIndex", 0).val();
  }


});

function refreshSwipe(type) {
  if ($("#" + type + "-cmd-input").attr('cmdId') != '') {
    getCmd({
      id: $("#" + type + "-cmd-input").attr('cmdId'),
      success: function (data) {
        $("#" + type + "-cmd-input").val(data.result.humanName);
      }
    })
  }

  if ($("#" + type + "-sc-input").attr('scId') != '') {
    getSimpleScenarioHumanName({
      id: $("#" + type + "-sc-input").attr('scId'),
      success: function (data) {
        data.forEach(sc => {
          if (sc['id'] == $("#" + type + "-sc-input").attr('scId')) {
            $("#" + type + "-sc-input").val(sc['humanName']);
          }
        })
      }
    })
  }
}

function objectSelected() {
  $("#mod-name-input").val($("#object-select  option:selected").text());
}

function swipeSelected(type) {
  val = $("#" + type + "-select  option:selected").val();
  if (val == 'cmd') {
    $("#" + type + "-cmd-div").css("display", "");
    $("#" + type + "-sc-div").css("display", "none");
  } else if (val == 'sc') {
    $("#" + type + "-cmd-div").css("display", "none");
    $("#" + type + "-sc-div").css("display", "");
  } else if (val == 'none') {
    $("#" + type + "-cmd-div").css("display", "none");
    $("#" + type + "-sc-div").css("display", "none");
  }
}

function selectSimpleCmd(name) {
  jeedom.cmd.getSelectModal({
    cmd: {
      type: 'action',
      subType: 'other'
    }
  }, function (result) {
    $("#" + name + "-cmd-input").val(result.human);
    $("#" + name + "-cmd-input").attr('cmdId', result.cmd.id);
  })
}

function selectSimpleSc(name) {
  jeedom.scenario.getSelectModal({}, function (result) {
    $("#" + name + "-sc-input").attr('scId', result.id);
    $("#" + name + "-sc-input").val(result.human);
  })
}

function getSimpleScenarioHumanName(_params) {
  var params = $.extend({}, jeedom.private.default_params, {}, _params || {});

  var paramsAJAX = jeedom.private.getParamsAJAX(params);
  paramsAJAX.url = 'core/ajax/scenario.ajax.php';
  paramsAJAX.data = {
    action: 'all',
    id: _params.id
  };
  $.ajax(paramsAJAX);
}

function getSimpleIcon(name) {
  getIconModal({
    title: "Choisir une icône",
    withIcon: "1",
    withImg: "1",
    icon: htmlToIcon($("#icon-div").children().first())
  }, (result) => {
    $("#icon-div").html(iconToHtml(result));
  })
}