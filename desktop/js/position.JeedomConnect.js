/**
 * 
 * coming from https://nouvelle-techno.fr/articles/pas-a-pas-inserer-une-carte-openstreetmap-sur-votre-site 
 * 
 */

function getPositions() {
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            'action': 'getAllPositions'
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function (data) {
            // console.log(data)
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
function initMap() {


    // Créer l'objet "macarte" et l'insèrer dans l'élément HTML qui a l'ID "map"
    macarte = L.map('map').setView([lat, lon], 13);
    markerClusters = L.markerClusterGroup(); // Nous initialisons les groupes de marqueurs

    // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        // Il est toujours bien de laisser le lien vers la source des données
        attribution: '&copy; <a href="//osm.org/copyright">OpenStreetMap</a>',
        minZoom: 1,
        maxZoom: 20
    }).addTo(macarte);
    // Nous parcourons la liste des villes

    allJcPositions.forEach(function (jcPosition) {
        addJcMapListener(jcPosition.cmdId)
        // console.log('position: :', jcPosition);
        // Nous définissons l'icône à utiliser pour le marqueur, sa taille affichée (iconSize), sa position (iconAnchor) et le décalage de son ancrage (popupAnchor)
        var myIcon = L.icon({
            iconUrl: jcPosition.icon,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [-3, -40],
        });
        var marker = L.marker([jcPosition.lat, jcPosition.lon], { icon: myIcon, title: jcPosition.name, customJcCmdId: jcPosition.cmdId }); //.addTo(macarte);
        var latlon = jcPosition.lat + ',' + jcPosition.lon;


        var popUpData = getHtmlPopUp(jcPosition.name, jcPosition.lastSeen, latlon);

        marker.bindPopup(popUpData);
        markerClusters.addLayer(marker); // Nous ajoutons le marqueur aux groupes
        markers.push(marker); // Nous ajoutons le marqueur à la liste des marqueurs
    });
    macarte.addLayer(markerClusters);

}



function getHtmlPopUp(name, lastSeen, latlon) {
    var urlNav = "https://www.google.com/maps/search/?api=1&query=" + latlon;

    var html = `<h4 class="text-center">${name}</h4>
            <table  style="font-size:14px">
                <tr style="background-color:transparent!important"><td><b>Maj : </b></td><td style="padding-left:5px">${lastSeen}</td></tr>
                <tr style="background-color:transparent!important"><td><b>Position : </b></td><td style="padding-left:5px"><a href="${urlNav}" target="_blank">${latlon}</a></td></tr>
                <tr style="background-color:transparent!important"><td colspan="2" class="text-center"><a href="${urlNav}" target="_blank">Y aller !</a></td></tr>
            </table>`;

    return html;
}


$("body").on('change', '.zoomSelection', function () {

    if ($(this).val() == 'all') {
        var group = new L.featureGroup(markers); // Nous créons le groupe des marqueurs pour adapter le zoom
        macarte.fitBounds(group.getBounds().pad(0.5)); // Nous demandons à ce que tous les marqueurs soient visibles, et ajoutons un padding (pad(0.5)) pour que les marqueurs ne soient pas coupés
    }
    else {
        macarte.setView([lat, lon], 13);  //on recentre sur le point initial
    }
});


var macarte = null;
var markerClusters;
var markers = []; // Nous initialisons la liste des marqueurs
getPositions();
initMap();

var popup = L.popup();


macarte.on('click', function (e) {
    return;
    // console.log("position =>", e.latlng.toString());
    var position = e.latlng.toString().replace('LatLng(', '').replace(')', '');
    var positionArr = position.split(', ');
    var lat = positionArr[0];
    var lng = positionArr[1];
    var coordinates = lat + ',' + lng;

    var html = `<b><u>Nouvelle position</u></b><br>
                <b>Lat :</b>${lat} - <b>Lng :</b>${lng}<br><br>
                <a class='btn btn-success center-block' type='button' data-coordinates='${coordinates}'>Ajouter ici</a>`;


    popup
        .setLatLng(e.latlng)
        .setContent(html)
        .openOn(macarte);
});

function refreshJcPosition(cmdId, position) {

    var marker = markers.find(i => i.options.customJcCmdId == cmdId);
    if (marker) {
        // console.log('marker item =>', marker);
        data = position.split(',');
        marker.setLatLng([data[0], data[1]]);
    }
}

function addJcMapListener(id) {
    var script = `<script>
        jeedom.cmd.update['${id}'] = function (_options) {
             refreshJcPosition(${id}, _options.value);
            }
            jeedom.cmd.update['${id}']({ value: "#state#" })
    </script>`

    $('#jcMapScript').append(script);

}
