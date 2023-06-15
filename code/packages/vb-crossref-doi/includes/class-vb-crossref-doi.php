<?php
/**
 * Main class
 *
 * @package vb-crossref-doi
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-common.php';
require_once plugin_dir_path( __FILE__ ) . './class-vb-crossref-doi-update.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-crossref-doi-admin.php';

if ( ! class_exists( 'VB_CrossRef_DOI' ) ) {

	/**
	 * Main class
	 */
	class VB_CrossRef_DOI {

		/**
		 * Class providing common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		protected $common;

		/**
		 * Filepath to base plugin file.
		 *
		 * @var string
		 */
		protected $base_file;

		/**
		 * Class providing admin settings page
		 *
		 * @var VB_CrossRef_DOI_Admin
		 */
		protected $admin;

		/**
		 * Class that handles schedule and manual status updates
		 *
		 * @var VB_CrossRef_DOI_Update
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
			$this->common    = new VB_CrossRef_DOI_Common( $plugin_name );
			$this->admin     = new VB_CrossRef_DOI_Admin( $plugin_name );
			$this->update    = new VB_CrossRef_DOI_Update( $plugin_name );
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
		 * Run method that is called from plugin base file.
		 */
		public function run() {
			register_activation_hook( $this->base_file, array( $this, 'activate' ) );
			register_deactivation_hook( $this->base_file, array( $this, 'deactivate' ) );
			register_uninstall_hook( $this->base_file, 'vb_crossref_doi_uninstall' );

			add_action( 'init', array( $this, 'action_init' ) );

			$this->admin->run();
			$this->update->run();
		}

	}

}
