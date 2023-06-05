
jQuery( document ).ready( function( $ ) {

    function build_json() {
        let author_affiliations = {};
        $("table#vb-author-affiliations_table input").each(function() {
            let author_id = $(this).data("author-id");
            let field_type = $(this).data("affiliation-field");
            let value = $(this).val();

            if (!(author_id in author_affiliations)) {
                author_affiliations[author_id] = {}
            }
            author_affiliations[author_id][field_type] = value;
        });
        return JSON.stringify(author_affiliations);
    }

    $("table#vb-author-affiliations_table input").on("keyup", function (event) {
        $("textarea#vb-author-affiliations_textarea").val(build_json());
    });

});