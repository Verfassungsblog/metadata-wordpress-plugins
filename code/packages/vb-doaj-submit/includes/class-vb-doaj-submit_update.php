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
            $rest = new VB_DOAJ_Submit_REST($this->common, $this->status);

            // iterate over all posts that require identifying
            $identify_query = $this->status->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $success = $rest->identify_post($post);
                    if (!$success) {
                        return;
                    }
                }
            }
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