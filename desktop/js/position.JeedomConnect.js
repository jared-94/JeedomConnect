/**
 * 
 * coming from https://nouvelle-techno.fr/articles/pas-a-pas-inserer-une-carte-openstreetmap-sur-votre-site 
 * 
 */

$.post({
    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
    data: { 'action': 'getDefaultPosition' },
    dataType: 'json',
    async: false,
    success: function (data) {
        if (data.state != 'ok') {
            $('#div_alert').showAlert({ message: data.result, level: 'danger' });
        }
        else {
            lat = data.result.lat;
            lon = data.result.lon;
            $('.defaultText').text(data.result.defaultText)
        }
    }
});


function getLocalisations() {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            'action': 'getAllPositions'
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            // console.log('getAllPositions', data)
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else {
                if (data.result.length == 0) {
                    $('#div_alert').showAlert({
                        message: "Aucun équipement autorisé",
                        level: 'warning'
                    });
                }
                allJcPositions = data.result;
            }
        }
    });
}

// Fonction d'initialisation de la carte
function initLocalisationMap() {

    createJcMap();

    allJcPositions.forEach(function (jcPosition) {
        addJcMapListener(jcPosition.cmdId);
        addMarker(jcPosition, true)
    });
    macarte.addLayer(markerClusters);

}

function getHtmlPopUp(geo) {
    var latlon = geo.lat + ',' + geo.lon;
    var urlNav = "https://www.google.com/maps/search/?api=1&query=" + latlon;

    var html = `<h4 class="text-center">${geo.name || ''}</h4>
            <table  style="font-size:14px">`;
    html += (!geo.radius) ? `<tr style="background-color:transparent!important"><td><b>Maj : </b></td><td style="padding-left:5px">${geo.lastSeen}</td></tr>` : '';
    html += `<tr style = "background-color:transparent!important" ><td><b>Position : </b></td><td style="padding-left:5px"><a href="${geo.urlNav}" target="_blank">${latlon}</a></td></tr >`;
    html += (!geo.radius) ? `<tr style="background-color:transparent!important"><td><b>Distance : </b></td><td style="padding-left:5px">${geo.distance}</td></tr>` : '';
    html += (!geo.radius) ? `<tr style="background-color:transparent!important"><td colspan="2" class="text-center"><a href="${urlNav}" target="_blank">Y aller !</a></td></tr>` : '';
    html += (geo.radius) ? `<tr style="background-color:transparent!important"><td><b>Rayon : </b></td><td style="padding-left:5px">${geo.radius} m</td></tr>` : '';
    html += `</table>`;

    return html;
}


$("body").off('change', '.zoomSelection').on('change', '.zoomSelection', function () {

    if ($(this).val() == 'all') {
        var group = new L.featureGroup(markers); // Nous créons le groupe des marqueurs pour adapter le zoom
        macarte.fitBounds(group.getBounds().pad(0.5)); // Nous demandons à ce que tous les marqueurs soient visibles, et ajoutons un padding (pad(0.5)) pour que les marqueurs ne soient pas coupés
    }
    else {
        getFocus([lat, lon], 13);
    }
});


function getFocus(coordinates, zoom) {
    macarte.setView(coordinates, zoom);//on recentre la carte
}

function getGeofences() {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'getAllGeofences',
            eqId: eqId
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            // console.log('getAllGeofences', data)
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else {
                if (data.result.length == 0) {
                    $('#div_alert').showAlert({
                        message: "Aucune zone trouvée",
                        level: 'warning'
                    });
                }
                allJcGeofences = data.result.equipment;
                allJcGeofencesConfig = data.result.config;
            }
        }
    });
}

function createJcMap() {
    // Créer l'objet "macarte" et l'insèrer dans l'élément HTML qui a l'ID "map"
    macarte = L.map('jcMap').setView([lat, lon], 13);
    markerClusters = L.markerClusterGroup(); // Nous initialisons les groupes de marqueurs

    // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        // Il est toujours bien de laisser le lien vers la source des données
        attribution: '&copy; <a href="//osm.org/copyright">OpenStreetMap</a>',
        minZoom: 1,
        maxZoom: 20
    }).addTo(macarte);

}

// Fonction d'initialisation de la carte
function initGeofenceMap() {

    createJcMap();

    var parents = []

    allJcGeofences.forEach(function (jcGeofence) {
        addCircle(jcGeofence, 'green');
        addGeofenceToTable('.currentEq', jcGeofence, false)
        parents.push(jcGeofence.parent)
    });

    allJcGeofencesConfig.forEach(function (geoConfig) {

        addGeofenceToTable('.otherItems', geoConfig, true);
        if ($.inArray(geoConfig.id, parents) !== -1) {
            $('.otherItems tbody tr:last').hide();
        }
        else {
            addCircle(geoConfig);
        }
    });

}


