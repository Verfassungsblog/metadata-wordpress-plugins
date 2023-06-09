<?php
/**
 * Test
 *
 * @package vb-metadata-export
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-metadata-export-admin.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-shortcode.php';

if ( ! class_exists( 'VB_Metadata_Export' ) ) {

	/**
	 * Main class of Verfassungsblog Metadata Export plugin.
	 */
	class VB_Metadata_Export {

		/**
		 * Common methods
		 *
		 * @var VB_Metadata_Export_Common
		 */
		protected $common;

		/**
		 * Plugin version string
		 *
		 * @var string
		 */
		protected $plugin_version;

		/**
		 * Path to base plugin file
		 *
		 * @var string
		 */
		protected $base_file;

		/**
		 * Admin class
		 *
		 * @var VB_Metadata_Export_Admin
		 */
		protected $admin;

		/**
		 * Shortcode class
		 *
		 * @var VB_Metadata_Export_Shortcode
		 */
		protected $shortcode;

		/**
		 * OAI-PMH class
		 *
		 * @var VB_Metadata_Export_OAI_PMH
		 */
		protected $oaipmh;

		/**
		 * Intialize main class.
		 *
		 * @param string $base_file path to plugin base file.
		 * @param string $plugin_name name of plugin.
		 * @param string $plugin_version version of plugin.
		 */
		public function __construct( $base_file, $plugin_name, $plugin_version ) {
			$this->plugin_version = $plugin_version;
			$this->base_file      = $base_file;
			$this->common         = new VB_Metadata_Export_Common( $plugin_name );
			$this->admin          = new VB_Metadata_Export_Admin( $plugin_name );
			$this->shortcode      = new VB_Metadata_Export_Shortcode( $plugin_name );
			$this->oaipmh         = new VB_Metadata_Export_OAI_PMH( $plugin_name );
		}

		/**
		 * Add rewrite rules that allow to render xml exports.
		 */
		protected function add_rewrite_rules() {
			// Add rewrite rule to output custom metadata formats instead of html.
			add_rewrite_tag( '%' . $this->common->plugin_name . '%', '([^&]+)' );
		}

		/**
		 * WordPress plugin activation hook
		 */
		public function activate() {
			$this->add_rewrite_rules();
			$this->oaipmh->add_rewrite_rules();
			flush_rewrite_rules();
		}

		/**
		 * WordPress plugin deactivation hook
		 */
		public function deactivate() {

		}

		/**
		 * WordPress plugin init action
		 */
		public function action_init() {
			$this->add_rewrite_rules();

			load_plugin_textdomain(
				$this->common->plugin_name,
				false,
				dirname( plugin_basename( $this->base_file ) ) . '/languages'
			);
		}

		/**
		 * WordPress plugin template include action
		 *
		 * @param string $template the template that is used to render the current page.
		 */
		public function action_template_include( $template ) {
			global $wp_query;
			global $post;

			if ( isset( $wp_query->query_vars[ $this->common->plugin_name ] ) ) {
				$format = $wp_query->query_vars[ $this->common->plugin_name ];
				if ( $this->common->is_valid_format( $format ) ) {
					if ( $this->common->is_format_enabled( $format ) ) {
						return dirname( $this->base_file ) . '/public/' . $format . '.php';
					}
				}

				// show 404 page if format is incorrect or disabled
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return get_query_template( '404' );
			}

			return $template;
		}

		/**
		 * Main run method.
		 */
		public function run() {
			register_activation_hook( $this->base_file, array( $this, 'activate' ) );
			register_deactivation_hook( $this->base_file, array( $this, 'deactivate' ) );
			register_uninstall_hook( $this->base_file, 'vb_metadata_export_uninstall' );

			add_action( 'init', array( $this, 'action_init' ) );

			$template_priority = (int) $this->common->get_settings_field_value( 'template_priority' );
			add_filter( 'template_include', array( $this, 'action_template_include' ), $template_priority, 1 );

			$this->admin->run();
			$this->shortcode->run();
			$this->oaipmh->run();
		}

	}

}
