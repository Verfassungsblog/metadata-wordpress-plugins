<?php

require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-doaj-submit_status.php';

if (!class_exists('VB_DOAJ_Submit_Queries')) {

    class VB_DOAJ_Submit_Queries
    {
        protected $common;

        protected $status;

        public function __construct($plugin_name)
        {
            $this->common = new VB_DOAJ_Submit_Common($plugin_name);
            $this->status = new VB_DOAJ_Submit_Status($plugin_name);
        }

        protected function add_doi_requirement_to_query(&$query_args)
        {
            $require_doi = $this->common->get_settings_field_value("require_doi");
            if ($require_doi) {
                $doi_meta_key = $this->common->get_settings_field_value("doi_meta_key");
                $query_args['meta_query'] = array_merge($query_args['meta_query'], array(
                    array(
                        'key' => $doi_meta_key,
                        'value' => "",
                        'compare' => "!=",
                    ),
                ));
            }
        }

        public function get_number_of_posts_that_were_not_submitted_yet()
        {
            $query = $this->query_posts_that_were_not_submitted_yet(false);
            return $query->post_count;
        }

        public function query_posts_that_were_not_submitted_yet($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'orderby' => 'modified',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => $this->common->get_identify_timestamp_meta_key(),
                                'compare' => "EXIST",
                            ),
                            array(
                                'key' => $this->common->get_identify_timestamp_meta_key(),
                                'value' => "",
                                'compare' => "!=",
                            ),
                        ),
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => $this->common->get_article_id_meta_key(),
                                'compare' => "EXISTS",
                            ),
                            array(
                                'key' => $this->common->get_article_id_meta_key(),
                                'value' => "",
                                'compare' => "!=",
                            ),
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_submit_timestamp_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_submit_timestamp_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                ),
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            $this->add_doi_requirement_to_query($query_args);

            return new WP_Query( $query_args );
        }


        public function get_number_of_posts_that_need_submitting_because_modified()
        {
            $query = $this->query_posts_that_need_submitting_because_modified(false);
            return $query->post_count;
        }

        public function query_posts_that_need_submitting_because_modified($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish', 'trash'),
                'orderby' => 'modified',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_identify_timestamp_meta_key(),
                        'compare' => "EXIST",
                    ),
                    array(
                        'key' => $this->common->get_identify_timestamp_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                    array(
                        'key' => $this->common->get_submit_timestamp_meta_key(),
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_submit_timestamp_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            // articles that have been modified since date
            $since = $this->status->get_last_updated_post_modified_date();
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

            $this->add_doi_requirement_to_query($query_args);

            return new WP_Query( $query_args );
        }

        public function query_posts_that_need_identifying($batch)
        {
            $require_doi = $this->common->get_settings_field_value("require_doi");

            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'orderby' => 'modified',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_article_id_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_article_id_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                ),
            );

            if ($batch) {
                $query_args['posts_per_page'] = $batch;
            } else {
                $query_args['nopaging'] = true;
            }

            $this->add_doi_requirement_to_query($query_args);

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
                'post_status' => array('publish'),
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'compare' => "EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'value' => "",
                            'compare' => "!=",
                        ),
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => $this->common->get_article_id_meta_key(),
                            'compare' => "EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_article_id_meta_key(),
                            'value' => "",
                            'compare' => "!=",
                        ),
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

        public function get_number_of_posts_that_have_article_id()
        {
            $query = $this->query_posts_that_have_article_id(false);
            return $query->post_count;
        }

        public function query_posts_that_have_article_id($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_article_id_meta_key(),
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_article_id_meta_key(),
                        'value' => "",
                        'compare' => "!=",
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

        public function query_posts_that_were_submitted($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_submit_timestamp_meta_key(),
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_submit_timestamp_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                    array(
                        'key' => $this->common->get_article_id_meta_key(),
                        'compare' => "EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_article_id_meta_key(),
                        'value' => "",
                        'compare' => "!=",
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

        public function get_number_of_posts_that_were_submitted()
        {
            $query = $this->query_posts_that_were_submitted(false);
            return $query->post_count;
        }

    }

}