<?php
/**
 * Main class
 *
 * @package vb-author-affiliations
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-author-affiliations-common.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-author-affiliations-admin.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-author-affiliations-meta-box.php';

if ( ! class_exists( 'VB_Author_Affiliations' ) ) {

	/**
	 * Main class
	 */
	class VB_Author_Affiliations {
		/**
		 * Common methods
		 *
		 * @var VB_Author_Affiliations_Common
		 */
		protected $common;

		/**
		 * Path to base plugin file
		 *
		 * @var string
		 */
		protected $base_file;

		/**
		 * Admin class handling settings page
		 *
		 * @var VB_Author_Affiliations_Admin
		 */
		protected $admin;

		/**
		 * Admin class handling post edit meta box
		 *
		 * @var VB_Author_Affiliations_Meta_Box
		 */
		protected $meta_box;

		/**
		 * Initialize main class.
		 *
		 * @param string $base_file the base filename of this plugin.
		 * @param string $plugin_name the name of the plugin.
		 */
		public function __construct( $base_file, $plugin_name ) {
			$this->base_file = $base_file;
			$this->common    = new VB_Author_Affiliations_Common( $plugin_name );
			$this->admin     = new VB_Author_Affiliations_Admin( $plugin_name );
			$this->meta_box  = new VB_Author_Affiliations_Meta_Box( $plugin_name );
		}

		/**
		 * WordPress activate hook.
		 */
		public function activate() {
			// empty.
		}

		/**
		 * WordPress deactivate hook.
		 */
		public function deactivate() {
			// empty.
		}

		/**
		 * WordPress init action hook.
		 */
		public function action_init() {
			load_plugin_textdomain(
				$this->common->plugin_name,
				false,
				dirname( plugin_basename( $this->base_file ) ) . '/languages'
			);
		}

		/**
		 * WordPress plugin row meta filter hook.
		 *
		 * Adds a link to the plugin list about who developed this plugin.
		 *
		 * @param array  $plugin_meta array of meta data information shown for each plugin.
		 * @param string $plugin_file the main plugin file whose meta data information is filtered.
		 * @param array  $plugin_data information about the plugin.
		 * @param mixed  $status unknown.
		 */
		public function filter_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
			if ( strpos( $plugin_file, plugin_basename( $this->base_file ) ) !== false ) {
				$developed_by = array(
					'Developed by <a href="https://knopflogik.de/" target="_blank">knopflogik GmbH</a>',
				);

				$plugin_meta = array_merge( $plugin_meta, $developed_by );
			}
			return $plugin_meta;
		}

		/**
		 * Main run method.
		 */
		public function run() {
			register_activation_hook( $this->base_file, array( $this, 'activate' ) );
			register_deactivation_hook( $this->base_file, array( $this, 'deactivate' ) );
			register_uninstall_hook( $this->base_file, 'vb_author_affiliations_uninstall' );

			add_action( 'init', array( $this, 'action_init' ) );
			add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 4 );

			$this->admin->run();
			$this->meta_box->run();
		}

	}

}
