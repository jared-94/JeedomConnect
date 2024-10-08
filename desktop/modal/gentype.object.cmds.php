<?php

// HACK delete file when gentype config in plugin is not needed anymore

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}

if (init('objectId') == '') {
	throw new Exception('{{objectId obligatoire}}');
}

$objectId = init('objectId');
$object = jeeObject::byId($objectId);
?>
<style>
	.mini {
		font-size: 0.8em;
		font-style: italic;
	}
</style>

<div class="displayMessage"></div>
<a onclick="saveCmds()" class="btn btn-sm btn-success pull-right" style="margin-top:5px;"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
<ul class="nav nav-tabs" style="padding-left:8px">
	<li><a class="cursor" id="bt_return" style="width:32px;"><i class="fas fa-arrow-circle-left"></i></a></li>
	<li class="active">
		<?php
		if (is_object($object)) {
			echo '<a>' . $object->getDisplay('icon', '<i class="far blank"></i>') . ' ' . $object->getName() . '</a>';
		} else {
			echo '<a><i class="far blank"></i> Aucun</a>';
		}
		?>
	</li>
</ul>
<br>

<div class="input-group" style="margin-bottom:5px;">
	<input class="form-control roundedLeft" placeholder="{{Rechercher une commande ou un type/sous-type}}" id="in_searchCmd" />
	<div class="input-group-btn">
		<a class="btn" id="bt_resetCmdSearch" style="width:30px"><i class="fas fa-times"></i>
		</a><a class="btn" id="bt_openAll"><i class="fas fa-folder-open"></i>
		</a><a class="btn roundedRight" id="bt_closeAll"><i class="fas fa-folder"></i></a>
	</div>
</div>

<?php
$objectId = is_object($object) ? $object->getId() : null;
/** @var array<eqLogic> */
$eqLogics = eqLogic::byObjectId($objectId, true, false, null, null, false, true);

usort($eqLogics, function ($a, $b) {
	return strcasecmp($a->getName(), $b->getName());
});

echo '<div class="panel-group" id="accordionObjects">';

foreach ($eqLogics as $eqLogic) {
	echo '<div class="panel panel-default">';

	echo '<div class="panel-heading">';
	echo '<h3 class="panel-title">';
	echo '<a class="accordion-toggle" data-toggle="collapse" aria-expanded="false" data-parent="" href="#eqLogic_' . $eqLogic->getId() . '"><span class="eqLogicAttr hidden" data-l1key="id">' . $eqLogic->getId() . '</span>' . $eqLogic->getName()  . ' <span class="mini">(' . $eqLogic->getEqType_name() . ')</span> </a>';
	echo '</h3>';
	echo '</div>';

	echo '<div id="eqLogic_' . $eqLogic->getId() . '" class="panel-collapse collapse">';
	echo '<div class="panel-body">';
	echo '<table id=' . $eqLogic->getId() . ' class="table table-bordered table-condensed tableCmd">';
	echo '<tr>';
	echo '<th>{{Nom}}</th>';
	echo '<th>{{Sous-type}}</th>';
	echo '<th>{{Type Générique}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="mini reinitCmd cursor">réinitialiser toutes les commandes</span></th>';
	echo '</tr>';

	$cmds = cmd::byEqLogicId($eqLogic->getId());
	usort($cmds, function ($a, $b) {
		return strcasecmp($a->getName(), $b->getName());
	});
	foreach ($cmds as $cmd) {
		echo '<tr class="cmdLine">';
		echo '<td class="cmdName">' . $cmd->getName() . '</td>';
		echo "<td class='cmdType'>{$cmd->getType()}/{$cmd->getSubType()}</td>";
		echo '<td>';
		echo '<span class="cmdAttr" data-l1key="id" style="display:none;">' . $cmd->getId() . '</span>';
?>
		<select class="cmdAttr form-control" data-l1key="generic_type" data-cmd_id="<?php echo $cmd->getId(); ?>">
			<option value="">{{Aucun}}</option>
			<?php
			$groups = array();
			$generic_type_config = jeedom::getConfiguration('cmd::generic_type');
			$genericsJC = JeedomConnect::pluginGenericTypes();
			$allGeneric = array_merge($generic_type_config, $genericsJC);
			foreach ($allGeneric as $key => $info) {
				if ($cmd->getType() == 'info' && $info['type'] == 'Action') {
					continue;
				} elseif ($cmd->getType() == 'action' && $info['type'] == 'Info') {
					continue;
				} elseif (isset($info['ignore']) && $info['ignore'] == true) {
					continue;
				}
				$info['key'] = $key;
				if (!isset($groups[$info['family']])) {
					$groups[$info['family']][0] = $info;
				} else {
					array_push($groups[$info['family']], $info);
				}
			}
			ksort($groups);
			foreach ($groups as $group) {
				usort($group, function ($a, $b) {
					return strcmp($a['name'], $b['name']);
				});
				foreach ($group as $key => $info) {
					if ($key == 0) {
						echo '<optgroup label="{{' . $info['family'] . '}}">';
					}
					if ($info['key'] == 'DONT') continue;
					if ($info['key'] == $cmd->getGeneric_type()) {
						echo '<option value="' . $info['key'] . '" selected>' .  $info['name'] . '</option>';
					} else {
						echo '<option value="' . $info['key'] . '">'  . $info['name'] . '</option>';
					}
				}
				echo '</optgroup>';
			}
			?>
		</select>
<?php
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}
echo '</div>';
?>

<?php
include_file('desktop', 'gentype.object.cmds', 'js', 'JeedomConnect');
?>