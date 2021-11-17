<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

?>

<style>
	.required:after {
		content: " *";
		color: red;
	}

	#widgetImg {
		display: block;
		margin-left: auto;
		margin-right: auto;
		width: 100px;
		margin-bottom: 25px;
		margin-top: 15px;
	}
</style>

<link href="/plugins/JeedomConnect/desktop/css/md/css/materialdesignicons.css" rel="stylesheet">

<div>
	<div style="display:none;" id="widget-alert"></div>
	<div class="row">
		<div class="input-group pull-right widgetMenu" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default roundedLeft" onclick="gotoGenTypeConfig()"><i class="fas fa-external-link-alt"></i> {{Configurer vos types génériques}}
				</a><a class="btn btn-sm btn-success saveWidget" onclick="saveWidgetBulk()" exit-attr="true"><i class="fas fa-check-circle"></i> {{Créer}}
				</a><a class="btn btn-sm btn-warning roundedRight" onclick="refreshAddWidgetBulk()"><i class="fas fa-times"></i> {{Réinitialiser}}
				</a>
			</span>
		</div>
		<div class="pull-left col-sm-10">
			<div class="col-sm-2">
				<img id="widgetImg" />
			</div>
			<div class="col-sm-10">
				<div class="row">
					<h3>Choix du widget</h3>
				</div>
				<div class="row">
					<div class="col-sm-4">
						<select name="widgetsList" id="widgetBulkList-select" onchange="refreshAddWidgetBulk();">
						</select>
					</div>
					<div class="col-sm-8">
						<div class="alert alert-info" id="widgetDescription"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-12 text-center noGenType">
		{{Désolé ...}} <br />{{aucune commande disponible avec un type générique correspond à ce widget !}} <i class="far fa-frown"></i>
		<!--
			HACK remove link to genType config when not needed anymore
		 -->
		<br /><br /><a onclick="gotoGenTypeConfig()" class="btn btn-sm" style="margin-top:5px;"><i class="fas fa-external-link-alt"></i> {{Configurer vos types génériques}}</a>
	</div>
	<div class="col-sm-12 optionWidgetBulk">
		<div style="display:inline-flex;">
			<h4 style="margin-left:25px;">Options du widget</h4>
			<!-- <br> -->
			<div style="font-style: italic;margin-left:25px;margin-top:15px;font-size: 0.8em;"><span class="required"></span> Les options marquées d'une étoile sont obligatoires.</div>
			<div class="alreadyExist" style="font-style: italic;margin-left:25px;margin-top:15px;font-size: 0.8em;display:hide">Certains des équipements/commandes ci-dessous ont déjà été utilisés sur d'autres widgets de votre système. Les lignes ont été mises en couleur et décochées !</div>
		</div>
		<form class="form-horizontal widgetForm" style="overflow: hidden;">
			<table class="table table-bordered table-condensed" id="table_widgets">
			</table>
		</form>
	</div>

</div>

<?php include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'widgetBulkModal.JeedomConnect', 'js', 'JeedomConnect'); ?>