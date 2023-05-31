<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_status.php';

if (!class_exists('VB_CrossRef_DOI_REST')) {

    class VB_CrossRef_DOI_REST
    {
        protected $common;
        protected $status;
        protected $last_crossref_request_time;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->status = new VB_CrossRef_DOI_Status($plugin_name);
            $this->last_crossref_request_time = null;
        }

        protected function wait_for_next_request()
        {
            if (!empty($this->last_crossref_request_time)) {
                $last_request_ms = (int)$this->last_crossref_request_time->format('Uv');

                $now = new DateTime("now", new DateTimeZone("UTC"));
                $now_ms = (int)$now->format('Uv');

                $diff_ms = $now_ms - $last_request_ms;

                $max_requests_per_second = (float)$this->common->get_settings_field_value("requests_per_second");
                $max_requests_per_second = $max_requests_per_second < 1.0 ? 1.0 : $max_requests_per_second;
                $minimum_time_between_requests_ms = 1000 / $max_requests_per_second;

                if ($minimum_time_between_requests_ms > $diff_ms) {
                    $wait_time_ms = $minimum_time_between_requests_ms - $diff_ms;

                    // wait for next request in order not to trigger CrossRef rate limit
                    usleep($wait_time_ms * 1000);
                }
            }
        }

        protected function update_last_request_time()
        {
            // save last request time for next request
            $this->last_crossref_request_time = new DateTime("now", new DateTimeZone("UTC"));
        }

        protected function build_multipart_body($boundary, $api_user, $api_password, $xml)
        {
            $data = "";

            $form_elements = array(
                "usr" => $api_user,
                "pwd" => $api_password,
                "operation" => "doMDUpload",
            );

            $keys = array("usr", "pwd", "operation");
            foreach($keys as $key) {
                $data .= "--" . $boundary . "\r\n";
                $data .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n\r\n";
                $data .= $form_elements[$key] . "\r\n";
            }

            $data .= "--" . $boundary . "\r\n";
            $data .= "Content-Disposition: form-data; name=\"mdFile\"; filename=\"deposit.xml\"\r\n";
            $data .= "Content-Type: application/xml\r\n\r\n";
            $data .= $xml . "\r\n";
            $data .= "--" . $boundary . "--\r\n";
            return $data;
        }

        public function submit_new_or_existing_post($post)
        {
            $renderer = new VB_CrossRef_DOI_Render($this->common->plugin_name);
            $xml = $renderer->render($post);
            if (empty($xml)) {
                $this->status->set_last_error($renderer->get_last_error());
                return false;
            }

            $api_user = $this->common->get_settings_field_value("api_user");
            if (empty($api_user)) {
                $this->status->set_last_error("CrossRef API user required for submitting new posts");
                return false;
            }

            $api_password = $this->common->get_settings_field_value("api_password");
            if (empty($api_password)) {
                $this->status->set_last_error("CrossRef API password required for submitting new posts");
                return false;
            }

            // build request url
            $api_url = $this->common->get_settings_field_value("api_url");
            if (empty($api_url)) {
                $this->status->set_last_error("CrossRef API URL can not be empty!");
                return false;
            }

            // build request content
            $boundary = bin2hex(random_bytes(20));
            $body = $this->build_multipart_body($boundary, $api_user, $api_password, $xml);

            // do http request
            $this->wait_for_next_request();
            $response = wp_remote_request($api_url, array(
                "method" => "POST",
                "headers" => array(
                    "Content-Type" => "multipart/form-data; boundary=" . $boundary,
                    "Accept" =>  "application/xml",
                ),
                "timeout" => 120,
                "body" => $body,
            ));
            $this->update_last_request_time();

            // validate response
            if (is_wp_error($response)) {
                $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 401) {
                $this->status->set_last_error("CrossRef API Key not correct (status code '" . $status_code . "')");
                return false;
            } else if ($status_code == 403) {
                // CrossRef provides an XML document with errors
                $response_body = wp_remote_retrieve_body($response);
                $response_xml = new \DOMDocument();
                $response_xml->loadXML($response_body);
                $batch_id = $response_xml->getElementsByTagName('batch_id')->item(0)->nodeValue;
                $msg = $response_xml->getElementsByTagName('msg')->item(0)->nodeValue;
                $this->status->set_last_error("CrossRef request failed with status code '" . $status_code . "' and
                    message '" . $msg . "' (batch id '" . $batch_id . "')");
            } else if ($status_code !== 200) {
                $this->status->set_last_error("CrossRef request has invalid status code '" . $status_code . "'");
                return false;
            }

            // parse success response
            $response_body = wp_remote_retrieve_body($response);
            $response_xml = new \DOMDocument();
            $response_xml->loadXML($response_body);
            $batch_id = $response_xml->getElementsByTagName('batch_id')->item(0)->nodeValue;
            $submission_id = $response_xml->getElementsByTagName('submission_id')->item(0)->nodeValue;
            $success_count = (int)$response_xml->getElementsByTagName('success_count')->item(0)->nodeValue;
            $warning_count = (int)$response_xml->getElementsByTagName('warning_count')->item(0)->nodeValue;
            $failure_count = (int)$response_xml->getElementsByTagName('failure_count')->item(0)->nodeValue;
            if ($success_count < 1 || $failure_count > 0 || $warning_count > 0) {
                $msg = $response_xml->getElementsByTagName('msg')->item(0)->nodeValue;
                $this->status->set_last_error("CrossRef request failed with status code '" . $status_code . "' and
                    message '" . $msg . "' (batch id '" . $batch_id . "', submission id '" . $submission_id . "')");
                return false;
            }

            // retrieve doi from response
            $doi = $response_xml->getElementsByTagName('doi')->item(0)->nodeValue;

            // update post status
            $this->status->set_post_doi($post, $doi);
            $this->status->set_post_submit_timestamp($post);
            $this->status->set_post_submit_batch_id($post, $batch_id);
            $this->status->set_post_submit_submission_id($post, $submission_id);
            return true;
        }

    }

}