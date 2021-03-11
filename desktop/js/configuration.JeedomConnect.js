
$('.jeedomConnect').off('click', '#removeAllWidgets').on('click', '#removeAllWidgets', function() {
    $('.actions-detail').hideAlert();
    var warning = "<i source='md' name='alert-outline' style='color:#ff0000' class='mdi mdi-alert-outline'></i>" ;
    getSimpleModal({title: "Confirmation", fields:[{type: "string",
        value:warning +" Vous allez supprimer l'ensemble des widgets sauvegardés ainsi que remettre à 0 la configuration de tous vos équipements.<br><b>Le retour arrière n'est pas possible.</b><br>Voulez-vous continuer ? "+ warning }] }, function(result) {
        $.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {
                action: 'removeWidgetConfig',
                all : true
            },
            cache: false,
            dataType: 'json',
            success: function( data ) {
                if (data.state != 'ok') {
                    $('.actions-detail').showAlert({
                    message: data.result,
                    level: 'danger'
                    });
                }
                else{
                    $('.actions-detail').showAlert({
                        message: data.result.widget + ' widgets ont été supprimés <br>Ainsi que ' + data.result.eqLogic + ' équipements réinitialisé(s)',
                        level: 'success'
                    });
                }
            }
        });
    });

})


$('.jeedomConnect').off('click', '#reinitAllEq').on('click', '#reinitAllEq', function() {

    $('.actions-detail').hideAlert();
    var warning = "<i source='md' name='alert-outline' style='color:#ff0000' class='mdi mdi-alert-outline'></i>" ;
    getSimpleModal({title: "Confirmation", fields:[{type: "string",
    value:warning +" Vous allez remettre à 0 la configuration de tous vos équipements.<br><b>Le retour arrière n'est pas possible.</b><br>Voulez-vous continuer ? "+ warning }] }, function(result) {
        $.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {
                action: 'reinitEquipement'
            },
            cache: false,
            dataType: 'json',
            success: function( data ) {
                if (data.state != 'ok') {
                    $('.actions-detail').showAlert({
                    message: data.result,
                    level: 'danger'
                    });
                }
                else{
                    $('.actions-detail').showAlert({
                        message: data.result.eqLogic + ' équipements ont été réinitialisés',
                        level: 'success'
                    });
                }
            }
        });
    });

})


$('.jeedomConnect').off('click', '#listWidget').on('click', '#listWidget', function() {

    var onlyUnused = $('#unusedOnly').is(':checked') ;
    $('.actions-detail').hideAlert();
    $('.resultListWidget').hideAlert();
    
    getSimpleModal({title: "Confirmation", fields:[{type: "string",
        value:warning +" Confirmez ? "+ warning }] }, function(result) {
        $.post({
            url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
            data: {
                action: 'reinitEquipement'
            },
            cache: false,
            dataType: 'json',
            success: function( data ) {
                if (data.state != 'ok') {
                    $('.actions-detail').showAlert({
                    message: data.result,
                    level: 'danger'
                    });
                }
                else{
                    $('.actions-detail').showAlert({
                        message: data.result.eqLogic + ' équipements ont été réinitialisés',
                        level: 'success'
                    });
                }
            }
        });
    });

})



function getSimpleModal(_options, _callback) {
    if (!isset(_options)) {
      return;
    }
      $("#simpleModal").dialog('destroy').remove();
    if ($("#simpleModal").length == 0) {
      $('body').append('<div id="simpleModal"></div>');
      $("#simpleModal").dialog({
        title: _options.title,
        closeText: '',
        autoOpen: false,
        modal: true,
        width: 350
      });
      jQuery.ajaxSetup({
        async: false
      });
      $('#simpleModal').load('index.php?v=d&plugin=JeedomConnect&modal=assistant.simpleModal.JeedomConnect');
      jQuery.ajaxSetup({
        async: true
      });
    }
    setSimpleModalData(_options.fields);
    $("#simpleModal").dialog({title: _options.title, buttons: {
      "Annuler": function() {
        $(this).dialog("close");
      },
      "Valider": function(result) {
        if ($.trim(result) != '' && 'function' == typeof(_callback)) {
          _callback(result);
        }
        $(this).dialog('close');
      }
    }});
    $('#simpleModal').dialog('open');
  };



  function setSimpleModalData(options) {
	items = [];
	options.forEach(option => {
	    if (option.type == "string") {
			items.push(`<li>${option.value}</li>`);
		}
	});

	$("#modalOptions").append(items.join(""));

}