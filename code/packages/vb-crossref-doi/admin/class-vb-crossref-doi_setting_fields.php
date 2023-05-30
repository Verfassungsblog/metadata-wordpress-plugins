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
                    "description" => "The length of the randomly generated suffix (min 12, max 64).",
                ),
                array(
                    "name" => "issn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "ISSN",
                    "placeholder" => "ISSN",
                    "description" => "The <a href=\"https://en.wikipedia.org/wiki/International_Standard_Serial_Number\" target=\"_blank\">
                            International Standard Serial Number</a> (ISSN) of this journal.
                        <br>For example: <code>2366-7044</code> = ISSN of the Verfassungsblog",
                ),
                array(
                    "name" => "copyright_general",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Copyright / Licence <br>for all posts",
                    "placeholder" => "a copyright / license note",
                    "description" => "The default copyright or licence name for all posts. Only Creative Commons
                    licences are supported. This note can be overwritten with a post-specific copyright note if it is
                        provided via via a custom field, see tab \"Custom Fields\". <br>
                        For example: <code>CC BY-SA 4.0</code> = Creative Commons Attribution-ShareAlike 4.0 International",
                ),
                array(
                    "name" => "include_excerpt",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Include Excerpt", "vb-crossref-doi"),
                    "description" => "Whether to include the post excerpt as abstract when submitting meta data to CrossRef.",
                ),

                // ------------- institution fields -------------

                array(
                    "name" => "institution_name",
                    "type" => "string",
                    "section" => "institution",
                    "label" => "Institution Name",
                    "placeholder" => "name of the institution publishing articles.",
                    "description" => "The name of the institution that is publishing articles.",
                ),
                array(
                    "name" => "institution_rorid",
                    "type" => "string",
                    "section" => "institution",
                    "label" => "Institution ROR-ID",
                    "placeholder" => "ROR id of the institution publishing articles.",
                    "description" => "The ROR-ID of the institution that is publishing articles.",
                ),
                array(
                    "name" => "institution_isni",
                    "type" => "string",
                    "section" => "institution",
                    "label" => "Institution ISNI",
                    "placeholder" => "ISNI of the institution publishing articles.",
                    "description" => "The ISNI of the institution that is publishing articles.",
                ),
                array(
                    "name" => "institution_wikidata_id",
                    "type" => "string",
                    "section" => "institution",
                    "label" => "Institution Wikidata ID",
                    "placeholder" => "wikidata id of the institution publishing articles.",
                    "description" => "The Wikidata ID of the institution that is publishing articles.",
                ),

                // ------------- update settings fields -------------

                array(
                    "name" => "auto_update",
                    "type" => "boolean",
                    "section" => "update",
                    "label" => "Automatic Update",
                    "description" => "Whether new posts should be automatically submitted to CrossRef in regular intervals.",
                ),
                array(
                    "name" => "interval",
                    "type" => "string",
                    "section" => "update",
                    "label" => "Update Interval",
                    "placeholder" => "update interval in minutes",
                    "description" => "The number of minutes between updates. On each update, the database is checked
                    and new posts are submitted to CrossRef.",
                ),
                array(
                    "name" => "batch",
                    "type" => "string",
                    "section" => "update",
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
                array(
                    "name" => "copyright_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Copyright / Licence<br>(custom field / meta key)",
                    "placeholder" => "meta key for a copyright / licence note",
                    "description" => "The meta key for the custom field that contains the copyright or licence note for
                        a specific post. If a post-specific copyright note is provided, the default copyright note is
                        overwritten. Only Creative Commons licences are supported.",
                ),

                // ------------- custom user fields --------------

                array(
                    "name" => "orcid_meta_key",
                    "type" => "string",
                    "section" => "user_meta",
                    "label" => "Author ORCID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the ORCID of the author",
                    "description" => "The meta key for the custom field that contains the ORCID of the post author.<br>ORCIDs need to be provided as code (not as URI), e.g. <code>0000-0003-1279-3709</code>.",
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