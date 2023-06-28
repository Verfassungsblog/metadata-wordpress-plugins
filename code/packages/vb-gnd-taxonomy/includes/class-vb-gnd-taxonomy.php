<?php
/**
 * Main class
 *
 * @package vb-gnd-taxonomy
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . './class-vb-gnd-taxonomy-common.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-gnd-taxonomy-admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . '../admin/class-vb-gnd-taxonomy-admin-edit.php';

if ( ! class_exists( 'VB_GND_Taxonomy' ) ) {

	/**
	 * Main class
	 */
	class VB_GND_Taxonomy {
		/**
		 * Common methods
		 *
		 * @var VB_GND_Taxonomy_Common
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
		 * @var VB_GND_Taxonomy_Admin_Settings
		 */
		protected $admin_settings;

		/**
		 * Admin class handling post edit page
		 *
		 * @var VB_GND_Taxonomy_Admin_Edit
		 */
		protected $admin_edit;

		/**
		 * Initialize main class.
		 *
		 * @param string $base_file the base filename of this plugin.
		 * @param string $plugin_name the name of the plugin.
		 */
		public function __construct( $base_file, $plugin_name ) {
			$this->common         = new VB_GND_Taxonomy_Common( $plugin_name );
			$this->base_file      = $base_file;
			$this->admin_settings = new VB_GND_Taxonomy_Admin_Settings( $plugin_name );
			$this->admin_edit     = new VB_GND_Taxonomy_Admin_Edit( $plugin_name );
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
			// register gnd taxonomy.
			$labels = array(
				'name'                   => __( 'GND Taxonomy', 'vb-gnd-taxonomy' ),
				'singular_name'          => __( 'GND Entity', 'vb-gnd-taxonomy' ),
				'search_items'           => __( 'Search GND Entities', 'vb-gnd-taxonomy' ),
				'all_items'              => __( 'All Entities', 'vb-gnd-taxonomy' ),
				'parent_item'            => __( 'Parent GND Entities', 'vb-gnd-taxonomy' ),
				'parent_item_colon'      => __( 'Parent GND Entity:', 'vb-gnd-taxonomy' ),
				'edit_item'              => __( 'Edit GND Entity', 'vb-gnd-taxonomy' ),
				'update_item'            => __( 'Update GND Entity', 'vb-gnd-taxonomy' ),
				'add_new_item'           => __( 'Add New GND Entity', 'vb-gnd-taxonomy' ),
				'new_item_name'          => __( 'New GND Entity Name', 'vb-gnd-taxonomy' ),
				'menu_name'              => __( 'GND Taxonomy', 'vb-gnd-taxonomy' ),
				'slug_field_description' => __( 'The GND-ID of the entity (not the URI). For example: ', 'vb-gnd-taxonomy' ) .
					'<code>4036934-1</code><br>' . __( 'You can search for GND entities at ', 'vb-gnd-taxonomy' ) .
					'<a href="https://lobid.org/gnd/" target="_blank">lobid.org</a>.',
			);
			$args   = array(
				'hierarchical'         => false,
				'labels'               => $labels,
				'show_ui'              => true,
				'show_admin_column'    => true,
				'query_var'            => true,
				'rewrite'              => array( 'slug' => 'gnd' ),
				'meta_box_sanitize_cb' => array( $this->admin_edit, 'meta_box_sanitize_cb' ),
			);
			register_taxonomy( 'gnd', array( 'post' ), $args );

			$merge_with_tags = $this->common->get_settings_field_value( 'merge_with_tags' );
			if ( $merge_with_tags ) {
				add_filter( 'get_the_terms', array( $this, 'filter_get_the_terms' ), 10, 3 );
			}

			load_plugin_textdomain(
				$this->common->plugin_name,
				false,
				dirname( plugin_basename( $this->base_file ) ) . '/languages'
			);
		}

		/**
		 * WordPress filter that modifies "get_the_terms" to return both tags and GND subjects.
		 *
		 * @param array   $terms the terms that are assigned to a post.
		 * @param WP_Post $post the post whose terms are requested.
		 * @param string  $taxonomy the name of the taxonomy for which terms are requested.
		 * @return array the combined set of terms including GND subjects (only if post tags are requested)
		 */
		public function filter_get_the_terms( $terms, $post, $taxonomy ) {
			if ( is_admin() || 'post_tag' !== $taxonomy ) {
				// dont modify terms in admin area or for other taxonomies than regular tags.
				return $terms;
			}

			$gnd_terms = get_the_terms( $post, 'gnd' );
			if ( ! empty( $gnd_terms ) && count( $gnd_terms ) > 0 ) {
				$terms = array_merge( $terms, $gnd_terms );
				// sort tags and GND subjects.
				usort(
					$terms,
					function ( $t1, $t2 ) {
						return strcmp( $t1->name, $t2->name );
					},
				);
			}

			return $terms;
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
			register_uninstall_hook( $this->base_file, 'vb_gnd_taxonomy_uninstall' );

			add_action( 'init', array( $this, 'action_init' ) );
			add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 4 );

			$this->admin_settings->run();
			$this->admin_edit->run();
		}

	}

}
