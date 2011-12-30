/**
 * Author: Ondrej Slamecka, www.slamecka.cz
 * Package: CoolMS
 */

$(document).ready(function(){  
    
    // Ajaxed buttons
    $("a.ajax").live("click", function (event) {
        event.preventDefault();
        $.get(this.href);
    });        

    // Ajaxed forms
    $("form.ajax").live("submit", function () {
        $(this).ajaxSubmit();    
        return false;
    });

    // Continual saving of forms in ArticleModule and PageModule
    $savableForm = $('form.savable');
    $continualSaveError = $('#continualSaveError');
    $inputId = $("#frmarticleForm-id");
   
    var fncSaveContinually = function(){        
        setTimeout(function()
        {
            // Ajax submit to server
            var form = $savableForm;
            form.ajaxSubmit({
                success: function(payload){
                    fncSaveContinually();
                    if(!payload)
                        return false;
                    
                    if(payload.error){
                        $continualSaveError.show();
                    }else{
                        $continualSaveError.hide();
                        if(payload.draft_id)
                            $inputId.val(payload.draft_id);
                    }
                
                    return false;
                },
                error: function(){ // includes timeout
                    $continualSaveError.show();
                }
            });             
        }, 10 * 1000 );
    };
   
    if ($savableForm.length) {
        fncSaveContinually();
    }    

});