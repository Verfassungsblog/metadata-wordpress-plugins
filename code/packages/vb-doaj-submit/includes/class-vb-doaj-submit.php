<?php
/**
 * Main class.
 *
 * @package vb-doaj-submit
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-common.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-doaj-submit-update.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-doaj-submit-admin.php';

if ( ! class_exists( 'VB_DOAJ_Submit' ) ) {

	/**
	 * Main class.
	 */
	class VB_DOAJ_Submit {
		/**
		 * Common methods
		 *
		 * @var VB_DOAJ_Submit_Common
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
		 * @var VB_DOAJ_Submit_Admin
		 */
		protected $admin;

		/**
		 * Update class handling scheduled updates
		 *
		 * @var VB_DOAJ_Submit_Update
		 */
		protected $update;

		/**
		 * Initialize main class.
		 *
		 * @param string $base_file the base filename of this plugin.
		 * @param string $plugin_name the name of the plugin.
		 */
		public function __construct( $base_file, $plugin_name ) {
			$this->base_file = $base_file;
			$this->common    = new VB_DOAJ_Submit_Common( $plugin_name );
			$this->update    = new VB_DOAJ_Submit_Update( $plugin_name );
			$this->admin     = new VB_DOAJ_Submit_Admin( $plugin_name );
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
		 * Run method that is called from plugin base file.
		 */
		public function run() {
			register_activation_hook( $this->base_file, array( $this, 'activate' ) );
			register_deactivation_hook( $this->base_file, array( $this, 'deactivate' ) );
			register_uninstall_hook( $this->base_file, 'vb_doaj_submit_uninstall' );

			add_action( 'init', array( $this, 'action_init' ) );
			add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 4 );

			$this->admin->run();
			$this->update->run();
		}

	}

}
