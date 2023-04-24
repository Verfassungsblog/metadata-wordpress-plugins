<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_converter.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_renderer.php';

if (!class_exists('VB_Metadata_Export_Settings')) {

    class VB_Metadata_Export_Settings
    {

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
        }

        public function action_init()
        {
            $section_names = array(
                "general" => $this->common->settings_page_name . "_general",
                "post" => $this->common->settings_page_name . "_post",
            );

            add_settings_section(
                $section_names["general"],
                __("General"),
                array($this, 'callback_general_section'),
                $this->common->settings_page_name
            );

            add_settings_section(
                $section_names["post"],
                __("Advanced Custom Fields"),
                array($this, 'callback_post_section'),
                $this->common->settings_page_name
            );

            foreach (array_values($this->common->get_settings_fields()) as $i => $field) {
                $field_id = $this->common->get_value_field_id($field["name"]);

                register_setting($this->common->settings_page_name, $field_id, array(
                    "type" => $field["type"],
                    "default" => $field["default"]
                )
                );

                add_settings_field(
                    $field_id,
                    __($field["label"]),
                    array($this, 'callback_field'),
                    $this->common->settings_page_name,
                    $section_names[$field["section"]],
                    array(
                        'label_for' => $field_id,
                        "field_name" => $field["name"]
                    )
                );
            }

            wp_register_style(
                'vb-metadata-export-admin-styles',
                plugins_url("css/settings.css", __FILE__),
                array(),
                filemtime(realpath(plugin_dir_path(__FILE__) . "css/settings.css")),
                "screen"
            );
        }

        public function callback_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how Marc21 XML documents are created.',
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function callback_post_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each individual post via the Advanced Custom
                    Fields (ACF) plugin. Each option specifies the ACF field key that contains the relevant information.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function callback_field($args)
        {
            $field_id = $args['label_for'];
            $field_name = $args["field_name"];
            $field = $this->common->get_settings_field_info($field_name);
            $option = get_option($field_id);
            ?>
            <input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" class="regular-text code"
                type="text" value="<?php echo $option ?>" placeholder="<?php echo $field["placeholder"] ?>">
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
                'Verfassungsblog Metadata Export',
                'VB Metadata Export',
                'manage_options',
                'vb_metadata_export',
                array($this, 'render')
            );

            add_action("load-" . $admin_page_hook, array($this, "help_tab"));
            add_action("admin_print_styles-{$admin_page_hook}", array($this, "action_admin_print_styles"));
        }

        public function action_admin_print_styles()
        {
            wp_enqueue_style("vb-metadata-export-admin-styles");
        }

        public function help_tab()
        {
            $screen = get_current_screen();
            $screen->add_help_tab(
                array(
                    "id" => $this->common->plugin_name . "_help_tab",
                    "title" => __("Help"),
                    "content" => "<h2>Verfassungsblog Metadata Export</h2>",
                )
            );
        }

        public function render()
        {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (!empty($_POST["reset"])) {
                foreach (array_values($this->common->get_settings_fields()) as $field) {
                    $field_id = $this->common->get_value_field_id($field["name"]);
                    delete_option($field_id);
                }
            }

            $tab = isset($_GET['tab']) ? $_GET['tab'] : "general";

            $menu_items = array(
                "general" => "General",
                "marc21" => "Marc21 XML",
                "mods" => "MODS 3.7",
                "oai-pmh" => "OAI-PMH 2.0",
                "dc" => "Dublin Core"
            );

            $render_functions = array(
                "general" => array($this, "render_general_tab"),
                "marc21" => array($this, "render_marc21_tab"),
                "mods" => array($this, "render_mods_tab"),
                "oai-pmh" => array($this, "render_oai_pmh_tab"),
                "dc" => array($this, "render_dublin_core_tab")
            );

            ?>
            <div class="vb-metadata-export-admin-header">
                <div class="vb-metadata-export-title-section">
                    <h1>
                        <?php echo esc_html(get_admin_page_title()); ?>
                    </h1>
                </div>
                <nav class="vb-metadata-export-admin-header-nav">
                    <?php
                    foreach ($menu_items as $menu_tab => $menu_label) {
                        ?>
                        <a class="vb-metadata-export-admin-header-tab <?php echo $tab == $menu_tab ? "active" : "" ?>"
                            href="?page=vb_metadata_export&tab=<?php echo $menu_tab ?>">
                            <?php echo $menu_label ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
            </div>
            <hr class="wp-header-end">
            <div class="vb-metadata-export-admin-content">
                <?php
                call_user_func_array($render_functions[$tab], array());
                ?>
            </div>
            <div class="clear"></div>
            <?php
        }

        public function render_general_tab()
        {
            ?>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->common->settings_page_name);
                do_settings_sections($this->common->settings_page_name);
                submit_button(__('Save Settings', "vb-metadata-export"));
                ?>
            </form>
            <hr />
            <form method="post" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="reset" value="true" />
                <p>
                    The following action will reset all options of this plugin to their default value
                    (including options in other tabs). Use with care only.
                </p>
                <?php
                submit_button(__('Reset Settings to Default', "vb-metadata-export"), "secondary", "reset");
                ?>
            </form>
            <?php
        }

        public function render_marc21_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Renderer($this->common->plugin_name);
            $marc21xml = $renderer->render($posts[0]);
            ?>
            <h2>
                <?php echo __("Marc21 Example", "vb-metadata-export") ?>
            </h2>

            <pre><?php echo esc_html($marc21xml) ?></pre>

            <?php
        }

        public function render_mods_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Renderer($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();

            $marc21xml = $renderer->render($posts[0]);
            $mods_xml = $converter->convertMarc21ToMods($marc21xml);

            ?>
            <h2>
                <?php echo __("MODS Example", "vb-metadata-export") ?>
            </h2>

            <pre><?php echo esc_html($mods_xml) ?></pre>

            <?php
        }

        public function render_oai_pmh_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Renderer($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();

            $marc21xml = $renderer->render($posts[0]);
            $oai_dc = $converter->convertMarc21ToOaiDc($marc21xml);

            ?>
            <h2>
                <?php echo __("OAI DC Example", "vb-metadata-export") ?>
            </h2>

            <pre><?php echo esc_html($oai_dc) ?></pre>
            <?php
        }

        public function render_dublin_core_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Renderer($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();

            $marc21xml = $renderer->render($posts[0]);
            $rdf_dc = $converter->convertMarc21ToRdfDc($marc21xml);

            ?>
            <h2>
                <?php echo __("RDF Dublin Core Example", "vb-metadata-export") ?>
            </h2>

            <pre><?php echo esc_html($rdf_dc) ?></pre>
            <?php
        }

        public function run()
        {
            if (!is_admin()) {
                // settings should not be loaded for non-admin-interface pages
                return;
            }

            add_action('admin_init', array($this, 'action_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
        }

    }

}