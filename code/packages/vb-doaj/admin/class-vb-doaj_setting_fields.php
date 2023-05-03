<?php

if (!class_exists('VB_DOAJ_Setting_Fields')) {

    class VB_DOAJ_Setting_Fields
    {

        protected $settings_fields;

        protected $settings_fields_by_name;

        public function __construct()
        {

        }

        protected function load_settings_fields()
        {
            if ($this->settings_fields) {
                return;
            }
            $this->settings_fields = array(
                // ------------------- general settings ---------------------

                array(
                    "name" => "api_key",
                    "type" => "string",
                    "section" => "general",
                    "label" => "API Key",
                    "placeholder" => "DOAJ API Key",
                    "description" => "The API Key provided by the DOAJ.",
                ),
                array(
                    "name" => "auto_update",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Automatic Update",
                    "description" => "Whether new posts should be automatically submitted to the DOAJ in regular intervals.",
                ),
                array(
                    "name" => "interval",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Update Interval",
                    "placeholder" => "update interval in minutes",
                    "description" => "The number of minutes between updates. On each update, the database is checked
                    and new posts are submitted to the DOAJ.",
                ),
                array(
                    "name" => "issn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "ISSN",
                    "placeholder" => "ISSN",
                    "description" => "The <a href=\"https://en.wikipedia.org/wiki/International_Standard_Serial_Number\" target=\"_blank\">
                            International Standard Serial Number</a> (ISSN) of this journal (or blog).",
                ),
                array(
                    "name" => "issue",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Issue Number<br/>for all posts",
                    "placeholder" => "issue number",
                    "description" => "The issue number of this journal within which all posts appear.",
                ),
                array(
                    "name" => "volume",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Volume<br/>for all posts",
                    "placeholder" => "volume",
                    "description" => "The volume of this journal within which all posts appear.",
                ),
                array(
                    "name" => "require_doi",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Require DOI", "vb-doaj"),
                    "description" => "Whether to require the DOI to be available for a post to be submitted. If
                        checked, published posts without a DOI are not submitted to the DOAJ.",
                ),
                array(
                    "name" => "include_excerpt",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Include Excerpt", "vb-doaj"),
                    "description" => "Whether to include the post excerpt as abstract.",
                ),
                array(
                    "name" => "include_tags",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Include Tags", "vb-doaj"),
                    "description" => "Whether to include the post tags as free-text keywords.",
                ),

                // ------------- advanced custom field settings -------------

                array(
                    "name" => "issue_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Issue<br>(ACF key)",
                    "placeholder" => "ACF field key for the Issue Number",
                    "description" => "The ACF field key that contains the issue number for a post.",
                ),
                array(
                    "name" => "volume_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Volume<br>(ACF key)",
                    "placeholder" => "ACF field key for the Volume",
                    "description" => "The ACF field key that contains the volume for a post.",
                ),
                array(
                    "name" => "doi_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "DOI<br>(ACF key)",
                    "placeholder" => "ACF field key for DOI",
                    "description" => "The ACF field key that contains the DOI for a post.",
                ),
                array(
                    "name" => "subheadline_acf",
                    "type" => "string",
                    "section" => "post_acf",
                    "label" => "Sub-Headline<br>(ACF key)",
                    "placeholder" => "ACF field key for a sub-headline",
                    "description" => "The ACF field key that contains the sub-headline for a post.",
                ),
                array(
                    "name" => "orcid_acf",
                    "type" => "string",
                    "section" => "user_acf",
                    "label" => "ORCID<br>(ACF key)",
                    "placeholder" => "ACF field key for the ORCID of the author",
                    "description" => "The ACF field key that contains the ORCID of the post author.",
                ),
                array(
                    "name" => "author_affiliation_acf",
                    "type" => "string",
                    "section" => "user_acf",
                    "label" => "Author Affiliation<br>(ACF key)",
                    "placeholder" => "ACF field key for author affiliation",
                    "description" => "The ACF field key that contains the affiliation of the post author.",
                ),
            );
            // index settings field by their name
            $this->settings_fields_by_name = array();
            foreach($this->settings_fields as $field) {
                $this->settings_fields_by_name[$field["name"]] = $field;
            }
        }

        public function get_list()
        {
            $this->load_settings_fields();
            return $this->settings_fields;
        }

        public function get_field($field_name)
        {
            $this->load_settings_fields();
            return $this->settings_fields_by_name[$field_name];
        }

    }

}