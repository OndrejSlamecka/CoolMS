$(document).ready(function(){           
    
    /* --- INIT --- */
    $("#frmsearchForm-q").focus();
    $('.delete.confirm').confirmdialog();    

    /* --- HTML5 SUBMIT OF FILES --- */
    $status = $("#status");
    $statusBar = $("#status span");
    
    $("form.html5upload").sexyPost({
        start: function()
        {
            $status.show("slow");          
        },
        progress: function(event, completed, loaded, total)
        {
            value = (completed * 100).toFixed(2) + "%";
            $statusBar.width( value );
            $statusBar.text( value )
        },
        complete: function(event, responseText)
        {
            value = "100%";
            $statusBar.width( value );
            $statusBar.text( value );
            jQuery.nette.success( JSON.parse(responseText) );
            $('.delete.confirm').confirmdialog();  
        }
    });
    
    /* --- SEARCH BOX AJAX --- */    
    var $breadcrumbs = $('#breadcrumbs');
    
    $("#frmsearchForm-q").live("keyup", function(ev) { 
        
        var inputLength = $(this).val().length;  
        
        $(this.form).ajaxSubmit({
            success: function(payload) {
                jQuery.nette.success(payload);
                $('.delete.confirm').confirmdialog();    
                
                if(inputLength)
                    $breadcrumbs.css('display', 'none');
                else
                    $breadcrumbs.css('display', 'inline');
            }
        }); 
        
    });
    
});        