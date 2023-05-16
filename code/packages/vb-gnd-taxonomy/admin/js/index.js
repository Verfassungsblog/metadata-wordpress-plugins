
jQuery( document ).ready( function( $ ) {

    const suggestionCache = {};

    function split( val ) {
        return val.split(new RegExp(',\\s*' ));
    }

    function getLast( term ) {
        return split( term ).pop();
    }

    function encodeName(raw) {
        return raw.replaceAll("<", "[").replaceAll(">", "]").replaceAll(",", " | ").replaceAll("  ", " ");
    };

    function codeFromURI(uri) {
        return uri.replace("https://d-nb.info/gnd/", "")
    }

    function buildSuggestQueryURL(term) {
        let url = vb_gnd_taxonomy_options.api_baseurl + "search?format=json:suggest"
            + "&q=" + encodeURIComponent(term)
            + "&size=" + encodeURIComponent(vb_gnd_taxonomy_options.query_size);

        if (vb_gnd_taxonomy_options.query_filter) {
            url += "&filter=" + encodeURIComponent(vb_gnd_taxonomy_options.query_filter);
        }

        console.log("query url " + url);

        return url;
    }

    function queryForSuggestions(term, callback) {
        const url = buildSuggestQueryURL(term);
        if (url in suggestionCache) {
            callback(suggestionCache[url]);
        } else {
            $.ajax({
                url: url,
                dataType: "json",
                data : "",
                processData: false,
                success: function(data) {
                    suggestionCache[url] = data;
                    callback(data);
                },
            });
        }
    }

    function queryForDescription(gnd_id, callback) {
        $.ajax({
            url: vb_gnd_taxonomy_options.api_baseurl + gnd_id + ".json",
            dataType: "json",
            data : "",
            processData: false,
            success: function(data) {
                if (data && data.definition && data.definition.length > 0) {
                    callback(data.definition[0]);
                }
            },
        });
    }

    // overwrite autocomplete in post edit gnd taxonomy meta box
    if ($("input#new-tag-gnd").length) {
        setTimeout(function() {
            $("input#new-tag-gnd").removeAttr("autocomplete").off('click').off("change").off("keyup");
            $("input#new-tag-gnd").autocomplete({
                source: function (autocomplete_request, autocomplete_response) {
                    queryForSuggestions(getLast(autocomplete_request.term), function(data) {
                        autocomplete_response(data.map(function(result, idx) {
                            return {id: idx, name: encodeName(result.label) + " [gnd:" + codeFromURI(result.id) + "]"}
                        }));
                    });
                },
            });
        }, 500);
    }

    // add autocomplete to new GND entity form
    if ($("form#addtag input[name=\"taxonomy\"][value=\"gnd\"]").length) {
        $("input#tag-name").autocomplete({
            source: function (autocomplete_request, autocomplete_response) {
                queryForSuggestions(autocomplete_request.term, function(data) {
                    autocomplete_response(data.map(function(result) {
                        return {
                            label: encodeName(result.label) + " [gnd:" + codeFromURI(result.id) + "]",
                            value: {
                                name: encodeName(result.label),
                                gnd_id: codeFromURI(result.id),
                            }
                        };
                    }));
                });
            },
            select: function(event, ui) {
                // write entity name as tag name
                $(this).val(ui.item.value.name);
                // write gnd id as slug
                $("input#tag-slug").val(ui.item.value.gnd_id);
                // load description and write as term description
                queryForDescription(ui.item.value.gnd_id, function(description) {
                    $("textarea#tag-description").val(description);
                });
                return false;
            }
        });
    }
});