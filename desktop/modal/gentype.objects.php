<?php
// HACK delete file when gentype config in plugin is not needed anymore

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}

$allObjects = jeeObject::buildTree(null, false);
?>

<legend><i class="far fa-object-group"></i> {{Objets / Pièces}}</legend>
<div class="input-group" style="margin-bottom:5px;">
	<input class="form-control roundedLeft" placeholder="{{Rechercher un objet / une pièce}}" id="in_searchObject" />
	<div class="input-group-btn">
		<a id="bt_resetObjectSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i> </a>
	</div>
</div>
<div id="objectPanel" class="panel">
	<div class="panel-body">
		<div class="objectListContainer">
			<?php
			$echo = '';
			foreach ($allObjects as $object) {
				$echo .= '<div style="display:none" class="objectDisplayCard cursor" data-object_id="' . $object->getId() . '" data-object_name="' . $object->getName() . '" data-object_icon=\'' . $object->getDisplay('icon', '<i class="far blank"></i>') . '\'>';
				$echo .= $object->getDisplay('icon', '<i class="far blank"></i>');
				$echo .= "<br/>";
				$echo .= '<span class="name" style="background:' . $object->getDisplay('tagColor') . ';color:' . $object->getDisplay('tagTextColor') . '">' . $object->getName() . '</span><br/>';
				$echo .= $object->getHtmlSummary();
				$echo .= '</div>';
			}
			echo $echo;
			?>
		</div>
	</div>
</div>
<?php
include_file('desktop', 'gentype.objects', 'js', 'JeedomConnect');
?>