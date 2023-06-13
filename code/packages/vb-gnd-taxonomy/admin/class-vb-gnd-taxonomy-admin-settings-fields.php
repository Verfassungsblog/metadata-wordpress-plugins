<?php

if (!class_exists('VB_GND_Taxonomy_Admin_Settings_Fields')) {

    class VB_GND_Taxonomy_Admin_Settings_Fields
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
                    "name" => "suggest_enabled",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Suggestions Enabled",
                    "description" => "Whether suggestions are shown while typing the name of a GND entity.",
                ),
                array(
                    "name" => "api_baseurl",
                    "type" => "string",
                    "section" => "general",
                    "label" => "API Base URL",
                    "placeholder" => "lobid.org GND API URL",
                    "description" => "The URL to the lobid.org
                        <a href=\"https://lobid.org/gnd/api\" target=\"_blank\">GND API</a>.<br>
                        Usually: <code>https://lobid.org/gnd/</code>",
                ),
                array(
                    "name" => "label_format",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Label Format",
                    "placeholder" => "lobid.org label format",
                    "description" => "The label format that is used to describe GND entities.<br>
                        For example:<br>
                        <ul>
                            <li><code>suggest</code> = default format</li>
                            <li><code>preferredName</code> = only the preferred name of the entity</li>
                            <li><code>preferredName,placeOfBirth</code> = preferred name and place of birth</li>
                        </ul>
                        Additional information can be found at the
                        <a href=\"https://lobid.org/gnd/api#auto-complete\" target=\"_blank\">API documentation</a>.
                    ",
                ),
                array(
                    "name" => "query_filter",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Query Filter",
                    "placeholder" => "lobid.org query filter",
                    "description" => "The filter that is used when suggesting GND entities via lobid.org.<br>
                        For example:<br>
                        <ul>
                            <li>
                                <code>type:SubjectHeading</code>
                                = Shows only subjects (and no persons or places)
                            </li>
                            <li>
                                <code>type:Person</code>
                                = Shows only persons
                            </li>
                            <li>
                                <code>gndSubjectCategory.id:\"https://d-nb.info/standards/vocab/gnd/gnd-sc#7.3\"</code>
                                = Only things related to constitutional law
                            </li>
                            <li>
                                <code>+(type:SubjectHeading) +(gndSubjectCategory.id:\"https://d-nb.info/standards/vocab/gnd/gnd-sc#7.3\")</code>
                                = A combination of multiple filters
                            </li>
                        </ul>
                        More information are available in this
                        <a href=\"https://blog.lobid.org/2018/07/06/lobid-gnd-queries.html\" target=\"_blank\">blog post</a>
                        or by checking the URLs that are generated when <a href=\"https://lobid.org/gnd/search\" target=\"_blank\">browsing</a> the GND taxonomy.",
                ),
                array(
                    "name" => "query_size",
                    "type" => "string",
                    "section" => "general",
                    "label" => "Query Size",
                    "placeholder" => "the number of results to show",
                    "description" => "The number of GND entity suggestions to show to the user.",
                ),
                array(
                    "name" => "verify_gnd_id",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Verify GND-ID",
                    "description" => "Whether to check that a GND-ID actually exists. If enabled, invalid entries are not added to the taxonomy.",
                ),
                array(
                    "name" => "merge_with_tags",
                    "type" => "boolean",
                    "section" => "general",
                    "label" => "Merge with Tags",
                    "description" => "Whether to merge GND entities with regular tags when listing tags in a theme.",
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