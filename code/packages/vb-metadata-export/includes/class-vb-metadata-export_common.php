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
                    "marc21_enabled" => true,
                    "mods_enabled" => true,
                    "oai_pmh_enabled" => true,
                    "dc_enabled" => true,
                    "marc21_leader" => "_____nam__22_____uu_4500",
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
                    "marc21_enabled" => true,
                    "mods_enabled" => true,
                    "oai_pmh_enabled" => true,
                    "dc_enabled" => true,
                    "require_doi" => false,
                    "blog_title" => get_bloginfo("name"),
                    "language" => "eng",
                    "marc21_leader" => "_____nam__22_____uu_4500",
                );
            }
        }

        public function get_setting_field_id($field_name)
        {
            return $this->plugin_name . '_field_' . $field_name . '_value';
        }

        public function get_setting_field_default_value($field_name)
        {
            if (array_key_exists($field_name, $this->setting_field_defaults)) {
                return $this->setting_field_defaults[$field_name];
            }
            return;
        }

    }

}