var macarte = null;
var markerClusters;
var markers = []; // Nous initialisons la liste des marqueurs
var circles = []; // Nous initialisons la liste des cercles
if (!geo) {
    getLocalisations();
    initLocalisationMap();
}
else {
    $('#jcMap').addClass("col-sm-6")

    getGeofences();
    initGeofenceMap();

    macarte.off('click').on('click', function (e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;

        var html = `<b><u>Nouvelle position</u></b><br>
                <b>Lat :</b>${lat} - <b>Lng :</b>${lng}<br><br>
                <a class='btn btn-success center-block btnAddCoordinates' type='button' data-lat='${lat}' data-lon='${lng}'>Ajouter ici</a><br/> `;

        popup
            .setLatLng(e.latlng)
            .setContent(html)
            .openOn(macarte);
    });
}

var popup = L.popup();


function addGeofenceToTable(elt, geo, config) {

    var tr = '<tr>';
    tr += '<td>';
    tr += '<span class="geoAttr" data-l1key="id" ></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="geoAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="geoAttr form-control input-sm" data-l1key="lat" placeholder="{{Latitude}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="geoAttr form-control input-sm" data-l1key="lon" placeholder="{{Longitude}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="geoAttr form-control input-sm" data-l1key="radius" placeholder="{{Radius}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<i class="fa fa-map-marker-alt pull-right geoFocusMarker cursor geoOpt" title="Centrer sur cette zone"></i>';
    if (config) {
        tr += '<i class="fas fa-minus-circle pull-right cursor removeGeo geoOpt" title="Supprimer cette zone des modèles"></i>';
        tr += '<i class="fas fa-plus-circle pull-right cursor addGeoToEquipment geoOpt" title="Ajouter cette zone à mon équipement"></i>';
    }
    else {
        tr += '<i class="fas fa-minus-circle pull-right cursor removeGeo geoOpt" title="Supprimer cette zone/commande de mon équipement"></i>';
    }
    tr += '</td>';
    tr += '</tr>';
    $(elt).append(tr);
    $(elt + ' tbody tr:last').setValues(geo, '.geoAttr');
    if (config) {
        $(elt + ' tbody tr:last').find('.geoAttr').addClass('forConfig');
        $(elt + ' tbody tr:last').find('.geoOpt').addClass('forConfig');
    }
    else {
        $(elt + ' tbody tr:last').find('.geoAttr').addClass('forCmd');
        $(elt + ' tbody tr:last').find('.geoOpt').addClass('forCmd');
    }
    $(elt + ' tbody tr:last').attr('data-parent', geo.parent);
    $(elt + ' tbody tr:last').attr('data-id', geo.id);
}

$('body').off('click', '.removeGeo').on('click', '.removeGeo', function () {
    var isConfig = $(this).hasClass('forConfig');
    var elt = $(this).closest('tr');
    var id = elt.find('.geoAttr[data-l1key=id]').text();
    var parent = elt.data('parent');

    bootbox.confirm("Supprimer cette zone ?", function (result) {
        if (result) {
            if (isConfig) {
                actionOnConfigGeo({ id: id }, 'remove');
                removeCircle(id);
            }
            else {
                if (parent != undefined) {
                    var eltParent = $('.otherItems tr[data-id=' + parent + ']');
                    if (eltParent.length) {
                        eltParent.show();
                        var geofenceData = eltParent.getValues('.geoAttr')[0];
                        addCircle(geofenceData);
                    }
                }
                actionOnCmdGeo({ id: id }, 'remove');
                removeCircle(id);
            }
            elt.remove();
        }
    });
})

function controlFields(geo) {
    var msgErr = [];
    if (geo.name == '') msgErr.push('nom');
    if (geo.lat == '') msgErr.push('latitude');
    if (geo.lon == '') msgErr.push('longitude')
    if (geo.radius == '') msgErr.push('rayon');
    if (msgErr.length != 0) {
        let plurial = msgErr.length > 1 ? 's' : '';
        $('#div_alert').showAlert({
            message: 'Champ' + plurial + ' obligatoire' + plurial + ' : ' + msgErr.join(', '),
            level: 'danger'
        });
        return false;
    }
    return true;
}


$('body').off('click', '.addGeoToEquipment').on('click', '.addGeoToEquipment', function () {
    createCmdAndMoveItem($(this));
});

async function createCmdAndMoveItem(elt) {
    let tr = elt.closest("tr");
    let geo = getGeofencesData(elt);

    if (!controlFields(geo)) return;

    var creaCmd = await actionOnCmdGeo(geo);

    //if creation failed then do not move item to equipment
    if (creaCmd.state == 'ok') {
        let oldId = geo.id;
        geo.id = creaCmd.result.id;
        geo.parent = oldId;

        addGeofenceToTable('.currentEq', geo)

        removeCircle(oldId);
        addCircle(geo, 'green');

        tr.hide();
    }
}

$('body').off('change', '.forConfig, .forCmd').on('change', '.forConfig, .forCmd', function () {
    let geo = getGeofencesData($(this));

    if ($(this).hasClass('forConfig')) {
        actionOnConfigGeo(geo);
    }
    else {
        actionOnCmdGeo(geo);
    }

})

$('body').off('change', '.geoAttr').on('change', '.geoAttr', function () {
    let geo = getGeofencesData($(this));

    updateCircle(geo);
})

