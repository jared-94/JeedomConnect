var configData;
var widgetsList;
var summaryConfig ;


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
			success: function( data ) {
				data.widgets.sort(function (a, b) {
					return a.name.localeCompare( b.name );
				});
				widgetsList = data;
				summaryConfig = data.summaries;
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
	refreshSummaryData();
	refreshWidgetData();
}

function refreshBottomTabData() {
	tabs = configData.payload.tabs.sort(function(s,t) {
		return s.index - t.index;
	});
	var items = [];
	$.each( tabs, function( key, val ) {
		items.push( `<li data-id="${val.id}" class="bottomItem"><a  onclick="editBottomTabModal('${val.id}');">
			${iconToHtml(val.icon)}<i style="margin-left:10px;"></i>${val.name}</a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>

			<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upBottomTab('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downBottomTab('${val.id}');"></i> -->

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
		items.push( `<li data-id="${val.id}" class="topItem"><a  onclick="editTopTabModal('${val.id}');">${val.name}</a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>	

			<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upTopTab('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downTopTab('${val.id}');"></i> -->

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
		items.push( `<li data-id="${val.id}" class="roomItem"><a>${val.name}</a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
			
			<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upRoom('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downRoom('${val.id}');"></i> -->
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
	// console.log('widgets', configData.payload.widgets)
	items = [];
	//console.log(" ==== tous les widgets ===> " , allWidgetsDetail) ;
	$.each( rootElmts, function( key, value ) {
		var val = allWidgetsDetail.find(w => w.id == value.id) ;
		if (val  !=undefined && val.type !== undefined) { //it is a widget
			var img = widgetsList.widgets.find(w => w.type == val.type).img;
			items.push( `<li class="widgetItem" data-id="${val.id}" data-parentId="${value.parentId}" data-index="${value.index}"><a title="id=${val.id}" onclick="editWidgetModal('${val.id}');">
			<img src="plugins/JeedomConnect/data/img/${img}" class="imgList"/>${val.name}<br/>
			<span style="font-size:12px;margin-left:40px;">${getRoomName(val.room) || 'Pas de pièce'}</span></a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
			
			<i class="mdi mdi-arrow-up-circle" title="Monter" style="color:orange;font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidget('${val.id}','${value.parentId}','${value.index}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:orange;font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidget('${val.id}','${value.parentId}','${value.index}');"></i> 

			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteWidget('${val.id}','${value.parentId}','${value.index}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="moveWidgetModal('${val.id}','${value.parentId}','${value.index}');"></i></li>`);
			//<i class="mdi mdi-content-copy" title="Dupliquer" style="color:rgb(195, 125, 40);font-size:20px;;" aria-hidden="true" onclick="duplicateWidget('${val.id}');"></i></li>`);
		} else { //it's a group
			items.push( `<div  class="widgetItem widgetGroup" data-id="${value.id}" data-parentId="${value.parentId}" data-index="${value.index}"><li><a  onclick="editGroupModal('${value.id}');"><i class="fa fa-list"></i> ${value.name}</a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
			
			<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upGroup('${value.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downGroup('${value.id}');"></i> -->
			
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteGroup('${value.id}');"></i>
			<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveGroupModal('${value.id}');"></i></li>`);
			var curWidgets = configData.payload.widgets.filter(w => w.parentId == value.id);
			curWidgets = curWidgets.sort(function(s,t) {
				return s.index - t.index;
			});
			items.push(`<li><ul class="tabSubUL widgetGroup" data-id="${value.id}">`);
			$.each(curWidgets, function (key, wid) {
				var w = allWidgetsDetail.find(x => x.id == wid.id) ;
				if ( w != undefined){
					var img = widgetsList.widgets.find(i => i.type == w.type).img;
					items.push( `<li  class='widgetItem' data-id="${w.id}" data-parentId="${wid.parentId}" data-index="${wid.index}"><a title="id=${w.id}" onclick="editWidgetModal('${w.id}');"><img src="plugins/JeedomConnect/data/img/${img}" class="imgList"/>${w.name}</a>
					<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
					
					<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upWidget('${w.id}','${wid.parentId}','${wid.index}');"></i>
				<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downWidget('${w.id}','${wid.parentId}','${wid.index}');"></i> -->
				
				<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="deleteWidget('${w.id}','${wid.parentId}','${wid.index}');"></i>
				<i class="mdi mdi-arrow-right-circle" title="Déplacer vers..." style="color:rgb(50, 130, 60);font-size:24px;;" aria-hidden="true" onclick="moveWidgetModal('${w.id}','${wid.parentId}','${wid.index}');"></i></li>`);
				}
			});
			items.push("</ul></li></div>");
		}
	});
	$("#widgetsUL").html(items.join(""));
	$("#widgetsUL").attr('data-id', parentId);
}

function incrementIdCounter() {
	configData.idCounter += 1;
}

function save(){
	configData['payload'].configVersion += 1;
	//console.log(configData);
	$.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {'action': 'saveConfig', 'config': JSON.stringify(configData), 'apiKey': apiKey },
            success: function () {
               $('#jc-assistant').showAlert({message: 'Configuration sauvegardée', level: 'success'});
            },
            error: function (error) {
             //console.log(error);
			 $('#jc-assistant').showAlert({message: 'Erreur lors de la sauvegarde', level: 'danger'});
            }
    });

}

function resetConfig() {
	getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"La configuration va être remise à zéro. Voulez-vous continuer ?"}] }, function(result) {
		configData = {
				'type':  'JEEDOM_CONFIG',
				'formatVersion' : '1.0',
				'idCounter': 0,
				'payload': {
					'configVersion': 0,
					'tabs': [],
					'sections': [],
					'rooms': [],
					'groups': [],
					'summaries': [],
					'widgets': []
				}
		};
		initData();
	});
}

