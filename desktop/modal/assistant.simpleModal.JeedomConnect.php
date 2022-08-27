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
</style>
<div style="display: none;" id="div_simpleModalAlert"></div>
<form class="form-horizontal" style="overflow: hidden;">
  <div id="simpleModalAlert" style="display:none"></div>
  <ul id="modalOptions" style="padding-left:10px; list-style-type: none;">
    <li id="object-li" style="display:none;">
      <div class='form-group'>
        <label class='col-xs-3 '>Objet</label>
        <div class='col-xs-9'>
          <select id="object-select" onchange="objectSelected();">
            <option value="none">{{Aucun}}</option>
            <?php
            foreach ((jeeObject::buildTree(null, false)) as $object) {
              echo '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
            }
            ?>
          </select>
        </div>
    </li>
  </ul>
</form>

<?php include_file('desktop', 'assistant.simpleModal.JeedomConnect', 'js', 'JeedomConnect'); ?>