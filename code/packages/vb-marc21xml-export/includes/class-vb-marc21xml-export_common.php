<?php

if (!class_exists('VB_Marc21Xml_Export_Common')) {

    class VB_Marc21Xml_Export_Common
    {
        public $plugin_name;

        public $settings_page_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
            $this->settings_page_name = $plugin_name . "_settings";
        }

        public function get_settings_fields() {
            return array(
                array(
                    "name" => "leader",
                    "section" => "general",
                    "label" => __("Marc21 Leader", "vb-marc21xml-export"),
                    "placeholder" => __("marc21 leader attribute", "vb-marc21xml-export"),
                    "description" => __("The Marc21", "vb-marc21xml-export") . " <a href=\"https://www.loc.gov/marc/bibliographic/bdleader.html\"
                        target=\"_blank\">" . __("leader attribute", "vb-marc21xml-export") . "</a>" . ", " . __("for example:", "vb-marc21xml-export") . "
                         <pre><code>     nam  22     uu 4500</code></pre>",
                    "default" => "     nam  22     uu 4500",
                ),
                array(
                    "name" => "773a",
                    "section" => "general",
                    "label" => __("Blog Owner", "vb-marc21xml-export") . "<br>(Marc21 773a)",
                    "placeholder" => __("blog owner", "vb-marc21xml-export"),
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">main entry heading</a> of the host item entry, for example the blog owner.",
                    "default" => null,
                ),
                array(
                    "name" => "773t",
                    "section" => "general",
                    "label" => __("Blog Title", "vb-marc21xml-export") . "<br>(Marc21 773t)",
                    "placeholder" => __("blog title", "vb-marc21xml-export"),
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">title</a> of the host item entry, for example the blog title.",
                    "default" => get_bloginfo("name"),
                ),
                array(
                    "name" => "773x",
                    "section" => "general",
                    "label" => "ISSN<br>(Marc21 773x)",
                    "placeholder" => "ISSN",
                    "description" => "The <a href=\"https://www.loc.gov/marc/bibliographic/bd773.html\"
                    target=\"_blank\">International Standard Serial Number</a> (ISSN) of the host item entry, for
                    example the ISSN of this blog.",
                    "default" => null,
                ),
                array(
                    "name" => "doi_acf_key",
                    "section" => "post",
                    "label" => "DOI Key<br>(Marc21 024a)",
                    "placeholder" => "ACF field key for DOI",
                    "description" => "The key of the Advanced Custom Fields (ACF) field that contains the DOI.",
                    "default" => "doi",
                )
            );
        }

        public function get_value_field_id($field_name) {
            return $this->settings_page_name . '_field_' . $field_name . '_value';
        }

        public function get_settings_field_info($field_name) {
            foreach ($this->get_settings_fields() as $field) {
                if ($field["name"] == $field_name) {
                    return $field;
                }
            }
            return null;
        }

    }

}