/*HELPER FUNCTIONS */


function getRootObjects(id) {
	if (id == 'undefined') { id = undefined; }
	var widgets = configData.payload.widgets.filter(w => w.parentId == id);
	var groups = configData.payload.groups.filter(g => g.parentId == id);
	return groups.concat(widgets);
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


/* BOTTOM TAB FUNCTIONS */

function addBottomTabModal() {
	getSimpleModal({title: "Ajouter un menu bas", fields:[{type: "enable", value: true},{type: "name"},
		{type:"icon"}, {type: "swipeUp"}, {type: "swipeDown"}, {type: "action"}] }, function(result) {
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
		if (result.action) { newTab.action = result.action; }
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
			{type:'swipeUp', value:tabToEdit.swipeUp}, {type:'swipeDown', value:tabToEdit.swipeDown}, {type:'action', value:tabToEdit.action}] },
		function(result) {
				tabToEdit.name = result.name;
				tabToEdit.icon = result.icon;
				tabToEdit.swipeUp = result.swipeUp;
				tabToEdit.swipeDown = result.swipeDown;
				tabToEdit.action = result.action;
				tabToEdit.enable = result.enable;
				refreshBottomTabData();
  });
}

/*
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
*/

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
				removeWidgetAssistant(widget.id, widget.parentId, widget.index);
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

