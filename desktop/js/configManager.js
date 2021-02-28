var configData;
var widgetsList;


$.post({
	url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
	data: {'action': 'getConfig', 'apiKey': apiKey },
	cache: false,
	success: function( config ) {
		//console.log("config : ", config);
		configData = json_decode(config).result;
		//console.log("configData : ", configData);
		validateDataIndex();
		$.ajax({
			dataType: 'json',
			url: "plugins/JeedomConnect/resources/widgetsConfig.json",
			cache: false,
			success: function( widgets ) {
				widgetsList = widgets;
				initData();
			}
		});
	}
});

function initData() {
	document.getElementById("defaultOpen").click();
	refreshBottomTabData();
	refreshTopTabData();
	refreshRoomData();
	refreshWidgetData();
}

function refreshBottomTabData() {
	tabs = configData.payload.tabs.sort(function(s,t) {
		return s.index - t.index;
	});
	var items = [];
	$.each( tabs, function( key, val ) {
		var icon = typeof(val.icon) == 'string' ? `mdi mdi-${val.icon}` : val.icon.source == 'md' ?
			`mdi mdi-${val.icon.name}` : val.icon.source == 'fa' ? `fa fa-${val.icon.name}` : `icon ${val.icon.name}`;
		items.push( `<li><a  onclick="editBottomTabModal('${val.id}');">
			<i class="${icon}" aria-hidden="true" style="margin-right:15px;"></i>${val.name}</a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upBottomTab('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downBottomTab('${val.id}');"></i>
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteBottomTab('${val.id}');"></i></li>`);
	});
	$("#bottomUL").html(items.join(""));
}

function refreshTopTabData() {
	if (configData.payload.tabs.length == 0) {
		$("#topTabParents-select").html("<option>Aucun</option>");
	} else {
		bottomTabs = configData.payload.tabs.sort(function(s,t) {
			return s.index - t.index;
		});
		var items = [];
		if (configData.payload.sections.filter(tab => tab.parentId === undefined).length > 0) {
			items.push("<option>Aucun</option>");
		}
		$.each(bottomTabs, function(key, val) {
			items.push(`<option value="${val.id}">${val.name}</option>`);
		});
		$("#topTabParents-select").html(items.join(""));
	}
	refreshTopTabContent();
}

function refreshTopTabContent() {
	var parentId = $("#topTabParents-select option:selected").attr('value');
	var tabs = configData.payload.sections.filter(tabs => tabs.parentId == parentId);

	tabs = tabs.sort(function(s,t) {
		return s.index - t.index;
	});

	items = [];
	$.each( tabs, function( key, val ) {
		items.push( `<li><a  onclick="editTopTabModal('${val.id}');">${val.name}</a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upTopTab('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downTopTab('${val.id}');"></i>
			<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteTopTab('${val.id}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Supprimer" style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveTopTabModal('${val.id}');"></i></li>`);
	});
	$("#topUL").html(items.join(""));
}

function refreshRoomData() {
	rooms = configData.payload.rooms.sort(function(s,t) {
		return s.index - t.index;
	});
	var items = [];
	$.each( rooms, function( key, val ) {
		items.push( `<li><a  onclick="editRoomModal('${val.id}');">${val.name}</a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upRoom('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downRoom('${val.id}');"></i>
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteRoom('${val.id}');"></i></li>`);
	});
	$("#roomUL").html(items.join(""));
}

function refreshWidgetData() {
	var items = [];
	if (configData.payload.tabs.length == 0 & configData.payload.sections.length == 0) {
		$("#widgetsParents-select").html("<option>Aucun</option>");
	} else {
	  var parents = getWidgetsParents();
	  $.each(parents, function(key, val) {
			items.push(`<option value="${val.id}">${val.name}</option>`);
	  });
	  $("#widgetsParents-select").html(items.join(""));
	}

	refreshWidgetsContent();
}

