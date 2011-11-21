$(document).ready(function(){           

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
        }
    });     
});        