function addCircle(geo, color = 'red') {
    var circle = L.circle([geo.lat, geo.lon], {
        color: color,
        fillOpacity: 0.5,
        radius: geo.radius,
        id: geo.id,
        geoData: geo
    }).addTo(macarte);
    circles.push(circle);

    addMarker(geo);
}

function updateCoordinates(id, lat, lon) {
    var circle = circles.find(i => i.options.id == id);
    if (circle) {
        var geo = circle.options.geoData;
        geo['lat'] = lat;
        geo['lon'] = lon;
        circle.options.geoData = geo;

        var currentEq = $('.currentEq tr[data-id=' + id + ']');
        var otherItems = $('.otherItems tr[data-id=' + id + ']');
        if (currentEq.length) {
            currentEq.setValues(geo, '.geoAttr');
        }
        else if (otherItems.length) {
            otherItems.setValues(geo, '.geoAttr');
        }
    }
}

function updateCircle(geo) {
    var circle = circles.find(i => i.options.id == geo.id);
    if (circle) {
        circle.setLatLng([geo.lat, geo.lon]);
        circle.setRadius(geo.radius);
        circle.options.geoData = geo
        updateMarker(geo)
    }
}

function removeCircle(id) {
    var circle = circles.find(i => i.options.id == id);
    if (circle) {
        macarte.removeLayer(circle);
        removeMarker(id);
    }
}

function addMarker(geo, withCluster = false) {

    if (!geo.icon) {
        var myIcon = L.icon({
            iconUrl: 'plugins/JeedomConnect/data/img/pin.png',
            iconSize: [32, 48],
            iconAnchor: [16, 48],
            popupAnchor: [-3, -40],
        });
    }
    else {

        var myIcon = L.icon({
            iconUrl: geo.icon,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [-3, -40],
        });
    }
    var marker = L.marker([geo.lat, geo.lon], { icon: myIcon, draggable: true, title: geo.name, id: geo.id })

    marker.on('dragend', function (event) {
        var position = marker.getLatLng();
        updateCoordinates(marker.options.id, position.lat, position.lng)
    });

    let popUpData = getHtmlPopUp(geo);

    marker.bindPopup(popUpData);
    markers.push(marker);
    if (withCluster) {
        markerClusters.addLayer(marker);
    }
    else {
        marker.addTo(macarte);
    }

}

function updateMarker(geo) {
    if (!marker) var marker = markers.find(i => i.options.id == geo.id);
    if (marker) {
        marker.setLatLng([geo.lat, geo.lon]);
        var popUpData = getHtmlPopUp(geo);
        // if (geo.name) marker._icon.title = geo.name || '';
        marker.setPopupContent(popUpData);
    }
}

function removeMarker(id) {
    var marker = markers.find(i => i.options.id == id);
    if (marker) {
        macarte.removeLayer(marker)
    }
}

function getGeofencesData(elt) {
    return $(elt).closest("tr").getValues('.geoAttr')[0];
}

$('body').off('click', '.geoFocusMarker').on('click', '.geoFocusMarker', function () {
    let geo = getGeofencesData($(this));

    getFocus([geo.lat, geo.lon], 15)
})

function refreshJcPosition(cmdId, position) {
    let data = position.split(',');
    updateMarker({ id: cmdId, lat: data[0], lon: data[1] })
}

function addJcMapListener(id) {
    let script = `<script>
        jeedom.cmd.update['${id}'] = function (_options) {
             refreshJcPosition(${id}, _options.value);
            }
            jeedom.cmd.update['${id}']({ value: "#state#" })
    </script>`

    $('#jcMapScript').append(script);

}

$("body").off('click', '.btnAddCoordinates').on('click', '.btnAddCoordinates', function () {

    let geofenceData = {
        id: makeid(),
        lat: $(this).data('lat'),
        lon: $(this).data('lon'),
        radius: 100
    };
    addGeofenceToTable('.otherItems', geofenceData, true);

    macarte.closePopup();

    addCircle(geofenceData);
    actionOnConfigGeo(geofenceData);

});

//----- FOR CMD
async function actionOnCmdGeo(geofence, type = 'createOrUpdate') {
    const result = await $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'createOrUpdateCmdGeo',
            type: type,
            data: geofence,
            eqId: eqId
        },
        cache: false,
        dataType: 'json',
        async: false
    });

    if (result.state != 'ok') {
        $('#div_alert').showAlert({
            message: result.result,
            level: 'danger'
        });
    }

    return result;
}

//------- FOR CONFIG 
function actionOnConfigGeo(geofence, type = 'createOrUpdate') {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'createOrUpdateConfigGeo',
            type: type,
            data: geofence,
            id: geofence.id
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
        }
    });
}

$("body").off('change', '.updateMapData').on('change', '.updateMapData', function () {
    let type = $(this).data('conf');

    if (type == 'object_id') {
        var data = $("option:selected", this).val() || null;
    }
    else {
        var data = $(this).is(':checked');
    }

    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'updateEqWidgetMaps',
            type: type,
            data: data
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else {
                //set something to refresh the page    
            }
        }
    });

})


function makeid() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for (var i = 0; i < 5; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
}