function refreshWidgetsContent() {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);
	rootElmts = rootElmts.sort(function(s,t) {
		return s.index - t.index;
	});

	items = [];
	//console.log(" ==== tous les widgets ===> " , allWidgetsDetail) ;
	$.each( rootElmts, function( key, value ) {
		var val = allWidgetsDetail.find(w => w.id == value.id) ; 
		if (val  !=undefined && val.type !== undefined) { //it is a widget
			var img = widgetsList.widgets.find(w => w.type == val.type).img;
			items.push( `<li><a  onclick="editWidgetModal('${val.id}');">
			<img src="plugins/JeedomConnect/data/img/${img}" class="imgList"/>${val.name}<br/>
			<span style="font-size:12px;margin-left:40px;">${getRoomName(val.room) || 'Pas de pièce'}</span></a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidget('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidget('${val.id}');"></i>
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteWidget('${val.id}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveWidgetModal('${val.id}');"></i></li>`);
		} else { //it's a group
			items.push( `<li><a  onclick="editGroupModal('${value.id}');"><i class="fa fa-list"></i> ${value.name}</a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upGroup('${value.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downGroup('${value.id}');"></i>
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteGroup('${value.id}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveGroupModal('${value.id}');"></i></li>`);
			var curWidgets = configData.payload.widgets.filter(w => w.parentId == value.id);
			curWidgets = curWidgets.sort(function(s,t) {
				return s.index - t.index;
			});
			items.push("<li><ul class='tabSubUL'>");
			$.each(curWidgets, function (key, wid) {
				var w = allWidgetsDetail.find(x => x.id == wid.id) ; 
				var img = widgetsList.widgets.find(i => i.type == w.type).img;
				items.push( `<li><a  onclick="editWidgetModal('${w.id}');"><img src="plugins/JeedomConnect/data/img/${img}" class="imgList"/>${w.name}</a>
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidget('${w.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidget('${w.id}');"></i>
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteWidget('${w.id}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveWidgetModal('${w.id}');"></i></li>`);
			});
			items.push("</ul></li>");
		}
	});
	$("#widgetsUL").html(items.join(""));
}

function incrementIdCounter() {
	configData.idCounter += 1;
}

function save(){
	configData['payload'].configVersion += 1;
	console.log(configData);
	$.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {'action': 'saveConfig', 'config': JSON.stringify(configData), 'apiKey': apiKey },
            success: function () {
               $('#jc-assistant').showAlert({message: 'Configuration sauvegardée', level: 'success'});
            },
            error: function (error) {
             console.log(error);
			 $('#jc-assistant').showAlert({message: 'Erreur lors de la sauvegarde', level: 'danger'});
            }
    });

}

function resetConfig() {
	getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"La configuration va être remise à zéro. Voulez-vous continuer ?"}] }, function(result) {
		configData = {
				'type':  'JEEDOM_CONFIG',
				'idCounter': 0,
				'payload': {
					'configVersion': 0,
					'tabs': [],
					'sections': [],
					'rooms': [],
					'groups': [],
					'widgets': []
				}
		};
		initData();
	});
}

/*HELPER FUNCTIONS */

function getMaxIndex(array) {
	var maxIndex = -1;
	array.forEach( item => {
		if (item.index > maxIndex) {
			maxIndex = item.index;
		}
	});
	return maxIndex;
}

function getRoomName(id) {
	if (id == 'global') { return 'Global'; }
	const room = configData.payload.rooms.find(r => r.id == id);
	if (room) {
		return room.name;
	} else {
		return undefined;
	}
}

function getRootObjects(id) {
	var widgets = configData.payload.widgets.filter(w => w.parentId == id);
	var groups = configData.payload.groups.filter(g => g.parentId == id);
	return groups.concat(widgets);
}

function getWidgetPath(id) {
	var widget = configData.payload.widgets.find(w => w.id == id);
	var name = (' '+widget.name).slice(1);

	if (widget.parentId === undefined | widget.parentId == null) {
		return name;
	}
	var id = (' ' + widget.parentId.toString()).slice(1);
	parent = configData.payload.groups.find(i => i.id == id);
	if (parent) {
		name = parent.name + " / " + name;
		if (parent.parentId === undefined | parent.parentId == null) {
			return name;
		}
		id = (' ' + parent.parentId.toString()).slice(1);
	}
	parent2 = configData.payload.sections.find(i => i.id == id);
	if (parent2) {
		name = parent2.name + " / " + name;
		if (parent2.parentId === undefined | parent2.parentId == null) {
			return name;
		}
		id = (' ' + parent2.parentId.toString()).slice(1);
	}
	parent3 = configData.payload.tabs.find(i => i.id == id);
	if (parent3) {
		name = parent3.name + " / " + name;
	}
	return name;
}

