/**
 * ConfirmDialog
 * ----------------
 * 
 * Lightweight jQuery plugin for creating simpliest confirmation dialogs.
 *  
 * 
 * Author: Ondrej Slamecka, http://www.slamecka.cz/, ondrej / slamecka / cz
 * Made: January, 2010
 * Version: 0.1 (Revision 1)
 * Copyright: (c) Ondrej Slamecka 2010  
 * License: New BSD
 * 
 * Developed on jQuery version 1.4. Not tested on other versions (if you have any experience, please mail me to e-mail above).    
 *
 */
 
;(function($){

var jqConfirmDialog = function( options, element ){
 
  /******** Options ********/
  this.defaultopts = {
    actionText : element.html(),
    cancelText : 'Cancel',    
    dialoghtml : '<div id="confirmDialog"><a href="#" id="confirmAction">%action%</a> / <a href="#" id="confirmCancel">%cancel%</a></div>',
  };
  
  // Build options   
  this.options = $.extend({}, this.defaultopts, options);
  
  // Replace text in dialog
  this.options.dialoghtml = this.options.dialoghtml.replace( '%cancel%', this.options.cancelText );
  this.options.dialoghtml = this.options.dialoghtml.replace( '%action%', this.options.actionText );
 
  // Tohle je onen potvrzovaci dialog
  var confirmDialog = $( this.options.dialoghtml );
  
  // Pri kazdem kliknuti na element s tridou delete; Viz dokumentace jQuery - funkce live (jQuery 1.3+), ale postaci i .click( ... )
  $( element ).bind('click', function(e){
  
    // Box se bude zobrazovat vzdy u mysi (s 5px odsazenim z horizontalne)
    confirmDialog.css( 'left', e.pageX + 5 );
    confirmDialog.css( 'top', e.pageY );
  
    // Pridame box na konec elementu body, a to s peknou animaci
    confirmDialog.css( 'opacity', 0.2 );
    $('body').append( confirmDialog );
    confirmDialog.animate( { 'opacity' : 1 }, 100 );
  
    // URL puvodniho odkazu na smazani
    var actionURL = $(this).attr( 'href' );
  
    // Nabindujeme click event na odkaz "Delete" v dialogu - pri kliknuti odkaze na deleteURL
    $( '#confirmAction', confirmDialog ).bind( 'click', function(el){
      window.location.href = actionURL;
    });
  
    // Nabindujeme click event na "Cancel" v dialogu - jednoduse smazeme box
    $( '#confirmCancel', confirmDialog ).bind( 'click', function(e){
      $( '#confirmDialog' ).remove();
    });
  
    // Zrusime vychozi funkci (odkazani) odkazu
    e.preventDefault();
  });     
    
};

// Pluginez SlideShow class
$.fn.confirmdialog = function( options ){

  return this.each( function( ){

    var el = $(this);
    
    // If has slideshow
    if( el.data( 'confirmdialog' ) )
      return;
    
    var confirmdialog = new jqConfirmDialog( options, el );
    
    // Save data to elements's data
    el.data( 'confirmdialog' , confirmdialog );
    
    return el;

  });
  
};

})(jQuery);  