<?php

if (!class_exists('VB_Marc21Xml_Export_Common')) {

    class VB_Marc21Xml_Export_Common
    {
        public $plugin_name;

        public $settings_page_name;

        protected $settings_fields;

        protected $settings_fields_by_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
            $this->settings_page_name = $plugin_name . "_settings";
        }

        protected function load_settings_fields()
        {
            if ($this->settings_fields) {
                return;
            }
            $this->settings_fields = array(
                array(
                    "name" => "leader",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Marc21 Leader", "vb-marc21xml-export"),
                    "placeholder" => __("marc21 leader attribute", "vb-marc21xml-export"),
                    "description" => implode("", array(
                        __("The", "vb-marc21xml-export"),
                        " <a href=\"https://www.loc.gov/marc/bibliographic/bdleader.html\" target=\"_blank\">",
                        __("Marc21 leader attribute", "vb-marc21xml-export"),
                        "</a>. ",
                        __("Use the underscore (_) instead of a space ( ).", "vb-marc21xml-export"),
                        "<br>",
                        __("For example:", "vb-marc21xml-export"),
                        "<code>_____nam__22_____uu_4500</code>",
                    )),
                    "default" => "_____nam__22_____uu_4500",
                ),
                array(
                    "name" => "blog_owner",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Blog Owner", "vb-marc21xml-export"),
                    "placeholder" => __("blog owner", "vb-marc21xml-export"),
                    "description" => "The main entry heading (see <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">Marc21 773a</a>) of the host item entry, for example the
                        blog owner.",
                    "default" => "Max Steinbeis Verfassungsblog gGmbH",
                ),
                array(
                    "name" => "blog_title",
                    "type" => "string",
                    "section" => "general",
                    "label" => __("Blog Title", "vb-marc21xml-export"),
                    "placeholder" => __("blog title", "vb-marc21xml-export"),
                    "description" => "The title (see <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">Marc21 773t</a>) of the host item entry, for example the blog title.",
                    "default" => get_bloginfo("name"),
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
                    "default" => "2366-7044",
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
                    "default" => "342",
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
                    "default" => "CC BY-SA 4.0",
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
                    "default" => "funded by the government",
                ),
                array(
                    "name" => "doi_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "DOI<br>(ACF key)",
                    "placeholder" => "ACF field key for DOI",
                    "description" => "The ACF field key that contains the DOI for a specific post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd024.html\" target=\"_blank\">Marc21 024a</a>).",
                    "default" => "doi",
                ),
                array(
                    "name" => "subheadline_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "Sub-Headline<br>(TODO)",
                    "placeholder" => "ACF field key for a sub-headline",
                    "description" => "The ACF field key that contains the sub-headline
                    for a particular post.",
                    "default" => "subheadline",
                ),
                array(
                    "name" => "orcid_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "ORCID<br>(TODO)",
                    "placeholder" => "ACF field key for a ORCID",
                    "description" => "The ACF field key that contains the ORCID of the post author.",
                    "default" => "orcid",
                ),
                array(
                    "name" => "ddc_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "DDC<br>(ACF key)",
                    "placeholder" => "ACF field key for comma separated DDC codes",
                    "description" => "The ACF field key that contains the list of
                        <a href=\"https://deweysearchde.pansoft.de/webdeweysearch/mainClasses.html\"
                        target=\"_blank\">Dewey Decimal Classification</a> codes that is applicable for a particular
                        post (see <a href=\"https://www.loc.gov/marc/bibliographic/bd084.html\" target=\"_blank\">Marc21 084a</a>).",
                    "default" => "ddc",
                ),
                array(
                    "name" => "copyright_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "Copyright / Licence<br>(ACF key)",
                    "placeholder" => "ACF field key for a copyright / licence note",
                    "description" => "The ACF field key that contains the copyright or licence note for a specific post
                        (see <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 540a</a>).
                        If a post-specific copyright note is provided, the default copyright note is overwritten.",
                    "default" => "copyright",
                ),
                array(
                    "name" => "funding_acf",
                    "type" => "string",
                    "section" => "post",
                    "label" => "Funding<br>(ACF key)",
                    "placeholder" => "ACF field key for a funding note",
                    "description" => "The ACF field key that contains a funding note for a particular post (see
                        <a href=\"https://www.loc.gov/marc/bibliographic/bd540.html\" target=\"_blank\">Marc21 536a</a>).
                        If a post-specific funding note is provided, the default funding note is overwritten.",
                    "default" => "funding",
                ),
            );
            // index settings field by their name
            $this->settings_fields_by_name = array();
            foreach($this->settings_fields as $field) {
                $this->settings_fields_by_name[$field["name"]] = $field;
            }
        }

        public function get_settings_fields()
        {
            $this->load_settings_fields();
            return $this->settings_fields;
        }

        public function get_value_field_id($field_name)
        {
            return $this->settings_page_name . '_field_' . $field_name . '_value';
        }

        public function get_settings_field_info($field_name)
        {
            $this->load_settings_fields();
            return $this->settings_fields_by_name[$field_name];
        }

    }

}