<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_rest.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_status.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_queries.php';

if (!class_exists('VB_DOAJ_Submit_Update')) {

    class VB_DOAJ_Submit_Update
    {
        protected $common;
        protected $status;
        protected $queries;
        protected $rest;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($plugin_name);
            $this->queries = new VB_DOAJ_Submit_Queries($plugin_name);
            $this->rest = new VB_DOAJ_Submit_REST($plugin_name);
        }

        protected function submit_posts_from_query($query)
        {
            if ($query->post_count > 0) {
                foreach($query->posts as $post) {
                    $success = false;
                    if ($post->post_status == "publish") {
                        $success = $this->rest->submit_new_or_existing_post($post);
                    } else if ($post->post_status == "trash") {
                        $success = $this->rest->submit_trashed_post($post);
                    } else {
                        $this->status->set_last_error("cannot submit post with post status '" . $post->post_status . "'");
                    }

                    if (!$success) {
                        return true;
                    }
                }
                return true;
            }
            return false;
        }

        public function check_for_modified_posts()
        {
            // check posts that need updating because modified
            $modified_query = $this->queries->query_posts_that_were_modified_since_last_check();
            foreach ($modified_query->posts as $post_id) {
                $post = $post = new stdClass();
                $post->ID = $post_id;
                $this->status->set_post_submit_status($post, VB_CrossRef_DOI_Status::SUBMIT_MODIFIED);
            }
            $this->status->set_date_of_last_modified_check();
        }

        public function mark_all_posts_as_modified()
        {
            $this->status->set_date_of_last_modified_check(1);
            $this->check_for_modified_posts();
        }

        public function do_update() {
            $this->status->clear_last_error();
            $this->status->set_last_update();
            $batch = (int)$this->common->get_settings_field_value("batch");
            $batch = $batch < 1 ? 1 : $batch;

            $this->check_for_modified_posts();

            // iterate over all posts that require update because they were modified recently
            $modified_query = $this->queries->query_posts_that_need_submitting_because_modified($batch);
            if ($this->submit_posts_from_query($modified_query)) {
                // stop if any posts were submitted
                return;
            }

            // iterate over all posts that were never submitted before
            $not_submitted_yet_query = $this->queries->query_posts_that_were_not_submitted_yet($batch);
            if ($this->submit_posts_from_query($not_submitted_yet_query)) {
                // stop if any posts were submitted
                return;
            }

            // iterate over all posts that require identifying
            $identify_query = $this->queries->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $success = $this->rest->identify_post($post);
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
            $identify_query = $this->queries->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $success = $this->rest->identify_post($post);
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