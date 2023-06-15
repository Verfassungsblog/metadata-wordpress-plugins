<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-crossref-doi-settings-fields.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-crossref-doi-sanitize.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi-common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi-render.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi-queries.php';


if (!class_exists('VB_CrossRef_DOI_Admin')) {

    class VB_CrossRef_DOI_Admin
    {
        protected $settings_fields;

        protected $setting_pages_by_tab;

        protected $setting_section_names;

        protected $tab_render_functions;

        protected $common;

        protected $queries;

        protected $status;

        protected $update;

        protected $sanitize;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->settings_fields = new VB_CrossRef_DOI_Settings_Fields();
            $this->queries = new VB_CrossRef_DOI_Queries($plugin_name);
            $this->status = new VB_CrossRef_DOI_Status($plugin_name);
            $this->update = new VB_CrossRef_DOI_Update($plugin_name);
            $this->sanitize = new VB_CrossRef_DOI_Sanitize($plugin_name);
        }

        protected function get_tab_labels()
        {
            return array(
                "settings" => "Settings",
                "fields" => "Custom Fields",
                "example" => "Example",
                "status" => "Status",
                "statistics" => "Statistics",
            );
        }

        protected function get_settings_section_labels()
        {
            return array(
                "general" => "General",
                "meta" => "Meta Data",
                "institution" => "Institution",
                "post_selection" => "Post Selection",
                "update" => "Automatic Updates",
                "post_meta" => "Custom Fields for Posts",
                "user_meta" => "Custom Fields for Users",
            );
        }

        protected function get_tab_by_section_map()
        {
            return array(
                "general" => "settings",
                "meta" => "settings",
                "institution" => "settings",
                "post_selection" => "settings",
                "update" => "settings",
                "post_meta" => "fields",
                "user_meta" => "fields",
            );
        }

        protected function get_setting_page_id_by_tab($tab_name)
        {
            return $this->common->plugin_name . "_" . $tab_name . "_tab_settings";
        }

        protected function get_render_function_by_tab($tab_name)
        {
            return array($this, "render_" . $tab_name . "_tab");
        }

        protected function get_setting_section_render_function_by_name($section_name)
        {
            return array($this, 'render_' . $section_name . '_section');
        }

        public function action_admin_init()
        {
            if (!current_user_can('manage_options')) {
                // settings are not allowed for non-admin users
                return;
            }

            // create sections
            $section_labels = $this->get_settings_section_labels();
            $tab_by_section = $this->get_tab_by_section_map();
            foreach ($section_labels as $section_name => $section_label) {
                add_settings_section(
                    $section_name,
                    $section_label,
                    $this->get_setting_section_render_function_by_name($section_name),
                    $this->get_setting_page_id_by_tab($tab_by_section[$section_name])
                );
            }

            // create fields
            foreach ($this->settings_fields->get_list() as $field) {
                $field_id = $this->common->get_settings_field_id($field["name"]);
                $default = $this->common->get_settings_field_default_value($field["name"]);
                $settings_page_id = $this->get_setting_page_id_by_tab($tab_by_section[$field["section"]]);

                register_setting(
                    $settings_page_id,
                    $field_id,
                    array(
                        "type" => $field["type"],
                        "default" => $default,
                    ),
                );

                add_settings_field(
                    $field_id,
                    __($field["label"]),
                    array($this, 'render_field'),
                    $settings_page_id,
                    $field["section"],
                    array(
                        'label_for' => $field_id,
                        "field_name" => $field["name"]
                    )
                );

                if (isset($field["sanitize"])) {
                    add_filter( 'sanitize_option_' . $field_id, array($this->sanitize, $field["sanitize"]), 10, 2 );
                }
            }

            // add css
            wp_register_style(
                $this->common->plugin_name . '-admin-styles',
                plugins_url("css/settings.css", __FILE__),
                array(),
                filemtime(realpath(plugin_dir_path(__FILE__) . "css/settings.css")),
                "screen"
            );

            // add error notice
            add_action('admin_notices', array($this, 'admin_notices'));
        }

        public function admin_notices()
        {
            $error = $this->status->get_last_error();
            if (!empty($error)) {
                ?>
                <div class="error notice">
                    <p><?php echo "Error in " . $this->common->plugin_name . ": " . $error ?></p>
                </div>
                <?php
            }
        }

        public function render_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'Please provide the following mandatory information such that posts can be submitted to CrossRef.',
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_meta_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence what meta data is included when submitting a post to CrossRef.',
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_institution_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following section allows to specify information about the institution that is publishing
                    articles. If at least one identifier is provided, the information is associated with every post.',
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_post_selection_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence which posts will be submitted to CrossRef.',
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_update_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how automatic updates are scheduled and performed.',
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_post_meta_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each individual post. Each option may
                    specify a meta key that is used to store the relevant information for each post. You may use, for
                    example, the Advanced Custom Fields (ACF) plugin to view or edit this meta data.",
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_user_meta_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each author (or user). Each option may
                    specify a meta key that is used to store the relevant information for each user.
                    You may use, for example, the Advanced Custom Fields (ACF) plugin to view or edit this meta data.
                    For user meta data, the corresponding ACF field group needs to be assigned to users instead of posts.
                    This can be achieved by specifying the ACF \"location rule\" for the corresponding field group as:
                    <code>User Role : is equal to : All</code>.",
                    "vb-crossref-doi"
                );
                ?>
            </p>
            <?php
        }

        public function render_field($args)
        {
            $field_id = $args['label_for'];
            $field_name = $args["field_name"];
            $field = $this->settings_fields->get_field($field_name);
            $value = get_option($field_id);
            if ($field["type"] == "boolean") {
                ?>
                <input
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_id); ?>"
                    type="checkbox"
                    <?php echo $value ? "checked" : "" ?>
                >
                <?php
            } else if ($field["type"] == "string" || $field["type"] == "password") {
                ?>
                <input
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($field_id); ?>"
                    class="regular-text code"
                    type="<?php echo $field["type"] == "password" ? "password" : "text" ?>"
                    value="<?php echo $value ?>"
                    placeholder="<?php echo $field["placeholder"] ?>">
                <?php
            } else {
                ?>
                invalid setting type for field '<?php echo esc_html($field_id) ?>'
                <?php
            }
            ?>
            <p class="description">
                <?php echo $field["description"] ?>
            </p>
            <?php
        }

        public function action_admin_menu()
        {
            if (!current_user_can('manage_options')) {
                // admin menu should not be loaded for non-admin users
                return;
            }

            $admin_page_hook = add_submenu_page(
                'options-general.php',
                'Verfassungsblog CrossRef DOI',
                'VB CrossRef DOI',
                'manage_options',
                $this->common->plugin_name,
                array($this, 'render')
            );

            add_action("load-" . $admin_page_hook, array($this, "help_tab"));
            add_action("admin_print_styles-{$admin_page_hook}", array($this, "action_admin_print_styles"));
        }

        public function action_admin_print_styles()
        {
            wp_enqueue_style($this->common->plugin_name . "-admin-styles");
        }

        public function help_tab()
        {
            $screen = get_current_screen();
            $screen->add_help_tab(
                array(
                    "id" => $this->common->plugin_name . "_help_tab",
                    "title" => __("Help"),
                    "content" => "<h2>Verfassungsblog CrossRef DOI</h2>",
                )
            );
        }

        public function render()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (!empty($_POST["reset_settings"])) {
                foreach ($this->settings_fields->get_list() as $field) {
                    $field_id = $this->common->get_settings_field_id($field["name"]);
                    delete_option($field_id);
                }
            }

            if (!empty($_POST["reset_status"])) {
                $this->status->reset_status();
            }

            if (!empty($_POST["manual_update"])) {
                $this->update->do_update();
            }

            if (!empty($_POST["check_modified"])) {
                $this->update->check_for_modified_posts();
            }

            if (!empty($_POST["mark_all_posts_as_modified"])) {
                $this->update->mark_all_posts_as_modified();
            }

            if (!empty($_POST["reset_last_error"])) {
                $this->status->clear_last_error();
            }

            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : "settings";
            $current_tab = isset($this->get_tab_labels()[$current_tab]) ? $current_tab : "settings";

            ?>
            <div class="vb-crossref-doi-admin-header">
                <div class="vb-crossref-doi-title-section">
                    <h1>
                        <?php echo esc_html(get_admin_page_title()); ?>
                    </h1>
                </div>
                <nav class="vb-crossref-doi-admin-header-nav">
                    <?php
                    foreach ($this->get_tab_labels() as $tab_name => $tab_label) {
                        ?>
                        <a class="vb-crossref-doi-admin-header-tab <?php echo $current_tab == $tab_name ? "active" : "" ?>"
                            href="?page=<?php echo $this->common->plugin_name ?>&tab=<?php echo $tab_name ?>">
                            <?php echo $tab_label ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
            </div>
            <hr class="wp-header-end">
            <div class="vb-crossref-doi-admin-content">
                <?php
                call_user_func_array($this->get_render_function_by_tab($current_tab), array());
                ?>
            </div>
            <div class="clear"></div>
            <?php
        }

        public function render_settings_tab()
        {
            ?>
            <?php
            $settings_page_id = $this->get_setting_page_id_by_tab("settings");
            ?>
            <form action="options.php" method="post">
                <?php
                settings_fields($settings_page_id);
                do_settings_sections($settings_page_id);
                submit_button(__('Save Settings', "vb-crossref-doi"));
                ?>
            </form>
            <hr />
            <form method="post" onsubmit="return confirm('Are you sure?');">
                <p>
                    The following action will reset all options of this plugin to their default value
                    (including options in other tabs). Use with care only.
                </p>
                <?php
                submit_button(__('Reset Settings to Default', "vb-crossref-doi"), "secondary", "reset_settings");
                ?>
            </form>
            <?php
        }

        public function render_fields_tab()
        {
            ?>
            <?php
            $settings_page_id = $this->get_setting_page_id_by_tab("fields");
            ?>
            <form action="options.php" method="post">
                <?php
                settings_fields($settings_page_id);
                do_settings_sections($settings_page_id);
                submit_button(__('Save Settings', "vb-crossref-doi"));
                ?>
            </form>
            <?php
        }

        protected function find_example_post()
        {
            $submit_query = $this->queries->query_posts_that_need_submitting(1);
            if (count($submit_query->posts) > 0) {
                return $submit_query->posts[0];
            }
            $posts = get_posts(array('numberposts' => 1));
            if (count($posts) > 0) {
                return $posts[0];
            }
            return false;
        }

        public function render_example_tab()
        {
            // get next post that needs identifying or submitting; or last published post
            $post = $this->find_example_post();
            if ($post) {
                $renderer = new VB_CrossRef_DOI_Render($this->common->plugin_name);
                $json_text = $renderer->render($post, $this->common->get_current_utc_timestamp());

                ?>
                <h2>Example</h2>
                <?php

                if (!empty($json_text)) {
                    ?>
                    <pre><?php echo htmlspecialchars($json_text) ?></pre>
                    <?php
                } else {
                    $error = $renderer->get_last_error();
                    echo "<p>Error: {$error}</p>";
                }
            }
        }

        public function render_status_tab()
        {
            $last_error = $this->status->get_last_error();
            ?>
            <h2>Status</h2>
            <ul>
                <li>Automatic Update: <?php echo $this->common->get_settings_field_value("auto_update") ? "enabled" : "disabled" ?></li>
                <li>Update Interval: <?php echo $this->update->get_update_interval_in_minutes() ?> min</li>
                <li>Last Update: <?php echo $this->status->get_last_update_text() ?></li>
                <li>Last Check for Modified Posts: <?php echo $this->status->get_text_of_last_modified_check() ?></li>
                <li>Last Error: <?php echo $last_error ? $last_error : "none" ?></li>
            </ul>
            <form method="post" onsubmit="return;">
            <p>
                <?php
                submit_button(__('Manually Update Now', "vb-crossref-doi"), "primary", "manual_update", false);
                echo " ";
                submit_button(__('Manually Check for Modified Posts Now', "vb-crossref-doi"), "secondary", "check_modified", false);
                ?>
            </p>
            <p>
                <?php
                submit_button(__('Reset Last Error', "vb-crossref-doi"), "secondary", "reset_last_error", false);
                ?>
            </p>
            </form>
            <hr />
            <h2>Resubmit all Posts</h2>
            <p>
                Clicking the following button will schedule all published posts to be re-submitted to CrossRef.
                This could take a very long time.
            </p>
            <form method="post" onsubmit="return confirm('Are you sure?');">
            <p>
                <?php
                submit_button(
                    __('Mark All Posts as Modified', "vb-crossref-doi"),
                    "secondary",
                    "mark_all_posts_as_modified",
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
            <p>
                <?php
                submit_button(__('Reset Status of all Posts', "vb-crossref-doi"), "secondary", "reset_status", false);
                ?>
            </p>
            </form>
            <?php
        }

        public function render_statistics_tab()
        {
            ?>
            <h2>Statistics</h2>
            <?php

            $have_doi = $this->queries->get_number_of_posts_that_have_doi();
            $need_submitting_never = $this->queries->get_number_of_posts_that_were_not_submitted_yet();
            $need_submitting_retry = $this->queries->get_number_of_posts_that_should_be_retried();
            $need_submitting_modified = $this->queries->get_number_of_posts_that_need_submitting_because_modified();
            $were_submitted = $this->queries->get_number_of_posts_that_were_successfully_submitted();
            $pending = $this->queries->get_number_of_posts_that_have_pending_submissions();
            $were_modified = $this->queries->get_number_of_posts_that_were_modified_since_last_check();
            ?>
            <ul>
                <li>Posts that have a DOI: <?php echo $have_doi; ?></li>
                <li>Posts that were modified since last update: <?php echo $were_modified ?></li>
                <li>Posts that need submitting because no DOI yet: <?php echo $need_submitting_never; ?></li>
                <li>Posts that need submitting because modified: <?php echo $need_submitting_modified; ?></li>
                <li>Posts that need submitting again after error: <?php echo $need_submitting_retry; ?></li>
                <li>Posts that have pending submission: <?php echo $pending; ?></li>
                <li>Posts that were successfully submitted: <?php echo $were_submitted; ?></li>
            </ul>
            <hr/>
            <?php
            $error_count = $this->queries->get_number_of_posts_with_submit_error();
            $error_query = $this->queries->query_posts_with_submit_error(5);
            ?>
            <h3>Posts with Errors (<?php echo $error_count ?>)</h3>
            <ul>
            <?php
            foreach($error_query->posts as $post) {
                ?>
                <li>
                    <a href="<?php echo get_edit_post_link($post) ?>">
                        Post [id=<?php echo $post->ID ?>]
                    </a>: <?php echo $this->status->get_post_submit_error($post) ?>
                </li>
                <?php
            }
            ?>
            </ul>
            <?php
        }

        public function run()
        {
            if (!is_admin()) {
                // settings should not be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_init', array($this, 'action_admin_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
        }

    }

}