<?php

if (!class_exists('VB_Author_Affiliations_Common')) {

    class VB_Author_Affiliations_Common
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
                    "autofill" => true,
                    "extract_from_orcid" => true,
                    "extract_from_rorid" => true,
                    "timeout" => 30,
                    "author_affiliations_meta_key" => "author_affiliations",
                    "affiliation_name_meta_key" => "affiliation",
                    "rorid_meta_key" => "rorid",
                    "orcid_meta_key" => "orcid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "autofill" => true,
                    "extract_from_orcid" => true,
                    "extract_from_rorid" => true,
                    "timeout" => 30,
                    "author_affiliations_meta_key" => "author_affiliations",
                    "affiliation_name_meta_key" => "affiliation",
                    "rorid_meta_key" => "rorid",
                    "orcid_meta_key" => "orcid",
                );
            }
        }

        public function get_settings_field_value($field_name)
        {
            $default = $this->get_settings_field_default_value($field_name);
            return get_option($this->get_settings_field_id($field_name), $default);
        }

        public function get_post_meta_field_value($field_name, $post)
        {
            $meta_key = $this->get_settings_field_value($field_name);
            if (empty($meta_key)) {
                return false;
            }
            return get_post_meta($post->ID, $meta_key, true);
        }

        public function get_user_meta_field_value($field_name, $user_id)
        {
            $meta_key = $this->get_settings_field_value($field_name);
            if (empty($meta_key)) {
                return false;
            }
            return get_user_meta($user_id, $meta_key, true);
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

        public function get_post_author_names($post)
        {
            $post_author_login = get_the_author_meta("user_login", $post->post_author);
        }

    }

}