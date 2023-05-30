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
                    "api_baseurl" => "https://api.crossref.org/v2/deposits",
                    "depositor_name" => "Wordpress Plugin " . $this->plugin_name,
                    "depositor_email" => "crossref@verfassungsblog.de",
                    "registrant" => $blog_title,
                    "doi_prefix" => "example-prefix",
                    "doi_suffix_length" => 16,
                    "issn" => "2366-7044",
                    "copyright_general" => "CC BY-SA 4.0",
                    "include_excerpt" => True,
                    // institution
                    "institution_name" => $blog_title,
                    "institution_wikidata_id" => "Q97588182",
                    // update
                    "auto_update" => false,
                    "interval" => 1,
                    "batch" => 1,
                    // post meta
                    "doi_meta_key" => "doi",
                    "copyright_meta_key" => "copyright",
                    // user meta
                    "orcid_meta_key" => "orcid",
                    "affiliation_meta_key" => "affiliation",
                    "rorid_meta_key" => "rorid",
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "api_baseurl" => "https://api.crossref.org/v2/deposits",
                    "depositor_name" => "Wordpress Plugin " . $this->plugin_name,
                    "doi_suffix_length" => 16,
                    "include_excerpt" => True,
                    // institution
                    "institution_name" => $blog_title,
                    // update
                    "auto_update" => false,
                    "interval" => 1,
                    "batch" => 1,
                    // post meta
                    "doi_meta_key" => "doi",
                    // user meta
                    "orcid_meta_key" => "orcid",
                    "affiliation_meta_key" => "affiliation",
                    "rorid_meta_key" => "rorid",
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

        public function get_doi_meta_key() {
            return $this->get_settings_field_value("doi_meta_key");
        }

        public function get_post_needs_update_meta_key()
        {
            return $this->plugin_name . "_post-needs-update";
        }

        public function get_submit_timestamp_meta_key() {
            return $this->plugin_name . "_submit-timestamp";
        }

        public function format_xml($xml_str)
        {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml_str);
            return $dom->saveXML();
        }

    }

}