/*
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
*/

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
			removeWidgetAssistant(widget.id, widget.parentId, widget.index)
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
  getSimpleModal({title: "Ajouter une pièce", fields:[{type: "object"}] }, function(result) {
		if ( result.object == 'none') return;
		var maxIndex = getMaxIndex(configData.payload.rooms);
		var newRoom = {};
		newRoom.index = maxIndex + 1;
		newRoom.name = result.name.replace(/\u00a0/g, "");
		newRoom.id = parseInt(result.object);

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

/*
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
*/

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
		var maxGroupId = getMaxId(configData.payload.groups , 999000)
		newGroup.id = maxGroupId +1 ;

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

/*
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
*/

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
			removeWidgetAssistant(widget.id, widget.parentId, widget.index);
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

/* SUMMARIES */

function importAllSummary(){
	$('#jc-assistant').hide();

	
	var existingSummaries = $("#summaryUL li")
								.map(function() { return $(this).data('id'); })
								.get();
	
	var allSummaries = $('#selSummaryDetail option');
	
	allSummaries.each(function() {
		if (existingSummaries.indexOf($(this).val()) == -1  && $(this).val() != 'none' ) {
			selectSummary($(this).val());
		}
		else {
			console.log(" ## SKIPPING option val : " + $(this).val() ) ;
		}
	});



}


function selectSummary(key) {
	$('#jc-assistant').hide();

	
	if (key == undefined){
		key = $('#selSummaryDetail option:selected').val() ;
	}

	if ( key == 'none' ){
		$('#jc-assistant').showAlert({message: 'Merci de sélectionner un résumé', level: 'danger'});
		return;
	}
	
	var summarySelected = $('#selSummaryDetail option[value="'+key+'"]') ;
	result = {};  	

	result.index = getMaxIndex(configData.payload.summaries) +1 ;
	result.enable = true;
	
	let data = summarySelected.map(function() {
		let o = this;
		return Object.keys(o.dataset).reduce(function(c, v) { c[v] = o.dataset[v]; return c;}, {})
	  }).get();
	
	icon = {};  
	$.each(data[0], function(key, val) {
		if ( key != 'iconSource' && key != 'iconName') {
			result[key] = val ;
		}
		else{
			var newKey = key.replace("icon", "").toLowerCase();
			icon[newKey] = val ;
		}
	});
	result['image']= icon;

	configData.payload.summaries.push(result);

	refreshSummaryData();
}

function refreshSummaryData() {
	if (typeof configData.payload.summaries == "undefined") {
		configData.payload.summaries=[];
	}

	$('#selSummaryDetail option').css('display','block');
	$('#btn-importAllSummary').css('display', '');

	summaries = configData.payload.summaries.sort(function(s,t) {
		return s.index - t.index;
	});
	var items = [];
	$.each( summaries, function( key, val ) {
		items.push( `<li data-id="${val.key}" class="summaryItem" ><a  onclick="editSummaryModal('${val.key}');">
			${iconToHtml(val.image)}<span style="margin-left:10px;"></span>${val.name}</a>
			<i class="mdi mdi-arrow-up-down-bold" title="Déplacer" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;cursor:grab!important;" aria-hidden="true"></i>
			<!-- <i class="mdi mdi-arrow-up-circle" title="Monter" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upSummary('${val.key}');"></i>
			<i class="mdi mdi-arrow-down-circle" title="Descendre" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downSummary('${val.key}');"></i> -->
			<i class="mdi mdi-minus-circle" title="Supprimer" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteSummary('${val.key}');"></i></li>`);
		
		$('#selSummaryDetail option[value="' + val.key + '"]').css('display','none');
		$("#selSummaryDetail").val($("#selSummaryDetail option:first").val());
	});
	$("#summaryUL").html(items.join(""));

	// if no more data available in select option, then hide import-all button
	var nb = $('#selSummaryDetail option:not([style*="display: none"])').length ; 
	if ( nb == 1 ){
		$('#btn-importAllSummary').css('display', 'none');
	}
	
}


/*
function upSummary(summaryKey) {
	var summaryToMove = configData.payload.summaries.find(summary => summary.key == summaryKey);
	var summaryIndex = summaryToMove.index;
	if (summaryIndex == 0) {
		console.log("can't move this summary");
		return;
	}
	var otherSummary = configData.payload.summaries.find(summary => summary.index == summaryIndex - 1);
	summaryToMove.index = summaryIndex - 1;
	otherSummary.index = summaryIndex;
	refreshSummaryData();
}

function downSummary(summaryKey) {
	var summaryToMove = configData.payload.summaries.find(summary => summary.key == summaryKey);
	var summaryIndex = summaryToMove.index;
	if (summaryIndex == getMaxIndex(configData.payload.summaries)) {
		console.log("can't move this summary");
		return;
	}
	var otherSummary = configData.payload.summaries.find(summary => summary.index == summaryIndex + 1);
	summaryToMove.index = summaryIndex + 1;
	otherSummary.index = summaryIndex;
	refreshSummaryData();
}
*/

function deleteSummary(summaryKey) {
	getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous supprimer ce résumé ?"}] }, function(result) {
		if ( $("#summaryModal").length > 0 ) {
			$("#summaryModal").dialog('destroy').remove();
	  	}
		
		var summaryToDelete = configData.payload.summaries.find(summary => summary.key == summaryKey);
		var index = configData.payload.summaries.indexOf(summaryToDelete);

		configData.payload.summaries.forEach(item => {
			if (item.index > summaryToDelete.index) {
				item.index = item.index - 1;
			}
		});
		
		configData.payload.summaries.splice(index, 1);
		refreshSummaryData();
 	 });
}


