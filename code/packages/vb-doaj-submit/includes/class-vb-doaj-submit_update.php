<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_rest.php';

if (!class_exists('VB_DOAJ_Submit_Update')) {

    class VB_DOAJ_Submit_Update
    {
        protected $common;
        protected $status;

        public function __construct($common, $status)
        {
            $this->common = $common;
            $this->status = $status;
        }

        public function do_update() {
            $this->status->clear_last_error();
            $this->status->set_last_update();
            $batch = (int)$this->common->get_settings_field_value("batch");
            $batch = $batch < 1 ? 1 : $batch;

            $rest = new VB_DOAJ_Submit_REST($this->common, $this->status);

            // iterate over all posts that require update because they were modified recently
            $modified_query = $this->status->query_posts_that_need_submitting($batch);
            if ($modified_query->post_count > 0) {
                foreach($modified_query->posts as $post) {
                    $success = false;
                    if ($post->post_status == "publish") {
                        $success = $rest->submit_new_or_existing_post($post);
                    } else if ($post->post_status == "trash") {
                        $success = $rest->submit_trashed_post($post);
                    } else {
                        $this->status->set_last_error("cannot submit post with post status '" . $post->post_status . "'");
                    }

                    if ($success) {
                        // remember last modified date of last post succesfully processed
                        // such that it is not processed again (unlesss modified again)
                        $this->status->set_last_updated_post_modified_date($post);
                    } else {
                        return;
                    }
                }
                return;
            }

            // iterate over all posts that require identifying
            $identify_query = $this->status->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $success = $rest->identify_post($post);
                    if (!$success) {
                        return;
                    }
                }
                return;
            }
        }

        public function do_identify() {
            $this->status->clear_last_error();
            $batch = (int)$this->common->get_settings_field_value("batch");
            $batch = $batch < 1 ? 1 : $batch;

            // iterate over all posts that require identifying
            $identify_query = $this->status->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $this->identify_post($post);
                }
            }
        }

        public function identify_post($post)
        {
            $title = rawurlencode(get_the_title($post));
            if (empty($title)) {
                $this->status->set_last_error("[ Post id=" . $post->ID . "] post has no title?!");
                return;
            }

            $issn = rawurlencode($this->common->get_settings_field_value("eissn"));
            if (empty($issn)) {
                $issn = rawurlencode($this->common->get_settings_field_value("pissn"));
            }

            if (empty($issn)) {
                $this->status->set_last_error("Either eISSN or pISSN needs to be provided!");
                return;
            }

            $baseurl = $this->common->get_settings_field_value("api_baseurl");
            $url = $baseurl . "search/articles/bibjson.title.exact:%22{$title}%22%20AND%20issn:%22{$issn}%22";
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                $this->status->set_last_error("[Request Error] " . $response->get_error_message());
                return;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $this->status->set_last_error("search request has invalid status code '" . $status_code . "'");
                return;
            }
            $json_data = json_decode(wp_remote_retrieve_body($response));
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->status->set_last_error("response is invalid json");
                return;
            }

            if ($json_data->total == 1 && count($json_data->results) == 1) {
                // exact match found
                $article_id = $json_data->results[0]->id;
                $this->status->set_post_article_id($post, $article_id);
            }

            $this->status->set_post_identify_timestamp($post);
        }

        public function action_init() {
            if (!has_action($this->common->plugin_name . "_update") ) {
                add_action($this->common->plugin_name . "_update", array($this, 'do_update'));
            }
            $update_enabled = $this->common->get_settings_field_value("auto_update");
            if ($update_enabled) {
                if (!wp_next_scheduled($this->common->plugin_name . "_update") ) {
                    wp_schedule_event( time(), $this->common->plugin_name . "_schedule", $this->common->plugin_name . "_update");
                }
            } else {
                wp_clear_scheduled_hook($this->common->plugin_name . "_update");
            }
        }

        public function get_update_interval_in_minutes() {
            $interval = (int)$this->common->get_settings_field_value("interval");
            $interval = $interval < 1 ? 5 : $interval;
            return $interval;
        }

        public function cron_schedules($schedules) {
            $minutes = $this->get_update_interval_in_minutes();
            $schedules[$this->common->plugin_name . "_schedule"] = array(
                'interval' => $minutes * 60,
                'display' => "Every " . $minutes . " minutes",
            );
            return $schedules;
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
            add_filter("cron_schedules", array($this, 'cron_schedules'));
        }

    }

}