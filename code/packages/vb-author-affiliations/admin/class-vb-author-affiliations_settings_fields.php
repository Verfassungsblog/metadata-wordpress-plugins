<?php

if (!class_exists('VB_Author_Affiliations_Settings_Fields')) {

    class VB_Author_Affiliations_Settings_Fields
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
                    "name" => "autofill",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Auto Fill Enabled",
                    "description" => "Whether the author affiliation is automatically copied form user information.",
                ),
                array(
                    "name" => "extract_from_rorid",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Extract from ROR-ID",
                    "description" => "Whether to automatically extract the name of the affiliation from a RORID.",
                ),
                array(
                    "name" => "extract_from_orcid",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Extract from ORCID",
                    "description" => "Whether to automatically extract the name of the affilitions from the user's ORCID (last employment).",
                ),
                array(
                    "name" => "timeout",
                    "type" => "text",
                    "section" => "general",
                    "label" => "Timeout in Seconds",
                    "placeholder" => "timeout seconds",
                    "description" => "The number of seconds that is waited for a response when retrieving the
                        affiliation from <code>orcid.org</code> or <code>ror.org</code>.",
                ),

                // ------------- custom post fields -------------

                array(
                    "name" => "author_affiliations_meta_key",
                    "type" => "text",
                    "section" => "post_meta",
                    "label" => "Author Affiliations<br>(custom field / meta key)",
                    "placeholder" => "meta key that contains the author affiliations",
                    "description" => "The meta key for the custom field that stores the author affiliations for a post.
                        It contains all affiliations and ROR-Ids of all authors of the post, including co-authors.",
                ),

                // ------------- custom user fields --------------

                array(
                    "name" => "affiliation_name_meta_key",
                    "type" => "text",
                    "section" => "user_meta",
                    "label" => "Author Affiliation Name<br>(custom field / meta key)",
                    "placeholder" => "meta key for the name of the affiliation of the author",
                    "description" => "The meta key for the custom field that contains the name of the affiliation of
                        the post author.<br>
                        The name may be provided in the format <code>Institute Name, Country</code>.",
                ),
                array(
                    "name" => "rorid_meta_key",
                    "type" => "text",
                    "section" => "user_meta",
                    "label" => "Author Affiliation ROR-ID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the ROR-ID",
                    "description" => "The meta key for the custom field that contains the ROR-ID of the affiliation of
                        an author. <br>
                        ROR-IDs should be provided as code (not as URI), e.g. <code>00ggpsq73</code>.",
                ),
                array(
                    "name" => "orcid_meta_key",
                    "type" => "text",
                    "section" => "user_meta",
                    "label" => "Author ORCID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the ORCID of the author",
                    "description" => "The meta key for the custom field that contains the ORCID of the post author. If
                        enabled, the user's affiliation is extracted from the last employment entry provided in the
                        meta data registered for an ORCID.<br>
                        ORCIDs need to be provided as code (not as URI), e.g. <code>0000-0003-1279-3709</code>.",
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