function editSummaryModal(summaryKey) {
	var summaryToEdit = configData.payload.summaries.find(summary => summary.key == summaryKey);
	getSummaryModal({title:"Editer un résumé", summary:summaryToEdit});
}


function getSummaryModal(_options, _callback) {
	// console.log("getsummaryModal option recues : ", _options)
	if (!isset(_options)) {
	  return;
	}
	$("#summaryModal").dialog('destroy').remove();
	if ($("#summaryModal").length == 0) {
	  	$('body').append('<div id="summaryModal"></div>');
  
	  	$("#summaryModal").dialog({
			title: _options.title,
			closeText: '',
			autoOpen: false,
			modal: true,
			width: 1250,
			height: 0.7*$(window).height()
	  	});
	  	jQuery.ajaxSetup({
			async: false
	  	});
	  	$('#summaryModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.summaryModal.JeedomConnect');
	  	jQuery.ajaxSetup({
			async: true
	  	});
	}
	setSummaryModalData(_options);
  
	$("#summaryModal").dialog({title: _options.title});
	$('#summaryModal').dialog('open');
  
};


function hideSummary(){
	$("#summaryModal").dialog('destroy').remove();
}

function removeSummary() {
	deleteSummary($('#summary-key').text());
}

function setSummaryModalData(options) {
	$('#widgetModal').dialog('destroy').remove();
	refreshAddSummaries(options) ;
	
	//Enable
	var enable = options.summary.enable ? "checked": "";
	$("#enable-input").prop('checked', enable);


	var summary = summaryConfig.find(i => i.type == 'summary');


	summary.options.forEach(option => {
		
		if (option.category == "string" & options.summary[option.id] !== undefined ) {
			$("#"+option.id+"-input").val(options.summary[option.id]);
		} 
		else if (option.category == "binary" & options.summary[option.id] !== undefined ) {
		   	$("#"+option.id+"-input").prop('checked', options.summary[option.id] ? "checked": "");
		}
		else if (option.category == "stringList" & options.summary[option.id] !== undefined) {
		   	var selectedChoice = option.choices.find(s => s.id == options.summary[option.id]);
		   	if (selectedChoice !== undefined) {
				$('#'+option.id+'-input option[value="'+options.summary[option.id]+'"]').prop('selected', true);
				if (option.id == "subtitle") {
				$("#subtitle-input-value").val(selectedChoice.id)
				}
		   	} 
		   	else if (option.id == "subtitle" & options.summary.subtitle !== undefined) {
				$('#subtitle-input option[value="custom"]').prop('selected', true);
				$("#subtitle-input-value").val(options.summary.subtitle)
				$("#subtitle-input-value").css('display', 'block');
				$("#subtitle-select").show();
		   	}
		}
		else if (option.category == "ifImgs" & options.summary[option.id] !== undefined) {
			imgCat = options.summary[option.id];
			refreshImgListOption('summary');
		}
		else if (option.category == "img" & options.summary[option.id] !== undefined ) {
			$("#icon-div-"+option.id).html(iconToHtml(options.summary[option.id]));
		}
	});
	
	refreshStrings();
	
  }

