<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit-common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit-status.php';

if (!class_exists('VB_DOAJ_Submit_REST')) {

    class VB_DOAJ_Submit_REST
    {
        protected $common;
        protected $status;
        protected $affiliation;
        protected $last_doaj_request_time;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($plugin_name);
            $this->last_doaj_request_time = null;
        }

        protected function wait_for_next_request()
        {
            if (!empty($this->last_doaj_request_time)) {
                $last_request_ms = (int)$this->last_doaj_request_time->format('Uv');

                $now = new DateTime("now", new DateTimeZone("UTC"));
                $now_ms = (int)$now->format('Uv');

                $diff_ms = $now_ms - $last_request_ms;

                $max_requests_per_second = (float)$this->common->get_settings_field_value("requests_per_second");
                $max_requests_per_second = $max_requests_per_second < 1.0 ? 1.0 : $max_requests_per_second;
                $minimum_time_between_requests_ms = 1000 / $max_requests_per_second;

                if ($minimum_time_between_requests_ms > $diff_ms) {
                    $wait_time_ms = $minimum_time_between_requests_ms - $diff_ms;

                    // wait for next request in order not to trigger DOAJ rate limit
                    usleep($wait_time_ms * 1000);
                }
            }
        }

        protected function update_last_request_time()
        {
            // save last request time for next request
            $this->last_doaj_request_time = new DateTime("now", new DateTimeZone("UTC"));
        }

        protected function parse_repones_for_error($post, $response, $expected_status_code)
        {
            // validate response
            if (is_wp_error($response)) {
                $error = "[Request Error] " . $response->get_error_message();
                $this->status->set_last_error($error);
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 400 || $status_code == 401 || $status_code == 403) {
                // parse error response
                $json_data = json_decode(wp_remote_retrieve_body($response));
                if (json_last_error() != JSON_ERROR_NONE) {
                    $error = "DOAJ responded with unknown error (status code '" . $status_code . "')";
                } else {
                    $msg = $json_data->error;
                    $error = "DOAJ responded with error '" . $msg . "' (status code '" . $status_code . "')";
                }
                $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_ERROR);
                $this->status->set_post_submit_error($post, $error);
                $this->status->set_last_error($error);
                return false;
            } else if ($status_code !== $expected_status_code) {
                $this->status->set_last_error("DOAJ responsed with invalid status code '" . $status_code . "'");
                $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_ERROR);
                return false;
            }

            return true;
        }

        public function identify_post($post)
        {
            $article_id = $this->status->get_post_doaj_article_id($post);

            if (!empty($article_id)) {
                // post has DOAJ article id already available, do nothing
                $this->status->set_post_identify_timestamp($post);
                return true;
            }

            $issn = rawurlencode($this->common->get_settings_field_value("eissn"));
            if (empty($issn)) {
                $issn = rawurlencode($this->common->get_settings_field_value("pissn"));
            }

            if (empty($issn)) {
                $this->status->set_last_error("Either eISSN or pISSN needs to be provided!");
                return false;
            }

            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            if (empty($baseurl)) {
                $this->status->set_last_error("DOAJ api base URL can not be empty!");
                return false;
            }

            $identify_by_permalink = $this->common->get_settings_field_value("identify_by_permalink");
            if ($identify_by_permalink) {
                // identify by permalink
                $permalink = rawurlencode(get_the_permalink($post));

                // rewrite staging permalinks
                $permalink = str_replace("staging.verfassungsblog.de", "verfassungsblog.de", $permalink);

                if (empty($permalink)) {
                    $this->status->set_last_error("[ Post id=" . $post->ID . "] has no permalink?!");
                    return false;
                }
                $url = $baseurl . "search/articles/bibjson.link.url.exact:%22{$permalink}%22%20AND%20issn:%22{$issn}%22";
            } else {
                // identify by title
                $title = rawurlencode(get_the_title($post));
                if (empty($title)) {
                    $this->status->set_last_error("[ Post id=" . $post->ID . "] post has no title?!");
                    return false;
                }
                $url = $baseurl . "search/articles/bibjson.title.exact:%22{$title}%22%20AND%20issn:%22{$issn}%22";
            }

            // do http request
            $this->wait_for_next_request();
            $response = wp_remote_request($url, array(
                "method" => "GET",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
            ));
            $this->update_last_request_time();

            if (is_wp_error($response)) {
                $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $this->status->set_last_error("identify request has invalid status code '" . $status_code . "'");
                return false;
            }
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->status->set_last_error("response is invalid json");
                return false;
            }

            if ($json_data->total == 1 && count($json_data->results) == 1) {
                // exact match found
                $article_id = $json_data->results[0]->id;
                $this->status->set_post_doaj_article_id($post, $article_id);
            } else {
                // no match found (article is new)
            }

            $this->status->set_post_identify_timestamp($post);
            return true;
        }

        public function submit_new_or_existing_post($post)
        {
            $renderer = new VB_DOAJ_Submit_Render($this->common->plugin_name);
            $json = $renderer->render($post);
            if (!$json) {
                $this->status->set_last_error($renderer->get_last_error());
                return false;
            }

            $article_id = get_post_meta($post->ID, $this->common->get_doaj_article_id_meta_key(), true);

            $test_without_api_key = $this->common->get_settings_field_value("test_without_api_key");
            if ($test_without_api_key) {
                // set submit timestamp independent of success
                $this->status->set_post_submit_timestamp($post);

                // simulate success or failure
                if (rand(0,1)) {
                    // generate random article id for testing purposes
                    $article_id = empty($article_id) ? uniqid() : $article_id;

                    // update post status
                    $this->status->set_post_doaj_article_id($post, $article_id);
                    $this->status->set_post_identify_timestamp($post);
                    $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_SUCCESS);
                    return true;
                } else {
                    // simulate error
                    $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_ERROR);
                    $this->status->set_post_submit_error($post, "simulated error message");
                    return false;
                }
            }

            $apikey = $this->common->get_settings_field_value("api_key");
            if (empty($apikey)) {
                $this->status->set_last_error("DOAJ api key required for submitting new posts");
                return false;
            }

            // build request url
            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            if (empty($baseurl)) {
                $this->status->set_last_error("DOAJ api base URL can not be empty!");
                return false;
            }
            if (empty($article_id)) {
                // new article
                $url = $baseurl . "articles?api_key=" . rawurlencode($apikey);
            } else {
                // update existing article
                $url = $baseurl . "articles/" . rawurlencode($article_id) . "?api_key=" . rawurlencode($apikey);
            }

            // wait for rate limit
            $this->wait_for_next_request();

            // save submit timestamp
            $this->status->set_post_submit_timestamp($post);
            $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_PENDING);

            // do http request
            $response = wp_remote_request($url, array(
                "method" => empty($article_id) ? "POST" : "PUT",
                "headers" => array(
                    "Content-Type" => "application/json",
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
                "body" => $json,
            ));
            $this->update_last_request_time();

            if (!$this->parse_repones_for_error($post, $response, empty($article_id) ? 201 : 204)) {
                return false;
            }

            // parse success response
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->status->set_last_error("response is invalid json");
                $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_ERROR);
                return false;
            }
            $article_id = $json_data->id;

            // update post status
            $this->status->set_post_doaj_article_id($post, $article_id);
            $this->status->set_post_identify_timestamp($post);
            $this->status->clear_post_submit_error($post);
            $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_SUCCESS);
            return true;
        }

        public function submit_trashed_post($post)
        {
            $article_id = get_post_meta($post->ID, $this->common->get_doaj_article_id_meta_key(), true);

            if (empty($article_id)) {
                // post was never actually submitted, nothing to do here
                $this->status->reset_post_status($post);
                return true;

            }

            $test_without_api_key = $this->common->get_settings_field_value("test_without_api_key");
            if ($test_without_api_key) {
                // update post status
                $this->status->reset_post_status($post);
                return true;
            }

            $apikey = $this->common->get_settings_field_value("api_key");
            if (empty($apikey)) {
                $this->status->set_last_error("DOAJ api key required for deleting articles");
                return false;
            }

            // build request url
            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            if (empty($baseurl)) {
                $this->status->set_last_error("DOAJ api base URL can not be empty!");
                return false;
            }
            $url = $baseurl . "articles/" . rawurlencode($article_id) . "?api_key=" . rawurlencode($apikey);

            // do http request
            $this->wait_for_next_request();

            // save submit timestamp
            $this->status->set_post_submit_timestamp($post);
            $this->status->set_post_submit_status($post, VB_DOAJ_Submit_Status::SUBMIT_PENDING);

            $response = wp_remote_request($url, array(
                "method" => "DELETE",
                "headers" => array(
                    "Accept" =>  "application/json",
                ),
                "timeout" => 30,
            ));
            $this->update_last_request_time();

            if (!$this->parse_repones_for_error($post, $response, 204)) {
                return false;
            }

            // update post status
            $this->status->reset_post_status($post);
            return true;
        }

    }

}