function getWidgetsParents() {
	var items = [];
	 $.each(configData.payload.tabs, function(key, val) {
	 	if (configData.payload.sections.find(s => s.parentId == val.id) === undefined) {
			items.push({id:val.id, name:val.name});
		 }
	 });
	 $.each(configData.payload.sections, function(key, val) {
		var tab = configData.payload.tabs.find(t => t.id == val.parentId);
		if (tab === undefined & configData.payload.tabs.length == 0) {
			items.push({id:val.id, name:val.name});
		} else {
			items.push({id:val.id, name:tab.name+" / "+val.name});
		}
	 });
	return items;
}

function validateDataIndex() {
	configData.payload.tabs.forEach(i => {
		reIndexArray(getRootObjects(i.id));
	});
	configData.payload.sections.forEach(i => {
		reIndexArray(getRootObjects(i.id));
	});
	configData.payload.groups.forEach(i => {
		reIndexArray(getRootObjects(i.id));
	});

}

function reIndexArray(array) {
	array.sort(function(s,t) {
		return s.index - t.index;
	});
	let index = 0;
	array.forEach(item => {
		item.index = index;
		index = index+1;
	});
}

function htmlToIcon(html) {
	let tag = html.split('\"')[1].split(' ');
	let source = '';
	let name = '';
	if (tag[0] == 'icon') {
		source = 'jeedom';
		name = tag[1];
	} else if (tag[0] == 'mdi') {
		source = 'md';
		name = tag[1].substring(4);
	} else if (tag[0] == 'fa') {
		source = 'fa';
		name = tag[1].substring(3)
	}
	let icon = { source, name };
	return icon;
}

function iconToHtml(icon) {
	let tag1 = '';
	let tag2 = '';
	if (icon.source == 'jeedom') {
		tag1 = 'icon'
		tag2 = icon.name;
	} else if (icon.source == 'md') {
		tag1 = 'mdi'
		tag2 = 'mdi-' + icon.name;
	} else if (icon.source == 'fa') {
		tag1 = 'fa'
		tag2 = 'fa-' + icon.name;
	}
	return `<i class="${tag1} ${tag2}"></i>`;
}

/* BOTTOM TAB FUNCTIONS */

function addBottomTabModal() {
	getSimpleModal({title: "Ajouter un menu bas", fields:[{type: "enable", value: true},{type: "name"},
		{type:"icon"}, {type: "swipeUp"}, {type: "swipeDown"}] }, function(result) {
	  var name = result.name;
	  var icon = result.icon;
	  if (name == ''  | icon.name == '') {
		return;
	  }

	  var maxIndex = getMaxIndex(configData.payload.tabs);
	  var newTab = {};
	  newTab.name = name;
	  newTab.icon = icon;
		if (result.swipeUp) { newTab.swipeUp = result.swipeUp; }
		if (result.swipeDown) { newTab.swipeDown = result.swipeDown; }
	  newTab.enable = result.enable;
	  newTab.index = maxIndex + 1;
	  newTab.id = configData.idCounter;

	  configData.payload.tabs.push(newTab);

		if (maxIndex == -1) { //this is the first bottom tab
			configData.payload.sections.forEach(item => {
				item.parentId = newTab.id;
			});
			configData.payload.groups.forEach(item => {
				item.parentId = item.parentId || newTab.id;
			});
			configData.payload.widgets.forEach(item => {
				item.parentId = item.parentId || newTab.id;
			});
		}
	  incrementIdCounter();
	  refreshBottomTabData();
	});
}

function editBottomTabModal(tabId) {
  var tabToEdit = configData.payload.tabs.find(tab => tab.id == tabId);
  getSimpleModal({title: "Editer un menu bas",
		fields:[{type: "enable", value: tabToEdit.enable},{type: "name",value:tabToEdit.name}, {type:"icon",value: tabToEdit.icon},
			{type:'swipeUp', value:tabToEdit.swipeUp}, {type:'swipeDown', value:tabToEdit.swipeDown}] },
		function(result) {
				tabToEdit.name = result.name;
				tabToEdit.icon = result.icon;
				tabToEdit.swipeUp = result.swipeUp;
				tabToEdit.swipeDown = result.swipeDown;
				tabToEdit.enable = result.enable;
				refreshBottomTabData();
  });
}

