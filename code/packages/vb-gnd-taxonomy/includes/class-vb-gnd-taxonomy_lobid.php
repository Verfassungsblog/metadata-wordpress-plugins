<?php

require_once plugin_dir_path(__FILE__) . './class-vb-gnd-taxonomy_common.php';

if (!class_exists('VB_GND_Taxonomy_Lobid')) {

    class VB_GND_Taxonomy_Lobid
    {
        protected $common;

        protected $cache;

        public function __construct($plugin_name)
        {
            $this->common = new VB_GND_Taxonomy_Common($plugin_name);
            $this->cache = array();
        }

        protected function get_gnd_entity_json($gnd_id) {
            $base_url = $this->common->get_settings_field_value("api_baseurl");
            $url = "{$base_url}{$gnd_id}.json";
            if (!array_key_exists($url, $this->cache)) {
                $response = wp_remote_request($url, array(
                    "method" => "GET",
                    "headers" => array(
                        "Accept" =>  "application/json",
                    ),
                    "timeout" => 30,
                ));

                // validate response
                if (is_wp_error($response)) {
                    return "";
                }
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code != 200) {
                    return "";
                }

                // parse response
                $json_data = json_decode(wp_remote_retrieve_body($response));
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return "";
                }

                $this->cache[$url] = $json_data;
                return $json_data;
            }
            return $this->cache[$url];
        }

        public function check_gnd_entity_exists($gnd_id) {
            $json_data = $this->get_gnd_entity_json($gnd_id);
            if (!empty($json_data) && !empty($json_data->gndIdentifier)) {
                return $gnd_id == $json_data->gndIdentifier;
            }
            return false;
        }

        public function load_gnd_entity_description($gnd_id) {
            $json_data = $this->get_gnd_entity_json($gnd_id);
            if (!empty($json_data) && !empty($json_data->definition) && count($json_data->definition) > 0) {
                return $json_data->definition[0];
            }
            return "";
        }

    }

}