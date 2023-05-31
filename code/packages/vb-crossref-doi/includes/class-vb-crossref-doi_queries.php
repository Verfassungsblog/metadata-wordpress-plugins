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
                    'relation' => 'OR',
                    array(
                        'key' => $this->common->get_doi_meta_key(),
                        'compare' => "NOT EXISTS",
                    ),
                    array(
                        'key' => $this->common->get_doi_meta_key(),
                        'value' => "",
                        'compare' => "==",
                    ),
                ),
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
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
                        'key' => $this->common->get_post_needs_update_meta_key(),
                        'compare' => "EXISTS",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
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
                        'key' => $this->common->get_doi_meta_key(),
                        'value' => "",
                        'compare' => "!=",
                    ),
                )
            );

            $this->add_batch_arguments_to_query($query_args, $batch);
            return new WP_Query( $query_args );
        }

        public function get_number_of_posts_that_were_submitted()
        {
            $query = $this->query_posts_that_were_submitted(false);
            return $query->post_count;
        }

        protected function query_posts_that_were_submitted($batch)
        {
            $query_args = array(
                'post_type' => 'post',
                'post_status' => array('publish'),
                'ignore_sticky_posts' => true,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => $this->common->get_submit_timestamp_meta_key(),
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