function upBottomTab(tabId) {
	var tabToMove = configData.payload.tabs.find(tab => tab.id == tabId);
	var tabIndex = tabToMove.index;
	if (tabIndex == 0) {
		return;
	}
	var otherTab = configData.payload.tabs.find(tab => tab.index == tabIndex - 1);
	tabToMove.index = tabIndex - 1;
	otherTab.index = tabIndex;
	refreshBottomTabData();
}

function downBottomTab(tabId) {
	var tabToMove = configData.payload.tabs.find(tab => tab.id == tabId);
	var tabIndex = tabToMove.index;
	if (tabIndex == getMaxIndex(configData.payload.tabs)) {
		return;
	}
	var otherTab = configData.payload.tabs.find(tab => tab.index == tabIndex + 1);
	tabToMove.index = tabIndex + 1;
	otherTab.index = tabIndex;
	refreshBottomTabData();
}

function deleteBottomTab(tabId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous vraiment supprimer ce menu ?"}] }, function(result) {
		var tabToDelete = configData.payload.tabs.find(tab => tab.id == tabId);
		var index = configData.payload.tabs.indexOf(tabToDelete);
		configData.payload.tabs.forEach(item => {
			if (item.index > tabToDelete.index) {
				item.index = item.index - 1;
			}
		});
		configData.payload.tabs.splice(index, 1);

		//remove all sub-elements
		configData.payload.sections.slice().forEach(section => {
			if (section.parentId == tabId) {
				removeTopTab(section.id);
			}
		});
		configData.payload.groups.slice().forEach(group => {
			if (group.parentId == tabId) {
				removeGroup(group.id);
			}
		});
		configData.payload.widgets.forEach(widget => {
			if (widget.parentId == tabId) {
				removeWidget(widget.id);
			}
		});

		refreshBottomTabData();
	});
}

/* TOP TAB FUNCTIONS */

function addTopTabModal() {
	getSimpleModal({title: "Ajouter un menu haut", fields:[{type: "enable", value: true},{type: "name"}] }, function(result) {
	  var name = result.name;
	  var parentId = $("#topTabParents-select option:selected").attr('value');
	  if (name == '') { return; }

		var tabList = configData.payload.sections.filter(tab => tab.parentId == parentId);

	  var maxIndex = getMaxIndex(tabList);
	  var newTab = {};
	  newTab.name = name;
	  newTab.enable = result.enable;
		newTab.parentId = parentId && parseInt(parentId);
	  newTab.index = maxIndex + 1;
	  newTab.id = configData.idCounter;

	  configData.payload.sections.push(newTab);

		if (maxIndex == -1) {//this is the first toptab
			configData.payload.groups.forEach(item => {
				item.parentId = (item.parentId == newTab.parentId) ? newTab.id : item.parentId;
			});
			configData.payload.widgets.forEach(item => {
				item.parentId = (item.parentId == newTab.parentId) ? newTab.id : item.parentId;
			});
		}

	  incrementIdCounter();
	  refreshTopTabContent();
	});
}

function editTopTabModal(tabId) {
  var tabToEdit = configData.payload.sections.find(tab => tab.id == tabId);
  getSimpleModal({title: "Editer un menu haut", fields:[{type: "enable", value: tabToEdit.enable},{type: "name",value:tabToEdit.name}] }, function(result) {
		tabToEdit.name = result.name;
		tabToEdit.enable = result.enable;
		refreshTopTabContent();
  });
}

function upTopTab(tabId) {
	var tabToMove = configData.payload.sections.find(tab => tab.id == tabId);
	var tabIndex = tabToMove.index;
	if (tabIndex == 0) {
		console.log("can't move this")
		return;
	}
	var tabList = configData.payload.sections.filter(tab => tab.parentId == tabToMove.parentId);

	var otherTab = tabList.find(tab => tab.index == tabIndex - 1);
	tabToMove.index = tabIndex - 1;
	otherTab.index = tabIndex;
	refreshTopTabContent();
}

