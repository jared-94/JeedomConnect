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

<div class="" style="margin:auto; width:800px; height:350px;">
  <div style="display:none;" id="notif-alert"></div>
  <h3 style="margin-left:25px;">Options de la notification</h3><br>
  <div style="margin-left:25px; font-size:12px; margin-top:-20px; margin-bottom:15px;">Les options marquées d'une étoile sont obligatoires.</div>
  <form class="form-horizontal" style="overflow: hidden;">
    <ul id="notifOptions" style="padding-left:10px; list-style-type: none;">
    </ul>
  </form>
</div>

<?php include_file('desktop', 'notifs.notifModal.JeedomConnect', 'js', 'JeedomConnect'); ?>