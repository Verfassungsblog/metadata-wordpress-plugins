<?php
/**
 * Class providing admin settings page.
 *
 * @package vb-author-affiliations
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-author-affiliations-settings-fields.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-author-affiliations-common.php';


if ( ! class_exists( 'VB_Author_Affiliations_Admin' ) ) {

	/**
	 * Class providing admin settings page.
	 */
	class VB_Author_Affiliations_Admin {
		/**
		 * Class that defines settings fields
		 *
		 * @var VB_Author_Affiliations_Settings_Fields
		 */
		protected $settings_fields;

		/**
		 * Common methods
		 *
		 * @var VB_Author_Affiliations_Common
		 */
		protected $common;

		/**
		 * Initialize class with plugin name
		 *
		 * @param string $plugin_name the name of the plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common          = new VB_Author_Affiliations_Common( $plugin_name );
			$this->settings_fields = new VB_Author_Affiliations_Settings_Fields();
		}

		/**
		 * Return associative array of tab labels that visualizes as a menu.
		 *
		 * @return array array of (tab name => label)
		 */
		protected function get_tab_labels() {
			return array(
				'settings' => 'Settings',
			);
		}

		/**
		 * Return associative array of section labels.
		 *
		 * @return array array of (section name => label)
		 */
		protected function get_settings_section_labels() {
			return array(
				'general'   => 'General',
				'post_meta' => 'Custom Fields for Posts',
				'user_meta' => 'Custom Fields for Users',
			);
		}

		/**
		 * Return associative array of which settings section should be rendered on what tab.
		 *
		 * @return array array of (section name => tab name)
		 */
		protected function get_tab_by_section_map() {
			return array(
				'general'   => 'settings',
				'post_meta' => 'settings',
				'user_meta' => 'settings',
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
		 * WordPress admin init action hook.
		 */
		public function action_admin_init() {
			if ( ! current_user_can( 'manage_options' ) ) {
				// settings are not allowed for non-admin users.
				return;
			}

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
				$this->common->plugin_name . '-admin-styles',
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
					'The following options influence how author affiliations are generated and stored.',
					'vb-author-affiliations'
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
					'The following settings define how author affiliations are stored for a post. Each option may
                    specify a meta key of the corresponding custom field. You may use, for
                    example, the Advanced Custom Fields (ACF) plugin to view or edit this meta data.',
					'vb-author-affiliations'
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
					'The following settings define how affiliation information is retrieved for each author (or user).
                    Each option may specify a meta key of the corresponding custom field. You may use, for example, the
                    Advanced Custom Fields (ACF) plugin to view or edit this meta data. For user meta data, the
                    corresponding ACF field group needs to be assigned to users instead of posts. This can be achieved
                    by specifying the ACF \'location rule\' for the corresponding field group as:
                    <code>User Role : is equal to : All</code>.',
					'vb-author-affiliations'
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
				'Verfassungsblog Author Affiliations',
				'VB Author Affiliations',
				'manage_options',
				$this->common->plugin_name,
				array( $this, 'render' )
			);

			add_action( 'load-' . $admin_page_hook, array( $this, 'help_tab' ) );
			add_action( 'admin_print_styles-' . $admin_page_hook, array( $this, 'action_admin_print_styles' ) );
		}

		/**
		 * WordPress admin print styles action hook.
		 */
		public function action_admin_print_styles() {
			wp_enqueue_style( $this->common->plugin_name . '-admin-styles' );
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
					'content' => '<h2>Verfassungsblog Author Affiliations</h2>',
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

			if ( ! empty( $_POST['reset_settings'] ) && check_admin_referer( $this->common->plugin_name . '_reset' ) ) {
				foreach ( $this->settings_fields->get_list() as $field ) {
					$field_id = $this->common->get_settings_field_id( $field['name'] );
					delete_option( $field_id );
				}
			}

			$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
			$current_tab = isset( $this->get_tab_labels()[ $current_tab ] ) ? $current_tab : 'settings';

			?>
			<div class="vb-author-affiliations-admin-header">
				<div class="vb-author-affiliations-title-section">
					<h1>
						<?php echo esc_html( get_admin_page_title() ); ?>
					</h1>
				</div>
				<nav class="vb-author-affiliations-admin-header-nav">
					<?php
					foreach ( $this->get_tab_labels() as $tab_name => $tab_label ) {
						?>
						<a
							class="vb-author-affiliations-admin-header-tab <?php echo $current_tab === $tab_name ? 'active' : ''; ?>"
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
			<div class="vb-author-affiliations-admin-content">
				<?php
				call_user_func_array( $this->get_render_function_by_tab( $current_tab ), array() );
				?>
			</div>
			<div class="clear"></div>
			<?php
		}

		/**
		 * Render function for settings tab.
		 */
		public function render_settings_tab() {
			?>
			<?php
			$settings_page_id = $this->get_setting_page_id_by_tab( 'settings' );
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields( $settings_page_id );
				do_settings_sections( $settings_page_id );
				submit_button( __( 'Save Settings', 'vb-author-affiliations' ) );
				?>
			</form>
			<hr />
			<form method="post" onsubmit="return confirm('Are you sure?');">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_reset' );
				?>
				<p>
					The following action will reset all options of this plugin to their default value
					(including options in other tabs). Use with care only.
				</p>
				<?php
				submit_button( __( 'Reset Settings to Default', 'vb-author-affiliations' ), 'secondary', 'reset_settings' );
				?>
			</form>
			<?php
		}

		/**
		 * Render function for custom fields tab.
		 */
		public function render_fields_tab() {
			?>
			<?php
			$settings_page_id = $this->get_setting_page_id_by_tab( 'fields' );
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields( $settings_page_id );
				do_settings_sections( $settings_page_id );
				submit_button( __( 'Save Settings', 'vb-author-affiliations' ) );
				?>
			</form>
			<?php
		}

		/**
		 * Run method that is called by main class.
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
