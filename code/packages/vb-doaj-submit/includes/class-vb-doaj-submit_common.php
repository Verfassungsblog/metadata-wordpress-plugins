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
                    "eissn" => "2366-7044",
                    "issue" => "2366-7044",
                    "require_doi" => true,
                    "include_excerpt" => true,
                    "include_tags" => true,
                    "include_subheadline" => false,
                    "identify_by_permalink" => true,
                    "test_without_apikey" => false,
                    // automatic update
                    "auto_update" => false,
                    "interval" => 10,
                    "requests_per_second" => 2.0,
                    "batch" => 1,
                    "retry_minutes" => 60,
                    // custom fields
                    "doaj_article_id_meta_key" => "doaj_article_id",
                    "doi_meta_key" => "doi",
                    "subheadline_meta_key" => "subheadline",
                    "orcid_meta_key" => "orcid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "api_baseurl" => "https://doaj.org/api/",
                    "require_doi" => true,
                    "include_excerpt" => true,
                    "include_tags" => true,
                    "identify_by_permalink" => true,
                    "test_without_apikey" => false,
                    // automatic updates
                    "auto_update" => false,
                    "interval" => 10,
                    "requests_per_second" => 2.0,
                    "batch" => 1,
                    "retry_minutes" => 60,
                    // custom fields
                    "doaj_article_id_meta_key" => "doaj_article_id",
                    "doi_meta_key" => "doi",
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

        public function get_doaj_article_id_meta_key() {
            return $this->get_settings_field_value("doaj_article_id_meta_key");
        }

        public function get_identify_timestamp_meta_key() {
            return $this->plugin_name . "_identify-timestamp";
        }

        public function get_post_submit_status_meta_key()
        {
            return $this->plugin_name . "_submit-status";
        }

        public function get_submit_timestamp_meta_key() {
            return $this->plugin_name . "_submit-timestamp";
        }

        public function get_post_submit_error_meta_key() {
            return $this->plugin_name . "_submit-error";
        }

        public function get_current_utc_timestamp() {
            return (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp();
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