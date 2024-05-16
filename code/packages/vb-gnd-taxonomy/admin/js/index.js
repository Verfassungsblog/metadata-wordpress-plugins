/**
 * Adds autocomplete functionality to GND taxonomy meta box and taxonomy edit page via jquery-ui and lobid.org.
 */

jQuery( document ).ready( function( $ ) {

    const suggestionCache = {};

    function split( val ) {
        return val.split(new RegExp(',\\s*' ));
    }

    function getLast( term ) {
        return split( term ).pop();
    }

    function encodeName(raw) {
        return raw.replaceAll("<", "[").replaceAll(">", "]").replaceAll(",", " ").replaceAll("  ", " ");
    };

    function codeFromURI(uri) {
        return uri.replace("https://d-nb.info/gnd/", "")
    }

    function buildSuggestQueryURL(term) {
        let url = vb_gnd_taxonomy_options.api_baseurl + "search?"
            + "format=json:" + encodeURIComponent(vb_gnd_taxonomy_options.label_format)
            + "&q=" + encodeURIComponent(term)
            + "&size=" + encodeURIComponent(vb_gnd_taxonomy_options.query_size);

        if (vb_gnd_taxonomy_options.query_filter) {
            url += "&filter=" + encodeURIComponent(vb_gnd_taxonomy_options.query_filter);
        }
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

	function parseDescriptionFromJson(callback) {
		return function (d) {
			if (d && d.definition && d.definition.length > 0) {
				callback(d.definition[0]);
			} else if (d && d.biographicalOrHistoricalInformation && d.biographicalOrHistoricalInformation.length > 0) {
				callback(d.biographicalOrHistoricalInformation[0]);
			} else if (d && d.usingInstructions && d.usingInstructions.length > 0) {
				callback(d.usingInstructions[0]);
			}
		}
	}

    function queryForDescription(gnd_id, callback) {
        $.ajax({
            url: vb_gnd_taxonomy_options.api_baseurl + gnd_id + ".json",
            dataType: "json",
            data : "",
            processData: false,
            success: parseDescriptionFromJson(callback),
        });
    }

	function renderAutoSuggestItem(ul, item) {
		return $("<li class=\"ui-menu-item\" />").append(
			"<div class=\"ui-menu-item-wrapper vb-gnd-taxonomy-autosuggest-item\" tabindex=\"-1\">"
			+ "<div class=\"vb-gnd-taxonomy-autosuggest-label\">"
			+ "<span class=\"vb-gnd-taxonomy-autosuggest-label-text\">" + item.label + "</span>"
			+ "<span class=\"vb-gnd-taxonomy-autosuggest-label-gndid\">[gnd: " + item.gnd_id + "]</span>"
			+ "</div><div class=\"vb-gnd-taxonomy-autosuggest-category\">"
			+ item.category
			+ "</div></div>"
		).appendTo(ul);
	}

	function autocompleteSource(autocomplete_request, autocomplete_response) {
		queryForSuggestions(getLast(autocomplete_request.term), function(data) {
			autocomplete_response(data.map(function(result, idx) {
				return {
					id: idx,
					label: result.label,
					category: encodeName(result.category),
					gnd_id: codeFromURI(result.id),
					name: encodeName(result.label) + " [gnd:" + codeFromURI(result.id) + "]",
				};
			}));
		});
	}

    // overwrite autocomplete in post edit gnd taxonomy meta box
    if ($("input#new-tag-gnd").length) {
        setTimeout(function() {
            $("input#new-tag-gnd").removeAttr("autocomplete").off('click').off("change").off("keyup");
            $("input#new-tag-gnd").autocomplete({
				position: { my: "left top", at: "left bottom", collision: "flipfit" },
                source: autocompleteSource,
            }).autocomplete("instance")._renderItem = renderAutoSuggestItem;
        }, 500);
    }

    // add autocomplete to new GND entity form
    if ($("form#addtag input[name=\"taxonomy\"][value=\"gnd\"]").length) {
		setTimeout(function() {
			$("input#tag-name").autocomplete({
				position: { my: "left top", at: "left bottom", collision: "flipfit" },
				source: autocompleteSource,
				focus: function(event, ui) {
					// do not write entity name when selecting with keyboard
					let child = $(event.currentTarget).children("li").eq(ui.item.id).first();
					child.siblings().removeClass("ui-state-focus").removeAttr("aria-selected");
					child.addClass("ui-state-focus").attr('aria-selected', true);
					return false;
				},
				select: function(event, ui) {
					// write entity name as tag name
					$(this).val(ui.item.label);
					// write gnd id as slug
					$("input#tag-slug").val(ui.item.gnd_id);
					// load description and write as term description
					queryForDescription(ui.item.gnd_id, function(description) {
						$("textarea#tag-description").val(description);
					});
					return false;
				}
			}).autocomplete("instance")._renderItem = renderAutoSuggestItem;
			$("input#tag-name").attr("role", "combobox").attr("aria-autocomplete", "list");
		}, 500);
    }
});