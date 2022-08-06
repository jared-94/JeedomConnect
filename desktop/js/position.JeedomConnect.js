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
        addMarker(jcPosition.identifier, jcPosition.lat, jcPosition.lon, jcPosition.name, null, jcPosition.lastSeen, jcPosition.distance, jcPosition.icon, true)
    });
    macarte.addLayer(markerClusters);

}



function getHtmlPopUp(name, lastSeen, latlon, distance, radius = null) {
    var urlNav = "https://www.google.com/maps/search/?api=1&query=" + latlon;

    var html = `<h4 class="text-center">${name || ''}</h4>
            <table  style="font-size:14px">`;
    html += (radius == null) ? `<tr style="background-color:transparent!important"><td><b>Maj : </b></td><td style="padding-left:5px">${lastSeen}</td></tr>` : '';
    html += `<tr style = "background-color:transparent!important" ><td><b>Position : </b></td><td style="padding-left:5px"><a href="${urlNav}" target="_blank">${latlon}</a></td></tr >`;
    html += (radius == null) ? `<tr style="background-color:transparent!important"><td><b>Distance : </b></td><td style="padding-left:5px">${distance}</td></tr>` : '';
    html += (radius == null) ? `<tr style="background-color:transparent!important"><td colspan="2" class="text-center"><a href="${urlNav}" target="_blank">Y aller !</a></td></tr>` : '';
    html += (radius != null) ? `<tr style="background-color:transparent!important"><td><b>Rayon : </b></td><td style="padding-left:5px">${radius} m</td></tr>` : '';
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

        addMarker(jcGeofence.identifier, jcGeofence.lat, jcGeofence.lon, jcGeofence.name, jcGeofence.radius);
        addCircle(jcGeofence.identifier, jcGeofence.lat, jcGeofence.lon, jcGeofence.radius);
        addGeofenceToTable('.currentEq', {
            id: jcGeofence.identifier,
            name: jcGeofence.name,
            lat: jcGeofence.lat,
            lon: jcGeofence.lon,
            radius: jcGeofence.radius,
            parent: jcGeofence.parent
        }, false)
        parents.push(jcGeofence.parent)
    });

    allJcGeofencesConfig.forEach(function (geoConfig) {

        let geofenceData = {
            id: geoConfig.id,
            lat: geoConfig.lat,
            lon: geoConfig.lon,
            name: geoConfig.name || '',
            radius: geoConfig.radius,
            parent: geoConfig.parent
        };
        addMarker(geofenceData.id, geofenceData.lat, geofenceData.lon, geofenceData.name, geofenceData.radius);
        addCircle(geofenceData.id, geofenceData.lat, geofenceData.lon, geofenceData.radius);
        addGeofenceToTable('.otherItems', geofenceData, true);
        if ($.inArray(geoConfig.id, parents) !== -1) {
            $('.otherItems tbody tr:last').hide();
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
        console.log('je rentre dans "macarte on click"');
        var latlngStr = e.latlng.toString();
        var position = latlngStr.replace('LatLng(', '').replace(')', '');
        var positionArr = position.split(', ');
        var lat = positionArr[0];
        var lng = positionArr[1];

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
                // console.log("removing config id", id);
                actionOnConfigGeo({ id: id }, 'remove');
            }
            else {

                // console.log("removing cmd id", id);
                actionOnCmdGeo({ id: id }, 'remove');
                if (parent != undefined) {
                    $('.otherItems tr[data-id=' + parent + ']').show();
                }
            }
            elt.remove();
        }
    });
})

$('body').off('click', '.addGeoToEquipment').on('click', '.addGeoToEquipment', function () {
    let tr = $(this).closest("tr");
    var geo = tr.getValues('.geoAttr')[0];
    geo['eqId'] = eqId;
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
        return;
    }
    addGeofenceToTable('.currentEq', geo)
    tr.hide();

    actionOnCmdGeo(geo)
});

$('body').off('change', '.forConfig').on('change', '.forConfig', function () {
    console.log('je rentre dans "change for Config"');
    let elt = $(this).closest("tr");

    let geofenceData = {
        id: elt.find('.geoAttr[data-l1key=id]').value(),
        lat: elt.find('.geoAttr[data-l1key=lat]').value(),
        lon: elt.find('.geoAttr[data-l1key=lon]').value(),
        radius: elt.find('.geoAttr[data-l1key=radius]').value(),
        name: elt.find('.geoAttr[data-l1key=name]').value()
    };
    actionOnConfigGeo(geofenceData);

});

