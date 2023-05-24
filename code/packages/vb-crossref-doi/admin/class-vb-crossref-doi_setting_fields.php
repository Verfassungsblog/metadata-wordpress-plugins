<?php

if (!class_exists('VB_CrossRef_DOI_Setting_Fields')) {

    class VB_CrossRef_DOI_Setting_Fields
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
                    "name" => "api_user",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Deposit Username",
                    "placeholder" => "CrossRef Deposit API Username",
                    "description" => "The username to the CrossRef Deposit API.",
                ),
                array(
                    "name" => "api_password",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Deposit Password",
                    "placeholder" => "CrossRef Deposit API Password",
                    "description" => "The password to the CrossRef Deposit API.",
                ),
                array(
                    "name" => "api_baseurl",
                    "type" => "string",
                    "section" => "general",
                    "label" => "API Base URL",
                    "placeholder" => "URL to the CrossRef Deposit API",
                    "description" => "The URL to the CrossRef Deposit API.<br>
                        Usually <code>https://api.crossref.org/v2/deposits</code>, for testing <code>https://test.crossref.org/v2/deposits</code>",
                ),
                array(
                    "name" => "depositor_name",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Depositer Name",
                    "placeholder" => "Depositor Name",
                    "description" => "The name of the organization registering the DOIs.",
                ),
                array(
                    "name" => "depositor_email",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Depositer eMail",
                    "placeholder" => "Depositor eMail",
                    "description" => "The e-mail address to which success and/or error messages are sent.",
                ),
                array(
                    "name" => "registrant",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Registrant",
                    "placeholder" => "Name of Registrant",
                    "description" => "The name of the organization responsible for the information being registered.",
                ),
                array(
                    "name" => "journal_title",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Journal Title",
                    "placeholder" => "Title of the Journal",
                    "description" => "The full title by which this journal is commonly known or cited.",
                ),
                array(
                    "name" => "eissn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "eISSN",
                    "placeholder" => "Electronic ISSN of this Journal",
                    "description" => "The electronic ISSN assigned to this Journal.",
                ),
                array(
                    "name" => "doi_prefix",
                    "type" => "string",
                    "section" => "general",
                    "label" => "DOI Prefix",
                    "placeholder" => "Prefix for DOIs",
                    "description" => "The prefix that is used when generating new DOIs for posts.",
                ),
                array(
                    "name" => "doi_suffix_length",
                    "type" => "string",
                    "section" => "general",
                    "label" => "DOI Suffix Length",
                    "placeholder" => "DOI suffix length",
                    "description" => "The length of the randomly generated suffix (min 8, max 64).",
                ),
                array(
                    "name" => "auto_update",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Automatic Update",
                    "description" => "Whether new posts should be automatically submitted to CrossRef in regular intervals.",
                ),
                array(
                    "name" => "interval",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Update Interval",
                    "placeholder" => "update interval in minutes",
                    "description" => "The number of minutes between updates. On each update, the database is checked
                    and new posts are submitted to CrossRef.",
                ),
                array(
                    "name" => "batch",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Batch Size",
                    "placeholder" => "batch size",
                    "description" => "The number of posts that are processed in one batch. High values (>1) might
                        trigger the CrossRef to block your IP for a while. Use at own risk!",
                ),

                // ------------- custom post fields -------------

                array(
                    "name" => "doi_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Article DOI<br>(custom field / meta key)",
                    "placeholder" => "meta key for the DOI",
                    "description" => "The meta key for the custom field that stores the DOI for a post.<br>The DOI is provided as a code (not as URI), e.g. <code>10.1214/aos/1176345451</code>.",
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