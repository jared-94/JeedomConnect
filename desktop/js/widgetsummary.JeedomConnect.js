$('#md_modal').off('click', '#bt_removeJcWidgetSummary').on('click', '#bt_removeJcWidgetSummary', function() {

    var myData = $('#table_JcWidgetSummary .removeWidget:checked');
    var count = myData.length;
    if ( count > 0){
        
        var warning = "<i source='md' name='alert-outline' style='color:#ff0000' class='mdi mdi-alert-outline'></i>" ;
        if ( count == 1 )
        {
            msg = warning + '  Voulez vous supprimer ce widget ? '  + warning ;
        }
        else{
            msg = warning + '  Voulez vous supprimer ces ' + count +' widgets ? '  + warning ;
        }


        getSimpleModal({title: "Confirmation", fields:[{type: "string",
        value: msg  }] }, function(result) {
            $('#alert_JcWidgetSummary').hideAlert();
        
            myData.each(function() {
                var widgetId = $(this).closest('.tr_object').data('widget_id');
                $.ajax({
                    type: "POST",
                    url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
                    data: {
                        action: "removeWidgetConfig",
                        eqId: widgetId
                    },
                    dataType: 'json',
                    error: function(error) {
                        $('#alert_JcWidgetSummary').showAlert({message: error.message, level: 'danger'});
                    },
                    success: function(data) {
                        if (data.state != 'ok') {
                            $('#alert_JcWidgetSummary').showAlert({
                                message: data.result,
                                level: 'danger'
                            });
                        }
                        else{
                            $('.tr_object[data-widget_id='+widgetId+']').remove();

                        }
                    }
                })
            });

            $('#alert_JcWidgetSummary').showAlert({
                message: 'Suppression effectuée',
                level: 'success'
            });
            $('#bt_removeJcWidgetSummary').css('display', 'none');
        });
    }


});

$('#bt_saveJcWidgetSummary').off('click').on('click', function() {
    $('#div_alert').hideAlert();
    $('#alert_JcWidgetSummary').hideAlert();

    myData = $('#table_JcWidgetSummary .tr_object[data-changed=true]').getValues('.objectAttr');

    if (myData.length > 0 ) {
        $.post({
                url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
                data: {
                    action: 'updateWidgetMass',
                    widgetsObj: myData
                },
                cache: false,
                dataType: 'json',
                async: false,
                success: function( data ) {
                    if (data.state != 'ok') {
                        $('#alert_JcWidgetSummary').showAlert({
                            message: data.result,
                            level: 'danger'
                        });
                    }
                    else{
                        var vars = getUrlVars()
                        var url = 'index.php?'
                        for (var i in vars) {
                        if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                            url += i + '=' + vars[i].replace('#', '') + '&'
                        }
                        }
                        modifyWithoutSave = false
                        url += '&saveSuccessFull=1'
                        loadPage(url)
                    }
                    
                }
            });
    }
    else{
        $('#alert_JcWidgetSummary').showAlert({
            message: 'Aucun changement',
            level: 'warning' 
        });
    }

});


$("#table_JcWidgetSummary").on('change', ' .objectAttr', function() {
    $(this).closest('.tr_object').attr('data-changed', true);
  });


$('#bt_updateWidgetSummary').off('click').on('click', function() {

    var myData = $('#table_JcWidgetSummary .tr_object[data-updated=true]');
    myData.each(function() {
        var id = $(this).attr('data-widget_id');
        
        //update data
        displayWidgetSummaryData(id);

        //highlight line in orange
        $(this).attr('style', 'background-color : #FF7B00 !important');
        //fadeout
        $(this).animate({
            'opacity': '0.5'
            }, 3000, function () {
                $(this).css({'backgroundColor': '#fff','opacity': '1'});
            }
        );
        //remove updated attr
        $(this).removeAttr( "data-updated" );
    });

    //hide update button
    $('#bt_updateWidgetSummary').css('display', 'none');
    
})


$('#md_modal').off('click', '.removeWidget').on('click', '.removeWidget', function() {

    var myData = $('#table_JcWidgetSummary .removeWidget:checked');
    var count = myData.length;
    if ( count > 0 ){
        $('#bt_removeJcWidgetSummary').css('display', '');
    }
    else{
        $('#bt_removeJcWidgetSummary').css('display', 'none');
    }
});

$('#table_JcWidgetSummary').off('click', '.bt_openWidget').on('click', '.bt_openWidget', function() {

    var eqId = $(this).closest('.tr_object').data('widget_id');
    editWidgetModal(eqId, false, false, false);
    $(this).closest('.tr_object').attr('data-updated', true);
    $('#bt_updateWidgetSummary').css('display', 'block');
    

})

function displayWidgetSummaryData(myId = ''){

    $.post({
        url: "plugins/JeedomConnect/core/ajax/jeedomConnect.ajax.php",
        data: {
            action: 'getWidgetMass',
            id: myId
        },
        cache: false,
        dataType: 'json',
        async: false,
        success: function( data ) {
            if (data.state != 'ok') {
                $('#alert_JcWidgetSummary').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            }
            else{
                if ( myId == ''){
                    console.log('adding all data', data.result)
                    $('#table_JcWidgetSummary tbody').html(data.result);
                    $("#table_JcWidgetSummary").trigger("update");

                }
                else{
                    console.log('updating '+ myId+ ' with data', data.result)
                    $('#table_JcWidgetSummary .tr_object[data-widget_id='+myId+']').html(data.result);
                    $("#table_JcWidgetSummary").trigger("update");
                }
            }
        }
    });

    

}


$(document).ready(function(){
    initTableSorter()
    displayWidgetSummaryData();
    
});