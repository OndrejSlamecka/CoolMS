/************** Functions *****************/
function fetchStructure(ul) {
  var lis = ul.children(), obj = {};
  lis.each(function(i, li) {
    var id = $(li).attr('id'); //.substr(3);
    if( id != "" && typeof id !== "undefined" ){
      obj[id] = fetchStructure($('> ul', li));      
      //opera.postError( id );
    }
  });
    
  return obj;
}


/************** Document Ready ****************/

$().ready(function() {

    makeSortable();

    $(".edit.ajax").click(function(){
        location.href="#snippet--MenuitemFormSnippet";
        
        $this = $(this);
        $parent = $this.closest( ".mi-container,.tmi-handler" ); // It isn't real parent, it's the closest sortable item
        $parent.css( 'opacity', 0.3 );
        $parent.animate({
            opacity:1
          },{queue:false, duration:1500});
        
    });

    // Changes highlight height according to currently moved object
    $( "#menu-designer-control" ).bind( "sort", function(event, ui){
       $('.ui-state-highlight-top').css( 'margin-bottom', parseInt( $(".ui-sortable-helper").height() ) -30 + 'px' );
    });

    // Submit
    $( "#frm-menuDesignerControlForm" ).submit(function(){
      var structure = fetchStructure($('ul#menu-designer-control'));
      $('#frm-menuDesignerControlForm input[name="structure"]').attr( 'value', JSON.stringify(structure) );    
    });

    /* Form type */
    $('#frm-menuitemForm input[type="radio"]').live( 'change' , function(){      
      $.ajax({ url : '/admin/menu/?do=changeFormMenuitemType&type='+$(this).attr( 'value' ),
                success : jQuery.nette.success
            });                        
    }); 
    
    // Module views
    $('select[name="module_name"]').live( 'change' , function(){
      var $title = $("#frmmenuitemForm-module_caption");
      var titleBackup = $title.val();
      
      $.ajax({ url : '/admin/menu/?do=changeFormChooseModule&name='+$(this).attr( 'value' ),
                success : function(payload){ 
                     jQuery.nette.success(payload);
                     $title = $("#frmmenuitemForm-module_caption");
                     $title.attr( 'value',titleBackup); }
            });
    }); 

    // View's params
    $('select[name="module_view"]').live( 'change' , function(){
      $.ajax({ url : '/admin/menu/?do=changeFormChooseModuleView&name='+$(this).attr( 'value' ),
                success : jQuery.nette.success
            });
    });     
    
    // Ajaxed forms
    $("form#menuEditForm").live("submit", function (el) {        
        el.preventDefault();
        $(this).ajaxSubmit( successSortablePayload ); 
    });    

});

function successSortablePayload(payload){
    jQuery.nette.success(payload); 
    if( $('#menu-designer-control').length > 0 )
        makeSortable();        
}

function makeSortable(){
      /* Menu designer */
      $('#menu-designer-control').sortable({ handle: '.tmi-handler', /*cursor: 'hand',*/ placeholder: 'ui-state-highlight-top'  }).disableSelection();
      $('#menu-designer-control ul').sortable({ connectWith: '#menu-designer-control ul', placeholder: 'ui-state-highlight', dropOnEmpty: false }).disableSelection();
      
}