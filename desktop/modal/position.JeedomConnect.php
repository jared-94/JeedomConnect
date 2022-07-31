<?php

include_file('desktop', 'leaflet/leaflet', 'css', 'JeedomConnect');
include_file('desktop', 'leaflet/MarkerCluster.Default', 'css', 'JeedomConnect');
include_file('desktop', 'leaflet/MarkerCluster', 'css', 'JeedomConnect');

include_file('desktop', 'leaflet/leaflet', 'js', 'JeedomConnect');
include_file('desktop', 'leaflet/leaflet.markercluster', 'js', 'JeedomConnect');
?>

<!-- Nous chargeons les fichiers CDN de Leaflet. Le CSS AVANT le JS -->
<style type="text/css">
    #map {
        /* la carte DOIT avoir une hauteur sinon elle n'apparaît pas */
        height: 600px;
        width: 650px;
    }
</style>

<div id="map">
    <!-- Ici s'affichera la carte -->
</div>

<div id="jcMapScript">

</div>

<div class='form-group'>
    <label class='col-xs-3'>Zoom :</label>
    <div class='col-xs-9'>
        <label class='radio-inline'>
            <label><input type="radio" class="zoomSelection" name="radio" value="home" checked><span class="defaultText"></span></label><br />
            <label><input type="radio" class="zoomSelection" name="radio" value="all"> Toutes les positions</label>
        </label>
    </div>
</div>


<!-- Fichiers Javascript -->
<?php include_file('desktop', 'position.JeedomConnect', 'js', 'JeedomConnect'); ?>