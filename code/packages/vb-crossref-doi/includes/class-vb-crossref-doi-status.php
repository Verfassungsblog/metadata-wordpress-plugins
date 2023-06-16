<?php
/**
 * Class managing submission status of posts.
 *
 * @package vb-doaj-submit
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-common.php';

if ( ! class_exists( 'VB_CrossRef_DOI_Status' ) ) {

	/**
	 * Class managing submission status of posts.
	 */
	class VB_CrossRef_DOI_Status {
		/**
		 * Indicates a successfull submission to CrossRef.
		 * The post should have a DOI stored as post meta data.
		 */
		public const SUBMIT_SUCCESS = 'success';

		/**
		 * Indicates that the submission caused an error. Erros may be caused by a failed HTTP request or
		 * based on a rejection by CrossRef (wrong password, invalid metadata, etc).
		 */
		public const SUBMIT_ERROR = 'error';

		/**
		 * Indicates that the submission was not fully completely (e.g. a timeout during the HTTP request).
		 * A pending submission is first checked again (in case it got through somehow), but at some point tried again.
		 */
		public const SUBMIT_PENDING = 'pending';

		/**
		 * Indicates that a post can not be submitted to CrossRef, e.g., when it already has a DOI with different
		 * prefix.
		 */
		public const SUBMIT_NOT_POSSIBLE = 'not-possible';

		/**
		 * Indicates that a post was modified and needs to be submitted again such that the potentially
		 * changed metadata is kept up-to-date at CrossRef.
		 */
		public const SUBMIT_MODIFIED = 'modified';

		/**
		 * The option key containing the timestamp the database was last checked for modified posts.
		 *
		 * @var string
		 */
		protected $date_of_last_modified_check_option_key;

		/**
		 * The option key containing the last global error of this plugin.
		 *
		 * @var string
		 */
		protected $last_error_option_key;

		/**
		 * The option key containg the timestamp of the last time the update method was called (either manually or via
		 * a cron schedule).
		 *
		 * @var string
		 */
		protected $last_update_option_key;

		/**
		 * Common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		protected $common;

		/**
		 * Initialize the class with the name of this plugin.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common                                 = new VB_CrossRef_DOI_Common( $plugin_name );
			$this->date_of_last_modified_check_option_key = $plugin_name . '_date_of_last_modified_check';
			$this->last_error_option_key                  = $plugin_name . '_status_last_error';
			$this->last_update_option_key                 = $plugin_name . '_status_last_update';
		}

		/**
		 * Sets the global last error message.
		 *
		 * @param string $error the error message.
		 * @return bool whether the message was successfully saved
		 */
		public function set_last_error( $error ) {
			return update_option( $this->last_error_option_key, $error );
		}

		/**
		 * Returns the global last error message.
		 *
		 * @return string the last error message
		 */
		public function get_last_error() {
			return get_option( $this->last_error_option_key, false );
		}

		/**
		 * Removes global last error message.
		 */
		public function clear_last_error() {
			delete_option( $this->last_error_option_key );
		}

		/**
		 * Sets the time when the update method was last called to the current time.
		 */
		public function set_last_update() {
			return update_option( $this->last_update_option_key, time() );
		}

		/**
		 * Returns the date and time in local timezone when the update method was last called.
		 *
		 * @return DateTime|bool the local time the update method was last called
		 */
		public function get_last_update_date() {
			$timestamp = get_option( $this->last_update_option_key, false );
			if ( empty( $timestamp ) ) {
				return false;
			}
			return ( new DateTime() )->setTimestamp( $timestamp )->setTimezone( wp_timezone() );
		}

		/**
		 * Returns a human readable time reporting when the update method was last called.
		 *
		 * @return string human readable time difference relative to now
		 */
		public function get_last_update_text() {
			$date = $this->get_last_update_date();
			if ( empty( $date ) ) {
				return 'never';
			}
			return human_time_diff( $date->getTimestamp() ) . ' ago';
		}

		/**
		 * Sets the timestamp of when the database was last checked for modified posts.
		 *
		 * @param int $timestamp optional custom timestamp, if null, the current time is used.
		 * @return bool true if the timestamp was saved succesfully
		 */
		public function set_date_of_last_modified_check( $timestamp = null ) {
			if ( null === $timestamp ) {
				$timestamp = ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
			}
			return update_option( $this->date_of_last_modified_check_option_key, $timestamp );
		}

		/**
		 * Return the local date when the database was last checked for modified posts.
		 *
		 * If the timestamp was never set, the current time is assumed, which implies that no posts are
		 * detected as modified when to plugin is first installed.
		 *
		 * @return DateTime the local date when the database was last checked for modified posts
		 */
		public function get_date_of_last_modified_check() {
			$timestamp = get_option( $this->date_of_last_modified_check_option_key, false );
			if ( empty( $timestamp ) ) {
				// no date available, set it.
				$this->set_date_of_last_modified_check();
				$timestamp = ( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
			}
			return ( new DateTime() )->setTimestamp( $timestamp )->setTimezone( wp_timezone() );
		}

		/**
		 * Return humand readable time of the date when the database was last checked for modified posts.
		 *
		 * @return string human readable time difference relative to now
		 */
		public function get_text_of_last_modified_check() {
			$date = $this->get_date_of_last_modified_check();
			return human_time_diff( $date->getTimestamp() ) . ' ago';
		}

		/**
		 * Removes the time when the database was last checked for modified posts.
		 */
		public function clear_date_of_last_modified_check() {
			delete_option( $this->date_of_last_modified_check_option_key );
		}

		/**
		 * Return the UTC timestamp a post was submitted to CrossRef.
		 *
		 * @param WP_Post $post the post.
		 * @return int the UTC timestamp
		 */
		public function get_post_submit_timestamp( $post ) {
			return get_post_meta(
				$post->ID,
				$this->common->get_post_submit_timestamp_meta_key(),
				true
			);
		}

		/**
		 * Sets the UTC timestamp when a post was submitted to CrossRef.
		 *
		 * @param WP_Post $post the post.
		 * @param int     $timestamp the UTC timestamp the post was submitted to CrossRef.
		 */
		public function set_post_submit_timestamp( $post, $timestamp ) {
			update_post_meta(
				$post->ID,
				$this->common->get_post_submit_timestamp_meta_key(),
				$timestamp,
			);
		}

		/**
		 * Removes the timestamp when a post was submitted to CrossRef.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_submit_timestamp( $post ) {
			delete_post_meta( $post->ID, $this->common->get_post_submit_timestamp_meta_key() );
		}

		/**
		 * Sets the CrossRef submission id for a post once it was successfully submitted.
		 *
		 * @param WP_Post $post the post.
		 * @param string  $submission_id the CrossRef submission ID.
		 */
		public function set_post_submit_id( $post, $submission_id ) {
			update_post_meta(
				$post->ID,
				$this->common->get_post_submit_id_meta_key(),
				$submission_id,
			);
		}

		/**
		 * Sets the post submit status (success, pending, error, modified).
		 *
		 * @param WP_Post $post the post.
		 * @param string  $status the status, which should be one of the constants provided by this class.
		 */
		public function set_post_submit_status( $post, $status ) {
			update_post_meta(
				$post->ID,
				$this->common->get_post_submit_status_meta_key(),
				$status,
			);
		}

		/**
		 * Return the error message that was recorded when a particular post was submitted to CrossRef.
		 *
		 * @param WP_Post $post the post.
		 * @return string the error message of this post
		 */
		public function get_post_submit_error( $post ) {
			return get_post_meta(
				$post->ID,
				$this->common->get_post_submit_error_meta_key(),
				true,
			);
		}

		/**
		 * Sets the error message that was recorded when a post could not be submitted to CrossRef.
		 *
		 * @param WP_Post $post the post.
		 * @param string  $msg the error message.
		 */
		public function set_post_submit_error( $post, $msg ) {
			update_post_meta(
				$post->ID,
				$this->common->get_post_submit_error_meta_key(),
				$msg,
			);
		}

		/**
		 * Removes the error message for a post.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_submit_error( $post ) {
			delete_post_meta(
				$post->ID,
				$this->common->get_post_submit_error_meta_key(),
			);
		}

		/**
		 * Sets the DOI for a post.
		 *
		 * @param WP_post $post the post.
		 * @param string  $doi the DOI prefix and suffix code (not the URI).
		 */
		public function set_post_doi( $post, $doi ) {
			update_post_meta(
				$post->ID,
				$this->common->get_post_doi_meta_key(),
				$doi,
			);
		}

		/**
		 * Resets all status information of this plugin (except for stored DOIs).
		 */
		public function reset_status() {
			$meta_keys = array(
				$this->common->get_post_submit_timestamp_meta_key(),
				$this->common->get_post_submit_id_meta_key(),
				$this->common->get_post_submit_error_meta_key(),
				$this->common->get_post_submit_status_meta_key(),
			);

			foreach ( $meta_keys as $meta_key ) {
				delete_metadata( 'post', 0, $meta_key, false, true );
			}

			$this->clear_date_of_last_modified_check();
			$this->clear_last_error();
		}

		/**
		 * WordPress init action hook.
		 */
		public function action_init() {
			add_option( $this->common->plugin_name . '_status_last_update', 0 );
		}

		/**
		 * Run method that is called by the main class.
		 */
		public function run() {
			add_action( 'init', array( $this, 'action_init' ) );
		}

	}

}
