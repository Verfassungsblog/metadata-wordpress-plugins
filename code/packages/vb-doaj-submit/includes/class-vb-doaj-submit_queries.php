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

        protected function add_batch_arguments_to_query(&$query_args, $batch)
        {
            if ($batch) {
                $query_args['posts_per_page'] = $batch;
                $query_args['no_found_rows'] = true;
                $query_args['orderby'] = 'modified';
                $query_args['order'] = 'DESC';
            } else {
                $query_args['nopaging'] = true;
                $query_args['fields'] = 'ids';
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
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'value' => "",
                            'compare' => "!=",
                        ),
                        array(
                            'key' => $this->common->get_doaj_article_id_meta_key(),
                            'value' => "",
                            'compare' => "!=",
                        ),
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => $this->common->get_submit_timestamp_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
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
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_DOAJ_Submit_Status::SUBMIT_MODIFIED,
                        'compare' => "=",
                    ),
                    array(
                        'key' => $this->common->get_doaj_article_id_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_doi_requirement_to_query($query_args);

            return new WP_Query( $query_args );
        }

        public function query_posts_that_need_identifying($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_identify_timestamp_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_doaj_article_id_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_doaj_article_id_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_doi_requirement_to_query($query_args);

            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_need_identifying()
        {
            $query = $this->query_posts_that_need_identifying(false);
            return $query->post_count;
        }

        public function get_number_of_posts_that_were_successfully_identified()
        {
            $query = $this->query_posts_that_were_successfully_identified(false);
            return $query->post_count;
        }

        protected function query_posts_that_were_successfully_identified($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->common->get_identify_timestamp_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                    array(
                        'key' => $this->common->get_doaj_article_id_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_have_article_id()
        {
            $query = $this->query_posts_that_have_article_id(false);
            return $query->post_count;
        }

        protected function query_posts_that_have_article_id($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_doaj_article_id_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

        protected function query_posts_that_were_successfully_submitted($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_DOAJ_Submit_Status::SUBMIT_SUCCESS,
                        'compare' => "=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_were_successfully_submitted()
        {
            $query = $this->query_posts_that_were_successfully_submitted(false);
            return $query->post_count;
        }

        public function get_number_of_posts_that_were_modified_since_last_check()
        {
            $query = $this->query_posts_that_were_modified_since_last_check();
            return $query->post_count;
        }

        public function query_posts_that_were_modified_since_last_check()
        {
            $last_check_date = $this->status->get_date_of_last_modified_check();
            $after_utc = $this->common->local_to_utc_iso8601($this->common->date_to_iso8601($last_check_date));
            $after = $this->common->local_to_utc_iso8601($after_utc);
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish', 'trash'),
                'ignore_sticky_posts' => true,
                'date_query' => array(
                    'column' => 'post_modified_gmt',
                    array(
                        'after'     => $after,
                        'inclusive' => false,
                    ),
                ),
                'meta_query' => array(),
            );

            $this->add_batch_arguments_to_query($query_args, false);
            $this->add_doi_requirement_to_query($query_args);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_with_submit_error()
        {
            $query = $this->query_posts_with_submit_error(false);
            return $query->post_count;
        }

        public function query_posts_with_submit_error($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_CrossRef_DOI_Status::SUBMIT_ERROR,
                        'compare' => "=",
                    ),
                    array(
                        'key' => $this->common->get_post_submit_error_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

    }

}