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

sendVarToJS('selectedIcon', [
	'source' => init('source', 0),
	'name' => init('name', 0),
	'color' => init('color', 0),
  'shadow' => init('shadow', 0)
]);

?>

<div style="display: none;" id="div_iconSelectorAlert"></div>
<ul class="nav nav-tabs" role="tablist">
  <?php if (init('withIcon') == "1") { ?>
	<li role="presentation" class="active"><a href="#jeedom" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-icons"></i> {{Icônes Jeedom}}</a></li>
	<li role="presentation"><a href="#md" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-icons"></i> {{Icônes Material Design}}</a></li>
  <li role="presentation"><a href="#fa" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-icons"></i> {{Icônes Font Awesome}}</a></li>
  <?php } ?>
  <?php if (init('withImg') == "1") { ?>
  <li role="presentation" <?php if (init('withIcon') != "1") { echo 'class="active"'; } ?>>
    <a href="#jc" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-image"></i> {{Images Jeedom Connect}}</a></li>
  <li role="presentation"><a href="#user" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-image"></i> {{Images Persos}}</a></li>
  <?php } ?>
</ul>

<div class="tab-content" style="overflow-y:scroll;">
  <!-- Jeedom icons -->
    <div role="tabpanel" class="tab-pane active" id="tabicon" source="jeedom" style="width:calc(100% - 20px); display:none">
      <?php
      $scanPaths = array('core/css/icon');
      $div = '';
      foreach ($scanPaths as $root) {
        $ls = ls($root, '*');
        foreach ($ls as $dir) {
          $root .= '/';
          if (!is_dir($root . $dir) || !file_exists($root . $dir . '/style.css')) {
            continue;
          }
          $fontfile = $root . $dir . 'fonts/' . substr($dir, 0, -1) . '.ttf';
          if (!file_exists($fontfile)) continue;

          $css = file_get_contents($root . $dir . '/style.css');
          $research = strtolower(str_replace('/', '', $dir));
          preg_match_all("/\." . $research . "-(.*?):/", $css, $matches, PREG_SET_ORDER);
          $div .= '<div class="iconCategory"><legend>' . str_replace('/', '', $dir) . '</legend>';

          $number = 1;
          foreach ($matches as $match) {
            if (isset($match[0])) {
              if ($number == 1) {
                $div .= '<div class="row">';
              }
              $div .= '<div class="col-lg-1 divIconSel">';
              $icon = str_replace(array(':', '.'), '', $match[0]);
              $div .= '<span class="iconSel"><i source="jeedom" name="'.$icon.'" class=\'icon ' . $icon . '\'></i></span><br/><span class="iconDesc">' . $icon . '</span>';
              $div .= '</div>';
              $number++;
            }
          }
          if($number != 0){
            $div .= '</div>';
          }
          $div .= '</div>';
        }
      }
      echo $div;
      ?>
    </div>

    <!-- Material -->
    <div role="tabpanel" class="tab-pane" id="tabicon" source="md" style="width:calc(100% - 20px); display:none">
      <?php
      $div = '';
      $dir = 'plugins/JeedomConnect/desktop/css/md/css';

          $css = file_get_contents($dir . '/materialdesignicons.css');
          $research = 'mdi';
          preg_match_all("/\." . $research . "-(.*?):/", $css, $matches, PREG_SET_ORDER);
          $div .= '<div class="iconCategory">';

          $number = 1;
          foreach ($matches as $match) {
            if (isset($match[0])) {
              if ($number == 1) {
                $div .= '<div class="row">';
              }
              $div .= '<div class="col-lg-1 divIconSel">';
              $icon = str_replace(array(':', '.', 'mdi-'), '', $match[0]);
              $div .= '<span class="iconSel"><i source="md" name="'.$icon.'" class=\'mdi mdi-' . $icon . '\'></i></span><br/><span class="iconDesc">' . 'mdi-' .$icon . '</span>';
              $div .= '</div>';
              $number++;
            }
          }
          if($number != 0){
            $div .= '</div>';
          }
          $div .= '</div>';
      echo $div;
      ?>
    </div>

    <!-- FA -->
    <div role="tabpanel" class="tab-pane active" id="tabicon" source="fa" style="width:calc(100% - 20px); display:none">
      <?php

      $div = '';
      $dir = 'plugins/JeedomConnect/desktop/css';

      $solid = json_decode(file_get_contents($dir . '/fa-solid.json'), true);
      foreach ($solid as $name => $data) {
        $solid[$name] = 'fas';
      }
      $brand = json_decode(file_get_contents($dir . '/fa-brand.json'), true);
      foreach ($brand as $name => $data) {
        $brand[$name] = 'fab';
      }
      $icons = array_merge($solid, $brand);
      ksort($icons);
      $div .= '<div class="iconCategory">';

      $number = 1;
      foreach ($icons as $name => $data) {
          if ($number == 1) {
            $div .= '<div class="row">';
          }
          $div .= '<div class="col-lg-1 divIconSel">';
          $div .= '<span class="iconSel"><i source="fa" name="'.$name.'" prefix="'.$data.'" class=\'' . $data . ' fa-' . $name . '\'></i></span><br/><span class="iconDesc">fa-' . $name . '</span>';
          $div .= '</div>';
          $number++;
      }
      if($number != 0){
        $div .= '</div>';
      }
      $div .= '</div>';
      echo $div;
      ?>
    </div>

    <!-- JC -->
    <div role="tabpanel" class="tab-pane active" id="tabimg" source="jc" style="width:calc(100% - 20px); display:none">
      <div class="imgContainer">
			  <div id="div_imageGallery">
          <?php
          $div = '';
          $dir = __DIR__ . '/../../data/img';
          $files = scandir($dir);

          foreach ($files as $key => $name) {
            if ($name == "." || $name == ".." || $name == "user_files") { continue; }
            $div .= '<div class="divIconSel divImgSel">';
            $div .= '<div class="cursor iconSel"><img source="jc" name="'.$name.'" class="img-responsive" src="plugins/JeedomConnect/data/img/'. $name . '"/></div>';
            $div .= '<div class="iconDesc">' . $name . '</div>';
            $div .= '</div>';
          }
          echo $div;
          ?>
        </div>
      </div>
    </div>

    <!-- USER -->
    <div role="tabpanel" class="tab-pane active" id="tabimg" source="user" style="width:calc(100% - 20px); display:none">
      <div class="imgContainer">
        <span class="btn btn-default btn-file pull-right">
				  <i class="fas fa-plus-square"></i> Ajouter<input id="bt_uploadImg" type="file" name="file" multiple="multiple" data-path="" style="display: inline-block;">
			  </span>

			  <div id="div_imageGallery" source="user">
          <?php
          $div = '';
          $dir = __DIR__ . '/../../data/img/user_files';
          $files = scandir($dir);

          foreach ($files as $key => $name) {
            if ($name == "." || $name == "..") { continue; }
            $div .= '<div class="divIconSel divImgSel">';
            $div .= '<div class="cursor iconSel"><img source="user" name="'.$name.'" class="img-responsive" src="plugins/JeedomConnect/data/img/user_files/'. $name . '"/></div>';
            $div .= '<div class="iconDesc">' . $name . '</div>';
            $div .= '</div>';
          }
          echo $div;
          ?>
        </div>
      </div>
    </div>

	</div>

