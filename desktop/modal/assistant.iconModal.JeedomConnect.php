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

function get_all_files($dir){
  $result = array();
  $dh = new DirectoryIterator($dir);   
  
  foreach ($dh as $item) {
      if (!$item->isDot() && substr($item, 0, 1) != '.' ) {
          if ($item->isDir()) {
            $tmp = get_all_files(  "$dir/$item" );
            $result = array_merge($result , $tmp );
          } 
          else {
              array_push($result, array('path' =>  preg_replace('#/+#','/', $item->getPathname() )  , 'name' => $item->getFilename() ) );
          }
      }
  }
  return $result;
}

$customPath = config::byKey('userImgPath', 'JeedomConnect') ;

$myFiles = get_all_files($customPath);


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
    <div role="tabpanel" class="tab-pane jcpanel active" id="tabicon" source="jeedom" style="width:calc(100% - 20px); display:none">
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
    <div role="tabpanel" class="tab-pane jcpanel" id="tabicon" source="md" style="width:calc(100% - 20px); display:none">
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
    <div role="tabpanel" class="tab-pane jcpanel active" id="tabicon" source="fa" style="width:calc(100% - 20px); display:none">
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
    <div role="tabpanel" class="tab-pane jcpanel active" id="tabimg" source="jc" style="width:calc(100% - 20px); display:none">
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
    <div role="tabpanel" class="tab-pane jcpanel active" id="tabimg" source="user" style="width:calc(100% - 20px); display:none">
      <div class="imgContainer">
        <div id="div_imageGallery" source="user">
          <?php
          $div = '';
          
          foreach ($myFiles as $img) {
            $div .= '<div class="divIconSel divImgSel">';
            $div .= '<div class="cursor iconSel"><img source="user" name="'.str_replace($customPath, '', $img['path']).'" class="img-responsive" src="' . $img['path'] . '"/></div>';
            $div .= '<div class="iconDesc">' . $img['name']. '</div>';
            $div .= '</div>';
          }
          echo $div;
          ?>
        </div>
      </div>
    </div>

	</div>

  <div id="mySearch" class="input-group col-sm-10" style="margin-left:6px;margin-top:6px;">
    <div class="col-sm-4 ">
      <div id="icon-params-div" class="input-group col-sm-12" style="display:none">
        <label style="float:left; margin-top:5px">Couleur :</label>
        <input class="form-control roundedLeft" style="width : 100px;margin-left:10px;" type="color" id="mod-color-picker" value='' onchange="iconColorDefined(this)">
          <input class="form-control roundedLeft" style="width : 100px;"  id="mod-color-input" value=''>
      </div>
      <div id="img-params-div" class="checkbox-group  col-sm-5" style="display:none">
        <label style="float:left">Noir et blanc :</label>
          <input class="form-control roundedLeft" style="margin-left:5px" id="bw-input" type="checkbox">
      </div>
      <div class="pull-right btnImgUserAdd  col-sm-6" style="display:none;">
          <span class="btn btn-default btn-file">
            <i class="fas fa-plus-square"></i> Ajouter<input id="bt_uploadImg" type="file" name="file" multiple="multiple" data-path="" style="display: inline-block;">
          </span>
      </div>
    </div>
    <div class="col-sm-7 ">
      <input class="form-control" placeholder="{{Rechercher}}" id="in_searchIconSelector">
    </div>
    <div class="col-sm-1">
      <a id="bt_resetSearchIcon" class="btn roundedRight" style="/*width:30px; margin-top:32px;*/"><i class="fas fa-times"></i> </a>
    </div>
    
  </div>

</div>


<script>

$('#bt_uploadImg').fileupload({
    add: function (e, data) {
			let currentPath = $('#bt_uploadImg').attr('data-path');
			data.url = 'core/ajax/jeedom.ajax.php?action=uploadImageIcon&filepath='+userImgPath;
      data.submit();
    },
		done: function(e, data) {
			if (data.result.state != 'ok') {
				$('#div_iconSelectorAlert').showAlert({message: data.result.result, level: 'danger'});
				return;
			}
      var name = data.result.result.filepath.replace(/^.*[\\\/]/, '');
      div = '<div class="divIconSel divImgSel">';
      div += '<div class="cursor iconSel"><img class="img-responsive" source="user" name="' + name + '" src="' + userImgPath + name + '" /></div>';
      div += '<div class="iconDesc">' + name + '</div>';
      div += '</div>';
      $("#div_imageGallery[source='user']").append(div);

			$('#div_iconSelectorAlert').showAlert({message: 'Fichier(s) ajouté(s) avec succès', level: 'success'});
		}
	});

  $('#iconModal').off('click', '.divIconSel').on('click', '.divIconSel', function() {
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
  $('.divIconSel').css({'display': ''})
	$('.iconCategory').css({'display': ''})

	if (search != '') {
		search = normTextLower(search)
		$('.iconDesc').each(function() {
      iconName = normTextLower($(this).text())
			if (iconName.indexOf(search) == -1) {
				$(this).closest('.divIconSel').css({'display': 'none'})
			}
		})
	}

	var somethingFound = 0
	$('.iconCategory').each(function() {
		var hide = true
		if ($(this).find('.divIconSel:visible').length == 0) {
			$(this).css({'display': 'none'})
		} else {
			somethingFound +=1
		}
	})
	if (somethingFound == 0) {
		$('.generalCategory').css({'display': ''})
	}
})

$('#bt_resetSearchIcon').on('click', function() {
	$('#in_searchIconSelector').val('').keyup()
})


$('#iconModal ul li a').click(function() {
  $('.jcpanel.tab-pane').css('display', 'none');

  var type = $(this).attr('href').replace('#','');
  
  $('.tab-pane[source="'+type+'"]').css('display', 'block');
  
  if (type == 'user'){
    $('.btnImgUserAdd').css('display', 'block');
  }
  else{
    $('.btnImgUserAdd').css('display', 'none');
  }
  
  if (['jc', 'user'].indexOf(type) > -1){
    setImgParams();
  }
  else{
    setIconParams()
  }
  
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
    $(`.tab-pane[source="${selectedIcon.source}"]`).find(`[name="${decodeURI(selectedIcon.name)}"]`).closest('.divIconSel').addClass('iconSelected');
    setTimeout(function() {
			elem = $('div.divIconSel.iconSelected')
			if (elem.position()) {
				container = $('#iconModal > .tab-content')
				pos = elem.position().top + container.scrollTop() - container.position().top
				container.animate({scrollTop: pos-20})
			}
		}, 250)
  }

  if (selectedIcon.color != 0) {
    $("#mod-color-picker").val("#" + selectedIcon.color);
    $("#mod-color-input").val("#" + selectedIcon.color);
  }
  if (selectedIcon.shadow === "true") {
    $("#bw-input").prop('checked', true);
  }


});

</script>
