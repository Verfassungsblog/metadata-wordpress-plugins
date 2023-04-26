<?php

if (!class_exists('VB_Metadata_Export_Common')) {

    class VB_Metadata_Export_Common
    {
        public $plugin_name;

        protected $setting_field_defaults;

        protected $settings_fields_by_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;

            $blog_title = get_bloginfo("name");

            if ($blog_title == "Verfassungsblog") {
                $this->setting_field_defaults = array(
                    "marc21xml_enabled" => true,
                    "mods_enabled" => true,
                    "oai-pmh_enabled" => true,
                    "dc_enabled" => true,
                    "marc21_leader" => "_____nam__22_____uu_4500",
                    "marc21_doi_as_control_number" => true,
                    "marc21_control_number_identifier" => "DE-Verfassungsblog",
                    "marc21_physical_description" => "cr|||||",
                    "blog_owner" => "Max Steinbeis Verfassungsblog gGmbH",
                    "blog_title" => get_bloginfo("name"),
                    "issn" => "2366-7044",
                    "publisher" => get_bloginfo("name"),
                    "require_doi" => true,
                    "language" => "ger",
                    "language_alternate" => "eng",
                    "language_alternate_category" => "English Articles",
                    "ddc_general" => "342",
                    "copyright_general" => "CC BY-SA 4.0",
                    "funding_general" => "funded by the government",
                    "doi_acf" => "doi",
                    "subheadline_acf" => "subheadline",
                    "orcid_acf" => "orcid",
                    "gndid_acf" => "gndid",
                    "ddc_acf" => "ddc",
                    "copyright_acf" => "copyright",
                    "funding_acf" => "funding",
                );
            } else {
                $this->setting_field_defaults = array(
                    "marc21xml_enabled" => true,
                    "marc21_leader" => "_____nam__22_____uu_4500",
                    "marc21_doi_as_control_number" => false,
                    "marc21_physical_description" => "cr|||||",
                    "mods_enabled" => true,
                    "oai-pmh_enabled" => true,
                    "dc_enabled" => true,
                    "require_doi" => false,
                    "blog_title" => get_bloginfo("name"),
                    "language" => "eng",
                );
            }
        }

        protected function is_format_requiring_doi($format) {
            if($format == "marc21xml") {
                return $this->get_settings_field_value("require_doi") || $this->get_settings_field_value("marc21_doi_as_control_number");
            }
            return $this->get_settings_field_value("require_doi");
        }

        public function get_available_formats() {
            return array_keys($this->get_format_labels());
        }

        public function get_format_labels() {
            return array(
                "marc21xml" => "Marc21 XML",
                "mods" => "MODS",
                "dc" => "Dublin Core",
                "oai-pmh" => "OAI PMH 2.0",
            );
        }

        public function is_valid_format($format) {
            return in_array($format, $this->get_available_formats());
        }

        public function is_format_enabled($format) {
            if (in_array($format, $this->get_available_formats())) {
                return $this->get_settings_field_value($format . "_enabled");
            }
            return false;
        }

        public function is_format_available($format, $post) {
            if (!$this->is_format_enabled($format)) {
                return false;
            }
            if ($this->is_format_requiring_doi($format)) {

                $doi = $this->get_acf_settings_post_field_value("doi_acf", $post);
                if (empty($doi)) {
                    return false;
                }
            }
            return true;
        }

        public function get_settings_field_value($field_name) {
            $default = $this->get_settings_field_default_value($field_name);
            return get_option($this->get_settings_field_id($field_name), $default);
        }

        public function get_acf_settings_post_field_value($field_name, $post)
        {
            if (!function_exists("get_field")) {
                return;
            }
            $acf_key = $this->get_settings_field_value($field_name);
            return get_field($acf_key, $post->ID);
        }

        public function get_acf_settings_user_field_value($field_name, $user_id)
        {
            if (!function_exists("get_field")) {
                return;
            }
            $acf_key = $this->get_settings_field_value($field_name);
            return get_field($acf_key, 'user_' . $user_id);
        }

        public function get_settings_field_id($field_name)
        {
            return $this->plugin_name . '_field_' . $field_name . '_value';
        }

        public function get_settings_field_default_value($field_name)
        {
            if (array_key_exists($field_name, $this->setting_field_defaults)) {
                return $this->setting_field_defaults[$field_name];
            }
            return false;
        }

        public function get_the_permalink($format, $post) {
            if (!$this->is_format_available($format, $post)) {
                // format must be valid and enabled
                return;
            }
            $permalink = get_the_permalink($post) ?? get_post_permalink($post);
            if (empty($permalink)) {
                return;
            }
            if (str_contains($permalink, "?")) {
                return  $permalink . "&" . $this->plugin_name . "={$format}";
            }
            return  $permalink . "?" . $this->plugin_name . "={$format}";
        }

        public function formatXml($xml_str)
        {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml_str);
            return $dom->saveXML();
        }

    }

}