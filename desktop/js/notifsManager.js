var notifData;

$.post({
	url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
	data: {'action': 'getNotifs', 'apiKey': apiKey },
	cache: false,
	success: function( config ) {
		notifData = json_decode(config).result;
		initData();
	}
});



function initData() {
	document.getElementById("defaultOpen").click();
	refreshChannelTabData();
	refreshNotifsTabData();
}

function refreshChannelTabData() {
	var items = [];
	$.each( notifData.channels, function( key, val ) {
		var channelHtml = `<li><a  onclick="editChannelTabModal('${val.id}');">${val.name}</a>`;
			if (val.id != 'default') {
				channelHtml += `<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;margin-left:5px;" aria-hidden="true" onclick="deleteChannelTab('${val.id}');"></i>`;
			}
		channelHtml += '</li>';
		items.push(channelHtml);
	});
	$("#channelsUL").html(items.join(""));
}

function refreshNotifsTabData() {
	notifs = notifData.notifs.sort(function(s,t) {
		return s.index - t.index;
	});
	var items = [];
	$.each( notifs, function( key, val ) {
		var notifHtml = `<li><a  onclick="editNotifModal('${val.id}');">${val.name}</a>
			<i class="mdi mdi-arrow-up-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;margin-left:10px;" aria-hidden="true" onclick="upNotif('${val.id}');"></i>
			<i class="mdi mdi-arrow-down-circle" style="color:rgb(80, 120, 170);font-size:24px;margin-right:10px;" aria-hidden="true" onclick="downNotif('${val.id}');"></i>`;
		if (val.id != 'defaultNotif') {
			notifHtml += `<i class="mdi mdi-minus-circle" style="color:rgb(185, 58, 62);font-size:24px;" aria-hidden="true" onclick="deleteNotif('${val.id}');"></i>`;
		}
		notifHtml += '</li>';
		items.push(notifHtml);
	});
	$("#notifsUL").html(items.join(""));
}

function incrementIdCounter() {
	notifData.idCounter += 1;
}

function save(){
	console.log(notifData);
	$.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {'action': 'saveNotifs', 'config': JSON.stringify(notifData), 'apiKey': apiKey },
            success: function () {
               $('#jc-assistant').showAlert({message: 'Configuration des notifications sauvegardée', level: 'success'});
            },
            error: function (error) {
             console.log(error);
			 $('#jc-assistant').showAlert({message: 'Erreur lors de la sauvegarde', level: 'danger'});
            }
    });

}

