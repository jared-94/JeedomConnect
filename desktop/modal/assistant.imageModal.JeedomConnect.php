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
  .row:after {
  content: "";
  display: table;
  clear: both;
}

.column {
  float: left;
  width: 25%;
  padding: 10px;
}

/* Style the images inside the grid */
.column img {
  opacity: 0.8;
  cursor: pointer;
}

.selected {
	border: solid;
	border-color: red;
}
</style>
<div style="overflow-y:auto; overflow-x:hidden; height:450px;">
<input type="file" accept="image/*" id="file-input" style="display:none;" >

<br/>
Images internes
<div class="row" id="internal-div"></div>
Images personnelles
<a class="btn btn-default roundedRight" onclick="addImage()">
	<i class="fas fa-plus-square"></i>
	Ajouter
</a>

<div class="row" id="user-div">

</div>
</div>
<script>

function addImage() {
	$("#file-input").click();
}

$("#file-input").change(function() {
	var fd = new FormData();
	if($(this).prop('files').length > 0)
    {
        file =$(this).prop('files')[0];
        fd.append("file", file);
		fd.append("action", "uploadImg");
		console.log(fd)
		$.post({
            url: 'plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
				setImageModalData();
            },
        });

    }
});

function selectImage(img) {
	$(".selected").removeClass("selected");
	$(img).addClass("selected");
	$( "#validateImg").click();
}
function setImageModalData(selected) {
	$.post({
        url: 'plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php',
		data: { 'action': 'getImgList' },
		cache: false,
        success: function(response){
            files = $.parseJSON(response).result;
			internalContent = "";
			for (var key in files.internal) {
				if (files.internal[key] == selected) {
					internalContent += `<div class="column">
					<img src="plugins/JeedomConnect/data/img/${files.internal[key]}" id="${files.internal[key]}" style="width:40px" class="selected" onclick="selectImage(this);">
					</div>`;
				} else {
					internalContent += `<div class="column">
					<img src="plugins/JeedomConnect/data/img/${files.internal[key]}" id="${files.internal[key]}" style="width:40px" onclick="selectImage(this);">
					</div>`;
				}

			}
			$("#internal-div").html(internalContent);
			userContent = "";
			for (var key in files.user) {
				if (files.user[key] == selected) {
					userContent += `<div class="column">
					<img src="plugins/JeedomConnect/data/img/user_files/${files.user[key]}" id="user_files/${files.user[key]}" class="selected" style="width:40px" onclick="selectImage(this);">
					</div>`;
				} else {
					userContent += `<div class="column">
					<img src="plugins/JeedomConnect/data/img/user_files/${files.user[key]}" id="user_files/${files.user[key]}"  style="width:40px" onclick="selectImage(this);">
					</div>`;
				}
			}
			$("#user-div").html(userContent);
        },
    });
}


</script>
