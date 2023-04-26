<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';

if (!class_exists('VB_Metadata_Export_OaiPmh')) {

    class VB_Metadata_Export_OaiPmh
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
        }

        public function get_base_url() {
            return get_home_url() . "/oai/repository";
        }

        protected function date_to_iso8601($date) {
            return $date->format("Y-m-d\TH:i:s\Z");
        }

        protected function post_date_to_iso8601($post_date) {
            $date = date_create_immutable_from_format('Y-m-d H:i:s', $post_date, new DateTimeZone('UTC'));
            return $this->date_to_iso8601($date);
        }

        protected function get_earliest_post_date() {
            $oldest_posts = get_posts(array("numberposts" => 1, "order" => "ASC"));
            if (count($oldest_posts) == 1) {
                return $this->post_date_to_iso8601($oldest_posts[0]->post_date);
            }
            // no posts yet, return current date
            return $this->date_to_iso8601(new DateTime());
        }

        protected function is_valid_metadata_prefix($metadataPrefix) {
            return in_array($metadataPrefix, array("oai_dc", "mods-xml", "MARC21-xml"));
        }

        public function render() {
            $verb = get_query_var('verb');
            if ($verb == "Identify") {
                return $this->render_identify();
            }
            if ($verb == "ListSets") {
                return $this->render_list_sets();
            }
            if ($verb == "ListMetadataFormats") {
                return $this->render_list_metadata_formats();
            }
            if ($verb == "GetRecord") {
                $identifier = get_query_var('identifier');
                $metadataPrefix = get_query_var('metadataPrefix');
                return $this->render_get_record($identifier, $metadataPrefix);
            }
            return $this->render_error($verb, "badVerb", "verb argument is not a legal OAI-PMH verb");
        }

        protected function implode_query_arguments($query) {
            $arguments = array();
            foreach ($query as $key => $value) {
                array_push($arguments, "{$key}=\"${value}\"");
            };
            return implode(" ", $arguments);
        }

        public function render_response($query, $content) {
            $base_url = $this->get_base_url();
            $response_date = $this->date_to_iso8601(new DateTime());
            $request_arguments = $this->implode_query_arguments($query);
            $xml = implode("", array(
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>",
                "<OAI-PMH
                    xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd\"
                    xmlns=\"http://www.openarchives.org/OAI/2.0/\"
                    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">",
                "<responseDate>{$response_date}</responseDate>",
                "<request {$request_arguments}>{$base_url}</request>",
                $content,
            "</OAI-PMH>"));
            return $this->common->formatXml($xml);
        }

        public function render_error($verb, $code, $text) {
            $xml = "<error code=\"{$code}\">{$text}</error>";
            return $this->render_response(array(), $xml);
        }

        public function render_identify() {
            // example: https://services.dnb.de/oai/repository?verb=Identify
            $blog_title = esc_html($this->common->get_settings_field_value("blog_title"));
            $base_url = $this->get_base_url();
            $earliest_date = $this->get_earliest_post_date();

            $xml = implode("", array(
                "<Identify>",
                "<repositoryName>{$blog_title}</repositoryName>",
                "<baseURL>{$base_url}</baseURL>",
                "<protocolVersion>2.0</protocolVersion>",
                "<adminEmail></adminEmail>",
                "<earliestDatestamp>{$earliest_date}</earliestDatestamp>",
                "<deletedRecord>no</deletedRecord>",
                "<granularity>YYYY-MM-DD</granularity>",
                "</Identify>",
            ));
            return $this->render_response(array("verb" => "Identify"), $xml);
        }

        public function render_list_sets() {
            // example: https://services.dnb.de/oai/repository?verb=ListSets
            $xml = implode("", array(
                "<ListSets>",
                "<set>",
                "<setSpec>posts</setSpec>",
                "<setName>Posts</setName>",
                "</set>",
                "</ListSets>",
            ));
            return $this->render_response(array("verb" => "ListSets"), $xml);
        }

        public function render_list_metadata_formats() {
            // example: https://services.dnb.de/oai/repository?verb=ListMetadataFormats
            $xml = implode("", array(
                "<ListMetadataFormats>",
                "<metadataFormat>",
                "<metadataPrefix>oai_dc</metadataPrefix>",
                "<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>",
                "<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc</metadataNamespace>",
                "</metadataFormat>",
                "<metadataFormat><metadataPrefix>mods-xml</metadataPrefix>",
                "<schema>http://www.loc.gov/standards/mods/v3/mods-3-7.xsd</schema>",
                "<metadataNamespace>http://www.loc.gov/mods/v3</metadataNamespace>",
                "</metadataFormat>",
                "<metadataFormat>",
                "<metadataPrefix>MARC21-xml</metadataPrefix>",
                "<schema>http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd</schema>",
                "<metadataNamespace>http://www.loc.gov/MARC21/slim</metadataNamespace>",
                "</metadataFormat>",
                "</ListMetadataFormats>",
            ));
            return $this->render_response(array("verb" => "ListMetadataFormats"), $xml);
        }

        public function render_get_record($identifier, $metadataPrefix) {
            // example: https://services.dnb.de/oai/repository?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:dnb.de/dnb/1277033072

            if (!$this->is_valid_metadata_prefix($metadataPrefix)) {
                return $this->render_error("GetRecord", "cannotDisseminateFormat", "invalid metadataPrefix format");
            }

            // TODO: check identifier

            // print result

            return $this->render_response(array(
                "verb" => "GetRecord",
                "identifier" => $identifier,
                "metadataPrefix" => $metadataPrefix
            ), "");
        }

        public function render_list_records() {
            return "";
        }

        public function render_list_identifiers() {
            // example: https://services.dnb.de/oai/repository?verb=ListIdentifiers&metadataPrefix=oai_dc&from=2023-01-01&until=2023-01-02
            return "";
        }

        public function run() {

        }

    }
}
