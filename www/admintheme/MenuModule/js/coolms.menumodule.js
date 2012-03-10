/**
 * Author: Ondrej Slamecka, www.slamecka.cz
 * Package: CoolMS
 */

/* --------------- Functions --------------- */
function fetchStructure(ol)
{
    var lis = ol.children(), obj = {};
    lis.each(function(i, li) {
        var id = $(li).attr('id'); //.substr(3);
        if( id != "" && typeof id !== "undefined" ){
            obj[id] = fetchStructure($('> ol', li));
        //opera.postError( id );
        }
    });

    return obj;
}

function successSortablePayload(payload)
{
    jQuery.nette.success(payload);
    if( $('#menu-designer-control').length > 0 )
        makeSortable();
}

function makeSortable()
{
	$('ol.branch').sortable({
        handle: '.header',
        placeholder: 'ui-highlight',
        connectWith: 'ol.branch' // This allows moving items between levels
    }).disableSelection();
}

/* ------------- Document Ready ------------- */

$().ready(function() {

    makeSortable();

    $(".edit.ajax").click(function(){
        location.href="#snippet--MenuitemFormSnippet";

        $this = $(this);
        $parent = $this.closest( ".header" ); // It isn't real parent, it's the closest sortable item
        $parent.css('opacity', 0.3);
        $parent.animate({
            opacity:1
        },{
            queue:false,
            duration:1500
        });

    });

    // Changes highlight height according to currently moved object
    $( "ol.branch" ).on( "sort", function(event, ui){
        $('.ui-highlight').css('margin-bottom', parseInt( $(".ui-sortable-helper").height() ) -30 + 'px');
    });

    // Submit of the designer
    $( "#frm-designerControlForm" ).submit(function(){
        var structure = fetchStructure($('ol#root'));
        $('#frm-designerControlForm input[name="structure"]').attr('value', JSON.stringify(structure));
    });

    // Changes of mode, module name and module view
    $('input[type="radio"], select', $("#frm-menuitemForm")).live('change', function(){
        $(this).parents('form:first').ajaxSubmit();
    });

    // Ajaxed forms
    $("form#menuEditForm").live("submit", function (el) {
        el.preventDefault();
        $(this).ajaxSubmit( successSortablePayload );
    });

});