$(document).ready(function(){  
    
  /* Language choose form */  
  $("#frm-languageChooseForm select").change( function(){
    this.form.submit();
  });

  // Ajaxed buttons
  $("a.ajax").live("click", function (event) {
    event.preventDefault();
    $.get(this.href);
  });     
  
  // Ajaxed buttons
  $("form.ajax.onchange input").live("keyup", function (){
    $( this.form ).ajaxSubmit( function( payload ){
            jQuery.nette.success(payload);
            $( "#frm-searchForm input").focusEnd();
        } );    

    return false;
  });       

  // Ajaxed forms
  $("form.ajax").live("submit", function () {
    $(this).ajaxSubmit();    
    return false;
  });


});