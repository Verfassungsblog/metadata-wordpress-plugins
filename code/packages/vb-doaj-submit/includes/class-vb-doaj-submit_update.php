<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

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
            $this->status->set_last_update();
            $batch = (int)$this->common->get_settings_field_value("batch");
            $batch = $batch < 1 ? 1 : $batch;

            // iterate over all posts that require identifying
            $identify_query = $this->status->query_posts_that_need_identifying($batch);
            if ($identify_query->post_count > 0) {
                foreach($identify_query->posts as $post) {
                    $this->identify_post($post);
                }
                return;
            }

            // iterate over all posts that require update because they were modified recently
            $modified_query = $this->status->query_posts_that_need_submitting($batch);
            if ($modified_query->post_count > 0) {
                foreach($modified_query->posts as $post) {
                    $this->submit_post($post, true);
                }
                return;
            }
        }

        public function do_identify() {
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
            $issn = rawurlencode($this->common->get_settings_field_value("eissn"));
            if (empty($issn)) {
                $issn = rawurlencode($this->common->get_settings_field_value("pissn"));
            }

            if (!empty($title) && !empty($issn)) {
                $url = "https://doaj.org/api/search/articles/bibjson.title.exact:%22{$title}%22%20AND%20issn:%22{$issn}%22";
                $response = wp_remote_get($url);
                $json_data = json_decode(wp_remote_retrieve_body($response));

                if ($json_data->total == 1 && count($json_data->results) == 1) {
                    $article_id = $json_data->results[0]->id;

                    update_post_meta(
                        $post->ID,
                        $this->common->get_doaj_article_id_key(),
                        $article_id,
                    );
                }
            }

            update_post_meta(
                $post->ID,
                $this->common->plugin_name . "_doaj_identify_timestamp",
                (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp()
            );
        }

        public function submit_post($post, $update_modified)
        {
            // TODO: do http request

            // TODO: parse response
            $article_id = uniqid();

            update_post_meta(
                $post->ID,
                $this->common->get_doaj_article_id_key(),
                $article_id,
            );

            update_post_meta(
                $post->ID,
                $this->common->plugin_name . "_doaj_submit_timestamp",
                (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp()
            );

            update_post_meta(
                $post->ID,
                $this->common->plugin_name . "_doaj_identify_timestamp",
                (new DateTime("now", new DateTimeZone("UTC")))->getTimestamp()
            );

            if ($update_modified) {
                $this->status->set_last_updated_post_modified_date($post);
            }
        }

        public function reset_all_posts()
        {
            $this->status->clear_last_updated_post_modified_date();
            $identified_query = $this->status->query_posts_that_were_identified(false);
            foreach($identified_query->posts as $post)
            {
                delete_post_meta($post->ID, $this->common->plugin_name . "_doaj_identify_timestamp");
                delete_post_meta($post->ID, $this->common->plugin_name . "_doaj_submit_timestamp");
                delete_post_meta($post->ID, $this->common->get_doaj_article_id_key());
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