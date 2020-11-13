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
    content:" *";
    color: red;
  }
</style>

<form class="form-horizontal" style="overflow: hidden;">
  <ul id="modalOptions" style="padding-left:10px; list-style-type: none;"></ul>
</form>


<script>
function setSimpleModalData(options) {
	items = [];
	options.forEach(option => {
		if (option.type == "enable") {
			var value = option.value ? 'checked' : '';
			enable = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Actif</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-enable-input" ${value}></div></div></div></li>`;
			items.push(enable);
		} else if (option.type == "name") {
			var value = option.value ? option.value : '';
			name = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Nom</label>
			<div class='col-xs-9'><div class='input-group'><input style="width:150px;" id="mod-name-input" value='${value}'></div></div></div></li>`;
			items.push(name);
		} else if (option.type == "icon") {
			var value = option.value ? option.value : '';
			icon = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Icone</label>
			<div class='col-xs-9'><div class='input-group'><input style="width:150px;" id="mod-icon-input" value='${value}'></div></div></div></li>`;
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
			items.push(`<li>${option.value}</li>`);
		} else if (option.type == "expanded") {
			var value = option.value ? 'checked' : '';
			expanded = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Développé par défaut</label>
			<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="mod-expanded-input" ${value}></div></div></div></li>`;
			items.push(expanded);
		} else if (option.type == "widget") {
			widget = `<li><div class='form-group'>
			<label class='col-xs-3  required' >Widget</label>
			<div class='col-xs-9'><div class='input-group'>
			<select style="width:250px;" id="mod-widget-input">`
			
			configData.payload.widgets.forEach(item => {
				if (option.choices.includes(item.type)) {
					var name = getWidgetPath(item.id);
					widget += `<option style="width:150px;" value="${item.id}" name="${name}">${name}</option>`;
				}
			})			
			widget += `</select></div></div></div></li>`;
			items.push(widget);
		}
	});
	
	
	
	$("#modalOptions").html(items.join(""));
}


</script>	
