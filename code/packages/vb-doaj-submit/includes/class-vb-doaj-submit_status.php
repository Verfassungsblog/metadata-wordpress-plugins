<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';

if (!class_exists('VB_DOAJ_Submit_Status')) {

    class VB_DOAJ_Submit_Status
    {
        protected $common;

        public function __construct($common)
        {
            $this->common = $common;
        }

        public function set_last_update() {
            return update_option($this->common->plugin_name . "_status_last_update", time());
        }

        public function get_last_update_date() {
            $timestamp = get_option($this->common->plugin_name . "_status_last_update", false);
            if (empty($timestamp)) {
                return false;
            }
            return (new DateTime())->setTimestamp($timestamp)->setTimezone(wp_timezone());
        }

        public function get_last_update_text() {
            $date = $this->get_last_update_date();
            if (empty($date)) {
                return "never";
            }
            return human_time_diff($date->getTimestamp()) . " ago";
        }

        public function set_last_updated_post_modified_date($post) {
            return update_option($this->common->plugin_name . "_modified_date_of_last_updated_post", $post->post_modified_gmt);
        }

        public function get_last_updated_post_modified_date() {
            $date = get_option($this->common->plugin_name . "_modified_date_of_last_updated_post", false);
            if (empty($date)) {
                return false;
            }
            $datetime = new DateTime();
            $datetime->setTimezone(new DateTimeZone("UTC"));
            $datetime->setTimestamp(strtotime($date));
            return $datetime;
        }

        public function clear_last_updated_post_modified_date() {
            delete_option($this->common->plugin_name . "_modified_date_of_last_updated_post");
        }

        public function get_last_updated_post_modified_text() {
            $date = $this->get_last_updated_post_modified_date();
            if (empty($date)) {
                return "no post submitted yet";
            }
            $date->setTimezone(wp_timezone());
            $timestamp = $date->getTimestamp() + $date->getOffset();
            return date_i18n(get_option('date_format'), $timestamp) . " at " . date_i18n(get_option('time_format'), $timestamp);
        }

        public function get_number_of_posts_that_need_submitting()
        {
            $query = $this->query_posts_that_need_submitting(false);
            return $query->post_count;
        }

        public function query_posts_that_need_submitting($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish', 'trash'),
                'order_by' => 'modified',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => $this->common->plugin_name . "_doaj_identify_timestamp",
                            'compare' => "EXIST",
                        ),
                    ),
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            // articles that have been modified since date
            $since = $this->get_last_updated_post_modified_date();
            if ($since) {
                // convert to UTC (even though time is already UTC, because date_query applies reverse transform)
                $after = $this->common->local_to_utc_iso8601($this->common->date_to_iso8601($since));
                $query_args['date_query'] = array(
                    'column' => 'post_modified_gmt',
                    array(
                        'after'     => $after,
                        'inclusive' => false,
                    ),
                );
            }

            $require_doi = $this->common->get_settings_field_value("require_doi");
            if ($require_doi) {
                $doi_acf_key = $this->common->get_settings_field_value("doi_acf");
                $query_args['meta_query'] = array_merge($query_args['meta_query'], array(
                    array(
                        'key' => $doi_acf_key,
                        'value' => "",
                        'compare' => "!=",
                    ),
                ));
            }

            return new WP_Query( $query_args );
        }

        public function query_posts_that_need_identifying($batch)
        {
            $require_doi = $this->common->get_settings_field_value("require_doi");

            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'order_by' => 'modified',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->plugin_name . "_doaj_identify_timestamp",
                        'compare' => "NOT EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_doaj_article_id_key(),
                        'compare' => "NOT EXISTS",
                    )
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            if ($require_doi) {
                $doi_acf_key = $this->common->get_settings_field_value("doi_acf");
                $query_args['meta_query'] = array_merge($query_args['meta_query'], array(
                    array(
                        'key' => $doi_acf_key,
                        'value' => "",
                        'compare' => "!=",
                    ),
                ));
            }

            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_need_identifying()
        {
            $query = $this->query_posts_that_need_identifying(false);
            return $query->post_count;
        }

        public function query_posts_that_were_identified($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->common->plugin_name . "_doaj_identify_timestamp",
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_doaj_article_id_key(),
                        'compare' => "EXISTS",
                    ),
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_have_article_id()
        {
            $query = $this->query_posts_that_have_article_id(false);
            return $query->post_count;
        }

        public function query_posts_that_have_article_id($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->common->get_doaj_article_id_key(),
                        'compare' => "EXISTS",
                    ),
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_were_identified()
        {
            $query = $this->query_posts_that_were_identified(false);
            return $query->post_count;
        }

        public function query_posts_that_are_submitted($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->plugin_name . "_doaj_submit_timestamp",
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_doaj_article_id_key(),
                        'compare' => "EXISTS",
                    )
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_are_submitted()
        {
            $query = $this->query_posts_that_are_submitted(false);
            return $query->post_count;
        }

        public function action_init() {
            add_option($this->common->plugin_name . "_status_last_update", "never");
        }

        public function run() {
            add_action("init", array($this, 'action_init'));
        }

    }

}