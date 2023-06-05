<?php

require_once plugin_dir_path(__FILE__) . './class-vb-author-affiliations_common.php';

if (!class_exists('VB_Author_Affiliations_REST')) {

    class VB_Author_Affiliations_REST
    {

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Author_Affiliations_Common($plugin_name);
        }

        protected function get_textual_author_affiliation($user_id)
        {
            return trim($this->common->get_user_meta_field_value("affiliation_meta_key", $user_id));
        }

        protected function get_affiliation_from_rorid($user_id)
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
                // $response->get_error_message()
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                // invalid status_code
                return false;
            }

            // parse response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                // invalid json
                return false;
            }
            if ($json_data->number_of_results != 1 || count($json_data->items) != 1) {
                // invalid rorid
                return false;
            }

            return $json_data->items[0]->name . ", " . $json_data->items[0]->country->country_name;
        }

        protected function get_affiliation_from_orcid($user_id)
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
                // $response->get_error_message()
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                // invalid status code
                return false;
            }

            // parse response
            $xml_data = wp_remote_retrieve_body($response);
            $xml = new SimpleXMLElement($xml_data);
            $path = '/record:record/activities:activities-summary/activities:employments/activities:affiliation-group/employment:employment-summary/common:organization';
            $organization_array = $xml->xpath($path);
            if (!$organization_array || count($organization_array) < 1) {
                // no employment info
                return false;
            }
            $name_array = $organization_array[0]->xpath("common:name");
            $country_array = $organization_array[0]->xpath("common:address/common:country");

            if (!$name_array || count($name_array) < 1) {
                // no employment name
                return false;
            }

            $affiliation = $name_array[0]->__toString();

            // add country to affiliation name
            if ($country_array && count($country_array) > 0) {
                // country available
                $affiliation = $affiliation . ", " .$country_array[0]->__toString();
            }
            return $affiliation;
        }

        public function retrieve_author_affiliation($user_id)
        {
            $affiliation = $this->get_textual_author_affiliation($user_id);
            if (!empty($affiliation)) {
                return $affiliation;
            }
            $extract_from_rorid = $this->common->get_settings_field_value("extract_from_rorid");
            if ($extract_from_rorid) {
                $affiliation = $this->get_affiliation_from_rorid($user_id);
                if (!empty($affiliation)) {
                    return $affiliation;
                }
            }
            $extract_from_orcid = $this->common->get_settings_field_value("extract_from_orcid");
            if ($extract_from_orcid) {
                $affiliation = $this->get_affiliation_from_orcid($user_id);
                if (!empty($affiliation)) {
                    return $affiliation;
                }
            }
            return "";
        }
    }

}