<div id="mySearch" class="input-group" style="margin-left:6px;margin-top:6px">
  <div id="icon-params-div" class="input-group" style="display:none">
    <label style="float:left; margin-top:5px">Couleur :</label>
    <input class="form-control roundedLeft" style="width : 100px;margin-left:10px;" type="color" id="mod-color-picker" value='' onchange="iconColorDefined(this)">
			<input class="form-control roundedLeft" style="width : 100px;"  id="mod-color-input" value=''>
	</div>
  <div id="img-params-div" class="input-group" style="display:none">
    <label style="float:left">Noir et blanc :</label>
			<input class="form-control roundedLeft" style="margin-left:5px" id="bw-input" type="checkbox">
	</div>

    <input class="form-control" placeholder="{{Rechercher}}" id="in_searchIconSelector">
  <div class="input-group-btn">
    <a id="bt_resetSearch" class="btn roundedRight" style="width:30px; margin-top:32px;"><i class="fas fa-times"></i> </a>
  </div>
</div>

</div>


<script>

$('#bt_uploadImg').fileupload({
    add: function (e, data) {
			let currentPath = $('#bt_uploadImg').attr('data-path');
			data.url = 'core/ajax/jeedom.ajax.php?action=uploadImageIcon&filepath=plugins/JeedomConnect/data/img/user_files/';
      data.submit();
    },
		done: function(e, data) {
			if (data.result.state != 'ok') {
				$('#div_iconSelectorAlert').showAlert({message: data.result.result, level: 'danger'});
				return;
			}
      var name = data.result.result.filepath.replace(/^.*[\\\/]/, '');
      div = '<div class="divIconSel divImgSel">';
      div += '<div class="cursor iconSel"><img class="img-responsive" src="plugins/JeedomConnect/data/img/user_files/' + name + '" /></div>';
      div += '<div class="iconDesc">' + name + '</div>';
      div += '</div>';
      $("#div_imageGallery[source='user']").append(div);

			$('#div_iconSelectorAlert').showAlert({message: 'Fichier(s) ajouté(s) avec succès', level: 'success'});
		}
	});


  $('.divIconSel').on('click', function() {
  	$('.divIconSel').removeClass('iconSelected');
  	$(this).closest('.divIconSel').addClass('iconSelected');
  });

  function iconColorDefined(c) {
    $("#mod-color-input").val(c.value);
  }

  function setIconParams() {
    $("#icon-params-div").show();
    $("#img-params-div").hide();
  }

  function setImgParams() {
    $("#icon-params-div").hide();
    $("#img-params-div").show();
  }