function downTopTab(tabId) {
	var tabToMove = configData.payload.sections.find(tab => tab.id == tabId);
	var tabIndex = tabToMove.index;
	if (tabIndex == getMaxIndex(configData.payload.sections)) {
		console.log("can't move this tab");
		return;
	}
	var tabList = configData.payload.sections.filter(tab => tab.parentId == tabToMove.parentId);

	var otherTab = tabList.find(tab => tab.index == tabIndex + 1);
	tabToMove.index = tabIndex + 1;
	otherTab.index = tabIndex;
	refreshTopTabContent();
}

function deleteTopTab(tabId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous vraiment supprimer ce menu ?"}] }, function(result) {
		removeTopTab(tabId);
		refreshTopTabContent();
  });
}

function removeTopTab(tabId) {
	var tabToDelete = configData.payload.sections.find(tab => tab.id == tabId);
	var index = configData.payload.sections.indexOf(tabToDelete);
	var tabList = configData.payload.sections.filter(tab => tab.parentId == tabToDelete.parentId);
	tabList.forEach(item => {
		if (item.index > tabToDelete.index) {
			item.index = item.index - 1;
		}
	});
	configData.payload.sections.splice(index, 1);

	configData.payload.groups.slice().forEach(group => {
		if (group.parentId == tabId) {
			removeGroup(group.id);
		}
	});

	configData.payload.widgets.forEach(widget => {
		if (widget.parentId == tabId) {
			removeWidget(widget.id)
		}
	});
}

function moveTopTabModal(tabId) {
	getSimpleModal({title: "Déplacer un menu haut", fields:[{type: "move",value:configData.payload.tabs}] }, function(result) {
	  var parentId = result.moveToId;
	  if (parentId === null) { return; }
	  var tabToMove = configData.payload.sections.find(tab => tab.id == tabId);
	  //re-index current tab list
	  var tabList = configData.payload.sections.filter(tab => tab.parentId == tabToMove.parentId);
	  tabList.forEach(item => {
			if (item.index > tabToMove.index) {
		  	item.index = item.index - 1;
			}
	  });

	  var maxIndex = getMaxIndex(configData.payload.sections.filter(s => s.parentId == parentId));
	  tabToMove.parentId = parseInt(parentId);
	  tabToMove.index = maxIndex + 1;

	  refreshTopTabContent();
	});
}

/* ROOM FUNCTIONS */

function addRoomModal() {
  getSimpleModal({title: "Ajouter une pièce", fields:[{type: "name"}, {type: "object"}] }, function(result) {
		var name = result.name;
		if (name == '') { return; }
		var maxIndex = getMaxIndex(configData.payload.rooms);
		var newRoom = {};
		newRoom.name = name;
		if (parseInt(result.object)) {
			newRoom.object = parseInt(result.object);
		}
		newRoom.index = maxIndex + 1;
		newRoom.id = configData.idCounter;

		configData.payload.rooms.push(newRoom);
		incrementIdCounter();
		refreshRoomData();
  });
}

function editRoomModal(roomId) {
	var roomToEdit = configData.payload.rooms.find(room => room.id == roomId);
	getSimpleModal({title: "Editer une pièce",
		fields:[{type: "name",value:roomToEdit.name}, {type: "object", value: roomToEdit.object}] }, function(result) {
	  roomToEdit.name = result.name;
		if (parseInt(result.object)) {
			roomToEdit.object = parseInt(result.object);
		} else {
			roomToEdit.object = undefined;
		}
	  refreshRoomData();
	});
}

function upRoom(roomId) {
	var roomToMove = configData.payload.rooms.find(room => room.id == roomId);
	var roomIndex = roomToMove.index;
	if (roomIndex == 0) {
		console.log("can't move this room");
		return;
	}
	var otherRoom = configData.payload.rooms.find(room => room.index == roomIndex - 1);
	roomToMove.index = roomIndex - 1;
	otherRoom.index = roomIndex;
	refreshRoomData();
}

