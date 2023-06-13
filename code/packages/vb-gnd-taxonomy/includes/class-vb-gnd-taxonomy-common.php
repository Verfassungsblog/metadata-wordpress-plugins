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
                    "query_filter" => "",
                    "query_size" => 10,
                    "verify_gnd_id" => true,
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "suggest_enabled" => true,
                    "api_baseurl" => "https://lobid.org/gnd/",
                    "label_format" => "suggest",
                    "merge_with_tags" => false,
                    "query_filter" => "",
                    "query_size" => 10,
                    "verify_gnd_id" => true,
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

        public function set_last_error($error) {
            return update_option($this->plugin_name . "_status_last_error", $error);
        }

        public function get_last_error() {
            return get_option($this->plugin_name . "_status_last_error", false);
        }

        public function clear_last_error() {
            delete_option($this->plugin_name . "_status_last_error");
        }

        public function extract_gnd_id_from_term($term) {
            preg_match('/\[gnd:([^\]]+)\]/', $term, $matches);
            if ($matches) {
                return $matches[1];
            }
            return null;
        }

    }

}