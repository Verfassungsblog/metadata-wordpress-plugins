<?php
/**
 * Class that performs scheduled or manual updates.
 *
 * @package vb-crossref-doi
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-common.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-rest.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-status.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-queries.php';

if ( ! class_exists( 'VB_CrossRef_DOI_Update' ) ) {

	/**
	 * Class that performs scheduled or manual updates.
	 */
	class VB_CrossRef_DOI_Update {

		/**
		 * Common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		protected $common;

		/**
		 * Class that manages post submission status
		 *
		 * @var VB_CrossRef_DOI_Status
		 */
		protected $status;

		/**
		 * Class that provides custom database queries
		 *
		 * @var VB_CrossRef_DOI_Queries
		 */
		protected $queries;

		/**
		 * Class that performs HTTP requests to CrossRef.
		 *
		 * @var VB_CrossRef_DOI_REST
		 */
		protected $rest;

		/**
		 * Initialize class with plugin name
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common  = new VB_CrossRef_DOI_Common( $plugin_name );
			$this->status  = new VB_CrossRef_DOI_Status( $plugin_name );
			$this->queries = new VB_CrossRef_DOI_Queries( $plugin_name );
			$this->rest    = new VB_CrossRef_DOI_REST( $plugin_name );
		}

		/**
		 * Triggers CrossRef submission for all posts of a query.
		 *
		 * @param WP_Query $query the query.
		 * @return bool false if no posts are submitted, else true
		 */
		protected function submit_posts_from_query( $query ) {
			if ( $query->post_count > 0 ) {
				foreach ( $query->posts as $post ) {
					$success = $this->rest->submit_new_or_existing_post( $post );
					if ( $success ) {
						continue;
					} else {
						return true;
					}
				}
				return true;
			}
			return false;
		}

		/**
		 * Triggers checking the submission result for all posts of a query.
		 *
		 * @param WP_Query $query the query.
		 * @return bool false if no posts are checked, else true
		 */
		protected function check_submissions_from_query( $query ) {
			if ( $query->post_count > 0 ) {
				foreach ( $query->posts as $post ) {
					$success = $this->rest->check_submission_result( $post );

					if ( $success ) {
						continue;
					} else {
						return true;
					}
				}
				return true;
			}
			return false;
		}

		/**
		 * Checks whether any posts have been modified since the last time this check was performed.
		 * Marks posts that have been modified by setting their submission status to modified.
		 */
		public function check_for_modified_posts() {
			// check posts that need updating because modified.
			$modified_query = $this->queries->query_posts_that_were_modified_since_last_check();
			foreach ( $modified_query->posts as $post_id ) {
				$post     = new stdClass();
				$post->ID = $post_id;
				$this->status->set_post_submit_status( $post, VB_CrossRef_DOI_Status::SUBMIT_MODIFIED );
			}
			$this->status->set_date_of_last_modified_check();
		}

		/**
		 * Marks all posts with a submission status of modified, such that they will be submitted again.
		 */
		public function mark_all_posts_as_modified() {
			$this->status->set_date_of_last_modified_check( 1 );
			$this->check_for_modified_posts();
		}

		/**
		 * Performs a scheduled or manual update, checking whether new or modified posts have to be submitted to
		 * CrossRef.
		 */
		public function do_update() {
			$this->status->clear_last_error();
			$this->status->set_last_update();
			$batch = (int) $this->common->get_settings_field_value( 'batch' );
			$batch = $batch < 1 ? 1 : $batch;

			$this->check_for_modified_posts();

			// check pending submissions.
			$pending_query = $this->queries->query_posts_that_have_pending_submissions( $batch );
			if ( $this->check_submissions_from_query( $pending_query ) ) {
				// stop if any pending submissions were checked.
				return;
			}

			// iterate over all posts that need submitting (modified, retry, new).
			$submit_query = $this->queries->query_posts_that_need_submitting( $batch );
			if ( $this->submit_posts_from_query( $submit_query ) ) {
				// stop if any posts were submitted.
				return;
			}
		}

		/**
		 * WordPress init action hook.
		 */
		public function action_init() {
			if ( ! has_action( $this->common->plugin_name . '_update' ) ) {
				add_action( $this->common->plugin_name . '_update', array( $this, 'do_update' ) );
			}
			$update_enabled = $this->common->get_settings_field_value( 'auto_update' );
			if ( $update_enabled ) {
				if ( ! wp_next_scheduled( $this->common->plugin_name . '_update' ) ) {
					wp_schedule_event( time(), $this->common->plugin_name . '_schedule', $this->common->plugin_name . '_update' );
				}
			} else {
				wp_clear_scheduled_hook( $this->common->plugin_name . '_update' );
			}
		}

		/**
		 * Return the number of minutes that is used to scheduled updates via cron.
		 */
		public function get_update_interval_in_minutes() {
			$interval = (int) $this->common->get_settings_field_value( 'interval' );
			$interval = $interval < 1 ? 5 : $interval;
			return $interval;
		}

		/**
		 * Registers a cron schedule with WordPress.
		 *
		 * @param array $schedules the WordPress schedules array.
		 * @return array the modified WordPress schedules array
		 */
		public function cron_schedules( $schedules ) {
			$minutes = $this->get_update_interval_in_minutes();

			$schedules[ $this->common->plugin_name . '_schedule' ] = array(
				'interval' => $minutes * 60,
				'display'  => 'Every ' . $minutes . ' minutes',
			);
			return $schedules;
		}

		/**
		 * Run method that is called by the main class of this plugin.
		 */
		public function run() {
			add_action( 'init', array( $this, 'action_init' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) ); // phpcs:ignore
		}

	}

}
