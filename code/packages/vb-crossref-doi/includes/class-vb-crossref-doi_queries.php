<?php

require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_common.php';
require_once plugin_dir_path(__FILE__) . './class-vb-crossref-doi_status.php';

if (!class_exists('VB_CrossRef_DOI_Queries')) {

    class VB_CrossRef_DOI_Queries
    {
        protected $common;

        protected $status;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->status = new VB_CrossRef_DOI_Status($plugin_name);
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

        protected function add_post_selection_arguments_to_query(&$query_args)
        {
            $submit_all_posts = $this->common->get_settings_field_value("submit_all_posts");
            $include_post_category = $this->common->get_settings_field_value("include_post_category");


            if ($submit_all_posts) {
                // exclude posts from the exclude category
                $exclude_post_category = $this->common->get_settings_field_value("exclude_post_category");
                if (!empty($exclude_post_category)) {
                    $exclude_category_id = get_cat_ID($exclude_post_category);
                    if ($exclude_category_id > 0) {
                        $query_args["category__not_in"] = array($exclude_category_id);
                    }
                }
            } else {
                // include posts from the include category
                $include_post_category = $this->common->get_settings_field_value("include_post_category");
                $include_category_id = get_cat_ID($include_post_category);
                $query_args["category__in"] = array($include_category_id);
            }
        }

        public function query_posts_that_need_submitting($batch)
        {
            $modified = $this->query_posts_that_need_submitting_because_modified($batch);
            $retried = $this->query_posts_that_should_be_retried($batch);
            $not_yet = $this->query_posts_that_were_not_submitted_yet($batch);

            $combined = new WP_Query();
            $combined->posts = array_merge($modified->posts, $retried->posts, $not_yet->posts);
            $combined->posts = array_slice($combined->posts, 0, $batch);
            $combined->post_count = min($batch, $modified->post_count + $retried->post_count + $not_yet->post_count);

            return $combined;
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
                            'key' => $this->common->get_post_doi_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_post_doi_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_post_submit_status_meta_key(),
                            'compare' => "NOT EXISTS",
                        ),
                        array(
                            'key' => $this->common->get_post_submit_status_meta_key(),
                            'value' => "",
                            'compare' => "==",
                        ),
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_post_selection_arguments_to_query($query_args);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_should_be_retried()
        {
            $query = $this->query_posts_that_should_be_retried(false);
            return $query->post_count;
        }

        public function query_posts_that_should_be_retried($batch)
        {
            $retry_minutes = $this->common->get_settings_field_value("retry_minutes");
            $retry_timestamp = $this->common->get_current_utc_timestamp() - $retry_minutes * 60;

            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => $this->common->get_post_submit_status_meta_key(),
                            'value' => VB_CrossRef_DOI_Status::SUBMIT_PENDING,
                            'compare' => "==",
                        ),
                        array(
                            'key' => $this->common->get_post_submit_status_meta_key(),
                            'value' => VB_CrossRef_DOI_Status::SUBMIT_ERROR,
                            'compare' => "==",
                        )
                    ),
                    array(
                        'key' => $this->common->get_post_submit_timestamp_meta_key(),
                        'value' => $retry_timestamp,
                        'compare' => "<",
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_post_selection_arguments_to_query($query_args);
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
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_CrossRef_DOI_Status::SUBMIT_MODIFIED,
                        'compare' => "==",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_post_selection_arguments_to_query($query_args);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_have_doi()
        {
            $query = $this->query_posts_that_have_doi(false);
            return $query->post_count;
        }

        protected function query_posts_that_have_doi($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_doi_meta_key(),
                        'value' => "",
                        'compare' => "!=",
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
                        'value' => VB_CrossRef_DOI_Status::SUBMIT_SUCCESS,
                        'compare' => "==",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_were_modified_since_last_check()
        {
            $query = $this->query_posts_that_were_modified_since_last_check();
            return $query->post_count;
        }

        public function query_posts_that_were_modified_since_last_check()
        {
            $last_check_date = $this->status->get_date_of_last_modified_check();
            $after_utc = $this->common->subtract_timezone_offset_from_utc_iso8601($this->common->date_to_iso8601($last_check_date));
            $after = $this->common->subtract_timezone_offset_from_utc_iso8601($after_utc);
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'date_query' => array(
                    'column' => 'post_modified_gmt',
                    array(
                        'after'     => $after,
                        'inclusive' => false,
                    ),
                ),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_CrossRef_DOI_Status::SUBMIT_MODIFIED,
                        'compare' => "!=",
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, false);
            $this->add_post_selection_arguments_to_query($query_args);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_have_pending_submissions()
        {
            $query = $this->query_posts_that_have_pending_submissions(false);
            return $query->post_count;
        }

        public function query_posts_that_have_pending_submissions($batch)
        {
            $timeout_minutes = $this->common->get_settings_field_value("timeout_minutes");
            $timeout_timestamp = $this->common->get_current_utc_timestamp() - $timeout_minutes * 60;

            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_post_submit_status_meta_key(),
                        'value' => VB_CrossRef_DOI_Status::SUBMIT_PENDING,
                        'compare' => "==",
                    ),
                    array(
                        'key' => $this->common->get_post_submit_timestamp_meta_key(),
                        'value' => $timeout_timestamp,
                        'compare' => ">",
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_post_selection_arguments_to_query($query_args);
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
                        'compare' => "==",
                    ),
                    array(
                        'key' => $this->common->get_post_submit_error_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            $this->add_post_selection_arguments_to_query($query_args);
            return new WP_Query( $query_args );
        }
    }
}