function saveSummary() {
	$('#summary-alert').hideAlert();
	var result = {};
	var summaryKey = $('#summary-key').text() ;
	var summary = summaryConfig.find(i => i.type == 'summary');

	try{
		summary.options.forEach(option => {
			if (option.category == "string") {
				if ($("#"+option.id+"-input").val() == '' & option.required) {
					throw 'La commande '+option.name+' est obligatoire';
				}
				result[option.id] = $("#"+option.id+"-input").val();
			} 
			else if (option.category == "binary") {
				result[option.id] = $("#"+option.id+"-input").is(':checked');
			}
			else if (option.category == "ifImgs") {
				if (imgCat.length == 0 & option.required) {
					throw 'La commande '+option.name+' est obligatoire';
				}
				imgCat.forEach(item => {
					item.image = htmlToIcon($("#icon-div-"+item.index).children().first());
					item.info = { id: $("#info-"+item.index+" option:selected").attr('value'), type: $("#info-"+item.index+" option:selected").attr('type') };
					item.operator = $("#operator-"+item.index).val();
					item.value = $("#"+item.index+"-value").val();
				});
				result[option.id] = imgCat;
			}	
			else if (option.category == "img") {
				let icon = htmlToIcon($("#icon-div-"+option.id).children().first());
				if (icon.source == undefined & option.required) {
					throw "L'image est obligatoire";
				}
				result[option.id] = icon.source != undefined ? icon : undefined;
			}
		});

		summaryIndex = configData.payload.summaries.findIndex((obj => obj.key == summaryKey));
		previousSummary = configData.payload.summaries[summaryIndex] ;
		
		previousSummary['name']= result['name'] ;
		previousSummary['enable']= $('#enable-input').is(":checked") ;
		
		delete result['name'] ;
		
		const resultFinal = Object.assign(previousSummary, result);
		
		configData.payload.summaries[summaryIndex] = resultFinal ;
		
		$('#summaryModal').dialog('destroy').remove();
		refreshSummaryData();
	} 
	catch (error) {
		$('#summary-alert').showAlert({message: error, level: 'danger'});
		console.error(error);
  	}
	

}

function refreshAddSummaries(_options) {
	var summary = summaryConfig.find(i => i.type == 'summary');
	
	$("#summaryDescription").html(summary.description);
  
	if (summary.variables) {
		let varDescr = `Variables disponibles : <ul style="padding-left: 15px;">`;

		summary.variables.forEach(v => {
			varDescr += `<li>#${v.name}# : ${v.descr}</li>`;
		});

		varDescr += '</ul>';
		$("#summaryVariables").html(varDescr);
		$("#summaryVariables").show();
	} 
	else {
		$("#summaryVariables").hide();
	}
  
	var items = [];
  
	//Actif
	option = `<li><div class='form-group' >
		<label class='col-xs-3 '>Actif</label>
		<div class='col-xs-9'><div class='input-group'><input type="checkbox" style="width:150px;" id="enable-input" checked></div></div></div></li>`;
	items.push(option);


	//Key
	option = `<li><div class='form-group'>
		<label class='col-xs-3 '>Clé</label>
		<div class='col-xs-9'><div id='summary-key'>${_options.summary.key}</div></div></div></li>`;
	items.push(option);
  
	summary.options.forEach(option => {
		var required = (option.required) ? "required" : "";
		var description = (option.description == undefined) ? '' : option.description;
		var curOption = `<li><div class='form-group' id="${option.id}-div">
		<label class='col-xs-3  ${required}'   id="${option.id}-label">${option.name}</label>
		<div class='col-xs-9' id="${option.id}-div-right">
		<div class="description">${description}</div>`;
  
		if (option.category == "string") {
			curOption += `<div class='input-group'>
					<div style="display:flex"><input style="width:340px;" id="${option.id}-input" value=''>`;
			if (option.id == 'name') {
				curOption += `
					<div class="dropdown" id="name-select">
					<a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
					<i class="fas fa-plus-square"></i> </a>
					<ul class="dropdown-menu infos-select" input="${option.id}-input">`;
				if (summary.variables) {
					summary.variables.forEach(v => {
					curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
					});
				}
				curOption += `</ul></div></div>`
			}
			curOption += `</div></div></div></li>`;
		} 
		else if (option.category == "binary") {
			curOption += `<div class='input-group'><input type="checkbox" style="width:150px;" id="${option.id}-input"></div>
				</div></div></li>`;
		} 
		else if (option.category == "stringList") {
			curOption += `<div class='input-group'><select style="width:340px;" id="${option.id}-input" onchange="subtitleSelected();">`;
			if (!required) {
				curOption += `<option value="none">Aucun</option>`;
			}
			option.choices.forEach(item => {
				curOption += `<option value="${item.id}">${item.name}</option>`;
			})
			if (option.id == "subtitle") {
				curOption += `<option value="custom">Personalisé</option></select>`;
				curOption += `<div style="display:flex">
					<input style="width:340px; margin-top:5px; display:none;" id="subtitle-input-value" value='none'>`;

				curOption += `
					<div class="dropdown" id="subtitle-select" style=" display:none;">
					<a class="btn btn-default dropdown-toggle" data-toggle="dropdown" style="height" >
					<i class="fas fa-plus-square"></i> </a>
					<ul class="dropdown-menu infos-select" input="subtitle-input-value">`;
				if (summary.variables) {
					summary.variables.forEach(v => {
						curOption += `<li info="${v.name}" onclick="infoSelected('#${v.name}#', this)"><a href="#">#${v.name}#</a></li>`;
					});
				}
				curOption += `</ul></div></div>`
			}
			else {
				curOption += '</select>';
			}
			curOption += `</div></div></div></li>`;
		} 
		else if (option.category == "img") {
			curOption += `
				<a class="btn btn-success roundedRight" onclick="imagePicker(this)"><i class="fas fa-check-square">
				</i> Choisir </a>
				<a data-id="icon-div-${option.id}" id="icon-div-${option.id}" onclick="removeImage(this)"></a>
				</div></div></li>`;
		} 
		else if (option.category == "ifImgs") {
			curOption += `<span class="input-group-btn">
				<a class="btn btn-default roundedRight" onclick="addImgOption('summary')"><i class="fas fa-plus-square">
				</i> Ajouter</a></span><div id="imgList-option"></div>`;
			curOption += `</div></div></li>`;
		} 
		else {
			return;
		}

		items.push(curOption);

		});
  

	$("#summaryOptions").html(items.join(""));
  }

