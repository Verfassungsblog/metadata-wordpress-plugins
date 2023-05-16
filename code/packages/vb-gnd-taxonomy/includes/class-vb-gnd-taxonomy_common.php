<?php

if (!class_exists('VB_GND_Taxonomy_Common')) {

    class VB_GND_Taxonomy_Common
    {
        public $plugin_name;

        protected $setting_field_defaults;

        protected $settings_fields_by_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;

            $blog_title = get_bloginfo("name");

            if ($blog_title == "Verfassungsblog") {
                // default settings for Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "suggest_enabled" => true,
                    "api_baseurl" => "https://lobid.org/gnd/",
                    "label_format" => "suggest",
                    "merge_with_tags" => true,
                    "query_filter" => "type:SubjectHeading",
                    "query_size" => 10,

                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "suggest_enabled" => true,
                    "api_baseurl" => "https://lobid.org/gnd/",
                    "label_format" => "suggest",
                    "merge_with_tags" => false,
                    "query_filter" => "type:SubjectHeading",
                    "query_size" => 10,
                );
            }
        }

        public function get_settings_field_value($field_name)
        {
            $default = $this->get_settings_field_default_value($field_name);
            return get_option($this->get_settings_field_id($field_name), $default);
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

    }

}