//searching
$('#in_searchIconSelector').on('keyup',function() {

	var search = $(this).value()
  if (search.length == 1) { return; }
  $('.divIconSel').show()
	$('.iconCategory').show()

	if (search != '') {
		search = normTextLower(search)
		$('.iconDesc').each(function() {
			if ($(this).text().indexOf(search) == -1) {
				$(this).closest('.divIconSel').hide()
			}
		})
	}

	var somethingFound = 0
	$('.iconCategory').each(function() {
		var hide = true
		if ($(this).find('.divIconSel:visible').length == 0) {
			$(this).hide()
		} else {
			somethingFound +=1
		}
	})
	if (somethingFound == 0) {
		$('.generalCategory').show()
	}
})

$('#bt_resetSearch').on('click', function() {
	$('#in_searchIconSelector').val('').keyup()
})

$('#iconModal ul li a[href="#md"]').click(function() {
  $('.tab-pane[source="jeedom"]').hide();
  $('.tab-pane[source="fa"]').hide();
  $('.tab-pane[source="jc"]').hide();
  $('.tab-pane[source="user"]').hide();
	$('.tab-pane[source="md"]').show();
  setIconParams();
  $('#in_searchIconSelector').keyup();
})

$('#iconModal ul li a[href="#jeedom"]').click(function() {
  $('.tab-pane[source="md"]').hide();
  $('.tab-pane[source="fa"]').hide();
  $('.tab-pane[source="jc"]').hide();
  $('.tab-pane[source="user"]').hide();
  $('.tab-pane[source="jeedom"]').show();
  setIconParams();
  $('#in_searchIconSelector').keyup();
})

$('#iconModal ul li a[href="#fa"]').click(function() {
  $('.tab-pane[source="jeedom"]').hide();
  $('.tab-pane[source="md"]').hide();
  $('.tab-pane[source="jc"]').hide();
  $('.tab-pane[source="user"]').hide();
  $('.tab-pane[source="fa"]').show();
  setIconParams();
  $('#in_searchIconSelector').keyup();
})

$('#iconModal ul li a[href="#jc"]').click(function() {
  $('.tab-pane[source="jeedom"]').hide();
  $('.tab-pane[source="md"]').hide();
  $('.tab-pane[source="fa"]').hide();
  $('.tab-pane[source="user"]').hide();
  $('.tab-pane[source="jc"]').show();
  setImgParams();
  $('#in_searchIconSelector').keyup();
})

$('#iconModal ul li a[href="#user"]').click(function() {
  $('.tab-pane[source="jeedom"]').hide();
  $('.tab-pane[source="md"]').hide();
  $('.tab-pane[source="fa"]').hide();
  $('.tab-pane[source="jc"]').hide();
  $('.tab-pane[source="user"]').show();
  setImgParams();
  $('#in_searchIconSelector').keyup();
})

$(function() {
  var buttonSet = $('.ui-dialog[aria-describedby="iconModal"]').find('.ui-dialog-buttonpane')
	buttonSet.find('#mySearch').remove()
	var mySearch = $('.ui-dialog[aria-describedby="iconModal"]').find('#mySearch')
	buttonSet.append(mySearch)
  if (selectedIcon.source == 0) {
    $('#iconModal ul li a').first().click();
  } else {
    $(`#iconModal ul li a[href="#${selectedIcon.source}"]`).click();
    $(`.tab-pane[source="${selectedIcon.source}"]`).find(`[name="${selectedIcon.name}"]`).closest('.divIconSel').addClass('iconSelected');
    setTimeout(function() {
			elem = $('div.divIconSel.iconSelected')
			if (elem.position()) {
				container = $('#iconModal > .tab-content')
				pos = elem.position().top + container.scrollTop() - container.position().top
				container.animate({scrollTop: pos-20})
			}
		}, 250)
  }


});

</script>