function downRoom(roomId) {
	var roomToMove = configData.payload.rooms.find(room => room.id == roomId);
	var roomIndex = roomToMove.index;
	if (roomIndex == getMaxIndex(configData.payload.rooms)) {
		console.log("can't move this room");
		return;
	}
	var otherRoom = configData.payload.rooms.find(room => room.index == roomIndex + 1);
	roomToMove.index = roomIndex + 1;
	otherRoom.index = roomIndex;
	refreshRoomData();
}

function deleteRoom(roomId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous supprimer cette pièce ?"}] }, function(result) {
		var roomToDelete = configData.payload.rooms.find(room => room.id == roomId);
		var index = configData.payload.rooms.indexOf(roomToDelete);

		configData.payload.rooms.forEach(item => {
			if (item.index > roomToDelete.index) {
				item.index = item.index - 1;
			}
		});
		//remove room for used widgets
		configData.payload.widgets.forEach(widget => {
			if (widget.room == roomToDelete.id) {
				widget.room = undefined;
			}
		});

  	configData.payload.rooms.splice(index, 1);
		refreshRoomData();
  });
}

/* GROUPS */

function addGroupModal() {
  getSimpleModal({title: "Ajouter un groupe", fields:[{type: "enable", value: true},{type: "name"}, {type: "expanded", value: false}] }, function(result) {
		var name = result.name;
		if (name == '') { return; }
		var parentId = $("#widgetsParents-select option:selected").attr('value');
		var rootElmts = getRootObjects(parentId);

		var maxIndex = getMaxIndex(rootElmts);
		var newGroup = {};
		newGroup.name = name;
		newGroup.expanded = result.expanded;
		newGroup.enable = result.enable;
		newGroup.parentId = parentId && parseInt(parentId);
		newGroup.index = maxIndex + 1;
		newGroup.id = configData.idCounter;

		configData.payload.groups.push(newGroup);
		incrementIdCounter();
		refreshWidgetsContent();
  });
}

function editGroupModal(groupId) {
  var groupToEdit = configData.payload.groups.find(g => g.id == groupId);
  getSimpleModal({title: "Editer un groupe", fields:[ {type: "enable", value: groupToEdit.enable},{type: "name",value:groupToEdit.name},{type: "expanded", value:groupToEdit.expanded}] }, function(result) {
		groupToEdit.name = result.name;
		groupToEdit.expanded = result.expanded;
		groupToEdit.enable = result.enable;
		refreshWidgetsContent();
  });
}

function upGroup(groupId) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);

	var groupToMove = rootElmts.find(g => g.id == groupId);
	var groupIndex = groupToMove.index;
	if (groupIndex == 0) {
		console.log("can't move this group");
		return;
	}
	var otherElmt = rootElmts.find(e => e.index == groupIndex - 1);
	groupToMove.index = groupIndex - 1;
	otherElmt.index = groupIndex;
	refreshWidgetsContent();
}

function downGroup(groupId) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);

	var groupToMove = rootElmts.find(g => g.id == groupId);
	var groupIndex = groupToMove.index;
	if (groupIndex == getMaxIndex(rootElmts)) {
		console.log("can't move this group");
		return;
	}
	var otherElmt = rootElmts.find(e => e.index == groupIndex + 1);
	groupToMove.index = groupIndex + 1;
	otherElmt.index = groupIndex;
	refreshWidgetsContent();
}

function deleteGroup(groupId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Tous les widgets attachés à ce groupe seront supprimés. Voulez-vous continuer ?"}] }, function(result) {
		removeGroup(groupId);
		refreshWidgetsContent();
  });
}

function removeGroup(groupId) {
	var groupToDelete = configData.payload.groups.find(g => g.id == groupId);
	var index = configData.payload.groups.indexOf(groupToDelete);
	var rootElmts = getRootObjects(groupToDelete.parentId);
	rootElmts.forEach(item => {
		if (item.index > groupToDelete.index) {
			item.index = item.index - 1;
		}
	});
	configData.payload.groups.splice(index, 1);

	configData.payload.widgets.slice().forEach(widget => {
		if (widget.parentId == groupId) {
			removeWidget(widget.id);
		}
	});
}