function resetConfig() {
	getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"La configuration va être remise à zéro. Voulez-vous continuer ?"}] }, function(result) {
		notifData = {
				'idCounter': 0,
				'channels': [
					{
						'id': 'default',
						'name': 'Défaut'
					}
				],
				'notifs': [
					{
						'id': 'defaultNotif',
						'name': 'Notification',
						'channel': 'default',
						'index': 0
					}
				]
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

function htmlToIcon(html) {
	let icon = {};
	icon.source = html.attr("source");
	icon.name = html.attr("name");
	if (icon.source == 'fa') {
		icon.prefix = html.attr("prefix");
	}
	if ((icon.source == 'jeedom' | icon.source == 'md' | icon.source == 'fa') & typeof(html.attr("style")) == "string") {
		icon.color = html.attr("style").split(":")[1];
	}
	if (icon.source == 'jc' | icon.source == 'user') {
		let tags = html.attr("style").split(";");
		if (tags.includes('filter:grayscale(100%)')) {
			icon.shadow = true;
		}
	}

	return icon;
}

function iconToHtml(icon) {
	if (icon == undefined) { return ''; }
	if (typeof(icon) == "string") { //for old config structure
		if (icon.startsWith('user_files')) {
			icon = { source: 'user', name: icon.substring(icon.lastIndexOf("/") + 1) };
		} else if (icon.lastIndexOf(".") > -1) {
			icon = { source: 'jc', name: icon };
		} else {
			icon = { source: 'md', name: icon };
		}
	}
	if (icon.source == 'jeedom') {
		return `<i source="jeedom" name="${icon.name}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="icon ${icon.name}"></i>`;
	} else if (icon.source == 'md') {
		return `<i source="md" name="${icon.name}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="mdi mdi-${icon.name}"></i>`;
	} else if (icon.source == 'fa') {
		return `<i source="fa" name="${icon.name}" prefix="${icon.prefix || 'fa'}" ${icon.color ? 'style="color:'+icon.color+'"' : ''} class="${icon.prefix || 'fa'} fa-${icon.name}"></i>`;
	} else if (icon.source == 'jc') {
		return `<img source="jc" name="${icon.name}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="plugins/JeedomConnect/data/img/${icon.name}">`;
	} else if (icon.source == 'user') {
		return `<img source="user" name="${icon.name}" style="width:25px;${icon.shadow ? 'filter:grayscale(100%)' : ''}" src="plugins/JeedomConnect/data/img/user_files/${icon.name}">`;
	}
	return '';
}




/* CHANNELS TAB FUNCTIONS */

function addChannelTabModal() {
	getSimpleModal({title: "Ajouter un canal", fields:[{type: "name"}] }, function(result) {
	  var name = result.name;
	  if (name == '') {
			return;
	  }

	  var newChannel = {};
	  newChannel.name = name;
	  newChannel.id = 'channel-' + notifData.idCounter;

	  notifData.channels.push(newChannel);
	  incrementIdCounter();
	  refreshChannelTabData();
	});
}

function editChannelTabModal(channelId) {
	if (channelId == 'default') {
		return;
	}
  var channelToEdit = notifData.channels.find(i => i.id == channelId);
  getSimpleModal({title: "Editer un canal", fields:[{type: "name",value:channelToEdit.name}] }, function(result) {
	channelToEdit.name = result.name;
	refreshChannelTabData();
  });
}


function deleteChannelTab(channelId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous vraiment supprimer ce canal ?"}] }, function(result) {
	var channelToDelete = notifData.channels.find(i => i.id == channelId);
	var index = notifData.channels.indexOf(channelToDelete);
	notifData.channels.splice(index, 1);

	refreshChannelTabData();
  });
}

/* NOTIFICATIONS */



function upNotif(notifId) {
	var notifToMove = notifData.notifs.find(i => i.id == notifId);
 	var notifIndex = notifToMove.index;
  if (notifIndex == 0) {
		console.log("can't move this notif");
		return;
	}
	var otherElmt = notifData.notifs.find(e => e.index == notifIndex - 1);
	notifToMove.index = notifIndex - 1;
	otherElmt.index = notifIndex;

	refreshNotifsTabData();
}

function downNotif(notifId) {
	var notifToMove = notifData.notifs.find(i => i.id == notifId);
 	var notifIndex = notifToMove.index;

	if (notifIndex == getMaxIndex(notifData.notifs)) {
		console.log("can't move this notif");
		return;
	}
	var otherElmt = notifData.notifs.find(e => e.index == notifIndex + 1);
	notifToMove.index = notifIndex + 1;
  otherElmt.index = notifIndex;

	refreshNotifsTabData();
}

function deleteNotif(notifId) {
  getSimpleModal({title: "Confirmation", fields:[{type: "string",value:"Voulez-vous supprimer cette notification ?"}] }, function(result) {
	var notifToDelete = notifData.notifs.find(i => i.id == notifId);
	var index = notifData.notifs.indexOf(notifToDelete);
	notifData.notifs.forEach(item => {
		if (item.index > notifToDelete.index) {
			item.index = item.index - 1;
		}
	});
  notifData.notifs.splice(index, 1);
	refreshNotifsTabData();
  });
}


function addNotifModal() {
  getNotifModal({title:"Ajouter une notification"}, function(result) {
  	console.log(result)
	 	var maxIndex = getMaxIndex(notifData.notifs);
	  result.index = maxIndex + 1;
	  result.id = 'notif-' + notifData.idCounter;

	  notifData.notifs.push(result);
	  incrementIdCounter();
	  refreshNotifsTabData();

  });
}

function editNotifModal(notifId) {
  var notifToEdit = notifData.notifs.find(i => i.id == notifId);
  getNotifModal({title:"Editer une notification", notif:notifToEdit}, function(result) {
		console.log(result)
		notifToEdit = result;
	 	refreshNotifsTabData();

  });
}
