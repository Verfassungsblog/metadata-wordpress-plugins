<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_marc21xml.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_converter.php';

if (!class_exists('VB_Metadata_Export_OaiPmh')) {

    class VB_Metadata_Export_OaiPmh
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
        }

        protected function date_to_iso8601($date) {
            return $date->format("Y-m-d\TH:i:s\Z");
        }

        protected function get_post_id_from_identifier($identifier) {
            $permalink = str_replace("oai:", "http://", $identifier);
            return url_to_postid($permalink);
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

        public function get_base_url() {
            return get_home_url() . "/oai/repository/";
        }

        public function get_post_identifier($post) {
            return "oai:" . str_replace(array("http://", "https://"), "", get_the_permalink($post));
        }

        public function render() {
            $verb = get_query_var('verb');
            $identifier = get_query_var('identifier');
            $metadataPrefix = get_query_var('metadataPrefix');
            $from = get_query_var('from');
            $until = get_query_var('until');
            $set = get_query_var('set');
            $resumptionToken = get_query_var('resumptionToken');

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

                return $this->render_get_record($identifier, $metadataPrefix);
            }
            if ($verb == "ListIdentifiers") {

                return $this->render_list_identifiers($metadataPrefix, $from, $until, $set, $resumptionToken);
            }
            if ($verb == "ListRecords") {
                return $this->render_list_records($metadataPrefix, $from, $until, $set, $resumptionToken);
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

        protected function get_post_header($post) {
            return implode("", array(
                "<header>",
                "<identifier>",
                $this->get_post_identifier($post),
                "</identifier>",
                "<datestamp>",
                $this->post_date_to_iso8601($post->post_date),
                "</datestamp>",
                "<setSpec>posts</setSpec>",
                "</header>",
            ));
        }

        protected function get_post_metadata($post, $metadataPrefix) {

            $renderer = new VB_Metadata_Export_Marc21Xml($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();
            $marc21xml = $renderer->render($post);
            $metadata = "";
            if ($metadataPrefix == "MARC21-xml") {
                $metadata = $marc21xml;
            }
            if ($metadataPrefix == "oai_dc") {
                $metadata = $converter->convertMarc21ToOaiDc($marc21xml);
            }
            if ($metadataPrefix == "mods-xml") {
                $metadata = $converter->convertMarc21ToMods($marc21xml);
            }
            $metadata = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>", "", $metadata);
            $metadata = str_replace("<?xml version=\"1.0\"?>", "", $metadata);
            return "<metadata>" . $metadata . "</metadata>";
        }

        protected function query_for_posts($from, $until) {
            $after = empty($from) ? $this->get_earliest_post_date() : $from;
            $before = empty($until) ? $this->date_to_iso8601(new DateTime()) : $until;
            $require_doi = $this->common->get_settings_field_value("require_doi");

            $query_args = array(
                'date_query' => array(
                    array(
                        'after'     => $after,
                        'before'    => $before,
                        'inclusive' => true,
                    ),
                ),
                'post_status' => 'publish',
                'posts_per_page' => 10,
            );

            if ($require_doi) {
                $query_args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => "doi",
                        'value' => "",
                        'compare' => "!=",
                    ),
                );
            }

            return (new WP_Query( $query_args ))->get_posts();
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
            $admin_email = esc_html($this->common->get_settings_field_value("oai-pmh_admin_email"));

            $xml = implode("", array(
                "<Identify>",
                "<repositoryName>{$blog_title}</repositoryName>",
                "<baseURL>{$base_url}</baseURL>",
                "<protocolVersion>2.0</protocolVersion>",
                "<adminEmail>{$admin_email}</adminEmail>",
                "<earliestDatestamp>{$earliest_date}</earliestDatestamp>",
                "<deletedRecord>no</deletedRecord>",
                "<granularity>YYYY-MM-DDThh:mm:ssZ</granularity>",
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
            $post_id = $this->get_post_id_from_identifier($identifier);
            if ($post_id <= 0) {
                return $this->render_error("GetRecord", "idDoesNotExist", "invalid identifier");
            }

            $post = get_post($post_id);

            $xml = implode("", array(
                "<GetRecord>",
                $this->get_post_header($post),
                $this->get_post_metadata($post, $metadataPrefix),
                "</GetRecord>",
            ));

            return $this->render_response(array(
                "verb" => "GetRecord",
                "identifier" => $identifier,
                "metadataPrefix" => $metadataPrefix
            ), $xml);
        }

        public function render_list_records($metadataPrefix = "oai_dc", $from = null, $until = null, $set = null, $resumptionToken = null) {

            $posts = $this->query_for_posts($from, $until);

            $xml_array = array("<ListRecords>");
            foreach($posts as $post) {
                $xml_array = array_merge($xml_array, array(
                    $this->get_post_header($post),
                    $this->get_post_metadata($post, $metadataPrefix),
                ));
            }
            $xml_array = array_merge($xml_array, array("</ListRecords>"));

            return $this->render_response(array_filter(array(
                "verb" => "ListIdentifiers",
                "from" => $from,
                "until" => $until,
                "metadataPrefix" => $metadataPrefix,
                "set" => $set,
                "resumptionToken" => $resumptionToken
            )), implode("", $xml_array));

        }

        public function render_list_identifiers($metadataPrefix = "oai_dc", $from = null, $until = null, $set = null, $resumptionToken = null) {
            // example: https://services.dnb.de/oai/repository?verb=ListIdentifiers&metadataPrefix=oai_dc&from=2023-01-01&until=2023-01-02
            if (!$this->is_valid_metadata_prefix($metadataPrefix)) {
                return $this->render_error("ListIdentifiers", "cannotDisseminateFormat", "invalid metadataPrefix format");
            }

            $posts = $this->query_for_posts($from, $until);

            $xml_array = array("<ListIdentifiers>");
            foreach($posts as $post) {
                $xml_array = array_merge($xml_array, array($this->get_post_header($post)));
            }
            $xml_array = array_merge($xml_array, array("</ListIdentifiers>"));

            return $this->render_response(array_filter(array(
                "verb" => "ListIdentifiers",
                "from" => $from,
                "until" => $until,
                "metadataPrefix" => $metadataPrefix,
                "set" => $set,
                "resumptionToken" => $resumptionToken
            )), implode("", $xml_array));
        }

        public function run() {

        }

    }
}
