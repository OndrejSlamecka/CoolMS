/**
 * ConfirmDialog
 * -------------
 * 
 * Lightweight jQuery plugin for the simpliest confirmation dialogs.
 *  
 * 
 * Author: Ondrej Slamecka, http://www.slamecka.cz
 * Copyright: (c) Ondrej Slamecka
 * License: New BSD
 * 
 * Developed on jQuery version 1.7.
 *
 */
 
;(function($){

    var jqConfirmDialog = function(options, element){
 
        /* ------- Options ------- */
        this.defaultopts = {
            actionText : function() {
                if(element.attr('title')) return element.attr('title'); else return element.html();
            },
            cancelText : 'Cancel',    
            dialoghtml : '<div id="confirmDialog"><a href="#" id="confirmAction">%action%</a> / <a href="#" id="confirmCancel">%cancel%</a></div>'
        };
  
        // Build options   
        this.options = $.extend({}, this.defaultopts, options);
  
        // Replace text in dialog
        this.options.dialoghtml = this.options.dialoghtml.replace('%cancel%', this.options.cancelText);
        this.options.dialoghtml = this.options.dialoghtml.replace('%action%', this.options.actionText);
 
        // This is the confirmation dialog itself
        var confirmDialog = $(this.options.dialoghtml);
  
        $(element).on('click', function(e) {
  
            // Show near mouse
            confirmDialog.css('left', e.pageX + 5);
            confirmDialog.css('top', e.pageY);
  
            // Add the box at the end of the body element
            confirmDialog.css('opacity', 0.2);
            $('body').append(confirmDialog);
            confirmDialog.animate({
                'opacity' : 1
            }, 100);
  
            // URL of the original action
            var actionURL = $(this).attr('href');
      
            $('#confirmAction', confirmDialog).on('click', function(e) {
                window.location.href = actionURL;
            });
      
            $('#confirmCancel', confirmDialog).on('click', function(e) {
                $('#confirmDialog').remove();
            });
  
            // Prevent redirecting to link
            e.preventDefault();
            
        }); // /element . on click    
    
    };

    // Extend jQuery functions
    $.fn.confirmdialog = function(options){

        return this.each( function(){

            var el = $(this);
    
            // If was already initialized
            if (el.data('confirmdialog'))
                return null;
    
            var confirmdialog = new jqConfirmDialog( options, el );
    
            // Save data to elements's data
            el.data('confirmdialog' , confirmdialog);
    
            return el;

        });
        
    };
})(jQuery);  