function moveGroupModal(groupId) {
  getSimpleModal({title: "Déplacer un groupe", fields:[{type: "move",value:getWidgetsParents()}] }, function(result) {
		var parentId = result.moveToId;
		if (parentId === null) { 	return; }
		var groupToMove = configData.payload.groups.find(g => g.id == groupId);
		var rootElmts = getRootObjects(groupToMove.parentId);
		rootElmts.forEach(item => {
			if (item.index > groupToMove.index) {
				item.index = item.index - 1;
			}
		});
		var dest = getRootObjects(parentId);
		var maxIndex = getMaxIndex(dest);
		groupToMove.parentId = parseInt(parentId);
		groupToMove.index = maxIndex + 1;

		refreshWidgetsContent();
  });
}

/* WIDGETS */

function upWidget(widgetId) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);

	var widgetToMove = rootElmts.find(w => w.id == widgetId);
	if (widgetToMove !== undefined) { //widget is not in a group
	  var widgetIndex = widgetToMove.index;
	  if (widgetIndex == 0) {
			console.log("can't move this widget");
			return;
	  }
	  var otherElmt = rootElmts.find(e => e.index == widgetIndex - 1);
	  var group = configData.payload.groups.find(g => g.id == otherElmt.id);
	  if (group !== undefined) { //transfer widget to a group
			rootElmts.forEach(item => {
				if (item.index > widgetToMove.index) {
					item.index = item.index - 1;
				}
			});
			widgetToMove.index = getMaxIndex(getRootObjects(group.id)) + 1;
      widgetToMove.parentId = group.id;
	  } else {
			widgetToMove.index = widgetIndex - 1;
	    otherElmt.index = widgetIndex;
	  }
	} else {
		widgetToMove = configData.payload.widgets.find(w => w.id == widgetId);
		group = rootElmts.find(g => g.id == widgetToMove.parentId);
		var widgetIndex = widgetToMove.index;
	  if (widgetIndex == 0) { //exit from group
			rootElmts.forEach(item => {
		  	if (item.index > group.index) {
			   	item.index = item.index + 1;
		    }
	    });
			configData.payload.widgets.filter(w => w.parentId == group.id).forEach(item => {
			  item.index = item.index - 1;
	    });
			widgetToMove.index = group.index;
			widgetToMove.parentId = parentId && parseInt(parentId);
			group.index = group.index + 1
		} else {
			var otherWidget = configData.payload.widgets.find(w => w.parentId == widgetToMove.parentId & w.index == widgetIndex - 1);
			widgetToMove.index = widgetIndex - 1;
			otherWidget.index = widgetIndex;
		}
	}
	refreshWidgetsContent();
}

function downWidget(widgetId) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);

	var widgetToMove = rootElmts.find(w => w.id == widgetId);
	if (widgetToMove !== undefined) { //widget is not in a group
	  var widgetIndex = widgetToMove.index;
	  if (widgetIndex == getMaxIndex(rootElmts)) {
			console.log("can't move this widget");
			return;
	  }
	  var otherElmt = rootElmts.find(e => e.index == widgetIndex + 1);

	  var group = configData.payload.groups.find(g => g.id == otherElmt.id);
	  if (group !== undefined) { //transfer widget to a group
			rootElmts.forEach(item => {
				if (item.index > widgetToMove.index) {
					item.index = item.index - 1;
				}
			});
			widgetToMove.index = getMaxIndex(getRootObjects(group.id)) + 1;
      widgetToMove.parentId = group.id;
	  } else {
			widgetToMove.index = widgetIndex + 1;
	    otherElmt.index = widgetIndex;
	  }
	} else {
		widgetToMove = configData.payload.widgets.find(w => w.id == widgetId);
		var widgetsList = configData.payload.widgets.filter(w => w.parentId == widgetToMove.parentId);
		group = rootElmts.find(g => g.id == widgetToMove.parentId);
		var widgetIndex = widgetToMove.index;
	  if (widgetIndex == getMaxIndex(widgetsList)) { // exit from group
		  rootElmts.forEach(item => {
		    if (item.index > group.index) {
			  	item.index = item.index + 1;
		    }
	    });

		  widgetToMove.index = group.index + 1;
		  widgetToMove.parentId = parentId && parseInt(parentId);
		} else {
			var otherWidget = widgetsList.find(w => w.index == widgetIndex + 1);
			widgetToMove.index = widgetIndex + 1;
			otherWidget.index = widgetIndex;
		}
	}
	refreshWidgetsContent();
}

