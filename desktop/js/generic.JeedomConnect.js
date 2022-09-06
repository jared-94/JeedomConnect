/**
 * **** COLOR PICKUP 
 * update input when color selected
 */

$("body").on('change', '.changeJCColor', function () {
    $(this).siblings('.inputJCColor').val($(this).val());
});

//---------------------------------

function download(filename, text, add_date_time = false) {

    if (add_date_time) {
        var dt = new Date();
        var dd = String(dt.getDate()).padStart(2, '0');
        var mm = String(dt.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = dt.getFullYear();

        today = yyyy + mm + dd;
        var time = dt.getHours() + '' + dt.getMinutes() + '' + dt.getSeconds() + '';

        filename = today + '_' + time + '_' + filename;
    }

    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

function disableCheckboxWsPolling(useWs, polling) {
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

}


function shuffle(array) {
    let currentIndex = array.length, randomIndex;

    // While there remain elements to shuffle.
    while (currentIndex != 0) {

        // Pick a remaining element.
        randomIndex = Math.floor(Math.random() * currentIndex);
        currentIndex--;

        // And swap it with the current element.
        [array[currentIndex], array[randomIndex]] = [
            array[randomIndex], array[currentIndex]];
    }

    return array;
}


function parseString(string, infos) {
    let result = string;
    if (typeof (string) != "string") { return string; }
    const match = string.match(/#.*?#/g);
    if (!match) { return string; }
    match.forEach(item => {
        const info = infos.find(i => i.human == item);
        if (info) {
            result = result.replace(item, "#" + info.id + "#");
        }
    });
    return result;
}


function getMaxId(array, defaut = -1) {
    var maxId = defaut;
    array.forEach(item => {
        if (item.id > maxId) {
            maxId = item.id;
        }
    });
    return maxId;
}

function getMaxIndex(array) {
    var maxIndex = -1;
    array.forEach(item => {
        if (item.index > maxIndex) {
            maxIndex = item.index;
        }
    });
    return maxIndex;
}


function jeedomIconToIcon(html) {
    if (html.startsWith("<i ")) {
        let tag1 = html.split("\"")[1].split(" ")[0];
        let tag2 = html.split("\"")[1].split(" ")[1];
        if (tag1 == 'icon') {
            return { source: 'jeedom', name: tag2 };
        } else if (tag1.startsWith('fa')) {
            return { source: 'fa', prefix: tag1, name: tag2.replace("fa-", "") };
        }
    }
}

function getCmd({ id, error, success }) {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: { 'action': 'getCmd', 'id': id },
        cache: false,
        success: function (cmdData) {
            jsonData = JSON.parse(cmdData);
            if (jsonData.state == 'ok') {
                success && success(jsonData);
            } else {
                error && error(jsonData);
            }
        }
    });
}


function getScenarioHumanName(_params) {
    var params = $.extend({}, jeedom.private.default_params, {}, _params || {});

    var paramsAJAX = jeedom.private.getParamsAJAX(params);
    paramsAJAX.url = 'core/ajax/scenario.ajax.php';
    paramsAJAX.data = {
        action: 'all',
        id: _params.id
    };
    $.ajax(paramsAJAX);
}

function getHumanNameFromCmdId(_params, _callback) {
    if (typeof _params.alert == 'undefined') {
        _params.alert = '#div_alert';
    }

    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'cmdToHumanReadable',
            strWithCmdId: _params.cmdIdData
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            if (data.state != 'ok') {
                $(_params.alert).showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else {
                if ('function' == typeof (_callback)) {
                    _callback(data.result, _params);
                }
            }
        }
    });
}

function getCmdIdFromHumanName(_params, _callback) {
    if (typeof _params.alert == 'undefined') {
        _params.alert = '#div_alert';
    }

    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'humanReadableToCmd',
            human: _params.stringData
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            if (data.state != 'ok') {
                $(_params.alert).showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else {
                if ('function' == typeof (_callback)) {
                    _callback(data.result, _params);
                }
            }
        }
    });
}

function getHumanName(cmdId) {
    myCmd = allJeedomData.find(i => i.id == cmdId);
    myHumanName = myCmd?.humanName ? '#' + myCmd.humanName + '#' : '';
    return myHumanName;
}

function idToHuman(string, infos) {
    let result = string;
    if (typeof (string) != "string") { return string; }
    const match = string.match(/#.*?#/g);
    if (!match) { return string; }
    match.forEach(item => {
        const info = infos.find(i => i.id == item.replace(/\#/g, ""));
        if (info && info.human != '' && info.human != undefined) {
            result = result.replace(item, info.human);
        }
    });
    return result;
}


function getCmdDetail(_params, _callback) {
    if (typeof _params.alert == 'undefined') {
        _params.alert = '#div_alert';
    }
    var paramsRequired = ['id'];
    var paramsSpecifics = {
        global: false,
        success: function (result) {

            if ('function' == typeof (_callback)) {
                _callback(result, _params);
            }

        }
    };

    try {
        jeedom.private.checkParamsRequired(_params || {}, paramsRequired);
    } catch (e) {
        (_params.error || paramsSpecifics.error || jeedom.private.default_params.error)(e);
        $(_params.alert).showAlert({ message: e.message, level: 'danger' });
        return;
    }
    var params = $.extend({}, jeedom.private.default_params, paramsSpecifics, _params || {});
    var paramsAJAX = jeedom.private.getParamsAJAX(params);
    paramsAJAX.url = 'core/ajax/cmd.ajax.php';
    paramsAJAX.data = {
        action: 'getCmd',
        id: _params.id,
    };
    $.ajax(paramsAJAX);
};


// countdown function
function countDown(time, update, complete) {
    var start = new Date().getTime();
    window.interval = setInterval(function () {
        var now = time - (new Date().getTime() - start);
        if (now <= 0) {
            clearInterval(window.interval);
            complete();
        }
        else update(Math.floor(now / 1000));
    }, 100); // the smaller this number, the more accurate the timer will be
}

if (typeof jeedom.cmd.addUpdateFunction !== 'function') {
    jeedom.cmd.addUpdateFunction = function (id, func) {
        jeedom.cmd.update[id] = func;
    }
}