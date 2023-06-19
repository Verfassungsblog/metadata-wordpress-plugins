<?php
/**
 * Class providing custom database queries.
 *
 * @package vb-crossref-doi
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-common.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-status.php';

if ( ! class_exists( 'VB_CrossRef_DOI_Queries' ) ) {

	/**
	 * Class providing custom database queries.
	 */
	class VB_CrossRef_DOI_Queries {

		/**
		 * Common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		protected $common;

		/**
		 * Class that manages post submit status.
		 *
		 * @var VB_CrossRef_DOI_Status
		 */
		protected $status;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common = new VB_CrossRef_DOI_Common( $plugin_name );
			$this->status = new VB_CrossRef_DOI_Status( $plugin_name );
		}

		/**
		 * Adds multipe query arguments depending on whether all posts are supposed to be returned (e.g. for
		 * counting), or a specific number of posts are requested (batch size).
		 *
		 * @param array $query_args the query arguments to be extended depending on the batch parameter.
		 * @param int   $batch the batch parameter (0 means all posts, >0 means a maximum limit).
		 */
		protected function add_batch_arguments_to_query( &$query_args, $batch ) {
			if ( $batch ) {
				$query_args['posts_per_page'] = $batch;
				$query_args['no_found_rows']  = true;
				$query_args['orderby']        = 'modified';
				$query_args['order']          = 'DESC';
			} else {
				$query_args['nopaging'] = true;
				$query_args['fields']   = 'ids';
			}
		}

		/**
		 * Adds a category include/exclude filter to existing query arguments based on the post selection
		 * options defined in the admin settings.
		 *
		 * @param array $query_args the query arguments to be extended by an optional category requirements.
		 */
		protected function add_post_selection_arguments_to_query( &$query_args ) {
			$submit_all_posts      = $this->common->get_settings_field_value( 'submit_all_posts' );
			$include_post_category = $this->common->get_settings_field_value( 'include_post_category' );

			if ( $submit_all_posts ) {
				// exclude posts from the exclude category.
				$exclude_post_category = $this->common->get_settings_field_value( 'exclude_post_category' );
				if ( ! empty( $exclude_post_category ) ) {
					$exclude_category_id = get_cat_ID( $exclude_post_category );
					if ( $exclude_category_id > 0 ) {
						$query_args['category__not_in'] = array( $exclude_category_id );
					}
				}
			} else {
				// include posts from the include category.
				$include_post_category      = $this->common->get_settings_field_value( 'include_post_category' );
				$include_category_id        = get_cat_ID( $include_post_category );
				$query_args['category__in'] = array( $include_category_id );
			}
		}

		/**
		 * Returns combined query of posts that need submitting either because the were modified, had an error and
		 * need to be tried again, or a new posts that were never submitted before.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query object that contains all posts that need submitting
		 */
		public function query_posts_that_need_submitting( $batch ) {
			$modified = $this->query_posts_that_need_submitting_because_modified( $batch );
			$retried  = $this->query_posts_that_should_be_retried( $batch );
			$not_yet  = $this->query_posts_that_were_not_submitted_yet( $batch );

			$combined             = new WP_Query();
			$combined->posts      = array_merge( $modified->posts, $retried->posts, $not_yet->posts );
			$combined->posts      = array_slice( $combined->posts, 0, $batch );
			$combined->post_count = min( $batch, $modified->post_count + $retried->post_count + $not_yet->post_count );

			return $combined;
		}

		/**
		 * Return the number of posts that were not submitted yet (meaning they need to be submitted to CrossRef
		 * because they were never submitted before).
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_were_not_submitted_yet() {
			$query = $this->query_posts_that_were_not_submitted_yet( false );
			return $query->post_count;
		}

		/**
		 * Return a WP_Query object for posts that were not submitted yet (meaning they need to be submitted to
		 * CrossRef because they were never submitted before).
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query object that contains all posts that were not submitted yet
		 */
		public function query_posts_that_were_not_submitted_yet( $batch ) {
			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => $this->common->get_post_doi_meta_key(),
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => $this->common->get_post_doi_meta_key(),
							'value'   => '',
							'compare' => '==',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => $this->common->get_post_submit_status_meta_key(),
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => $this->common->get_post_submit_status_meta_key(),
							'value'   => '',
							'compare' => '==',
						),
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that should be submitted again after an error occurred during the last submission
		 * and some time has passed.
		 *
		 * @return int the number of posts.
		 */
		public function get_number_of_posts_that_should_be_retried() {
			$query = $this->query_posts_that_should_be_retried( false );
			return $query->post_count;
		}

		/**
		 * Return posts that should be submitted again after an error occurred during the last submission
		 * and some time has passed.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that should be submitted again after an error occurred
		 */
		public function query_posts_that_should_be_retried( $batch ) {
			$retry_minutes   = $this->common->get_settings_field_value( 'retry_minutes' );
			$retry_timestamp = $this->common->get_current_utc_timestamp() - $retry_minutes * 60;

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => $this->common->get_post_submit_status_meta_key(),
							'value'   => VB_CrossRef_DOI_Status::SUBMIT_PENDING,
							'compare' => '==',
						),
						array(
							'key'     => $this->common->get_post_submit_status_meta_key(),
							'value'   => VB_CrossRef_DOI_Status::SUBMIT_ERROR,
							'compare' => '==',
						),
					),
					array(
						'key'     => $this->common->get_post_submit_timestamp_meta_key(),
						'value'   => $retry_timestamp,
						'compare' => '<',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that need submitting because they were modified since the last submission.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_need_submitting_because_modified() {
			$query = $this->query_posts_that_need_submitting_because_modified( false );
			return $query->post_count;
		}

		/**
		 * Return posts that need submitting because they were modified since the last submission.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query object containing posts that need submitting because they were modified
		 */
		public function query_posts_that_need_submitting_because_modified( $batch ) {
			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_submit_status_meta_key(),
						'value'   => VB_CrossRef_DOI_Status::SUBMIT_MODIFIED,
						'compare' => '==',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that have a DOI assigned to them (independent of submission status).
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_have_doi() {
			$query = $this->query_posts_that_have_doi( false );
			return $query->post_count;
		}

		/**
		 * Return posts that have a DOI assigned to them (independent of submission status).
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query object containing posts that need submitting because they were modified
		 */
		protected function query_posts_that_have_doi( $batch ) {
			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_doi_meta_key(),
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that were successfully submitted to CrossRef.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_were_successfully_submitted() {
			$query = $this->query_posts_that_were_successfully_submitted( false );
			return $query->post_count;
		}

		/**
		 * Return posts that were successfully submitted to CrossRef.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that were succesfully submitted to the DOAJ
		 */
		protected function query_posts_that_were_successfully_submitted( $batch ) {
			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_submit_status_meta_key(),
						'value'   => VB_CrossRef_DOI_Status::SUBMIT_SUCCESS,
						'compare' => '==',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that were modified since the last check for modified posts.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_were_modified_since_last_check() {
			$query = $this->query_posts_that_were_modified_since_last_check();
			return $query->post_count;
		}

		/**
		 * Return all posts that were modified since the last check for modified posts.
		 *
		 * @return WP_Query the query that contains posts that were modified since the last check for modified posts
		 */
		public function query_posts_that_were_modified_since_last_check() {
			$last_check_date = $this->status->get_date_of_last_modified_check();
			$after_utc       = $this->common->subtract_timezone_offset_from_utc_iso8601( $this->common->date_to_iso8601( $last_check_date ) );
			$after           = $this->common->subtract_timezone_offset_from_utc_iso8601( $after_utc );
			$query_args      = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'date_query'          => array(
					'column' => 'post_modified_gmt',
					array(
						'after'     => $after,
						'inclusive' => false,
					),
				),
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_submit_status_meta_key(),
						'value'   => VB_CrossRef_DOI_Status::SUBMIT_MODIFIED,
						'compare' => '!=',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, false );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that that have a pending submission status.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_have_pending_submissions() {
			$query = $this->query_posts_that_have_pending_submissions( false );
			return $query->post_count;
		}

		/**
		 * Return posts that have a pending submission status.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that were succesfully submitted to the DOAJ
		 */
		public function query_posts_that_have_pending_submissions( $batch ) {
			$timeout_minutes   = $this->common->get_settings_field_value( 'timeout_minutes' );
			$timeout_timestamp = $this->common->get_current_utc_timestamp() - $timeout_minutes * 60;

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_submit_status_meta_key(),
						'value'   => VB_CrossRef_DOI_Status::SUBMIT_PENDING,
						'compare' => '==',
					),
					array(
						'key'     => $this->common->get_post_submit_timestamp_meta_key(),
						'value'   => $timeout_timestamp,
						'compare' => '>',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that that have a failed submission status with some error.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_with_submit_error() {
			$query = $this->query_posts_with_submit_error( false );
			return $query->post_count;
		}

		/**
		 * Return posts that that have a failed submission status with some error.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that were succesfully submitted to the DOAJ
		 */
		public function query_posts_with_submit_error( $batch ) {
			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_submit_status_meta_key(),
						'value'   => VB_CrossRef_DOI_Status::SUBMIT_ERROR,
						'compare' => '==',
					),
					array(
						'key'     => $this->common->get_post_submit_error_meta_key(),
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			$this->add_post_selection_arguments_to_query( $query_args );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that are assigned to the include category.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_have_include_category() {
			$query = $this->query_posts_that_have_include_category( false );
			return $query->post_count;
		}

		/**
		 * Return posts that are assigned to the include category.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that are assigned to the include category
		 */
		public function query_posts_that_have_include_category( $batch ) {
			$include_post_category = $this->common->get_settings_field_value( 'include_post_category' );
			$include_category_id   = get_cat_ID( $include_post_category );

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'category__in'        => array( $include_category_id ),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that can be added to the include category, meaning they have a DOI and are not
		 * already in the include category.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_can_be_added_to_include_category() {
			$query = $this->query_posts_that_can_be_added_to_include_category( false );
			return $query->post_count;
		}

		/**
		 * Return posts that can be added to the include category, meaning they have a DOI and are not
		 * already in the include category.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that can be added to the include category
		 */
		public function query_posts_that_can_be_added_to_include_category( $batch ) {
			$include_post_category = $this->common->get_settings_field_value( 'include_post_category' );
			$include_category_id   = get_cat_ID( $include_post_category );

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'category__not_in'    => array( $include_category_id ),
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $this->common->get_post_doi_meta_key(),
						'value'   => '',
						'compare' => '!=',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that are assigned to the exclude category.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_have_exclude_category() {
			$query = $this->query_posts_that_have_exclude_category( false );
			return $query->post_count;
		}

		/**
		 * Return posts that are assigned to the exclude category.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that are assigned to the exclude category
		 */
		public function query_posts_that_have_exclude_category( $batch ) {
			$exclude_post_category = $this->common->get_settings_field_value( 'exclude_post_category' );
			$exclude_category_id   = get_cat_ID( $exclude_post_category );

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'category__in'        => array( $exclude_category_id ),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}

		/**
		 * Return the number of posts that can be added to the exclude category, meaning they have no DOI and are not
		 * already in the exclude category.
		 *
		 * @return int the number of posts
		 */
		public function get_number_of_posts_that_can_be_added_to_exclude_category() {
			$query = $this->query_posts_that_can_be_added_to_exclude_category( false );
			return $query->post_count;
		}

		/**
		 * Return posts that can be added to the exclude category, meaning they have no DOI and are not
		 * already in the exclude category.
		 *
		 * @param int $batch the batch size (0 means all posts, >0 means a maximum limit).
		 * @return WP_Query the query that contains posts that can be added to the exclude category
		 */
		public function query_posts_that_can_be_added_to_exclude_category( $batch ) {
			$exclude_post_category = $this->common->get_settings_field_value( 'exclude_post_category' );
			$exclude_category_id   = get_cat_ID( $exclude_post_category );

			$query_args = array(
				'post_type'           => 'post',
				'post_status'         => array( 'publish' ),
				'ignore_sticky_posts' => true,
				'category__not_in'    => array( $exclude_category_id ),
				'meta_query'          => array( // phpcs:ignore
					'relation' => 'OR',
					array(
						'key'     => $this->common->get_post_doi_meta_key(),
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => $this->common->get_post_doi_meta_key(),
						'value'   => '',
						'compare' => '==',
					),
				),
			);

			$this->add_batch_arguments_to_query( $query_args, $batch );
			return new WP_Query( $query_args );
		}
	}
}
