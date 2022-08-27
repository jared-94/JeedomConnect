/**
 * 
 * coming from https://nouvelle-techno.fr/articles/pas-a-pas-inserer-une-carte-openstreetmap-sur-votre-site 
 * 
 */

var myDefaultIcon = L.icon({
    iconUrl: 'plugins/JeedomConnect/data/img/pin.png',
    iconSize: [32, 48],
    iconAnchor: [16, 48],
    popupAnchor: [-3, -40],
});

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
            lng = data.result.lng;
            $('.defaultText').text(data.result.defaultText)
        }
    }
});

// Fonction d'initialisation de la carte
function initLocalisationMap() {

    createJcMap();

    allJcPositions.forEach(function (jcPosition) {
        addMarker(jcPosition, false, true)
        addJcMapListener(jcPosition.id);
    });
    macarte.addLayer(markerClusters);

}

function getHtmlPopUp(geo) {
    var latlng = geo.lat + ',' + geo.lng;
    var urlNav = "https://www.google.com/maps/search/?api=1&query=" + latlng;

    let name = (geo.name) ? geo.name + ' (' + geo.id + ')' : '';
    var html = `<h4 class="text-center">${name}</h4>
            <table  style="font-size:14px">`;
    html += (!geo.radius) ? `<tr style="background-color:transparent!important"><td><b>Maj : </b></td><td style="padding-left:5px">${geo.lastSeen}</td></tr>` : '';
    html += `<tr style = "background-color:transparent!important" ><td><b>Position : </b></td><td style="padding-left:5px"><a href="${urlNav}" target="_blank">${latlng}</a></td></tr >`;
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
        getFocus([lat, lng], 13);
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
    // Créer l'objet "macarte" et l'insèrer dans l'élément HTML qui a l'ID "jcMap"
    macarte = L.map('jcMap').setView([lat, lng], 13);
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
    // var parents = $.map(allJcGeofences, function (elt, i) {
    //     return elt.parent || null;
    // });

    // var allConfig = $.map(allJcGeofencesConfig, function (elt, i) {
    //     return elt.id;
    // });

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

    // adding the search bar
    var geocoder = L.Control.geocoder({
        defaultMarkGeocode: false
    })
        .on('markgeocode', function (e) {
            var latlng = e.geocode.center;
            var html = e.geocode.html + '<br>'
            html += `<b>Lat :</b>${latlng.lat} - <b>Lng :</b>${latlng.lng}`;
            html += `<a class='btn btn-success center-block btnAddCoordinates' type='button' data-lat='${latlng.lat}' data-lng='${latlng.lng}'>Créer une zone ici</a><br/> `;
            var marker = L.marker(latlng, { icon: myDefaultIcon }).addTo(macarte).bindPopup(html).openPopup();
            macarte.fitBounds(e.geocode.bbox);
        })
        .addTo(macarte);

}


var macarte = null;
var markerClusters;
var markers = []; // Nous initialisons la liste des marqueurs
var circles = []; // Nous initialisons la liste des cercles
if (!geo) {
    initMap();
}
else {
    $('#jcMap').addClass("col-sm-6")

    getGeofences();
    initGeofenceMap();

    macarte.off('click').on('click', function (e) {
        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);

        var html = `<b><u>Nouvelle position</u></b><br>
                <b>Lat :</b>${lat} - <b>Lng :</b>${lng}<br><br>
                <a class='btn btn-success center-block btnAddCoordinates' type='button' data-lat='${lat}' data-lng='${lng}'>Créer une zone ici</a><br/> `;

        popup
            .setLatLng(e.latlng)
            .setContent(html)
            .openOn(macarte);
    });
}


async function initMap() {
    var infoPositions = await getInfoPosition();
    allJcPositions = infoPositions.result;
    initLocalisationMap();
}

var popup = L.popup();


function addGeofenceToTable(elt, geo, config, hide = false) {

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
    tr += '<input class="geoAttr form-control input-sm" data-l1key="lng" placeholder="{{Longitude}}">';
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
        if (!geo.parent) {
            tr += '<i class="fas fa-plus-circle pull-right cursor addGeoToConfig geoOpt" title="Ajouter cette zone aux modèles partagés" data-genid="' + makeid() + '"></i>';
        }
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
    if (hide) $(elt + ' tbody tr:last').hide();
}

$('body').off('click', '.removeGeo').on('click', '.removeGeo', function () {
    var isConfig = $(this).hasClass('forConfig');
    var elt = $(this).closest('tr');
    var id = elt.find('.geoAttr[data-l1key=id]').text();
    var parent = elt.data('parent');

    var cplt = isConfig ? 'des modèles' : 'de votre équipement';

    bootbox.confirm("Supprimer cette zone " + cplt + " ?", function (result) {
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
            $('.applyGeoModif').show();
        }
    });
})

