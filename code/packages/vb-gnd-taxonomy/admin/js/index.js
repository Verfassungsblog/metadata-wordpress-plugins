
jQuery( document ).ready( function( $ ) {
    // do stuff
    $("#vb-gnd-taxonomy_input").autocomplete({
        source: function (autocomplete_request, autocomplete_response) {
            // do ajax request
            $.ajax({
                url: "https://lobid.org/gnd/search",
                dataType: "jsonp",
                data : {
                    q : autocomplete_request.term,
                    filter: "type:SubjectHeading",
                    format : "json:suggest"
                },
                success: function(data) {
                    // const suggestions = data.result.map(function(v) { return v.name });
                    autocomplete_response(data);
                },
            });
        },
    });
});