/* WIDGETS */


function upWidget(widgetId, widgetParentId, widgetIndex) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);
	if (widgetParentId == 'undefined') { widgetParentId = undefined; }
	widgetIndex = parseInt(widgetIndex);
	var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
	if (configData.payload.groups.find(g => g.id == widgetParentId) === undefined) { //widget is not in a group
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
		group = configData.payload.groups.find(g => g.id == widgetParentId);
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

function downWidget(widgetId, widgetParentId, widgetIndex) {
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);
	if (widgetParentId == 'undefined') { widgetParentId = undefined; }
	widgetIndex = parseInt(widgetIndex);
	var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
	if (configData.payload.groups.find(g => g.id == widgetParentId) === undefined) { //widget is not in a group
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
		var widgetsList = configData.payload.widgets.filter(w => w.parentId == widgetToMove.parentId);
		group = configData.payload.groups.find(g => g.id == widgetParentId);
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


function deleteWidget(widgetId, widgetParentId, widgetIndex) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous supprimer ce widget ?"}] }, function(result) {
		removeWidgetAssistant(widgetId, widgetParentId, widgetIndex);
		refreshWidgetsContent();
  });
}

function removeWidgetAssistant(widgetId, widgetParentId, widgetIndex) {
	if (widgetParentId == 'undefined') { widgetParentId = undefined; }
	configData.payload.widgets = configData.payload.widgets.filter(w => w.id != widgetId | w.index != widgetIndex | w.parentId != widgetParentId);

	var rootElmts = getRootObjects(widgetParentId);
	rootElmts.forEach(item => {
		if (item.index > widgetIndex) {
			item.index = item.index - 1;
		}
	});

}

function moveWidgetModal(widgetId, widgetParentId, widgetIndex) {
  getSimpleModal({title: "Déplacer un widget", fields:[{type: "move",value:getWidgetsParents()}] }, function(result) {
	var parentId = result.moveToId;
	if (parentId === null) { return; }
	var widgetToMove = configData.payload.widgets.find(w => w.id == widgetId & w.index == widgetIndex & w.parentId == widgetParentId);
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
	$('#jc-assistant').hide();
	var widgetSelectedId = $("#selWidgetDetail option:selected").attr('data-widget-id');
	if ( widgetSelectedId == 'none'){
		$('#jc-assistant').showAlert({message: 'Merci de sélectionner un widget', level: 'danger'});
		return;
	}

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

function duplicateWidget(widgetId) {
	var widgetToDuplicate = configData.payload.widgets.find(w => w.id == widgetId);
	var newWidget = JSON.parse(JSON.stringify(widgetToDuplicate));
	var parentId = $("#widgetsParents-select option:selected").attr('value');
	var rootElmts = getRootObjects(parentId);

	var maxIndex = getMaxIndex(rootElmts);
	newWidget.index = maxIndex + 1;
	newWidget.id = widgetToDuplicate.id;

	configData.payload.widgets.push(newWidget);
	incrementIdCounter();
	refreshWidgetsContent();
}
