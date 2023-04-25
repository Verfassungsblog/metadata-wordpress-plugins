<?php

if (!class_exists('VB_Metadata_Export_Setting_Fields')) {

    class VB_Metadata_Export_Setting_Fields
    {

        protected $settings_fields;

        protected $settings_fields_by_name;

        public function __construct()
        {

        }

        protected function load_settings_fields()
        {
            if ($this->settings_fields) {
                return;
            }
            $this->settings_fields = array(
                array(
                    "name" => "marc21xml_enabled",
                    "type" => "boolean",
                    "section" => "marc21",
                    "label" => __("Marc21 Export Enabled", "vb-metadata-export"),
                    "description" => "Whether the Marc21 XML export is active or not.",
                ),
                array(
                    "name" => "mods_enabled",
                    "type" => "boolean",
                    "section" => "mods",
                    "label" => __("MODS Export Enabled", "vb-metadata-export"),
                    "description" => "Whether the MODS export is active or not.",
                ),
                array(
                    "name" => "oai-pmh_enabled",
                    "type" => "boolean",
                    "section" => "oai_pmh",
                    "label" => __("OAI-PMH 2.0 Enabled", "vb-metadata-export"),
                    "description" => "Whether the OAI-PMH 2.0 interface is active or not.",
                ),
                array(
                    "name" => "dc_enabled",
                    "type" => "boolean",
                    "section" => "dc",
                    "label" => __("Dublin Core Export Enabled", "vb-metadata-export"),
                    "description" => "Whether the Dublin Core export is active or not.",
                ),
                array(
                    "name" => "marc21_leader",
                    "type" => "string",
                    "section" => "marc21",
                    "label" => __("Marc21 Leader", "vb-metadata-export"),
                    "placeholder" => __("marc21 leader attribute", "vb-metadata-export"),
                    "description" => implode("", array(
                        __("The", "vb-metadata-export"),
                        " <a href=\"https://www.loc.gov/marc/bibliographic/bdleader.html\" target=\"_blank\">",
                        __("Marc21 leader attribute", "vb-metadata-export"),
                        "</a>. ",
                        __("Use the underscore (_) instead of a space ( ).", "vb-metadata-export"),
                        "<br>",
                        __("For example:", "vb-metadata-export"),
                        "<code>_____nam__22_____uu_4500</code>",
                    )),
                ),
                array(
                    "name" => "marc21_doi_as_control_number",
                    "type" => "boolean",
                    "section" => "marc21",
                    "label" => __("Use DOI as Control Number", "vb-metadata-export"),
                    "description" => "Whether to use the DOI (see ACF tab) for the Marc21 Control Number Field, or otherwise the sequential post number (post id). If enabled, posts without a DOI will not output any metadata.",
                ),
                array(
                    "name" => "blog_owner",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Blog Owner", "vb-metadata-export"),
                    "placeholder" => __("blog owner", "vb-metadata-export"),
                    "description" => "The main entry heading (see <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">Marc21 773a</a>) of the host item entry, for example the
                        blog owner.",
                ),
                array(
                    "name" => "blog_title",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Blog Title", "vb-metadata-export"),
                    "placeholder" => __("blog title", "vb-metadata-export"),
                    "description" => "The title (see <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">Marc21 773t</a>) of the host item entry, for example the blog title.",
                ),
                array(
                    "name" => "issn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "ISSN",
                    "placeholder" => "ISSN",
                    "description" => "The <a href=\"https://en.wikipedia.org/wiki/International_Standard_Serial_Number\" target=\"_blank\">
                            International Standard Serial Number</a> (ISSN) of the host item entry (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\" target=\"_blank\">Marc21 773x</a>).
                        <br>For example: <code>2366-7044</code> = ISSN of the Verfassungsblog",
                ),
                array(
                    "name" => "publisher",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Publisher", "vb-metadata-export"),
                    "placeholder" => __("name of publisher", "vb-metadata-export"),
                    "description" => "The publisher (see <a href=\"https://www.loc.gov/marc/bibliographic/bd264.html\"
                    target=\"_blank\">Marc21 264b</a>), for example the blog title.",
                ),
                array(
                    "name" => "require_doi",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Require DOI", "vb-metadata-export"),
                    "description" => "Whether to require the DOI to be available for a post in order to export metadata
                        (see ACF tab). If this option is set, a post that has no DOI available will not generate any
                        metadata output.",
                ),
                array(
                    "name" => "language",
                    "type" => "string",
                    "section" => "language",
                    "label" => "Language<br>Code",
                    "placeholder" => "language code",
                    "description" => "The default <a href=\"https://www.loc.gov/marc/languages/language_code.html\"
                        target=\"_blank\">Marc21 Language Code</a> (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd041.html\" target=\"_blank\">Marc21 041a</a>).
                        The language can be overwritten by assigning posts to a category, see below.
                        <br>For Example: <code>ger</code> = \"German\"",
                ),
                array(
                    "name" => "language_alternate",
                    "type" => "string",
                    "section" => "language",
                    "label" => "Alternate Language<br>Code",
                    "placeholder" => "language code",
                    "description" => "The alternate <a href=\"https://www.loc.gov/marc/languages/language_code.html\"
                        target=\"_blank\">Marc21 Language Code</a> (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd041.html\" target=\"_blank\">Marc21 041a</a>).
                        This language code is used in case a post is assigned to a specific category, see below.
                        <br>For Example: <code>eng</code> = \"English\"",
                ),
                array(
                    "name" => "language_alternate_category",
                    "type" => "string",
                    "section" => "language",
                    "label" => "Alternate Language<br>Category",
                    "placeholder" => "category name",
                    "description" => "The name of the category, which posts are assigned to, in case they are written
                        in the alternate language.
                        <br>For Example: <code>English Articles</code>",
                ),
                array(
                    "name" => "ddc_general",
                    "type" => "string",
                    "section" => "general",
                    "label" => "DDC<br>for all posts",
                    "placeholder" => "DDC as comma seperated codes",
                    "description" => "The comma-separated list of
                        <a href=\"https://deweysearchde.pansoft.de/webdeweysearch/mainClasses.html\"
                        target=\"_blank\">Dewey Decimal Classification</a> codes that are applicable to every post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd084.html\" target=\"_blank\">Marc21 084a</a>).
                        Additional codes can be provided via ACF, see below.
                        <br>For Example: <code>342</code> = \"Verfassungs- und Verwaltungsrecht\"",
                ),
                array(
                    "name" => "copyright_general",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Copyright / Licence <br>for all posts",
                    "placeholder" => "a copyright / license note",
                    "description" => "The default copyright or licence note for all posts (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 540a</a>).
                        This note can be overwritten with a post-specific copyright note if it is provided via an ACF
                        field. <br>For example: <code>CC BY-SA 4.0</code>
                         = Creative Commons Attribution-ShareAlike 4.0 International",
                ),
                array(
                    "name" => "funding_general",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Funding<br/>for all posts",
                    "placeholder" => "a funding note",
                    "description" => "The default funding note for all posts (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 536a</a>).
                        This note can be overwritten with a post-specific funding note if it is provided via an ACF field.",
                ),
                array(
                    "name" => "doi_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "DOI<br>(ACF key)",
                    "placeholder" => "ACF field key for DOI",
                    "description" => "The ACF field key that contains the DOI for a specific post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd024.html\" target=\"_blank\">Marc21 024a</a>).",
                ),
                array(
                    "name" => "subheadline_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Sub-Headline<br>(ACF key)",
                    "placeholder" => "ACF field key for a sub-headline",
                    "description" => "The ACF field key that contains the sub-headline for a particular post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd245.html\" target=\"_blank\">Marc21 245b</a>).",
                ),
                array(
                    "name" => "orcid_acf",
                    "type" => "string",
                    "section" => "user_acf",
                    "label" => "ORCID<br>(ACF key)",
                    "placeholder" => "ACF field key for the ORCID of the author",
                    "description" => "The ACF field key that contains the ORCID of the post author (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd100.html\" target=\"_blank\">Marc21 100 0</a>).",
                ),
                array(
                    "name" => "gndid_acf",
                    "type" => "string",
                    "section" => "user_acf",
                    "label" => "GND-ID<br>(ACF key)",
                    "placeholder" => "ACF field key for the GND-ID of the author",
                    "description" => "The ACF field key that contains the GND-ID of the post author (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd100.html\" target=\"_blank\">Marc21 100 0</a>).",
                ),
                array(
                    "name" => "ddc_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "DDC<br>(ACF key)",
                    "placeholder" => "ACF field key for comma separated DDC codes",
                    "description" => "The ACF field key that contains the list of
                        <a href=\"https://deweysearchde.pansoft.de/webdeweysearch/mainClasses.html\"
                        target=\"_blank\">Dewey Decimal Classification</a> codes that is applicable for a particular
                        post (see <a href=\"https://www.loc.gov/marc/bibliographic/bd084.html\" target=\"_blank\">Marc21 084a</a>).",
                ),
                array(
                    "name" => "copyright_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Copyright / Licence<br>(ACF key)",
                    "placeholder" => "ACF field key for a copyright / licence note",
                    "description" => "The ACF field key that contains the copyright or licence note for a specific post
                        (see <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 540a</a>).
                        If a post-specific copyright note is provided, the default copyright note is overwritten.",
                ),
                array(
                    "name" => "funding_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Funding<br>(ACF key)",
                    "placeholder" => "ACF field key for a funding note",
                    "description" => "The ACF field key that contains a funding note for a particular post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 536a</a>).
                        If a post-specific funding note is provided, the default funding note is overwritten.",
                ),
            );
            // index settings field by their name
            $this->settings_fields_by_name = array();
            foreach($this->settings_fields as $field) {
                $this->settings_fields_by_name[$field["name"]] = $field;
            }
        }

        public function get_list()
        {
            $this->load_settings_fields();
            return $this->settings_fields;
        }

        public function get_field($field_name)
        {
            $this->load_settings_fields();
            return $this->settings_fields_by_name[$field_name];
        }

    }

}