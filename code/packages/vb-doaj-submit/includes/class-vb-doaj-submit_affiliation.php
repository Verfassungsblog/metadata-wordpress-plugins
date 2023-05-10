<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Affiliation')) {

    class VB_DOAJ_Submit_Affiliation
    {

        protected $common;
        protected $status;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($plugin_name);
        }

        protected function get_textual_author_affiliation($user_id)
        {
            return trim($this->common->get_user_meta_field_value("affiliation_meta_key", $user_id));
        }

        protected function get_rorid_author_affiliation($user_id)
        {
            $rorid = trim($this->common->get_user_meta_field_value("rorid_meta_key", $user_id));
            if (empty($rorid)) {
                return false;
            }

            $rorid_encoded = rawurlencode($rorid);
            $url = "https://api.ror.org/organizations?query=%22{$rorid_encoded}%22";
            // do http request
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
            ));

            // validate response
            if (is_wp_error($response)) {
                $this->status->set_last_error("Error retrieving affiliation from ROR-ID: " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $this->status->set_last_error("Invalid status code '" . $status_code . "' when retrieving affiliation from ROR-ID");
                return false;
            }

            // parse response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                // invalid json
                $this->status->set_last_error("Invalid json response when retrieving affiliation from ROR-ID");
                return false;
            }
            if ($json_data->number_of_results != 1 || count($json_data->items) != 1) {
                $this->status->set_last_error("Invalid ROR-ID while retrieving affiliation");
                return false;
            }

            return $json_data->items[0]->name . ", " . $json_data->items[0]->country->country_name;
        }

        protected function find_orcid_author_affiliation($user_id)
        {
            $orcid = trim($this->common->get_user_meta_field_value("orcid_meta_key", $user_id));
            if (empty($orcid)) {
                return false;
            }

            $orcid_encoded = rawurlencode($orcid);

            $url = "https://pub.orcid.org/v3.0/{$orcid_encoded}/record";
            // do http request
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/xml",
                ),
                "timeout" => 30,
            ));

            // validate response
            if (is_wp_error($response)) {
                $this->status->set_last_error("Error retrieving affiliation from ORCID: " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $this->status->set_last_error("Invalid status code '" . $status_code . "' when retrieving affiliation from ORCID");
                return false;
            }

            // parse response
            $xml_data = wp_remote_retrieve_body($response);
            $xml = new SimpleXMLElement($xml_data);
            $organization_array = $xml->xpath('/record:record/activities:activities-summary/activities:employments/activities:affiliation-group/employment:employment-summary/common:organization');
            if (!$organization_array || count($organization_array) < 1) {
                // no employment info
                return false;
            }
            $name_array = $organization_array[0]->xpath("common:name");
            $country_array = $organization_array[0]->xpath("common:address/common:country");
            if (!$name_array || count($name_array) < 1 || !$country_array || count($country_array) < 1) {
                // no employment name
                return false;
            }
            return $name_array[0]->__toString() . ", " .$country_array[0]->__toString();
        }

        protected function find_author_affiliation($user_id)
        {
            $affiliation = $this->get_textual_author_affiliation($user_id);
            if (empty($affiliation)) {
                $affiliation = $this->get_rorid_author_affiliation($user_id);
            }
            if (empty($affiliation)) {
                $affiliation = $this->find_orcid_author_affiliation($user_id);
            }
            return $affiliation;
        }

        protected function get_post_coauthors($post)
        {
            if (!function_exists("get_coauthors")) {
                return array();
            }
            return array_slice(get_coauthors($post->ID), 1);
        }

        public function save_author_affiliations_for_post($post)
        {
            $affiliations = array();

            // add post author
            $post_author_login = get_the_author_meta("user_login", $post->post_author);
            $affiliations[$post_author_login] = $this->find_author_affiliation($post->post_author);

            // add post coauthors
            foreach ($this->get_post_coauthors($post) as $coauthor) {
                $coauthor_login = get_the_author_meta("user_login", $coauthor->ID);
                $affiliations[$coauthor_login] = $this->find_author_affiliation($coauthor->ID);
            }
            $affiliations = array_filter($affiliations);
            $author_affiliations_meta_key = $this->common->get_settings_field_value("author_affiliations_meta_key");

            // encode json
            if (!empty($affiliations)) {
                update_post_meta($post->ID, $author_affiliations_meta_key, json_encode($affiliations));
            }
        }

        public function get_author_affiliation_for_post($post, $user_id)
        {
            $author_affiliations_meta_key = $this->common->get_settings_field_value("author_affiliations_meta_key");
            $author_login = get_the_author_meta("user_login", $user_id);

            // decode json
            $json = get_post_meta($post->ID, $author_affiliations_meta_key, true);
            $affiliations = json_decode($json, true);

            if (isset($affiliations[$author_login])) {
                return $affiliations[$author_login];
            }
            return false;
        }

    }

}