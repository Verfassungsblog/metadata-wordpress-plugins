<?php

if (!class_exists('VB_CrossRef_DOI_Common')) {

    class VB_CrossRef_DOI_Common
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
                    "api_url_deposit" => "https://api.crossref.org/v2/deposits",
                    "api_url_submission" => "https://doi.crossref.org/servlet/submissionDownload",
                    "depositor_name" => "Wordpress Plugin " . $this->plugin_name,
                    "depositor_email" => "info@verfassungsblog.de",
                    "registrant" => $blog_title,
                    "doi_prefix" => "example-prefix",
                    "doi_suffix_length" => 16,
                    "issn" => "2366-7044",
                    "copyright_name_general" => "CC BY-SA 4.0",
                    "include_excerpt" => True,
                    // institution
                    "institution_name" => $blog_title,
                    "institution_wikidata_id" => "Q97588182",
                    // post selection
                    "submit_all_posts" => true,
                    "include_post_category" => "",
                    "exclude_post_category" => "No DOI",
                    // update
                    "auto_update" => false,
                    "interval" => 5,
                    "batch" => 1,
                    "requests_per_second" => 2.0,
                    "timeout_minutes" => 20,
                    "retry_minutes" => 60,
                    // post meta
                    "doi_meta_key" => "doi",
                    "copyright_name_meta_key" => "copyright",
                    "copyright_link_meta_key" => "copyright_link",
                    // user meta
                    "orcid_meta_key" => "orcid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "api_url_deposit" => "https://api.crossref.org/v2/deposits",
                    "api_url_submission" => "https://doi.crossref.org/servlet/submissionDownload",
                    "depositor_name" => "Wordpress Plugin " . $this->plugin_name,
                    "doi_suffix_length" => 16,
                    "include_excerpt" => True,
                    // institution
                    "institution_name" => $blog_title,
                    // post selection
                    "submit_all_posts" => false,
                    "include_post_category" => "DOI",
                    "exclude_post_category" => "No DOI",
                    // update
                    "auto_update" => false,
                    "interval" => 5,
                    "batch" => 1,
                    "requests_per_second" => 2.0,
                    "timeout_minutes" => 20,
                    "retry_minutes" => 60,
                    // post meta
                    "doi_meta_key" => "doi",
                    // user meta
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

        public function get_post_doi_meta_key() {
            return $this->get_settings_field_value("doi_meta_key");
        }

        public function get_post_submit_status_meta_key()
        {
            return $this->plugin_name . "_submit-status";
        }

        public function get_post_submit_timestamp_meta_key() {
            return $this->plugin_name . "_submit-timestamp";
        }

        public function get_post_submit_id_meta_key() {
            return $this->plugin_name . "_submit-id";
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

        public function subtract_timezone_offset_from_utc_iso8601($utc_iso) {
            $local = new Datetime("now", wp_timezone());
            $date = new Datetime("now", new DateTimeZone("UTC"));
            $date->setTimestamp($this->iso8601_to_date($utc_iso)->getTimestamp() - wp_timezone()->getOffset($local));
            return $this->date_to_iso8601($date);
        }

        public function format_xml($xml_str)
        {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            if ($dom->loadXML($xml_str)) {
                return $dom->saveXML();
            }
            return false;
        }

    }

}