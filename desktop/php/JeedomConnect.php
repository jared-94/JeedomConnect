<?php

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}


$plugin = plugin::byId('JeedomConnect');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-lg-2 col-sm-9 col-sm-4">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un appareil}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				}
			?>
			</ul>
		</div>
	</div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 130px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 140px;margin-left : 10px;">
				<center>
					<i class="fa fa-wrench" style="font-size : 5em;color:#94ca02;margin-top : 20px;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Configuration}}</center></span>
			</div>
		</div>
		<legend><i class="fa fa-table"></i> {{Mes appareils}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 140px;margin-left : 10px;" >
				<i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;margin-top : 25px;"></i>
				<br>
				<span style="font-size : 1.1em;position:relative; top : 10px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02">{{Ajouter}}</span>
			</div>
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
				echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
				echo "<br>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>
<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
	<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
	<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
	<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
		<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
		<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
	</ul>
	<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
		<div role="tabpanel" class="tab-pane active" id="eqlogictab">
			<br/>
			<form class="form-horizontal">
				<fieldset>
					<div class="form-group">
						<label class="col-sm-3 control-label">{{Nom de l'appareil}}</label>
						<div class="col-sm-3">
							<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
							<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Objet parent}}</label>
						<div class="col-sm-3">
							<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
								<option value="">{{Aucun}}</option>
								<?php
								foreach (jeeObject::all() as $object) {
									echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
								}
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
                            <label class="col-sm-3 control-label">{{Commentaire}}</label>
                            <div class="col-sm-3">
                                <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
                            </div>
                    </div>
					<div class="form-group">
						<label class="col-sm-3 control-label"></label>
						<div class="col-sm-9">
							<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
							<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
						</div>
					</div>

					<div class="col-sm-6">
						<legend><i class="fa fa-cogs"></i>  {{Paramètres}}</legend>

						<div class="form-group">
                            <label class="col-sm-3 control-label">{{Assistant}}</label>
                            <div class="col-sm-4">
                                <a class="btn btn-success" id="assistant-btn"><i class="fa fa-wrench"></i> {{Configurer l'appareil}}
                                </a>
                            </div>
                        </div>
						<div class="form-group">
                            <label class="col-sm-3 control-label">{{Actions}}</label>
							<div class="col-sm-4">
								<input type="file" accept=".json" id="import-input" style="display:none;" >
                                <a class="btn btn-warning" id="export-btn"><i class="fa fa-save"></i> {{Exporter}}</a>
								<a class="btn btn-primary" id="import-btn"><i class="fa fa-cloud-upload-alt"></i> {{Importer}}</a>
                            </div>
                        </div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Appareil enregistré :}}</label>
							<div class="col-sm-4">
								<span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="configuration" type="text" data-l2key="deviceName"></span>
								<a class="btn btn-danger" id="removeDevice" style="display:none"><i class="fa fa-minus-circle"></i> {{Détacher}} </a>
							</div>
						</div>
						<div class="form-group">
                            <label class="col-sm-3 control-label">{{Notifications}}</label>
                            <div class="col-sm-4">
                                <a class="btn btn-success" id="notifConfig-btn"><i class="fa fa-wrench"></i> {{Configurer}}
                                </a>
                            </div>
                        </div>
						<div class="form-group">
								<label class="col-sm-3 control-label">{{Accès scénarios}}</label>
								<div class="col-sm-3">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="scenariosEnabled" type="checkbox" placeholder="{{}}">
								</div>
						</div>
					</div>
					<div class="col-sm-6">
						<legend><i class="fa fa-info"></i>  {{Informations}}</legend>
						<div class="form-group">
							<div class="alert alert-info clo-sm-4" style=" margin-left:120px; margin-top:10px; width:500px;">
								Utilisez l'assistant de configuration pour gérer l'interface de l'application.<br/>
								Dans la partie Login de l'application, entrez manuellement l'adresse websocket et la clé API ci-dessous, ou bien scannez directement le QR Code.
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
                                <a class="btn btn-infos" id="qrcode-regenerate"><i class="fa fa-qrcode"></i> {{Regénérer QR Code}}
                                </a>
                            </div>
                        </div>
					</div>

				</fieldset>


			</form>
		</div>
		<div role="tabpanel" class="tab-pane" id="commandtab">

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
	</div>
</div>

<?php
  include_file('core', 'plugin.template', 'js');
  include_file('desktop', 'JeedomConnect', 'js', 'JeedomConnect');
?>
