<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-crossref-doi_setting_fields.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-crossref-doi_render.php';


if (!class_exists('VB_CrossRef_DOI_Admin')) {

    class VB_CrossRef_DOI_Admin
    {
        protected $setting_fields;

        protected $setting_pages_by_tab;

        protected $setting_section_names;

        protected $tab_render_functions;

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_CrossRef_DOI_Common($plugin_name);
            $this->setting_fields = new VB_CrossRef_DOI_Setting_Fields();
        }

        protected function get_tab_labels()
        {
            return array(
                "settings" => "Settings",
                "fields" => "Custom Fields",
                "example" => "Example",
                "status" => "Status",
            );
        }

        protected function get_settings_section_labels()
        {
            return array(
                "general" => "General",
                "post_meta" => "Custom Fields for Posts",
                "user_meta" => "Custom Fields for Users",
            );
        }

        protected function get_tab_by_section_map()
        {
            return array(
                "general" => "settings",
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
            foreach ($this->setting_fields->get_list() as $field) {
                $field_id = $this->common->get_settings_field_id($field["name"]);
                $default = $this->common->get_settings_field_default_value($field["name"]);
                $settings_page_id = $this->get_setting_page_id_by_tab($tab_by_section[$field["section"]]);

                register_setting(
                    $settings_page_id,
                    $field_id,
                    array(
                        "type" => $field["type"],
                        "default" => $default
                    )
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
            /*$error = $this->status->get_last_error();
            if (!empty($error)) {
                ?>
                <div class="error notice">
                    <p><?php echo "Error in " . $this->common->plugin_name . ": " . $error ?></p>
                </div>
                <?php
            }*/
        }

        public function render_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how metadata is submitted to CrossRef.',
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
            $field = $this->setting_fields->get_field($field_name);
            $value = get_option($field_id);
            if ($field["type"] == "boolean") {
                ?>
                <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" type="checkbox"
                    <?php echo $value ? "checked" : "" ?>
                >
                <?php
            } else {
                ?>
                <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" class="regular-text code"
                    type="text" value="<?php echo $value ?>" placeholder="<?php echo $field["placeholder"] ?>">
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
                foreach ($this->setting_fields->get_list() as $field) {
                    $field_id = $this->common->get_settings_field_id($field["name"]);
                    delete_option($field_id);
                }
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
                $json_text = $renderer->render($post);

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
            // empty
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