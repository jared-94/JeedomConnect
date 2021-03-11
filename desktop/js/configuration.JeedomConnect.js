
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

    var optionSelected = $('input[name=filter]:checked').attr('id');
    console.log("option selected ==> " + optionSelected);
    $('.actions-detail').hideAlert();
    $('.resultListWidget').hideAlert();
    
    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'countWigdetUsage'
        },
        cache: false,
        dataType: 'json',
        success: function( data ) {
            console.log('result : ' , data )
            if (data.state != 'ok') {
                $('.actions-detail').showAlert({
                message: data.result,
                level: 'danger'
                });
            }
            else{

                var resultUnused = data.result.unused;
                var resultUnexisting = data.result.unexisting;
                var resultCount = data.result.count;
                var typeAlert = 'success' ;
                if ( optionSelected == 'unusedOnly' ){
                    if (resultUnused.length > 0){
                        msgFinal = 'Voici les widget non utilisés <br>' + data.result.unused.join(", ") ; 
                    }
                    else{
                        msgFinal = 'Tous les widgets sont utilisés !'; 
                    }
                }
                else if ( optionSelected == 'unexistingOnly' ){
                    if (resultUnexisting.length > 0){
                        msgFinal = '<u>Voici des widgets inexistants mais présents dans vos fichiers de configuration </u><br><br>' + resultUnexisting.join(", ") ;
                        typeAlert = 'warning' ;
                    }
                    else{
                        msgFinal = 'Tous les widgets utilisés dans les équipements sont bien existants dans la configuration :)';
                    }
                }
                else{
                    var msgUnexisting = '';
                    var msgCount = '';

                    if ( ! jQuery.isEmptyObject(resultCount) ){
                        msgCount = '<u>Voici le compte de chaque widget </u><br><br>' + JSON.stringify(data.result.count) ;

                        var html = "<table><thead><tr><th>Id</th><th>Count</th></thead><tbody>";
                        $.each(data.result.count, function (id, count) {
                            html += "<tr><td>" + id + "</td><td>" + count + "</td></tr>"
                        })
                        //msgCount += html ;

                    }

                    if (resultUnexisting.length > 0){
                        msgUnexisting = '<u>Voici des widgets inexistants mais présents dans vos fichiers de configuration </u><br><br>' + resultUnexisting.join(", ") ;
                        typeAlert = 'warning' ;
                    }

                    if ( msgCount == '' && msgUnexisting != '' ){
                        msgFinal = msgUnexisting;
                    }
                    else if ( msgCount != '' && msgUnexisting == '' ){
                        msgFinal = msgCount;
                    }
                    else{
                        msgFinal = msgCount + '<br><br>'+"-".repeat(30)+'<br><br>'+ msgUnexisting;
                    }
                    
                }

                
                $('.resultListWidget').showAlert({
                    message: msgFinal,
                    level: typeAlert
                });
            }
        }
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