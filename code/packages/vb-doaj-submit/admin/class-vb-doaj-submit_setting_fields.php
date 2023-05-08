<?php

if (!class_exists('VB_DOAJ_Submit_Setting_Fields')) {

    class VB_DOAJ_Submit_Setting_Fields
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
                    "name" => "api_baseurl",
                    "type" => "string",
                    "section" => "general",
                    "label" => "API Base URL",
                    "placeholder" => "URL to DOAJ API",
                    "description" => "The URL to the DOAJ API.<br>
                        Usually <code>https://doaj.org/api/</code>, for testing: <code>https://testdoaj.cottagelabs.com/api/</code>",
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
                    "name" => "batch",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Batch Size",
                    "placeholder" => "batch size",
                    "description" => "The number of posts that are processed in one batch. High values (>1) might
                        trigger the DOAJ to block your IP for a while. Use at own risk!",
                ),
                array(
                    "name" => "eissn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "eISSN",
                    "placeholder" => "eISSN",
                    "description" => "The electronic <a href=\"https://en.wikipedia.org/wiki/International_Standard_Serial_Number\" target=\"_blank\">
                            International Standard Serial Number</a> (ISSN) of this journal (or blog). Either eISSN or pISSN needs to be provided!",
                ),
                array(
                    "name" => "pissn",
                    "type" => "string",
                    "section" => "general",
                    "label" => "pISSN",
                    "placeholder" => "pISSN",
                    "description" => "The print <a href=\"https://en.wikipedia.org/wiki/International_Standard_Serial_Number\" target=\"_blank\">
                            International Standard Serial Number</a> (ISSN) of this journal (or blog). Either eISSN or pISSN needs to be provided!",
                ),
                array(
                    "name" => "issue",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Issue Number<br/>for all posts",
                    "placeholder" => "issue number",
                    "description" => "The issue number of this journal that will be used for all posts (in case it is
                        the same for all posts). You can use a custom field to specify issue numbers for every
                        post individually.",
                ),
                array(
                    "name" => "volume",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Volume<br/>for all posts",
                    "placeholder" => "volume",
                    "description" => "The volume of this journal that will be used for all posts (in case it is the
                        same for all posts). You can use a custom field to specify volume numbers for every post
                        individually.",
                ),
                array(
                    "name" => "require_doi",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Require DOI", "vb-doaj"),
                    "description" => "Whether to require the DOI to be available for a post to be submitted. If
                        checked, published posts without a DOI are not submitted to the DOAJ. DOIs need to be stored in
                        a corresponding custom field.",
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
                array(
                    "name" => "identify_by_permalink",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => __("Identify by Permalink", "vb-doaj-submit"),
                    "description" => "Whether to identify posts by finding matching DOAJ articles based on their
                        permalink (recommended). If not checked, the post title is used instead. Using the post title
                        would be problematic if there are multiple articles with the same title.",
                ),

                // ------------- custom post fields -------------

                array(
                    "name" => "article_id_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "DOAJ Article ID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the DOAJ article id",
                    "description" => "The meta key for the custom field that contains the automatically retrieved DOAJ article id for a post.<br/>
                        (Any changes to this key will not copy data to the new field automatically. You can trigger
                        that article ids are retrieved from the DOAJ again via the \"Reset Status of all Posts\" button
                        in the status tab.)",
                ),
                array(
                    "name" => "issue_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Journal Issue<br>(custom field / meta key)",
                    "placeholder" => "meta key for the Issue Number",
                    "description" => "The meta key for the custom field that contains the issue number for a post.",
                ),
                array(
                    "name" => "volume_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Journal Volume<br>(custom field / meta key)",
                    "placeholder" => "meta key for the Volume",
                    "description" => "The meta key for the custom field that contains the volume for a post.",
                ),
                array(
                    "name" => "doi_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Article DOI<br>(custom field / meta key)",
                    "placeholder" => "meta key for the DOI",
                    "description" => "The meta key for the custom field that contains the DOI for a post.<br>The DOI should be provided as code (not as URI), e.g. <code>10.1214/aos/1176345451</code>.",
                ),
                array(
                    "name" => "author_affiliations_meta_key",
                    "type" => "string",
                    "section" => "post_meta",
                    "label" => "Author Affiliations<br>(custom field / meta key)",
                    "placeholder" => "meta key for the author affiliations",
                    "description" => "The meta key for the custom field that stores the affiliations for
                        each author at the time the post was submitted to the DOAJ. The affiliations are automatically
                        copied from the current information provided by each author. The affiliation is first tried to
                        be copied from the specfic textual affiliation provided by the author (see setting \"Author
                        Affiliation\" below), then tried to be extracted from the ROR-ID provided by the author (see
                        setting \"Author Affiliation ROR-ID\" below), and lastly tried to be extracted from the ORCID
                        provided by the author (see setting \"Author ORCID\" below).",
                ),

                // ------------- custom user fields -------------

                array(
                    "name" => "orcid_meta_key",
                    "type" => "string",
                    "section" => "user_meta",
                    "label" => "Author ORCID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the ORCID of the author",
                    "description" => "The meta key for the custom field that contains the ORCID of the post author.<br>ORCIDs need to be provided as code (not as URI), e.g. <code>0000-0003-1279-3709</code>.",
                ),
                array(
                    "name" => "affiliation_meta_key",
                    "type" => "string",
                    "section" => "user_meta",
                    "label" => "Author Affiliation<br>(custom field / meta key)",
                    "placeholder" => "meta key for the affiliation of the author",
                    "description" => "The meta key for the custom field that contains the textual description of the affiliation of the post author.<br>The textual description should be provided in the format <code>Institute Name, Country</code>.",
                ),
                array(
                    "name" => "rorid_meta_key",
                    "type" => "string",
                    "section" => "user_meta",
                    "label" => "Author Affiliation ROR-ID<br>(custom field / meta key)",
                    "placeholder" => "meta key for the ROR-ID",
                    "description" => "The meta key for the custom field that contains the ROR-ID of the affiliation of an author. <br>ROR-IDs should be provided as code (not as URI), e.g. <code>00ggpsq73</code>.",
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