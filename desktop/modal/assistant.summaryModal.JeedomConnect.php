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

<link href="/plugins/JeedomConnect/desktop/css/md/css/materialdesignicons.css" rel="stylesheet">

<div>
	<div style="display:none;" id="summary-alert"></div>
	<div class="input-group pull-right " style="display:inline-flex;">
		<span class="input-group-btn">
			</a><a class="btn btn-sm btn-success roundedLeft" onclick="saveSummary()"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
			</a><a class="btn btn-sm btn-warning " onclick="hideSummary()"><i class="fas fa-times"></i> {{Annuler}}
				<a class="btn btn-sm btn-danger roundedRight " onclick="removeSummary()"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
		</span>
	</div>
	<div class="col-sm-12">
		<div class="col-sm-2">
			<div class="alert alert-info" id="summaryDescription"></div>
			<div class="alert alert-info" id="summaryVariables"></div>
		</div>

		<div class="col-sm-10 borderLef">
			<h3 style="margin-left:25px;">Options du résumé</h3><br>
			<div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
			<form class="form-horizontal summaryForm">
				<ul id="summaryOptions" style="padding-left:10px; list-style-type:none;">
				</ul>
			</form>
		</div>
	</div>

</div>

<?php //include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect'); 
?>
<?php include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect'); ?>
<?php include_file('desktop', 'assistant.JeedomConnect', 'css', 'JeedomConnect'); ?>