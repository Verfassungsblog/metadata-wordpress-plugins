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
                    "api_baseurl" => "https://doaj.org/api/",
                    "auto_update" => true,
                    "interval" => 1,
                    "batch" => 1,
                    "eissn" => "2366-7044",
                    "issue" => "2366-7044",
                    "require_doi" => true,
                    "include_excerpt" => true,
                    "include_tags" => true,
                    "article_id_acf" => "doaj_article_id",
                    "doi_acf" => "doi",
                    "subheadline_acf" => "subheadline",
                    "orcid_acf" => "orcid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "api_baseurl" => "https://doaj.org/api/",
                    "interval" => 1,
                    "batch" => 1,
                    "require_doi" => true,
                    "article_id_acf" => "doaj_article_id",
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

        public function get_doaj_article_id_key() {
            $acf_key = $this->get_settings_field_value("article_id_acf");
            if (empty($acf_key)) {
                return $this->plugin_name . "_doaj_article_id";
            }
            return $acf_key;
        }

        public function date_to_iso8601($date) {
            return $date->format("Y-m-d\TH:i:s\Z");
        }

        public function iso8601_to_date($iso) {
            $date = date_create_immutable_from_format('Y-m-d\\TH:i:s\\Z', $iso, new DateTimeZone('UTC'));
            if (!$date) {
                $date = date_create_immutable_from_format('Y-m-d', $iso, new DateTimeZone('UTC'));
            }
            return $date;
        }

        public function local_to_utc_iso8601($utc_iso) {
            $local = new Datetime("now", wp_timezone());
            $date = new Datetime("now", new DateTimeZone("UTC"));
            $date->setTimestamp($this->iso8601_to_date($utc_iso)->getTimestamp() - wp_timezone()->getOffset($local));
            return $this->date_to_iso8601($date);
        }

    }

}