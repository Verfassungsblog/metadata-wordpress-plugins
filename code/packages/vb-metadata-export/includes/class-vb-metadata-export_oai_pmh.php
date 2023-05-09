<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_marc21xml.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_converter.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_dc.php';

if (!class_exists('VB_Metadata_Export_OAI_PMH')) {

    class VB_Metadata_Export_OAI_PMH
    {
        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
        }

        protected function date_to_iso8601($date) {
            return $date->format("Y-m-d\TH:i:s\Z");
        }

        protected function iso8601_to_date($iso) {
            $date = date_create_immutable_from_format('Y-m-d\\TH:i:s\\Z', $iso, new DateTimeZone('UTC'));
            if (!$date) {
                $date = date_create_immutable_from_format('Y-m-d', $iso, new DateTimeZone('UTC'));
            }
            return $date;
        }

        protected function get_post_id_from_identifier($identifier) {
            $url = parse_url(site_url());
            $path = isset($url["path"]) ? $url["path"] : "";
            $host = $url["host"];
            return (int)str_replace("oai:" . $host . "/" . $path, "", $identifier) ?? false;
        }

        protected function post_date_to_iso8601($post_date) {
            $date = date_create_immutable_from_format('Y-m-d H:i:s', $post_date, new DateTimeZone('UTC'));
            return $this->date_to_iso8601($date);
        }

        protected function get_earliest_post_date() {
            $oldest_posts = get_posts(array("numberposts" => 1, "order" => "ASC"));
            if (count($oldest_posts) == 1) {
                return $this->post_date_to_iso8601($oldest_posts[0]->post_date_gmt);
            }
            // no posts yet, return current date
            return $this->date_to_iso8601(new DateTime());
        }

        protected function metadataPrefix_to_common_format($metadataPrefix) {
            return array(
                "oai_dc" => "dc",
                "mods-xml" => "mods",
                "MARC21-xml" => "marc21xml",
            )[$metadataPrefix];
        }

        protected function is_valid_metadata_prefix($metadataPrefix) {
            return in_array($metadataPrefix, array("oai_dc", "mods-xml", "MARC21-xml")) &&
                $this->common->is_format_enabled($this->metadataPrefix_to_common_format($metadataPrefix));
        }

        protected function get_list_arguments_error($verb, $metadataPrefix, $from, $until, $resumptionToken) {
            if (empty($metadataPrefix)) {
                return $this->render_error($verb, "badArgument", "metadataPrefix is required");
            }
            if (!$this->is_valid_metadata_prefix($metadataPrefix)) {
                return $this->render_error($verb, "cannotDisseminateFormat", "invalid metadataPrefix format");
            }
            if (!empty($from) && !$this->iso8601_to_date($from)) {
                return $this->render_error($verb, "badArgument", "from date is not iso8601");
            }
            if (!empty($until) && !$this->iso8601_to_date($until)) {
                return $this->render_error($verb, "badArgument", "until date is not iso8601");
            }
            if (!empty($from) && !empty($until) && str_contains($from, "T") != str_contains($until, "T")) {
                return $this->render_error($verb, "badArgument", "both from and until date should have same granularity");
            }
            if (!empty($resumptionToken) && (!empty($from) || !empty($until))) {
                return $this->render_error($verb, "badArgument", "date not allowed in combination with resumptionToken");
            }
            return false;
        }

        protected function get_list_size() {
            $list_size = (int)$this->common->get_settings_field_value("oai-pmh_list_size");
            if ($list_size <= 0) {
                return 10;
            }
            return $list_size;
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
                "<header",
                $post->post_status == "trash" ? " status=\"deleted\"" : "",
                ">",
                "<identifier>",
                $this->get_post_identifier($post),
                "</identifier>",
                "<datestamp>",
                $this->post_date_to_iso8601($post->post_modified_gmt),
                "</datestamp>",
                "<setSpec>posts</setSpec>",
                "</header>",
            ));
        }

        protected function get_post_metadata($post, $metadataPrefix) {
            if ($post->post_status == "trash") {
                // do not show metadata of deleted posts
                return "";
            }
            $renderer = new VB_Metadata_Export_Marc21Xml($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();
            $marc21xml = $renderer->render($post);
            $metadata = "";
            if ($metadataPrefix == "MARC21-xml") {
                $metadata = $marc21xml;
            }
            if ($metadataPrefix == "oai_dc") {
                $oaidc = new VB_Metadata_Export_DC($this->common->plugin_name);
                $metadata = $oaidc->render($post);
            }
            if ($metadataPrefix == "mods-xml") {
                $metadata = $converter->convertMarc21ToMods($marc21xml);
            }
            $metadata = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>", "", $metadata);
            $metadata = str_replace("<?xml version=\"1.0\"?>", "", $metadata);
            return "<metadata>" . $metadata . "</metadata>";
        }

        protected function local_to_utc_iso8601($utc_iso) {
            $local = new Datetime("now", wp_timezone());
            $date = new Datetime("now", new DateTimeZone("UTC"));
            $date->setTimestamp($this->iso8601_to_date($utc_iso)->getTimestamp() - wp_timezone()->getOffset($local));
            return $this->date_to_iso8601($date);
        }

        protected function query_for_posts($offset, $from, $until) {
            $after = empty($from) ? $this->get_earliest_post_date() : $from;
            $before = empty($until) ? $this->date_to_iso8601(new DateTime("now", new DateTimeZone("UTC"))) : $until;
            $require_doi = $this->common->get_settings_field_value("require_doi");

            // convert after/before to UTC (even though they are UTC) because date_query will always convert to local
            $after = $this->local_to_utc_iso8601($after);
            $before = $this->local_to_utc_iso8601($before);

            $query_args = array(
                'offset' => $offset,
                'date_query' => array(
                    'column' => 'post_modified_gmt',
                    array(
                        'after'     => $after,
                        'before'    => $before,
                        'inclusive' => true,
                    ),
                ),
                'post_type' => 'post',
                'post_status' => array('publish', 'trash'),
                'posts_per_page' => $this->get_list_size(),
                'order_by' => 'modified'
            );

            if ($require_doi) {
                $doi_meta_key = $this->common->get_settings_field_value("doi_meta_key");
                $query_args['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => $doi_meta_key,
                        'value' => "",
                        'compare' => "!=",
                    ),
                );
            }

            return new WP_Query( $query_args );
        }

        protected function parse_resumption_token($token) {
            if (empty($token)) {
                return false;
            }
            parse_str(base64_decode($token), $options);
            $allowed_options = array("offset", "from", "until", "metadataPrefix", "set");
            return array_intersect_key($options, array_flip($allowed_options));
        }

        protected function get_resumption_token($offset, $list_count, $total_count, $from, $until, $metadataPrefix, $set) {
            $next_offset = $offset + $list_count;
            $options = array_filter(array(
                "offset" => $next_offset,
                "from" => $from,
                "until" => $until,
                "metadataPrefix" => $metadataPrefix,
                "set" => $set
            ));
            $token = http_build_query($options);
            return array(
                "<resumptionToken cursor=\"{$offset}\" completeListSize=\"{$total_count}\">",
                base64_encode($token),
                "</resumptionToken>",
            );
        }

        protected function render_list_request($verb, $metadataPrefix, $from, $until, $set, $resumptionToken) {
            $requestOptions = array_filter(array(
                "verb" => $verb,
                "from" => $from,
                "until" => $until,
                "metadataPrefix" => $metadataPrefix,
                "set" => $set,
                "resumptionToken" => $resumptionToken
            ));

            $resumptionOptions = $this->parse_resumption_token($resumptionToken);
            if (!empty($resumptionToken) && !$resumptionOptions) {
                return $this->render_error($verb, "badResumptionToken", "invalid resumption token");
            }

            $offset = 0;
            if ($resumptionOptions) {
                $offset = $resumptionOptions["offset"] ?? $offset;
                $from = $resumptionOptions["from"] ?? $from;
                $until = $resumptionOptions["until"] ?? $until;
                $metadataPrefix = $resumptionOptions["metadataPrefix"] ?? $metadataPrefix;
                $set = $resumptionOptions["set"] ?? $set;
            }

            $error = $this->get_list_arguments_error($verb, $metadataPrefix, $from, $until, $resumptionToken);
            if ($error) {
                return $error;
            }

            $query = $this->query_for_posts($offset, $from, $until);
            $total_count = $query->found_posts;
            $list_count = $query->post_count;
            $posts = $query->get_posts();

            if ($list_count == 0) {
                return $this->render_error($verb, "noRecordsMatch", "no records match the provided criteria");
            }

            $xml_array = array("<{$verb}>");
            foreach($posts as $post) {
                $xml_array = array_merge($xml_array, array(
                    "<record>",
                    $this->get_post_header($post),
                    $verb == "ListRecords" ? $this->get_post_metadata($post, $metadataPrefix) : "",
                    "</record>",
                ));
            }
            if($total_count > $list_count + $offset) {
                // there are more results, use resumptionToken
                $xml_array = array_merge($xml_array, $this->get_resumption_token($offset, $list_count, $total_count, $from, $until, $metadataPrefix, $set));
            }
            $xml_array = array_merge($xml_array, array("</{$verb}>"));

            return $this->render_response($requestOptions, implode("", $xml_array));
        }

        public function get_base_url() {
            return get_home_url() . "/oai/repository/";
        }

        public function get_post_identifier($post) {
            $url = parse_url(site_url());
            $path = isset($url["path"]) ? $url["path"] : "";
            $host = $url["host"];
            return "oai:" . $host . "/" . $path . $post->ID;
        }

        public function get_permalink($post) {
            return $this->get_base_url() . "?verb=GetRecord&metadataPrefix=oai_dc&identifier=" . $this->get_post_identifier($post);
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
            return $this->common->format_xml($xml);
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
                "<deletedRecord>transient</deletedRecord>",
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
                $this->common->is_format_enabled("dc") ? "<metadataFormat>
                <metadataPrefix>oai_dc</metadataPrefix>
                <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>
                <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc</metadataNamespace>
                </metadataFormat>" : "",
                $this->common->is_format_enabled("mods") ? "<metadataFormat>
                <metadataPrefix>mods-xml</metadataPrefix>
                <schema>http://www.loc.gov/standards/mods/v3/mods-3-7.xsd</schema>
                <metadataNamespace>http://www.loc.gov/mods/v3</metadataNamespace>
                </metadataFormat>" : "",
                $this->common->is_format_enabled("marc21xml") ? "<metadataFormat>
                <metadataPrefix>MARC21-xml</metadataPrefix>
                <schema>http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd</schema>
                <metadataNamespace>http://www.loc.gov/MARC21/slim</metadataNamespace>
                </metadataFormat>" : "",
                "</ListMetadataFormats>",
            ));
            return $this->render_response(array("verb" => "ListMetadataFormats"), $xml);
        }

        public function render_get_record($identifier, $metadataPrefix) {
            // example: https://services.dnb.de/oai/repository?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:dnb.de/dnb/1277033072
            if (empty($metadataPrefix)) {
                return $this->render_error("GetRecord", "badArgument", "metadataPrefix required");
            }
            if (!$this->is_valid_metadata_prefix($metadataPrefix)) {
                return $this->render_error("GetRecord", "cannotDisseminateFormat", "invalid metadataPrefix format");
            }
            if (empty($identifier)) {
                return $this->render_error("GetRecord", "badArgument", "identifier required");
            }
            $post_id = $this->get_post_id_from_identifier($identifier);
            if ($post_id <= 0) {
                return $this->render_error("GetRecord", "idDoesNotExist", "invalid identifier");
            }

            $post = get_post($post_id);

            if (!is_post_publicly_viewable($post) && !$post->post_status == "trash") {
                return $this->render_error("GetRecord", "idDoesNotExist", "invalid identifier");
            }

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

        public function render_list_records($metadataPrefix, $from = null, $until = null, $set = null, $resumptionToken = null) {
            return $this->render_list_request("ListRecords", $metadataPrefix, $from, $until, $set, $resumptionToken);
        }

        public function render_list_identifiers($metadataPrefix, $from = null, $until = null, $set = null, $resumptionToken = null) {
            // example: https://services.dnb.de/oai/repository?verb=ListIdentifiers&metadataPrefix=oai_dc&from=2023-01-01&until=2023-01-02
            return $this->render_list_request("ListIdentifiers", $metadataPrefix, $from, $until, $set, $resumptionToken);
        }

        public function action_init() {
            // write rules for OAI-PMH
            add_rewrite_rule('^oai/repository/?([^/]*)', 'index.php?' . $this->common->plugin_name . '=oai-pmh&$matches[1]', 'top');
            add_rewrite_tag('%verb%', '([^&]+)');
            add_rewrite_tag('%identifier%', '([^&]+)');
            add_rewrite_tag('%metadataPrefix%', '([^&]+)');
            add_rewrite_tag('%from%', '([^&]+)');
            add_rewrite_tag('%until%', '([^&]+)');
            add_rewrite_tag('%resumptionToken%', '([^&]+)');
            add_rewrite_tag('%set%', '([^&]+)');
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
        }

    }
}
