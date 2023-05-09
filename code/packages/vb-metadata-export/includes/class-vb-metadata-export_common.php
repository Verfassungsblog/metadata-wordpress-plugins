<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_oai_pmh.php';

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
                // default settings for Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "blog_owner" => "Max Steinbeis Verfassungsblog gGmbH",
                    "blog_title" => $blog_title,
                    "issn" => "2366-7044",
                    "publisher" => $blog_title,
                    "require_doi" => true,
                    "include_excerpt" => true,
                    "ddc_general" => "342",
                    "copyright_general" => "CC BY-SA 4.0",
                    "funding_general" => "funded by the government",

                    // language
                    "language" => "ger",
                    "language_alternate" => "eng",
                    "language_alternate_category" => "English Articles",

                    // content types
                    "podcast_category" => "Podcast",

                    // custom fields
                    "doi_meta_key" => "doi",
                    "subheadline_meta_key" => "subheadline",
                    "orcid_meta_key" => "orcid",
                    "gndid_meta_key" => "gndid",
                    "ddc_meta_key" => "ddc",
                    "copyright_meta_key" => "copyright",
                    "funding_meta_key" => "funding",

                    // marc21xml
                    "marc21xml_enabled" => true,
                    "marc21_doi_as_control_number" => true,
                    "marc21_control_number_identifier" => "DE-Verfassungsblog",
                    "marc21_leader" => "_____nam__22_____uu_4500",
                    "marc21_physical_description" => "cr|||||",
                    "marc21_content_type" => "Text,txt,rdacontent",
                    "marc21_podcast_leader" => "_____nim__22_____uu_4500",
                    "marc21_podcast_physical_description" => "sr|||||",
                    "marc21_podcast_content_type" => "Spoken Word,spw,rdacontent",

                    // mods
                    "mods_enabled" => true,

                    // oai-pmh
                    "oai-pmh_enabled" => true,
                    "oai-pmh_admin_email" => "admin@example.com",
                    "oai-pmh_list_size" => 10,

                    // dublin core
                    "dc_enabled" => true,
                );
            } else {
                // default settings for any other blog than Verfassungsblog
                $this->setting_field_defaults = array(
                    // general
                    "blog_title" => $blog_title,
                    "require_doi" => false,
                    "include_excerpt" => false,

                    // language
                    "language" => "eng",

                    // marc21xml
                    "marc21xml_enabled" => true,
                    "marc21_doi_as_control_number" => false,
                    "marc21_leader" => "_____nam__22_____uu_4500",
                    "marc21_physical_description" => "cr|||||",
                    "marc21_content_type" => "Text,txt,rdacontent",
                    "marc21_podcast_leader" => "_____nim__22_____uu_4500",
                    "marc21_podcast_physical_description" => "sr|||||",
                    "marc21_podcast_content_type" => "Spoken Word,spw,rdacontent",

                    // mods
                    "mods_enabled" => true,

                    // oai-pmh
                    "oai-pmh_enabled" => true,
                    "oai-pmh_list_size" => 10,

                    // dublin core
                    "dc_enabled" => true,
                );
            }
        }

        protected function is_format_requiring_doi($format)
        {
            if ($format == "marc21xml") {
                return $this->get_settings_field_value("require_doi") || $this->get_settings_field_value("marc21_doi_as_control_number");
            }
            return $this->get_settings_field_value("require_doi");
        }

        public function get_available_formats()
        {
            return array_keys($this->get_format_labels());
        }

        public function get_format_labels()
        {
            return array(
                "marc21xml" => "Marc21 XML",
                "mods" => "MODS",
                "dc" => "Dublin Core",
                "oai-pmh" => "OAI PMH 2.0",
            );
        }

        public function is_valid_format($format)
        {
            return in_array($format, $this->get_available_formats());
        }

        public function is_format_enabled($format)
        {
            if (in_array($format, $this->get_available_formats())) {
                return $this->get_settings_field_value($format . "_enabled");
            }
            return false;
        }

        public function is_metadata_available($format, $post)
        {
            if (!$this->is_format_enabled($format)) {
                return false;
            }
            if ($this->is_format_requiring_doi($format)) {
                $doi = $this->get_post_meta_field_value("doi_meta_key", $post);
                if (empty($doi)) {
                    return false;
                }
            }
            if (!is_post_publicly_viewable($post)) {
                return false;
            }
            return true;
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

        public function get_the_permalink($format, $post)
        {
            // check settings
            if (!$this->is_metadata_available($format, $post)) {
                // format must be valid and enabled
                return;
            }

            // check permalink
            $permalink = get_the_permalink($post) ?? get_post_permalink($post);
            if (empty($permalink)) {
                return;
            }

            if ($format == "oai-pmh") {
                $oaipmh = new VB_Metadata_Export_OAI_PMH($this->plugin_name);
                return $oaipmh->get_permalink($post);
            }

            // transform permalink
            if (str_contains($permalink, "?")) {
                return $permalink . "&" . $this->plugin_name . "={$format}";
            }
            return $permalink . "?" . $this->plugin_name . "={$format}";
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