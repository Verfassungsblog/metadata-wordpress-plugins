<?php

if (!class_exists('VB_DOAJ_Submit_Common')) {

    class VB_DOAJ_Submit_Common
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
                    "auto_update" => true,
                    "interval" => 1,
                    "issn" => "2366-7044",
                    "issue" => "2366-7044",
                    "require_doi" => true,
                    "include_excerpt" => true,
                    "include_tags" => true,
                    "doi_acf" => "doi",
                    "subheadline_acf" => "subheadline",
                    "orcid_acf" => "orcid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "interval" => 1,
                    "require_doi" => true,
                );
            }
        }

        public function get_settings_field_value($field_name)
        {
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

    }

}