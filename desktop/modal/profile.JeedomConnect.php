<?php

// HACK delete file when gentype config in plugin is not needed anymore

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
require_once dirname(__FILE__) . '/../../core/class/JeedomConnect.class.php';


$allProfiles = JeedomConnectUtils::getAllAppProfil();

//create de option tags
$profileOptions = '';
foreach ($allProfiles as $item) {
	$profileOptions .= '<option value="' . $item['key'] . '" data-text="' . strtolower($item['name'])  . '">' . $item['name'] . '</option>';
}


?>
<div class="row">
	<div class="input-group pull-right" style="display:inline-flex;">
		<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
		<a class="btn btn-sm roundedLeft" id="bt_editJcProfil"><i class="fa fa-pencil-alt"></i> {{Editer}}
		</a><a class="btn btn-info btn-sm" id="bt_createJcProfil"><i class="fa fa-plus-circle"></i> {{Ajouter}}
		</a><a class="btn btn-success btn-sm" id="bt_saveJcProfil"><i class="fa fa-check-circle"></i> {{Sauvegarder}}
		</a><a class="btn btn-danger btn-sm roundedRight" id="bt_removeJcProfil"><i class="fa fa-minus-circle"></i> {{Supprimer}}
		</a>
	</div>


</div>
<div class="input-group pull-right">
	<span class="infoSave jcRed" style="display:none;" data-change="false">Pensez à sauvegarder !</span>
</div>

<div class=" row">
	<div class="form-group">
		<label class="col-sm-4 control-label">
			<legend><i class="fa fa-cogs"></i> {{Configurer le profil :}}
				<sup><i class="fas fa-question-circle  showInfoAppProfil"></i></sup>
			</legend>
		</label>
		<div class="col-sm-7 control-label">
			<select id="profileAllSelect" class="JC" style="width:auto">
				<?= $profileOptions; ?>
			</select>
			&nbsp;&nbsp;&nbsp;


		</div>
	</div>
</div>


<div class="input-group" style="margin-bottom:5px;">
	<input class="form-control roundedLeft" placeholder="{{Rechercher une autorisation}}" id="in_searchObject" />
	<div class="input-group-btn">
		<a class="btn" id="bt_resetObjectSearch" style="width:30px"><i class="fas fa-times"></i>
		</a><a class="btn" id="bt_openAll"><i class="fas fa-folder-open"></i>
		</a><a class="btn roundedRight" id="bt_closeAll"><i class="fas fa-folder"></i></a>
	</div>
</div>

<?php


$appProfilConfig = JeedomConnectUtils::getAppProfilConfig();
echo '<span class="mini">Sélectionner : <a href="#" id="btn_selectAll">Tous</a> / <a href="#" id="btn_deselectAll">Aucun</a></span><br/><br/>';
echo
"<div class='description infoAppProfil' style='display:none;'>Les accès que vous définissez ici permettent d'afficher ou masquer les menus/options dans le menu de l'application de chaque équipement.<br/> 
En aucun cas cela ne réalise l'option/action elle même au moment où vous cochez la case !<br/>
Vous pouvez donc définir plusieurs profils applicatifs (Parent, Enfant, Amis, Restreint, Locataire, ...) que vous lierez à vos équipements de façon à autoriser/restreindre certains accès.<br/><br/></div>";
// echo "Par exemple si vous cochez 'Recharger les données' :<br/>
// l'utilisateur aura la possibilité d'utiliser cette option dans le menu de son app ; 
// mais vous ne réalisez pas l'opération vous même à cet instant<br/><br/>
// </div>";
echo '<div class="panel-group jcMenuContainer" id="accordionProfil">';
$i = 0;
$groupName = '';
foreach ($appProfilConfig as $cred) {

	if ($groupName != $cred['groupName']) {
		if ($groupName != '') { //for the 1st iteration, no need to close item as it doesnt exist yet

			//closing the previous table
			echo '</table>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
			$i++;
		}

		// creating a new group
		echo '<div class="panel panel-default jcMenu">';

		echo '<div class="panel-heading">';
		echo '<h3 class="panel-title">';
		echo '<a class="accordion-toggle" data-toggle="collapse" aria-expanded="false" data-parent="" href="#jcMenu_' . $i . '">';
		echo '<span class="mini countElt"></span>&nbsp;&nbsp;&nbsp;' . $cred['groupName'] . '</a>';
		// echo $cred['groupName']  . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="mini countElt"></span> </a>';
		echo '</h3>';
		echo '</div>';

		echo '<div id="jcMenu_' . $i . '" class="panel-collapse collapse">';
		echo '<div class="panel-body">';
		echo '<table id=' . $i . ' class="table table-bordered table-condensed tableCmd">';
		echo '<tr>';
		echo '<th style="width:100px">{{Autorisé}}</th>';
		echo '<th>{{Nom}}</th>';
		echo '<th>{{Description}}</th>';
		echo '</tr>';

		$groupName = $cred['groupName'];
	}

	echo '<tr class="cmdLine">';
	// echo '<td><input type="checkbox" class="profilAttr " data-l1key="' . $cred['groupNameId'] . '" data-l2key="' . $cred['credentialId'] . '" data-l3key="' . $cred['credentialName'] . '" /></td>';
	echo '<td><input type="checkbox" class="profilAttr " data-l1key="' . $cred['credentialId'] . '" /></td>';
	// echo '<td class="profileName"><span class="profileAttrList" data-l1key="' . $cred['groupName'] . '" >' . $cred['credentialName'] . '</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="mini description">' . $cred['description'] . '</span></td>';
	echo '<td class="profileName"><span class="profileAttrList" data-l1key="' . $cred['groupName'] . '" >' . $cred['credentialName'] . '</span></td>';
	echo '<td><span class="description mini">' . $cred['description'] . '</span></td>';
	echo '</tr>';
}

echo '</div>';

?>

<!-- Fichiers Javascript -->
<?php include_file('desktop', 'profile.JeedomConnect', 'js', 'JeedomConnect'); ?>