function controlFields(geo) {
    var msgErr = [];
    if (geo.name == '') msgErr.push('nom');
    if (geo.lat == '') msgErr.push('latitude');
    if (geo.lng == '') msgErr.push('longitude')
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
    $('.applyGeoModif').show();
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

        addGeofenceToTable('.currentEq', geo, false)

        removeCircle(oldId);
        addCircle(geo, 'green');

        tr.hide();
    }
}

$('body').off('click', '.addGeoToConfig').on('click', '.addGeoToConfig', function () {
    let geo = getGeofencesData($(this));

    addConfAndUpdateEqlogic(geo, $(this).data('genid'), $(this))
});

async function addConfAndUpdateEqlogic(geo, parentId, elt) {
    var cmdId = geo.id;
    geo.id = parentId;

    var creaConfig = await actionOnConfigGeo(geo);

    //if creation failed then do not move item to equipment
    if (creaConfig.state == 'ok') {
        $('#div_alert').showAlert({
            message: "zone partagée avec succès",
            level: 'success'
        });

        updateParent(cmdId, parentId)
        //hide add button
        $(elt).hide();
        //add data-parent to main elt
        elt.closest("tr").attr('data-parent', parentId);

        //add line to conf, in order to display it if it gets removed from the eq at the same time
        addGeofenceToTable('.otherItems', geo, true, true);

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
    var circle = L.circle([geo.lat, geo.lng], {
        color: color,
        fillOpacity: 0.5,
        radius: geo.radius,
        id: geo.id,
        geoData: geo
    }).addTo(macarte);
    circles.push(circle);

    addMarker(geo);
}

function updateCoordinates(id, lat, lng) {
    var circle = circles.find(i => i.options.id == id);
    if (circle) {
        var geo = circle.options.geoData;
        geo['lat'] = lat.toFixed(6);
        geo['lng'] = lng.toFixed(6);
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
        circle.setLatLng([geo.lat, geo.lng]);
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

function addMarker(geo, isDraggable = true, withCluster = false) {

    if (!geo.icon) {
        var myIcon = myDefaultIcon
    }
    else {
        var originalWidth = geo.infoImg[0];
        var originalWheight = geo.infoImg[1];

        var width = Math.floor(40 * originalWidth / originalWheight);
        var halfWidth = Math.floor(width / 2);

        var myIcon = L.icon({
            iconUrl: geo.icon,
            iconSize: [width, 40],
            iconAnchor: [halfWidth, 40],
            popupAnchor: [-3, -40],
        });
    }
    var marker = L.marker([geo.lat, geo.lng], { icon: myIcon, draggable: isDraggable, title: geo.name, id: geo.id })

    if (isDraggable) {
        marker.on('dragend', function (event) {
            var position = marker.getLatLng();
            updateCoordinates(marker.options.id, position.lat, position.lng)
        });
    }

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
        marker.setLatLng([geo.lat, geo.lng]);
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

    getFocus([geo.lat, geo.lng], 15)
})

$("body").off('click', '.btnAddCoordinates').on('click', '.btnAddCoordinates', function () {

    let geofenceData = {
        id: makeid(),
        lat: $(this).data('lat'),
        lng: $(this).data('lng'),
        radius: 100
    };
    addGeofenceToTable('.otherItems', geofenceData, true);

    macarte.closePopup();

    addCircle(geofenceData);
    actionOnConfigGeo(geofenceData);

});

async function refreshJcPositionData(cmdId, position) {
    var geoData = await getInfoPosition(cmdId);

    if (geoData.state == 'ok') {
        // console.log('geoData', geoData.result[0]);
        updateMarker(geoData.result[0])
    }
}

function addJcMapListener(cmdId) {
    let script = `<script>
        jeedom.cmd.update['${cmdId}'] = function (_options) {
             refreshJcPositionData(${cmdId}, _options.value);
            }
            jeedom.cmd.update['${cmdId}']({ value: "#state#" })
    </script>`

    $('#jcMapScript').append(script);

}

async function getInfoPosition(cmdId = 'all') {

    const result = await $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'getAllPositions',
            id: cmdId
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

    if (cmdId == 'all') allJcPositions = result;
    return result;
}


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
async function actionOnConfigGeo(geofence, type = 'createOrUpdate') {
    const result = await $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'createOrUpdateConfigGeo',
            type: type,
            data: geofence,
            id: geofence.id
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

async function updateParent(cmdId, parentId) {
    const result = await $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'updateCmdParent',
            id: cmdId,
            parentId: parentId
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