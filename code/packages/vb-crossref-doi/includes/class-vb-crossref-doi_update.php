<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_rest.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_status.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_queries.php';

if (!class_exists('VB_CrossRef_DOI_Update')) {

    class VB_CrossRef_DOI_Update
    {
        protected $common;
        protected $status;
        protected $queries;
        protected $rest;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->status = new VB_CrossRef_DOI_Status($plugin_name);
            $this->queries = new VB_CrossRef_DOI_Queries($plugin_name);
            $this->rest = new VB_CrossRef_DOI_REST($plugin_name);
        }

        protected function submit_posts_from_query($query)
        {
            if ($query->post_count > 0) {
                foreach($query->posts as $post) {
                    $success = false;
                    if ($post->post_status == "publish") {
                        $success = $this->rest->submit_new_or_existing_post($post);
                    } else {
                        $this->status->set_last_error("cannot submit post with post status '" . $post->post_status . "'");
                    }

                    if ($success) {
                        // TODO
                    } else {
                        return true;
                    }
                }
                return true;
            }
            return false;
        }

        public function do_update() {
            $this->status->clear_last_error();
            $this->status->set_last_update();
            $batch = (int)$this->common->get_settings_field_value("batch");
            $batch = $batch < 1 ? 1 : $batch;

            // iterate over all posts that were never submitted before
            $not_submitted_yet_query = $this->queries->query_posts_that_were_not_submitted_yet($batch);
            if ($this->submit_posts_from_query($not_submitted_yet_query)) {
                // stop if any posts were submitted
                return;
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