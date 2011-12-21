$(document).ready(function(){  
    
    // Ajaxed buttons
    $("a.ajax").live("click",function (event) {
        event.preventDefault();
        $.get(this.href);
    });     
  
    // Search box in FileManager
    $("form.ajax.onchange input").live("keyup",function () {
        $(this.form).ajaxSubmit(
        {
            success: function(payload) {
                jQuery.nette.success(payload);
                $("#frm-searchForm input").focusEnd();
            }
        }); 
        
    });       

    // Ajaxed forms
    $("form.ajax").live("submit",function () {
        $(this).ajaxSubmit();    
        return false;
    });

    // Continual saving of forms
    $savableForm = $('form.savable');
    $continualSaveError = $('#continualSaveError');
   
    var fncSaveContinually = function(){        
        setTimeout(function()
        {
            // Ajax submit to server
            var form = $savableForm;
            form.ajaxSubmit({
                success: function(payload){
                    if(payload.error){
                        $continualSaveError.show();
                    }else{
                        $continualSaveError.hide();
                    }
                    fncSaveContinually();
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