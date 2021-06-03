<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$customPath = config::byKey('userImgPath', 'JeedomConnect') ;
sendVarToJS('userImgPath', $customPath );

// Déclaration des variables obligatoires
$plugin = plugin::byId('JeedomConnect');
sendVarToJS('eqType', $plugin->getId());

$isExpert = config::byKey('isExpert', 'JeedomConnect') ? true : false ;
sendVarToJS('isJcExpert', $isExpert );

$eqLogics = eqLogic::byType($plugin->getId());

$widgetArray= JeedomConnectWidget::getWidgets();

$jcFilter = $_GET['jcFilter'] ?? '';
$orderBy = $_GET['jcOrderBy'] ?? config::byKey('jcOrderByDefault', 'JeedomConnect', 'object');
$widgetSearch = $_GET['jcSearch'] ?? '';

switch ($orderBy) {
	case 'name':
		$widgetName = array_column($widgetArray, 'name');
		array_multisort($widgetName, SORT_ASC, $widgetArray);
		break;

	case 'type':
		$widgetType = array_column($widgetArray, 'type');
		$widgetName = array_column($widgetArray, 'name');
		array_multisort($widgetType, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
		break;

	default:
		// $roomName  = array_column($widgetArray, 'roomName');
		// $widgetName = array_column($widgetArray, 'name');

		// array_multisort($roomName, SORT_ASC, $widgetName, SORT_ASC, $widgetArray);
		break;
}

$allConfig = JeedomConnect::getWidgetParam();
$widgetTypeArray = array();

$listWidget = '';
foreach ($widgetArray as $widget) {

	$img = $widget['img'] ;

	$opacity = $widget['enable'] ? '' : 'disableCard';
	$widgetName = $widget['name'] ;
	$widgetRoom = $widget['roomName'] ; ;
	$id = $widget['id'];
	$widgetType = $widget['type'];

	$styleHide = ($jcFilter == '') ? '' : ( $jcFilter == $widgetType ? '' : 'style="display:none;"' ) ;

	//used later by the filter select item
	if(!in_array($widgetType, $widgetTypeArray, true)) $widgetTypeArray[$widgetType]=$allConfig[$widgetType];

	$name = '<span class="label labelObjectHuman" style="text-shadow : none;">'.$widgetRoom.'</span><br><strong> '.$widgetName.'</strong>' ;

	$listWidget .= '<div class="widgetDisplayCard cursor '.$opacity.'" '.$styleHide.' title="id='.$id.'" data-widget_id="' . $id . '" data-widget_type="' . $widgetType . '">';
	$listWidget .= '<img src="' . $img . '"/>';
	$listWidget .= '<br>';
	$listWidget .= '<span class="name">' . $name . '</span>';
	$listWidget .= '</div>';

}


// $optionsOrderBy = $_GET['jcOrderBy'] ?? '';
$optionsOrderBy = '';
$orderByArray = array (
		"object" => "Pièce",
		"name" => "Nom",
		"type" => "Type"
	);

foreach ($orderByArray as $key => $value) {
	$selected = ($key ==  $orderBy) ? 'selected' : '';
	$optionsOrderBy .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
}


asort($widgetTypeArray);
$typeSelection2 = '';
$hasSelected = false ;
foreach ($widgetTypeArray as $key => $value) {
	$selected = ($key ==  $jcFilter) ? 'selected' : '' ;
	$hasSelected = $hasSelected || ($key ==  $jcFilter) ;
	$typeSelection2 .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
}
$sel = $hasSelected ? '' : 'selected' ;
$typeSelection = '<option value="none" '.$sel.'>Tous</option>' . $typeSelection2 ;


?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction " data-action="add" style="color:rgb(27,161,242);">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter un Appareil}}</span>
			</div>
			<div class="cursor eqLogicAction " data-action="addWidget"  style="color:rgb(27,161,242);">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span style="color:var(--txt-color)">{{Ajouter un Widget}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="showSummary" style="color:rgb(27,161,242);">
				<i class="fas fa-tasks"></i>
				<br>
				<span style="color:var(--txt-color)">{{Vue d'ensemble}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>

		<!--   PANEL DES EQUIPEMENTS  -->
		<legend style="margin-top:10px"><i class="fas fa-mobile-alt fa-lg"></i> {{Mes appareils}}</legend>
		<!-- Champ de recherche -->
		<div class="input-group" style="margin:10px 5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
			<div class="input-group-btn">
				<a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
		</div>
		<!-- Liste des équipements du plugin -->
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
		<!--  FIN --- PANEL DES EQUIPEMENTS  -->

		<!--   PANEL DES WIDGETS  -->
		<legend><i class="fas fa-table"></i> {{Mes widgets}}

			<div class="pull-right" >
			<span style="margin-right:10px">{{Trie}}
				<select id="widgetOrder" onchange="updateOrderWidget()" style="width:100px">
					<?php
						echo $optionsOrderBy;
					?>
				</select>
			</span>
			<span>{{Filtre}}
				<select id="widgetTypeSelect" style="width:auto">
					<?php
						echo $typeSelection;
					?>
				</select>
			</span>
			<span id="eraseFilterChoice" class="btn roundedRight">
				<!-- <i class="fas fa-times"></i> -->
				<i class="fas fa-trash-alt"></i>
			</span>
			</div>
		</legend>
		<!-- Champ de recherche widget -->
		<div class="input-group" style="margin:10px 5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher sur le nom ou l'id}}" id="in_searchWidget"  value="<?=$widgetSearch?>" />
			<div class="input-group-btn">
				<a id="bt_resetSearchWidget" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
			</div>
		</div>
		<!-- Liste des widgets du plugin -->
		<div class="eqLogicThumbnailContainer" id="widgetsList-div">
			<?php
			echo $listWidget ;
			?>
		</div>
	</div> <!-- /.eqLogicThumbnailDisplay -->
	<!--  FIN ---  PANEL DES WIDGETS  -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i><span class="hidden-xs"> {{Équipement}}</span></a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i><span class="hidden-xs"> {{Commandes}}</span></a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-7 jeedomConnect">
							<legend><i class="fas fa-wrench"></i> {{Général}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'appareil}}</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;"/>
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label" >{{Objet parent}}</label>
								<div class="col-sm-7">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-9">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Options}}</label>
								<div class="col-sm-7">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
								</div>
							</div>

							<div class="form-group" style="display:none;">
								<label class="col-sm-3 control-label" >{{Type}}</label>
								<div class="col-sm-7">
									<select id="sel_type" class="eqLogicAttr form-control" data-l1key="configuration"  data-l2key="type">
										<option value="mobile">mobile</option>
									</select>
								</div>
							</div>
							<br>

							<legend><i class="fa fa-cogs"></i>  {{Paramètres}}</legend>

							<div class="form-group">
									<label class="col-sm-3 control-label">{{Activer la connexion par Websocket}}</label>
									<div class="col-sm-7">
										<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="useWs" type="checkbox" placeholder="{{}}">
									</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Utilisateur}}</label>
								<div class="col-sm-7">
									<select class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="userId">
										<option value="">{{Aucun}}</option>
										<?php
										foreach (user::all() as $user) {
											echo '<option value="' . $user->getId() . '">' . $user->getLogin() . '</option>';
										}
										?>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Mot de passe}}
									<sup>
										<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Vous avez la possibilité d'utiliser un mot de passe alphanumérique pour confirmer une action<br>Il est nécessaire de le définir ici pour s'en servir dans l'application."></i>
									</sup>
								</label>
								<div class="col-sm-6 pass_show">
									<input id="actionPwd" type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="pwdAction" placeholder="{{Mot de passe pour confirmer une action sur l'application}}"/>
									<span toggle="#password-field" class="eye fa fa-fw fa-eye field_icon toggle-password"></span>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Assistant}}</label>
								<div class="col-sm-7">
									<a class="btn btn-success" id="assistant-btn"><i class="fa fa-wrench"></i> {{Configurer l'appareil}}
									</a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Configuration de l'équipement}}</label>
								<div class="col-sm-7 input-group" style="display:inline-flex;">
									<span class="input-group-btn">
										<input type="file" accept=".json" id="import-input" style="display:none;" >
										<a class="btn btn-warning" id="export-btn"><i class="fa fa-save"></i> {{Exporter}}</a>
										<a class="btn btn-primary" id="import-btn"><i class="fa fa-cloud-upload-alt"></i> {{Importer}}</a>
										<a class="btn btn-default" id="copy-btn"><i class="fas fa-copy"></i> {{Copier vers}}</a>
										&nbsp;&nbsp;<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="Partagez votre configuration sur un autre équipement"></i>
									</span>

								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Appareil enregistré}}</label>
								<div class="col-sm-7" style="display:inline-flex;">
									<span class="eqLogicAttr label" style="font-size:1em!important;margin-right:5px;" data-l1key="configuration" type="text" data-l2key="deviceName"></span>
									<a class="btn btn-danger" id="removeDevice"><i class="fa fa-minus-circle"></i> {{Détacher}} </a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{Notifications}}</label>
								<div class="col-sm-7">
									<a class="btn btn-success" id="notifConfig-btn"><i class="fa fa-wrench"></i> {{Configurer}}
									</a>
								</div>
							</div>

							<div class="form-group">
									<label class="col-sm-3 control-label">{{Accès scénarios}}</label>
									<div class="col-sm-7">
										<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="scenariosEnabled" type="checkbox" placeholder="{{}}">
									</div>
							</div>

							<div class="form-group">
									<label class="col-sm-3 control-label">{{Ajouter altitude à la position}}</label>
									<div class="col-sm-7">
										<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="addAltitude" type="checkbox" placeholder="{{}}">
									</div>
							</div>


							<legend><i class="fa fa-bug"></i>  {{Partager le fichier de configuration}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Debug Configuration}}</label>
								<div class="col-sm-7 input-group" style="display:inline-flex;">
									<span class="input-group-btn">
										<a class="btn btn-default" id="exportAll-btn"><i class="fa fa-file-export"></i> {{Partager}}</a>
										&nbsp;&nbsp;<i class="fas fa-question-circle floatright" style="color: var(--al-info-color) !important;" title="A la demande du développeur, partagez votre fichier de configuration finale"></i>
									</span>
								</div>
							</div>

						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche l'icône du plugin par défaut mais vous pouvez y afficher les informations de votre choix -->
						<div class="col-lg-5">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group" >
								<div class="alert alert-info" style="width:300px; margin: 10px auto;text-align:center;" >
									Utilisez l'assistant de configuration pour gérer l'interface de l'application.<br/>
									Dans la partie Login de l'application, scannez directement le QR Code.
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Clé API :}}</label>
								<div class="col-sm-4">
									<span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="configuration" type="text" data-l2key="apiKey"></span>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label">{{QR Code :}}</label>
								<img id="img_config" class="img-responsive" style="margin-top:10px; max-height : 250px;" />
								<div class="col-sm-3" style=" margin-left:185px; margin-top:10px;">
									<a class="btn btn-infos" id="qrcode-regenerate"><i class="fa fa-qrcode"></i> {{Regénérer QR Code}}</a>
								</div>
								<div class="alert alert-danger" style=" margin: 10px auto; margin-top:80px; width:400px;">
									Veuillez re-générer le QR code si vous avez modifié :<br/>
									* Les adresses dans la config du plugin<br/>
									* L'utilisateur de cet équipement<br/>
									* La connexion websocket de cet équipement
								</div>
							</div>
						</div>
					</fieldset>
				</form>
				<hr>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!-- <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a> -->
				<br/><br/>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="width: 50px;">#</th>
								<th style="width: 300px;">{{Nom}}</th>
								<th style="width: 160px;">{{Type}}</th>
								<th style="width: 200px;">{{Valeur}}</th>
								<th style="width: 100px;">{{Options}}</th>
								<th style="width: 100px;"></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->

</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect');?>
<?php include_file('desktop', 'assistant.JeedomConnect', 'js', 'JeedomConnect');?>
<?php include_file('desktop', 'JeedomConnect', 'css', 'JeedomConnect');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
