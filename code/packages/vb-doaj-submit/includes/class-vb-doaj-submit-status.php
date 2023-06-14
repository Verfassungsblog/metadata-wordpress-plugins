<?php
/**
 * Class managing submission status of posts.
 *
 * @package vb-doaj-submit
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-common.php';

if ( ! class_exists( 'VB_DOAJ_Submit_Status' ) ) {

	/**
	 * Class managing submission status of posts.
	 */
	class VB_DOAJ_Submit_Status {
		/**
		 * Indicates a successfull submission to the DOAJ.
		 * The post should have a corresponding DOAJ article id stored as post meta data.
		 */
		public const SUBMIT_SUCCESS = 'success';

		/**
		 * Indicates that the submission was not fully completely (e.g. a timeout during the HTTP request).
		 * A pending submission needs to be retried after an appropriate amount of time.
		 */
		public const SUBMIT_PENDING = 'pending';

		/**
		 * Indicates that the submission caused an error. Erros may be caused by a failed HTTP request or
		 * based on a rejection by the DOAJ (wrong API key, invalid metadata, etc).
		 */
		public const SUBMIT_ERROR = 'error';

		/**
		 * Indicates that a post was modified and needs to be submitted again such that the potentially
		 * changed metadata is kept up-to-date at the DOAJ.
		 */
		public const SUBMIT_MODIFIED = 'modified';

		/**
		 * Common methods.
		 *
		 * @var VB_DOAJ_Submit_Common
		 */
		protected $common;

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
		 * Initialize the class with the name of this plugin.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common                                 = new VB_DOAJ_Submit_Common( $plugin_name );
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
				$timestamp = $this->common->get_current_utc_timestamp();
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
				$timestamp = $this->common->get_current_utc_timestamp();
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
		 * Sets the timestamp when a post was submitted to the DOAJ as the current time.
		 *
		 * @param WP_Post $post the post.
		 */
		public function set_post_submit_timestamp( $post ) {
			update_post_meta(
				$post->ID,
				$this->common->get_submit_timestamp_meta_key(),
				$this->common->get_current_utc_timestamp()
			);
		}

		/**
		 * Removes the timestamp when a post was submitted to the DOAJ.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_submit_timestamp( $post ) {
			delete_post_meta( $post->ID, $this->common->get_submit_timestamp_meta_key() );
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
		 * Removes the post submit status.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_submit_status( $post ) {
			delete_post_meta( $post->ID, $this->common->get_post_submit_status_meta_key() );
		}

		/**
		 * Return the error message that was recorded when a particular post was submitted to the DOAJ.
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
		 * Sets the error message that was recorded when a post could not be submitted to the DOAJ.
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
		 * Sets the DOAJ article id for a post.
		 *
		 * @param WP_Post $post the post.
		 * @param string  $article_id the DOAJ article id for a post.
		 */
		public function set_post_doaj_article_id( $post, $article_id ) {
			update_post_meta(
				$post->ID,
				$this->common->get_doaj_article_id_meta_key(),
				$article_id,
			);
		}

		/**
		 * Returns the DOAJ article id for a post.
		 *
		 * @param WP_Post $post the post.
		 * @return string the DOAJ article id for a post
		 */
		public function get_post_doaj_article_id( $post ) {
			return get_post_meta(
				$post->ID,
				$this->common->get_doaj_article_id_meta_key(),
				true,
			);
		}

		/**
		 * Removes the DOAJ article id for a post.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_doaj_article_id( $post ) {
			delete_post_meta( $post->ID, $this->common->get_doaj_article_id_meta_key() );
		}

		/**
		 * Sets the timestamp when a post was tried to be identified in the DOAJ to the current time.
		 *
		 * The timestamp is saved independently of whether a identification was succesfull or not and indicates
		 * whether a post is a new post (identified but no aritcle id), or a known post (identified with known article
		 * id).
		 *
		 * @param WP_Post $post the post.
		 */
		public function set_post_identify_timestamp( $post ) {
			update_post_meta(
				$post->ID,
				$this->common->get_identify_timestamp_meta_key(),
				$this->common->get_current_utc_timestamp()
			);
		}

		/**
		 * Removes the timestamp when a post was tried to be identified in the DOAJ.
		 *
		 * @param WP_Post $post the post.
		 */
		public function clear_post_identify_timestamp( $post ) {
			delete_post_meta( $post->ID, $this->common->get_identify_timestamp_meta_key() );
		}

		/**
		 * Resets the status of a post by removing all related properties (article id, submit timestamp, etc.).
		 *
		 * Is used after a post was deleted in the DOAJ.
		 *
		 * @param WP_Post $post the post.
		 */
		public function reset_post_status( $post ) {
			$this->clear_post_doaj_article_id( $post );
			$this->clear_post_submit_timestamp( $post );
			$this->clear_post_identify_timestamp( $post );
			$this->clear_post_submit_error( $post );
			$this->clear_post_submit_status( $post );
		}

		/**
		 * Resets all status information of this plugin (except for known DOAJ article ids).
		 */
		public function reset_status() {
			$meta_keys = array(
				$this->common->get_identify_timestamp_meta_key(),
				$this->common->get_submit_timestamp_meta_key(),
				$this->common->get_post_submit_error_meta_key(),
				$this->common->get_post_submit_status_meta_key(),
			);

			foreach ( $meta_keys as $meta_key ) {
				delete_metadata( 'post', 0, $meta_key, false, true );
			}

			$this->clear_last_error();
			$this->clear_date_of_last_modified_check();
		}

	}

}