$('body').off('change', '.geoAttr').on('change', '.geoAttr', function () {
    console.log('je rentre dans "change for geoAttr"');
    let elt = $(this).closest("tr");
    let id = elt.find('.geoAttr[data-l1key=id]').value();
    let lat = elt.find('.geoAttr[data-l1key=lat]').value();
    let lon = elt.find('.geoAttr[data-l1key=lon]').value();
    let name = elt.find('.geoAttr[data-l1key=name]').value();
    let radius = elt.find('.geoAttr[data-l1key=radius]').value();

    updateCircle(id, lat, lon, radius);
    updateMarker(id, lat, lon, name, radius);
})

function addCircle(id, lat, lon, radius) {
    var circle = L.circle([lat, lon], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.5,
        radius: radius,
        identifier: id
    }).addTo(macarte);
    circles.push(circle);
}

function updateCircle(id, lat, lon, radius) {
    var circle = circles.find(i => i.options.identifier == id);
    if (circle) {
        circle.setLatLng([lat, lon]);
        circle.setRadius(radius);
    }
}

function addMarker(id, lat, lon, name, radius = null, lastSeen = null, distance = null, customIcon = null, withCluster = false) {

    if (customIcon == null) {
        var myIcon = L.icon({
            iconUrl: 'plugins/JeedomConnect/data/img/pin.png',
            iconSize: [32, 48],
            iconAnchor: [16, 48],
            popupAnchor: [-3, -40],
        });
    }
    else {

        var myIcon = L.icon({
            iconUrl: customIcon,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [-3, -40],
        });
    }
    let marker = L.marker([lat, lon], { icon: myIcon, title: name, identifier: id })

    let latlon = lat + ',' + lon;
    let popUpData = getHtmlPopUp(name, lastSeen, latlon, distance, radius);

    marker.bindPopup(popUpData);
    markers.push(marker);
    if (withCluster) {
        markerClusters.addLayer(marker);
    }
    else {
        marker.addTo(macarte);
    }

}

function updateMarker(id, lat, lon, name = null, radius = null) {
    var marker = markers.find(i => i.options.identifier == id);
    if (marker) {
        var noName = (name == null) ? marker.options.title : name;
        marker.setLatLng([lat, lon]);
        var popUpData = getHtmlPopUp(noName, null, lat + ',' + lon, null, radius);
        marker.setPopupContent(popUpData);
    }
}

$('body').off('click', '.geoFocusMarker').on('click', '.geoFocusMarker', function () {
    let elt = $(this).closest("tr");
    let lat = elt.find('.geoAttr[data-l1key=lat]').value();
    let lon = elt.find('.geoAttr[data-l1key=lon]').value();

    getFocus([lat, lon], 15)
})

function refreshJcPosition(cmdId, position) {
    let data = position.split(',');
    updateMarker(cmdId, data[0], data[1])
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
    console.log('je rentre dans btnAddCoordinates');

    let lat = $(this).data('lat');
    let lon = $(this).data('lon');
    let id = makeid();
    let geofenceData = {
        id: id,
        lat: lat,
        lon: lon,
        radius: 100
    };
    addGeofenceToTable('.otherItems', geofenceData, true);

    macarte.closePopup();

    addMarker(id, geofenceData.lat, geofenceData.lon, '');
    addCircle(id, geofenceData.lat, geofenceData.lon, geofenceData.radius);
    actionOnConfigGeo(geofenceData);

});

//----- FOR CMD
function actionOnCmdGeo(geofence, type = 'createOrUpdate') {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'createOrUpdateCmdGeo',
            type: type,
            data: geofence
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
                geofence['id'] = data.result.id;
                return geofence; //set something to refresh the page    
            }
        }
    });
}

$('body').on('JC_UPDATE_CMD_GEO', function (_event, _options) {
    // console.log('getting event : ', _options['previousId'], _options['id']);
    $('.currentEq tr[data-id=' + _options['previousId'] + ']').attr('data-parent', _options['previousId']);
    $('.currentEq tr[data-id=' + _options['previousId'] + ']').find('span.geoAttr[data-l1key=id]').text(_options['id']);
    $('.currentEq tr[data-id=' + _options['previousId'] + ']').attr('data-id', _options['id']);
})

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
            else {
                //set something to refresh the page    
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