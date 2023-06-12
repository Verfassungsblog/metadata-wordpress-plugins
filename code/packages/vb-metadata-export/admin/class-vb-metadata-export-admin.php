<?php
/**
 * Class that handles admin interface.
 *
 * @package vb-metadata-export
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-metadata-export-common.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-metadata-export-converter.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-metadata-export-marc21xml.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-metadata-export-oai-pmh.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-metadata-export-settings-fields.php';

if ( ! class_exists( 'VB_Metadata_Export_Admin' ) ) {

	/**
	 * Class that handles admin interface.
	 */
	class VB_Metadata_Export_Admin {
		/**
		 * Settings fields class.
		 *
		 * @var VB_Metadata_Export_Settings_Fields
		 */
		protected $settings_fields;

		/**
		 * Common methods
		 *
		 * @var VB_Metadata_Export_Common
		 */
		protected $common;

		/**
		 * Initialize class with the plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common          = new VB_Metadata_Export_Common( $plugin_name );
			$this->settings_fields = new VB_Metadata_Export_Settings_Fields();
		}

		/**
		 * Return associative array of tab labels that visualizes as a menu.
		 *
		 * @return array array of (tab name => label)
		 */
		protected function get_tab_labels() {
			return array(
				'general' => 'General',
				'fields'  => 'Custom Fields',
				'marc21'  => 'Marc21 XML',
				'mods'    => 'MODS',
				'dc'      => 'Dublin Core',
				'oai_pmh' => 'OAI-PMH',
			);
		}

		/**
		 * Return associative array of section labels.
		 *
		 * @return array array of (section name => label)
		 */
		protected function get_settings_section_labels() {
			return array(
				'general'      => 'General',
				'language'     => 'Language',
				'content_type' => 'Content Types',
				'theme'        => 'Theme Settings',
				'post_meta'    => 'Custom Fields for Posts',
				'user_meta'    => 'Custom Fields for Users',
				'marc21'       => 'Marc21 XML Settings',
				'mods'         => 'MODS Settings',
				'oai_pmh'      => 'OAI-PMH Settings',
				'dc'           => 'Dublin Core Settings',
			);
		}

		/**
		 * Return associative array of which settings section should be rendered on what tab.
		 *
		 * @return array array of (section name => tab name)
		 */
		protected function get_tab_by_section_map() {
			return array(
				'general'      => 'general',
				'language'     => 'general',
				'content_type' => 'general',
				'theme'        => 'general',
				'post_meta'    => 'fields',
				'user_meta'    => 'fields',
				'marc21'       => 'marc21',
				'mods'         => 'mods',
				'oai_pmh'      => 'oai_pmh',
				'dc'           => 'dc',
			);
		}

		/**
		 * Return id of settings page for a tab.
		 *
		 * @param string $tab_name the tab name.
		 * @return string the internal settings page id
		 */
		protected function get_setting_page_id_by_tab( $tab_name ) {
			return $this->common->plugin_name . '_' . $tab_name . '_tab_settings';
		}

		/**
		 * Return reference to render function for a tab.
		 *
		 * @param string $tab_name the tab name.
		 * @return array the render function
		 */
		protected function get_render_function_by_tab( $tab_name ) {
			return array( $this, 'render_' . $tab_name . '_tab' );
		}

		/**
		 * Return reference to render function for a settings section.
		 *
		 * @param string $section_name the name of the settings section.
		 * @return array the render function
		 */
		protected function get_setting_section_render_function_by_name( $section_name ) {
			return array( $this, 'render_' . $section_name . '_section' );
		}

		/**
		 * WordPress init action hook.
		 */
		public function action_admin_init() {
			// create sections.
			$section_labels = $this->get_settings_section_labels();
			$tab_by_section = $this->get_tab_by_section_map();
			foreach ( $section_labels as $section_name => $section_label ) {
				add_settings_section(
					$section_name,
					$section_label,
					$this->get_setting_section_render_function_by_name( $section_name ),
					$this->get_setting_page_id_by_tab( $tab_by_section[ $section_name ] )
				);
			}

			// create fields.
			foreach ( $this->settings_fields->get_list() as $field ) {
				$field_id         = $this->common->get_settings_field_id( $field['name'] );
				$default          = $this->common->get_settings_field_default_value( $field['name'] );
				$settings_page_id = $this->get_setting_page_id_by_tab( $tab_by_section[ $field['section'] ] );

				register_setting(
					$settings_page_id,
					$field_id,
					array(
						'type'    => $field['type'],
						'default' => $default,
					)
				);

				add_settings_field(
					$field_id,
					$field['label'],
					array( $this, 'render_field' ),
					$settings_page_id,
					$field['section'],
					array(
						'label_for'  => $field_id,
						'field_name' => $field['name'],
					)
				);
			}

			// add css.
			wp_register_style(
				'vb-metadata-export-admin-styles',
				plugins_url( 'css/settings.css', __FILE__ ),
				array(),
				filemtime( realpath( plugin_dir_path( __FILE__ ) . 'css/settings.css' ) ),
				'screen'
			);
		}

		/**
		 * Renders the general section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_general_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following options influence how metadata is exported independent of the specific format.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the language section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_language_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'All posts are assumed to be written in a default language unless they are assigned to a special
                    category indicating that those post were written in an alternative language.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the content type section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_content_type_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'By default all posts are assumed to be classic textual articles. By assigning posts to the
                    following categories, they can be assigned to a different content type.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the theme section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_theme_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'Settings that might need to be customized depending on the WordPress theme that is used.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the post meta section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_post_meta_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings add or overwrite meta data for each individual post via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the user meta section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_user_meta_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings add or overwrite meta data for each author (or user) via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.
                    The corresponding ACF field needs to be assigned to users instead of posts. This can be achieved by
                    an ACF "location rule" for the field group: <code>User Role : is equal to : All</code>.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the marc21 section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_marc21_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings influence how Marc21 XML is generated.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the mods section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_mods_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings influence how metadata is exported as MODS.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the OAI-PMH section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_oai_pmh_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings influence how metadata is provided via the OAI-PMH interface.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the dublin core section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_dc_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following settings influence how metadata is exported as Dublin Core.',
					'vb-metadata-export'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders an individual settings field.
		 *
		 * @param array $args the setting fields arguments.
		 */
		public function render_field( $args ) {
			$field_id   = $args['label_for'];
			$field_name = $args['field_name'];
			$field      = $this->settings_fields->get_field( $field_name );
			$value      = get_option( $field_id );
			if ( 'boolean' === $field['type'] ) {
				?>
				<input
					id="<?php echo esc_attr( $field_id ); ?>"
					name="<?php echo esc_attr( $field_id ); ?>"
					type="checkbox"
					<?php echo $value ? 'checked' : ''; ?>
				>
				<?php
			} else {
				?>
				<input
					id="<?php echo esc_attr( $field_id ); ?>"
					name="<?php echo esc_attr( $field_id ); ?>"
					class="regular-text code"
					type="text"
					value="<?php echo esc_attr( $value ); ?>"
					placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
				>
				<?php
			}
			?>
			<p class="description">
				<?php echo $field['description']; // phpcs:ignore ?>
			</p>
			<?php
		}

		/**
		 * WordPress admin menu action hook.
		 */
		public function action_admin_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				// admin menu should not be loaded for non-admin users.
				return;
			}

			$admin_page_hook = add_submenu_page(
				'options-general.php',
				'Verfassungsblog Metadata Export',
				'VB Metadata Export',
				'manage_options',
				$this->common->plugin_name,
				array( $this, 'render' )
			);

			add_action( 'load-' . $admin_page_hook, array( $this, 'help_tab' ) );
			add_action( 'admin_print_styles-' . $admin_page_hook, array( $this, 'action_admin_print_styles' ) );
		}

		/**
		 * WordPress admin print styles hook.
		 */
		public function action_admin_print_styles() {
			wp_enqueue_style( 'vb-metadata-export-admin-styles' );
		}

		/**
		 * Render function for admin help.
		 */
		public function help_tab() {
			$screen = get_current_screen();
			$screen->add_help_tab(
				array(
					'id'      => $this->common->plugin_name . '_help_tab',
					'title'   => __( 'Help' ),
					'content' => '<h2>Verfassungsblog Metadata Export</h2>',
				)
			);
		}

		/**
		 * Render function for admin page.
		 */
		public function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! empty( $_POST['reset'] ) ) {
				foreach ( $this->settings_fields->get_list() as $field ) {
					$field_id = $this->common->get_settings_field_id( $field['name'] );
					delete_option( $field_id );
				}
			}

			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
			$current_tab = isset( $this->get_tab_labels()[ $current_tab ] ) ? $current_tab : 'general';

			?>
			<div class="vb-metadata-export-admin-header">
				<div class="vb-metadata-export-title-section">
					<h1>
						<?php echo esc_html( get_admin_page_title() ); ?>
					</h1>
				</div>
				<nav class="vb-metadata-export-admin-header-nav">
					<?php
					foreach ( $this->get_tab_labels() as $tab_name => $tab_label ) {
						?>
						<a
							class="vb-metadata-export-admin-header-tab <?php echo $current_tab === $tab_name ? 'active' : ''; ?>"
							href="?page=<?php echo esc_attr( $this->common->plugin_name ); ?>&tab=<?php echo esc_attr( $tab_name ); ?>"
						>
							<?php echo esc_html( $tab_label ); ?>
						</a>
						<?php
					}
					?>
				</nav>
			</div>
			<hr class="wp-header-end">
			<div class="vb-metadata-export-admin-content">
				<?php
				$settings_page_id = $this->get_setting_page_id_by_tab( $current_tab );
				?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $settings_page_id );
					do_settings_sections( $settings_page_id );
					submit_button( __( 'Save Settings', 'vb-metadata-export' ) );
					?>
				</form>
				<hr />
				<?php
				call_user_func_array( $this->get_render_function_by_tab( $current_tab ), array() );
				?>
			</div>
			<div class="clear"></div>
			<?php
		}

		/**
		 * Render function for general tab.
		 */
		public function render_general_tab() {
			?>
			<form method="post" onsubmit="return confirm('Are you sure?');">
				<input type="hidden" name="reset" value="true" />
				<p>
					The following action will reset all options of this plugin to their default value
					(including options in other tabs). Use with care only.
				</p>
				<?php
				submit_button( __( 'Reset Settings to Default', 'vb-metadata-export' ), 'secondary', 'reset' );
				?>
			</form>
			<?php
		}

		/**
		 * Returns a appropriate example post that is used to render metadata.
		 *
		 * @return WP_Post|bool the example post
		 */
		protected function find_example_post() {
			// get last modified post.
			$query_args = array(
				'post_type'      => 'post',
				'post_status'    => array( 'publish' ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'posts_per_page' => 1,
			);

			// check with doi.
			$require_doi = $this->common->get_settings_field_value( 'require_doi' );
			if ( $require_doi ) {
				$doi_meta_key             = $this->common->get_settings_field_value( 'doi_meta_key' );
				$query_args['meta_query'] = array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => $doi_meta_key,
						'value'   => '',
						'compare' => '!=',
					),
				);
			}

			// query for posts.
			$query = new WP_Query( $query_args );
			if ( count( $query->posts ) > 0 ) {
				return $query->posts[0];
			}

			// otherwise, return any post.
			$posts = get_posts( array( 'numberposts' => 1 ) );
			if ( count( $posts ) > 0 ) {
				return $posts[0];
			}
			return false;
		}

		/**
		 * Render function for marc21 tab.
		 */
		public function render_marc21_tab() {
			$post = $this->find_example_post();
			if ( ! empty( $post ) ) {
				$renderer    = new VB_Metadata_Export_Marc21Xml( $this->common->plugin_name );
				$marc21xml   = $renderer->render( $post );
				$example_url = $this->common->get_the_permalink( 'marc21xml', $post );
				?>
				<h2>
					<a href="<?php echo esc_attr( $example_url ); ?>">
						<?php echo esc_html( __( 'Example', 'vb-metadata-export' ) ); ?>
					</a>
				</h2>

				<pre><?php echo htmlspecialchars( $marc21xml ); // phpcs:ignore ?></pre>

				<?php
			}
		}

		/**
		 * Render function for mods tab.
		 */
		public function render_mods_tab() {
			$post = $this->find_example_post();
			if ( ! empty( $post ) ) {
				$renderer  = new VB_Metadata_Export_Marc21Xml( $this->common->plugin_name );
				$converter = new VB_Metadata_Export_Converter();

				$marc21xml   = $renderer->render( $post );
				$mods_xml    = $converter->convert_marc21_xml_to_mods( $marc21xml );
				$example_url = $this->common->get_the_permalink( 'mods', $post );

				?>
				<h2>
					<a href="<?php echo esc_attr( $example_url ); ?>">
						<?php echo esc_html( __( 'Example', 'vb-metadata-export' ) ); ?>
					</a>
				</h2>

				<pre><?php echo htmlspecialchars( $mods_xml ); // phpcs:ignore ?></pre>

				<?php
			}
		}

		/**
		 * Render function for OAI-PMH tab.
		 */
		public function render_oai_pmh_tab() {
			$oaipmh_enabled = $this->common->get_settings_field_value( 'oai-pmh_enabled' );
			$post           = $this->find_example_post();
			if ( $oaipmh_enabled && ! empty( $post ) ) {
				$oaipmh          = new VB_Metadata_Export_OAI_PMH( $this->common->plugin_name );
				$oai_baseurl     = $oaipmh->get_base_url();
				$post_identifier = $oaipmh->get_post_identifier( $post );
				?>
				<h2>
					Example Requests
				</h2>

				<ul>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=Identify">
							<?php echo esc_html( __( 'Identify', 'vb-metadata-export' ) ); ?>
						</a>
					<li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=ListSets">
							<?php echo esc_html( __( 'ListSets', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=ListMetadataFormats">
							<?php echo esc_html( __( 'ListMetadataFormats', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a
							href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=GetRecord&identifier=<?php echo esc_attr( $post_identifier ); ?>&metadataPrefix=oai_dc">
							<?php echo esc_html( __( 'GetRecord', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=ListRecords&metadataPrefix=oai_dc">
							<?php echo esc_html( __( 'ListRecords', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=ListIdentifiers&metadataPrefix=oai_dc">
							<?php echo esc_html( __( 'ListIdentifiers', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=something">
							<?php echo esc_html( __( 'Error: BadVerb', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=GetRecord">
							<?php echo esc_html( __( 'Error: BadArgument', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_attr( $oai_baseurl ); ?>?verb=ListIdentifiers&metadataPrefix=bad">
							<?php echo esc_html( __( 'Error: CannotDisseminateFormat', 'vb-metadata-export' ) ); ?>
						</a>
					</li>
				</ul>
				<?php
			}
		}

		/**
		 * Render function for Dublin Core tab.
		 */
		public function render_dc_tab() {
			$post = $this->find_example_post();
			if ( ! empty( $post ) ) {
				$dc_renderer = new VB_Metadata_Export_DC( $this->common->plugin_name );
				$dc          = $dc_renderer->render( $post );
				$example_url = $this->common->get_the_permalink( 'dc', $post );

				?>
				<h2>
					<a href="<?php echo esc_attr( $example_url ); ?>">
						<?php echo esc_html( __( 'Example', 'vb-metadata-export' ) ); ?>
					</a>
				</h2>

				<pre><?php echo htmlspecialchars( $dc ); // phpcs:ignore ?></pre>
				<?php
			}
		}

		/**
		 * Render function for custom fields tab.
		 */
		public function render_fields_tab() {
			// empty.
		}

		/**
		 * Run method that is called from main class.
		 */
		public function run() {
			if ( ! is_admin() ) {
				// settings should not be loaded for non-admin-interface pages.
				return;
			}

			add_action( 'admin_init', array( $this, 'action_admin_init' ) );
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		}

	}

}
