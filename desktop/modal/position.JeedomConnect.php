<?php

/** @var eqLogic $eqLogic */
$eqLogic = eqLogic::byLogicalId('jcmapwidget', 'JeedomConnect');
if (!is_object($eqLogic)) {
    JCLog::error('Error - no MAP equipment found');
    $visible = $roomId = '';
} else {
    $visible = ($eqLogic->getIsVisible() == 1) ? 'checked' : '';
    $roomId = $eqLogic->getObject_id();
}


include_file('desktop', 'leaflet/leaflet', 'css', 'JeedomConnect');
include_file('desktop', 'leaflet/MarkerCluster.Default', 'css', 'JeedomConnect');
include_file('desktop', 'leaflet/MarkerCluster', 'css', 'JeedomConnect');

include_file('desktop', 'leaflet/leaflet', 'js', 'JeedomConnect');
include_file('desktop', 'leaflet/leaflet.markercluster', 'js', 'JeedomConnect');
?>

<!-- Nous chargeons les fichiers CDN de Leaflet. Le CSS AVANT le JS -->
<style type="text/css">
    #jcMap {
        /* la carte DOIT avoir une hauteur sinon elle n'apparaît pas */
        height: 580px;
        width: 650px;
    }
</style>

<div id="jcMap">
    <!-- Ici s'affichera la carte -->
</div>

<div id="jcMapScript">

</div>

<div class='form-group row'>
    <label class='col-xs-3'>Zoom :</label>
    <div class='col-xs-9'>
        <label class='radio-inline'>
            <label><input type="radio" class="zoomSelection" name="radio" value="home" checked><span class="defaultText"></span></label><br />
            <label><input type="radio" class="zoomSelection" name="radio" value="all"> Toutes les positions</label>
        </label>
    </div>
</div>

<div class="form-group row">
    <label class="col-xs-3 control-label">{{Visible}}
        <sup>
            <i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="rendre visible cette map sur le dashboard"></i>
        </sup>
    </label>
    <div class="col-xs-9">
        <input type="checkbox" class="updateMapData" data-conf="isVisible" <?= $visible ?>>
    </div>
</div>

<div class="form-group row">
    <label class="col-xs-3 control-label">{{Objet parent}}</label>
    <div class="col-xs-5">
        <select id="sel_object" class="updateMapData form-control" data-conf="object_id">
            <option value="">{{Aucun}}</option>
            <?php
            $options = '';
            foreach ((jeeObject::buildTree(null, false)) as $object) {
                $selected = ($roomId == $object->getId()) ? 'selected' : '';
                $options .= '<option value="' . $object->getId() . '" ' . $selected . '>' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
            }
            echo $options;
            ?>
        </select>
    </div>
</div>




<!-- Fichiers Javascript -->
<?php include_file('desktop', 'position.JeedomConnect', 'js', 'JeedomConnect'); ?>