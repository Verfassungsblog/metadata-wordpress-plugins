<?php
/**
 * Class managing automatic and manual updates.
 *
 * @package vb-doaj-submit
 */

/**
 * Class import
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-common.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-rest.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-status.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-queries.php';

if ( ! class_exists( 'VB_DOAJ_Submit_Update' ) ) {

	/**
	 * Class managing automatic and manual updates.
	 */
	class VB_DOAJ_Submit_Update {

		/**
		 * Common methods
		 *
		 * @var VB_DOAJ_Submit_Common
		 */
		protected $common;

		/**
		 * Class managing the status of submissions.
		 *
		 * @var VB_DOAJ_Submit_Status
		 */
		protected $status;

		/**
		 * Class providing custom database queries.
		 *
		 * @var VB_DOAJ_Submit_Queries
		 */
		protected $queries;

		/**
		 * Class that performs REST requests to the DOAJ.
		 *
		 * @var VB_DOAJ_Submit_REST
		 */
		protected $rest;

		/**
		 * Initialize class with plugin name.
		 *
		 * @param string $plugin_name the name of the plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common  = new VB_DOAJ_Submit_Common( $plugin_name );
			$this->status  = new VB_DOAJ_Submit_Status( $plugin_name );
			$this->queries = new VB_DOAJ_Submit_Queries( $plugin_name );
			$this->rest    = new VB_DOAJ_Submit_REST( $plugin_name );
		}

		/**
		 * Iterates over posts of a database query and triggers REST calls.
		 *
		 * @param WP_Query $query the database query.
		 * @return bool true if a problem occurred during submissions, else false
		 */
		protected function submit_posts_from_query( $query ) {
			if ( $query->post_count > 0 ) {
				foreach ( $query->posts as $post ) {
					$success = false;
					if ( 'publish' === $post->post_status ) {
						$success = $this->rest->submit_new_or_existing_post( $post );
					} elseif ( 'trash' === $post->post_status ) {
						$success = $this->rest->submit_trashed_post( $post );
					} else {
						$this->status->set_last_error( 'cannot submit post with post status "' . $post->post_status . '"' );
					}

					if ( ! $success ) {
						return true;
					}
				}
				return true;
			}
			return false;
		}

		/**
		 * Performs database query in order to find posts that have been modified since the last check.
		 */
		public function check_for_modified_posts() {
			// check posts that need updating because modified.
			$modified_query = $this->queries->query_posts_that_were_modified_since_last_check();
			foreach ( $modified_query->posts as $post_id ) {
				$post     = new stdClass();
				$post->ID = $post_id;
				$this->status->set_post_submit_status( $post, VB_DOAJ_Submit_Status::SUBMIT_MODIFIED );
			}
			$this->status->set_date_of_last_modified_check();
		}

		/**
		 * Marks all posts as modified by assuming the last modified check was at timestamp 1.
		 */
		public function mark_all_posts_as_modified() {
			$this->status->set_date_of_last_modified_check( 1 );
			$this->check_for_modified_posts();
		}

		/**
		 * Adds the include category to all posts that have a DOAJ article id available.
		 */
		public function add_include_category_to_posts_with_doaj_article_id() {
			$include_post_category = $this->common->get_settings_field_value( 'include_post_category' );
			$include_category_id   = get_cat_ID( $include_post_category );

			if ( empty( $include_category_id ) ) {
				$this->status->set_last_error( 'Can not add posts to include category. Category name not found.' );
				return;
			}

			$include_query = $this->queries->query_posts_that_can_be_added_to_include_category( false );

			foreach ( $include_query->posts as $post_id ) {
				wp_set_object_terms( $post_id, $include_category_id, 'category', true );
			}
		}

		/** Remove include category from all posts */
		public function remove_include_category_from_all_posts() {
			$include_post_category = $this->common->get_settings_field_value( 'include_post_category' );
			$include_category_id   = get_cat_ID( $include_post_category );

			if ( empty( $include_category_id ) ) {
				$this->status->set_last_error( 'Can not remove posts from include category. Category name not found.' );
				return;
			}

			$include_query = $this->queries->query_posts_that_have_include_category( false );

			foreach ( $include_query->posts as $post_id ) {
				wp_remove_object_terms( $post_id, $include_category_id, 'category' );
			}
		}

		/**
		 * Adds the exclude category to all posts that have no DOAJ article id available.
		 */
		public function add_exclude_category_to_posts_without_doaj_article_id() {
			$exclude_post_category = $this->common->get_settings_field_value( 'exclude_post_category' );
			$exclude_category_id   = get_cat_ID( $exclude_post_category );

			if ( empty( $exclude_category_id ) ) {
				$this->status->set_last_error( 'Can not add posts to exclude category. Category name not found.' );
				return;
			}

			$exclude_query = $this->queries->query_posts_that_can_be_added_to_exclude_category( false );

			foreach ( $exclude_query->posts as $post_id ) {
				wp_set_object_terms( $post_id, $exclude_category_id, 'category', true );
			}
		}

		/** Remove exclude category from all posts */
		public function remove_exclude_category_from_all_posts() {
			$exclude_post_category = $this->common->get_settings_field_value( 'exclude_post_category' );
			$exclude_category_id   = get_cat_ID( $exclude_post_category );

			if ( empty( $exclude_category_id ) ) {
				$this->status->set_last_error( 'Can not remove posts from exclude category. Category name not found.' );
				return;
			}

			$exclude_query = $this->queries->query_posts_that_have_exclude_category( false );

			foreach ( $exclude_query->posts as $post_id ) {
				wp_remove_object_terms( $post_id, $exclude_category_id, 'category' );
			}
		}

		/**
		 * Resets error status of all posts that are currently in error status.
		 */
		public function reset_all_errors() {
			$query = $this->queries->query_posts_with_submit_error( false );
			if ( $query->post_count > 0 ) {
				foreach ( $query->posts as $post_id ) {
					$post     = new stdClass();
					$post->ID = $post_id;
					$this->status->reset_post_error_status( $post );
				}
			}
		}

		/**
		 * Update function that is called by the cron scheduler or manually upon user input.
		 */
		public function do_update() {
			$this->status->clear_last_error();
			$this->status->set_last_update();
			$batch = (int) $this->common->get_settings_field_value( 'batch' );
			$batch = $batch < 1 ? 1 : $batch;

			$this->check_for_modified_posts();

			// iterate over all posts that need submitting.
			$submit_query = $this->queries->query_posts_that_need_submitting( $batch );
			if ( $this->submit_posts_from_query( $submit_query ) ) {
				// stop if any posts were submitted.
				return;
			}

			// iterate over all posts that require identifying.
			$identify_query = $this->queries->query_posts_that_need_identifying( $batch );
			if ( $identify_query->post_count > 0 ) {
				foreach ( $identify_query->posts as $post ) {
					$success = $this->rest->identify_post( $post );
					if ( ! $success ) {
						return;
					}
				}
				return;
			}

		}

		/**
		 * Identifies posts from the DOAJ. Can be triggered manually by user input.
		 */
		public function do_identify() {
			$this->status->clear_last_error();
			$batch = (int) $this->common->get_settings_field_value( 'batch' );
			$batch = $batch < 1 ? 1 : $batch;

			// iterate over all posts that require identifying.
			$identify_query = $this->queries->query_posts_that_need_identifying( $batch );
			if ( $identify_query->post_count > 0 ) {
				foreach ( $identify_query->posts as $post ) {
					$success = $this->rest->identify_post( $post );
					if ( ! $success ) {
						return;
					}
				}
			}
		}

		/**
		 * WordPress init action hook.
		 *
		 * Triggers cron schedule and calls the update function if it is time.
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
		 * Returns the update interval in minutes defined in admin settings.
		 *
		 * @return int the update interval in minutes
		 */
		public function get_update_interval_in_minutes() {
			$interval = (int) $this->common->get_settings_field_value( 'interval' );
			$interval = $interval < 1 ? 5 : $interval;
			return $interval;
		}

		/**
		 * Registers a custom cron schedule with WordPress.
		 *
		 * @param array $schedules the WordPress schedules array.
		 * @return array the WordPress schedules array
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
		 * Run method that is called by the main class.
		 */
		public function run() {
			add_action( 'init', array( $this, 'action_init' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) ); // phpcs:ignore
		}

	}

}