function deleteWidget(widgetId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous supprimer ce widget ?"}] }, function(result) {
		removeWidget(widgetId);
		refreshWidgetsContent();
  });
}

function removeWidget(widgetId) {
	var widgetToDelete = configData.payload.widgets.find(g => g.id == widgetId);
	var index = configData.payload.widgets.indexOf(widgetToDelete);
	var rootElmts = getRootObjects(widgetToDelete.parentId);
	rootElmts.forEach(item => {
		if (item.index > widgetToDelete.index) {
			item.index = item.index - 1;
		}
	});
	configData.payload.widgets.splice(index, 1);

	//remove widget if in a group widgets
	var groupList = [];
	widgetsList.widgets.forEach(w => {
		w.options.forEach(o => {
			if (o.category == 'widgets') {
				groupList.push({ type: w.type, optionId: o.id });
			}
		});
	});
	configData.payload.widgets.forEach((w, i) => {
		var wConfig = groupList.find(g => g.type == w.type);
		if (wConfig != undefined) {
			if (w[wConfig.optionId] != undefined) {
				var toRemove = w[wConfig.optionId].find(o => o.id == widgetId);
				if (toRemove != undefined) {
					index = w[wConfig.optionId].indexOf(toRemove);
					configData.payload.widgets[i][wConfig.optionId].forEach(item => {
						if (item.index > index) {
							item.index = item.index - 1;
						}
					});
					configData.payload.widgets[i][wConfig.optionId].splice(index, 1);
				}
			}
		}
	});
}

function moveWidgetModal(widgetId) {
  getSimpleModal({title: "Déplacer un widget", fields:[{type: "move",value:getWidgetsParents()}] }, function(result) {
	var parentId = result.moveToId;
	if (parentId === null) { return; }
	var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId);
	var rootElmts = getRootObjects(widgetToMove.parentId);
	rootElmts.forEach(item => {
		if (item.index > widgetToMove.index) {
			item.index = item.index - 1;
		}
	});
	var dest = getRootObjects(parentId);
	var maxIndex = getMaxIndex(dest);
	widgetToMove.index = maxIndex + 1;
	widgetToMove.parentId = parseInt(parentId);

	refreshWidgetsContent();
  });
}

function selectWidgetModal() {
  	result = {};
  	var parentId = $("#widgetsParents-select option:selected").attr('value');
  	var rootElmts = getRootObjects(parentId);

	var maxIndex = getMaxIndex(rootElmts);
	result.parentId = parentId && parseInt(parentId);
	result.index = maxIndex + 1;

	var widgetSelectedId = $("#selWidgetDetail option:selected").attr('data-widget-id');
	result.id = parseInt(widgetSelectedId);

	configData.payload.widgets.push(result);
	incrementIdCounter();
	refreshWidgetsContent();
}


function addWidgetModal() {
  getWidgetModal({title:"Ajouter un widget"}, function(result) {
  	//console.log(result)
	var parentId = $("#widgetsParents-select option:selected").attr('value');
  	var rootElmts = getRootObjects(parentId);

	var maxIndex = getMaxIndex(rootElmts);
	result.parentId = parentId && parseInt(parentId);
	result.index = maxIndex + 1;
	result.id = configData.idCounter;

	configData.payload.widgets.push(result);
	incrementIdCounter();
	refreshWidgetsContent();
  });
}



// var allWidgetsDetail;

// refreshWidgetDetails() ; 

// function refreshWidgetDetails(){

// 	$.post({
// 		url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
// 		data: {
// 			'action': 'getWidgetConfigAll'
// 		},
// 		cache: false,
// 		dataType: 'json',
// 		success: function( data ) {
// 			if (data.state != 'ok') {
// 				$('#div_alert').showAlert({
// 				  message: data.result,
// 				  level: 'danger'
// 				});
// 			}
// 			else{
// 				allWidgetsDetail = data.result;
// 				//console.log("allWidgetsDetail : ", allWidgetsDetail);
// 			}
// 		}
// 	});

// }