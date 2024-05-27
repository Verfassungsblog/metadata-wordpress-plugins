<?php
/**
 * Class that handles admin interface.
 *
 * @package vb-crossref-doi
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . '/class-vb-crossref-doi-settings-fields.php';
require_once plugin_dir_path( __FILE__ ) . '/class-vb-crossref-doi-sanitize.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-crossref-doi-common.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-crossref-doi-render.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-vb-crossref-doi-queries.php';


if ( ! class_exists( 'VB_CrossRef_DOI_Admin' ) ) {

	/**
	 * Class that handles admin interface.
	 */
	class VB_CrossRef_DOI_Admin {

		/**
		 * Settings fields class.
		 *
		 * @var VB_CrossRef_DOI_Settings_Fields
		 */
		protected $settings_fields;

		/**
		 * Common methods
		 *
		 * @var VB_CrossRef_DOI_Common
		 */
		protected $common;

		/**
		 * Class that provides custom database queries
		 *
		 * @var VB_CrossRef_DOI_Queries
		 */
		protected $queries;

		/**
		 * Class that handles post submission status
		 *
		 * @var VB_CrossRef_DOI_Status
		 */
		protected $status;

		/**
		 * Class that handles schedules or manually updates
		 *
		 * @var VB_CrossRef_DOI_Update
		 */
		protected $update;

		/**
		 * Class that checks user input for admin settings.
		 *
		 * @var VB_CrossRef_DOI_Sanitize
		 */
		protected $sanitize;

		/**
		 * Initialize class with the plugin name.
		 *
		 * @param string $plugin_name the name of this plugin.
		 */
		public function __construct( $plugin_name ) {
			$this->common          = new VB_CrossRef_DOI_Common( $plugin_name );
			$this->settings_fields = new VB_CrossRef_DOI_Settings_Fields();
			$this->queries         = new VB_CrossRef_DOI_Queries( $plugin_name );
			$this->status          = new VB_CrossRef_DOI_Status( $plugin_name );
			$this->update          = new VB_CrossRef_DOI_Update( $plugin_name );
			$this->sanitize        = new VB_CrossRef_DOI_Sanitize( $plugin_name );
		}

		/**
		 * Return associative array of tab labels that visualizes as a menu.
		 *
		 * @return array array of (tab name => label)
		 */
		protected function get_tab_labels() {
			return array(
				'settings'   => 'Settings',
				'fields'     => 'Custom Fields',
				'example'    => 'Example',
				'status'     => 'Status',
				'statistics' => 'Statistics',
			);
		}

		/**
		 * Return associative array of section labels.
		 *
		 * @return array array of (section name => label)
		 */
		protected function get_settings_section_labels() {
			return array(
				'general'        => 'General',
				'meta'           => 'Meta Data',
				'institution'    => 'Institution',
				'post_selection' => 'Post Selection',
				'update'         => 'Automatic Updates',
				'post_meta'      => 'Custom Fields for Posts',
				'user_meta'      => 'Custom Fields for Users',
			);
		}

		/**
		 * Return associative array of which settings section should be rendered on what tab.
		 *
		 * @return array array of (section name => tab name)
		 */
		protected function get_tab_by_section_map() {
			return array(
				'general'        => 'settings',
				'meta'           => 'settings',
				'institution'    => 'settings',
				'post_selection' => 'settings',
				'update'         => 'settings',
				'post_meta'      => 'fields',
				'user_meta'      => 'fields',
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
		 * Checks whether a submit button was clicked (other than "save settings") and if nonce is correct.
		 * perform corresponding action.
		 */
		protected function check_and_process_submit_buttons() {
			if ( ! empty( $_POST['reset_settings'] ) &&
					check_admin_referer( $this->common->plugin_name . '_reset' ) ) {
				foreach ( $this->settings_fields->get_list() as $field ) {
					$field_id = $this->common->get_settings_field_id( $field['name'] );
					delete_option( $field_id );
				}
			}

			if ( ! empty( $_POST['reset_last_error'] ) &&
					check_admin_referer( $this->common->plugin_name . '_reset_last_error' ) ) {
				$this->status->clear_last_error();
			}

			if ( ! empty( $_POST['manual_update'] ) &&
					check_admin_referer( $this->common->plugin_name . '_update' ) ) {
				$this->update->do_update();
			}

			if ( ! empty( $_POST['check_modified'] ) &&
					check_admin_referer( $this->common->plugin_name . '_update' ) ) {
				$this->update->check_for_modified_posts();
			}

			if ( ! empty( $_POST['add_include_category'] ) &&
					check_admin_referer( $this->common->plugin_name . '_include_exclude' ) ) {
				$this->update->add_include_category_to_posts_with_doi();
			}

			if ( ! empty( $_POST['remove_include_category'] ) &&
					check_admin_referer( $this->common->plugin_name . '_include_exclude' ) ) {
				$this->update->remove_include_category_from_all_posts();
			}

			if ( ! empty( $_POST['add_exclude_category'] ) &&
					check_admin_referer( $this->common->plugin_name . '_include_exclude' ) ) {
				$this->update->add_exclude_category_to_posts_without_doi();
			}

			if ( ! empty( $_POST['remove_exclude_category'] ) &&
					check_admin_referer( $this->common->plugin_name . '_include_exclude' ) ) {
				$this->update->remove_exclude_category_from_all_posts();
			}

			if ( ! empty( $_POST['mark_all_posts_as_modified'] ) &&
					check_admin_referer( $this->common->plugin_name . '_mark_all_posts_as_modified' ) ) {
				$this->update->mark_all_posts_as_modified();
			}

			if ( ! empty( $_POST['reset_status'] ) &&
					check_admin_referer( $this->common->plugin_name . '_reset_status' ) ) {
				$this->status->reset_status();
			}

		}

		/**
		 * WordPress init action hook.
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
					),
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

				if ( isset( $field['sanitize'] ) ) {
					add_filter( 'sanitize_option_' . $field_id, array( $this->sanitize, $field['sanitize'] ), 10, 2 );
				}
			}

			// add css.
			wp_register_style(
				$this->common->plugin_name . '-admin-styles',
				plugins_url( 'css/settings.css', __FILE__ ),
				array(),
				filemtime( realpath( plugin_dir_path( __FILE__ ) . 'css/settings.css' ) ),
				'screen'
			);

			// add error notice.
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		/**
		 * Render function for displaying error message in the admin interface.
		 */
		public function admin_notices() {
			$error             = $this->status->get_last_error();
			$show_admin_notice = $this->common->get_settings_field_value( 'show_admin_notice' );
			if ( ! empty( $error ) && $show_admin_notice ) {
				$admin_notice_field_name = $this->common->plugin_name . '_dismiss_admin_notice';

				if ( ! empty( $_POST[ $admin_notice_field_name ] ) && check_admin_referer( $admin_notice_field_name ) ) {
					$this->status->clear_last_error();
					return;
				}

				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<?php echo 'Error in ' . esc_html( $this->common->plugin_name ) . ': ' . esc_html( $error ); ?>
					</p>
					<form method="post" onsubmit="return;">
						<?php
						wp_nonce_field( $admin_notice_field_name );
						?>
						<button
							type="submit"
							name="<?php echo esc_attr( $admin_notice_field_name ); ?>"
							value="true"
							class="notice-dismiss"
						>
							<span class="screen-reader-text">dismiss</span>
						</button>
					</form>
				</div>
				<?php
			}
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
					'Please provide the following mandatory information such that posts can be submitted to CrossRef.',
					'vb-crossref-doi'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the meta data section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_meta_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following options influence what meta data is included when submitting a post to CrossRef.',
					'vb-crossref-doi'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the institution section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_institution_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following section allows to specify information about the institution that is publishing
                    articles. If at least one identifier is provided, the information is associated with every post.',
					'vb-crossref-doi'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the post selection section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_post_selection_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following options influence which posts will be submitted to CrossRef.',
					'vb-crossref-doi'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Renders the update section description.
		 *
		 * @param array $args the section arguments.
		 */
		public function render_update_section( $args ) {
			?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>">
				<?php
				echo __( // phpcs:ignore
					'The following options influence how automatic updates are scheduled and performed.',
					'vb-crossref-doi'
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
					'The following settings add or overwrite meta data for each individual post. Each option may
                    specify a meta key that is used to store the relevant information for each post. You may use, for
                    example, the Advanced Custom Fields (ACF) plugin to view or edit this meta data.',
					'vb-crossref-doi'
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
					'The following settings add or overwrite meta data for each author (or user). Each option may
                    specify a meta key that is used to store the relevant information for each user.
                    You may use, for example, the Advanced Custom Fields (ACF) plugin to view or edit this meta data.
                    For user meta data, the corresponding ACF field group needs to be assigned to users instead of posts.
                    This can be achieved by specifying the ACF "location rule" for the corresponding field group as:
                    <code>User Role : is equal to : All</code>.',
					'vb-crossref-doi'
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
				/>
				<?php
			} elseif ( 'string' === $field['type'] || 'password' === $field['type'] ) {
				?>
					<input
						id="<?php echo esc_attr( $field_id ); ?>"
						name="<?php echo esc_attr( $field_id ); ?>"
						class="regular-text code"
						type="<?php echo 'password' === $field['type'] ? 'password' : 'text'; ?>"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
					/>
				<?php
			} else {
				?>
					invalid setting type for field '<?php echo esc_html( $field_id ); ?>'
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
				'Verfassungsblog CrossRef DOI',
				'VB CrossRef DOI',
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
					'content' => '
						<h2>Verfassungsblog CrossRef DOI</h2>
						<p>More information about this plugin can be found on the
						<a href="https://github.com/Verfassungsblog/metadata-wordpress-plugins/tree/main/code/packages/vb-crossref-doi" target="_blank">GitHub</a>
						page.
					',
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

			$this->check_and_process_submit_buttons();

			$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore
			$current_tab = isset( $this->get_tab_labels()[ $current_tab ] ) ? $current_tab : 'settings';

			?>
			<div class="vb-crossref-doi-admin-header">
				<div class="vb-crossref-doi-title-section">
					<h1>
						<?php echo esc_html( get_admin_page_title() ); ?>
					</h1>
				</div>
				<nav class="vb-crossref-doi-admin-header-nav">
					<?php
					foreach ( $this->get_tab_labels() as $tab_name => $tab_label ) {
						?>
						<a class="vb-crossref-doi-admin-header-tab <?php echo $current_tab === $tab_name ? 'active' : ''; ?>"
							href="?page=<?php echo esc_attr( $this->common->plugin_name . '&tab=' . $tab_name ); ?>">
							<?php echo esc_html( $tab_label ); ?>
						</a>
						<?php
					}
					?>
				</nav>
			</div>
			<hr class="wp-header-end">
			<div class="vb-crossref-doi-admin-content">
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
				submit_button( __( 'Save Settings', 'vb-crossref-doi' ) );
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
				submit_button( __( 'Reset Settings to Default', 'vb-crossref-doi' ), 'secondary', 'reset_settings' );
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
				submit_button( __( 'Save Settings', 'vb-crossref-doi' ) );
				?>
			</form>
			<?php
		}

		/**
		 * Return the post in case a user provided a specific post id in the example tab.
		 *
		 * @return WP_Post|false $post the selected post
		 */
		protected function find_example_post_from_user_input() {
			if ( ! empty( $_POST['example_post'] ) && isset( $_POST[ $this->common->plugin_name . '_example-post' ] ) &&
					check_admin_referer( $this->common->plugin_name . '_example_post' ) ) {
				$post_id = (int) $_POST[ $this->common->plugin_name . '_example-post' ];
				$posts   = get_posts(
					array(
						'numberposts' => 1,
						'p'           => $post_id,
					),
				);
				if ( count( $posts ) > 0 ) {
					return $posts[0];
				}
			}

			return false;
		}

		/**
		 * Returns a appropriate example post that is used to render metadata.
		 *
		 * @return WP_Post|bool the example post
		 */
		protected function find_example_post() {
			$user_post = $this->find_example_post_from_user_input();
			if ( ! empty( $user_post ) ) {
				return $user_post;
			}
			$submit_query = $this->queries->query_posts_that_need_submitting( 1 );
			if ( count( $submit_query->posts ) > 0 ) {
				return $submit_query->posts[0];
			}
			$posts = get_posts( array( 'numberposts' => 1 ) );
			if ( count( $posts ) > 0 ) {
				return $posts[0];
			}
			return false;
		}

		/**
		 * Render function for example tab.
		 */
		public function render_example_tab() {
			// get next post that needs identifying or submitting; or last published post.
			$post = $this->find_example_post();
			if ( $post ) {
				$renderer  = new VB_CrossRef_DOI_Render( $this->common->plugin_name );
				$json_text = $renderer->render( $post, $this->common->get_current_utc_timestamp() );
				?>
				<h2>
					Example:
					<a href="<?php echo esc_attr( get_edit_post_link( $post ) ); ?>">
							<?php echo esc_html( wp_strip_all_tags( get_the_title( $post ) ) ); ?>
					</a>
				</h2>
				<form method="post" onsubmit="return;">
					<?php
					wp_nonce_field( $this->common->plugin_name . '_example_post' );
					?>
					<p>
					Show Post with ID
						<input
							type="text"
							name="<?php echo esc_attr( $this->common->plugin_name . '_example-post' ); ?>"
							value="<?php echo esc_attr( $post->ID ); ?>"
						/>
					<?php
					submit_button( __( 'Select', 'vb-crossref-doi' ), 'secondary', 'example_post', false );
					?>
					</p>
				</form>
				<p>The following XML document would be submitted to CrossRef.</p>
				<?php

				if ( ! empty( $json_text ) ) {
					?>
					<pre><?php echo htmlspecialchars( $json_text );  // phpcs:ignore ?></pre>
					<?php
				} else {
					$error = $renderer->get_last_error();
					echo '<p>Error: ' . esc_html( $error ) . '</p>';
				}
			}
		}

		/**
		 * Render function for status tab.
		 */
		public function render_status_tab() {
			$last_error = $this->status->get_last_error();
			?>
			<h2>Status</h2>
			<ul>
				<li>Automatic Update:
					<?php echo $this->common->get_settings_field_value( 'auto_update' ) ? 'enabled' : 'disabled'; ?>
				</li>
				<li>Update Interval:
					<?php echo esc_html( $this->update->get_update_interval_in_minutes() ); ?> min
				</li>
				<li>Last Update:
					<?php echo esc_html( $this->status->get_last_update_text() ); ?>
				</li>
				<li>Last Check for Modified Posts:
					<?php echo esc_html( $this->status->get_text_of_last_modified_check() ); ?>
				</li>
				<li>Last Error:
					<?php echo esc_html( $last_error ? $last_error : 'none' ); ?>
				</li>
			</ul>
			<form method="post" onsubmit="return;">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_reset_last_error' );
				?>
				<p>
					<?php
					submit_button( __( 'Reset Last Error', 'vb-crossref-doi' ), 'secondary', 'reset_last_error', false );
					?>
				</p>
				</form>
			<hr />
			<h2>Manual Update</h2>
			<p>
				The following buttons allow to trigger a manual update or partial update. An update will check the
				database for new or modified posts and, if neccessary, submit them to CrossRef. If automatic updates
				are enabled, the same update process is performed as per schedule.
			</p>
			<form method="post" onsubmit="return;">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_update' );
				?>
				<p>
					<?php
					submit_button( __( 'Manually Update', 'vb-crossref-doi' ), 'primary', 'manual_update', false );
					echo ' ';
					submit_button( __( 'Only Check for Modified Posts', 'vb-crossref-doi' ), 'secondary', 'check_modified', false );
					?>
				</p>
			</form>
			<hr />
			<h2>Include and Exclude Posts</h2>
			<p>
				The following buttons allow to included or exclude posts with or without a DOI by assigning them the
				corresponding categories defined in the "settings" tab. Depending on the number of posts in the
				database, this might take a long time.
			</p>
			<form method="post" onsubmit="return confirm('Are you sure?');">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_include_exclude' );
				?>
				<p>
					<?php
					submit_button(
						__( 'Add Include Category to Posts with a DOI', 'vb-crossref-doi' ),
						'secondary',
						'add_include_category',
						false,
					);
					echo ' ';
					submit_button(
						__( 'Remove Include Category from all Posts', 'vb-crossref-doi' ),
						'secondary',
						'remove_include_category',
						false
					);
					echo '</p><p>';
					submit_button(
						__( 'Add Exclude Category to Posts without a DOI', 'vb-crossref-doi' ),
						'secondary',
						'add_exclude_category',
						false
					);
					echo ' ';
					submit_button(
						__( 'Remove Exclude Category from all Posts', 'vb-crossref-doi' ),
						'secondary',
						'remove_exclude_category',
						false
					);
					?>
				</p>
			</form>
			<hr />
			<h2>Resubmit all Posts</h2>
			<p>
				Clicking the following button will schedule all eligible posts to be re-submitted to CrossRef.
				This could take a very long time.
			</p>
			<form method="post" onsubmit="return confirm('Are you sure?');">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_mark_all_posts_as_modified' );
				?>
				<p>
					<?php
					submit_button(
						__( 'Mark All Posts as Modified', 'vb-crossref-doi' ),
						'secondary',
						'mark_all_posts_as_modified',
						false
					);
					?>
				</p>
			</form>
			<hr />
			<h2>Reset</h2>
			<p>
				Clicking the following button will remove all status information about posts from the database.
				This includes whether a post was already submitted to CrossRef and potential error messages. Of course,
				DOIs are not deleted. Effectively, the status of the database will be the same as if the plugin was
				just freshly installed.
			</p>
			<form method="post" onsubmit="return confirm('Are you sure?');">
				<?php
				wp_nonce_field( $this->common->plugin_name . '_reset_status' );
				?>
				<p>
					<?php
					submit_button( __( 'Reset Status of all Posts', 'vb-crossref-doi' ), 'secondary', 'reset_status', false );
					?>
				</p>
			</form>
			<?php
		}

		/**
		 * Render function for statistics tab.
		 */
		public function render_statistics_tab() {
			?>
			<h2>Statistics</h2>
			<?php

			$have_doi                 = $this->queries->get_number_of_posts_that_have_doi();
			$need_submitting_never    = $this->queries->get_number_of_posts_that_were_not_submitted_yet();
			$need_submitting_retry    = $this->queries->get_number_of_posts_that_should_be_retried();
			$need_submitting_modified = $this->queries->get_number_of_posts_that_need_submitting_because_modified();
			$were_submitted           = $this->queries->get_number_of_posts_that_were_successfully_submitted();
			$pending                  = $this->queries->get_number_of_posts_that_have_pending_submissions();
			$were_modified            = $this->queries->get_number_of_posts_that_were_modified_since_last_check();
			$add_include              = $this->queries->get_number_of_posts_that_can_be_added_to_include_category();
			$remove_include           = $this->queries->get_number_of_posts_that_have_include_category();
			$add_exclude              = $this->queries->get_number_of_posts_that_can_be_added_to_exclude_category();
			$remove_exclude           = $this->queries->get_number_of_posts_that_have_exclude_category();
			?>
			<ul>
				<li>Posts that have a DOI:
					<?php echo esc_html( $have_doi ); ?>
				</li>
			</ul>
			<ul>
				<li>Posts that are assigned to the include category:
					<?php echo esc_html( $remove_include ); ?>
				</li>
				<li>Posts that would be added to the include category (published, have DOI, not already included):
					<?php echo esc_html( $add_include ); ?>
				</li>
				<li>Posts that are assigned to the exclude category:
					<?php echo esc_html( $remove_exclude ); ?>
				</li>
				<li>Posts that would be added to the exclude category (published, no DOI, not already excluded):
					<?php echo esc_html( $add_exclude ); ?>
				</li>
			</ul>
			<ul>
				<li>Posts that were modified since last update:
					<?php echo esc_html( $were_modified ); ?>
				</li>
			</ul>
			<ul>
				<li>Posts that need submitting because no DOI yet:
					<?php echo esc_html( $need_submitting_never ); ?>
				</li>
				<li>Posts that need submitting because modified:
					<?php echo esc_html( $need_submitting_modified ); ?>
				</li>
				<li>Posts that need submitting again after error:
					<?php echo esc_html( $need_submitting_retry ); ?>
				</li>
			</ul>
			<ul>
				<li>Posts that have pending submission:
					<?php echo esc_html( $pending ); ?>
				</li>
				<li>Posts that were successfully submitted:
					<?php echo esc_html( $were_submitted ); ?>
				</li>
			</ul>
			<hr />
			<?php
			$error_count = $this->queries->get_number_of_posts_with_submit_error();
			$error_query = $this->queries->query_posts_with_submit_error( 5 );
			?>
			<h3>
				Posts with Errors (<?php echo esc_html( $error_count ); ?>)
			</h3>
			<ul>
				<?php
				foreach ( $error_query->posts as $post ) {
					?>
					<li>
						<a href="<?php echo esc_attr( get_edit_post_link( $post ) ); ?>">
							Post [id=<?php echo esc_html( $post->ID ); ?>]
						</a>:
						<?php echo esc_html( $this->status->get_post_submit_error( $post ) ); ?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}

		/**
		 * Run method that is called by the main class.
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
