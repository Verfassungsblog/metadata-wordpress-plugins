<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_converter.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_marc21xml.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_oaipmh.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_setting_fields.php';

if (!class_exists('VB_Metadata_Export_Admin')) {

    class VB_Metadata_Export_Admin
    {
        protected $setting_fields;

        protected $setting_pages_by_tab;

        protected $setting_section_names;

        protected $tab_render_functions;

        protected $common;

        public function __construct($plugin_name)
        {
            $this->common = new VB_Metadata_Export_Common($plugin_name);
            $this->setting_fields = new VB_Metadata_Export_Setting_Fields();
        }

        protected function get_tab_labels()
        {
            return array(
                "general" => "General",
                "acf" => "ACF",
                "marc21" => "Marc21 XML",
                "mods" => "MODS",
                "dc" => "Dublin Core",
                "oai_pmh" => "OAI-PMH 2.0",
            );
        }

        protected function get_settings_section_labels()
        {
            return array(
                "general" => "General",
                "language" => "Language",
                "post_acf" => "Advanced Custom Fields for Posts",
                "user_acf" => "Advanced Custom Fields for Users",
                "marc21" => "Marc21 XML Settings",
                "mods" => "MODS Settings",
                "oai_pmh" => "OAI-PMH 2.0 Settings",
                "dc" => "Dublin Core Settings",
            );
        }

        protected function get_tab_by_section_map()
        {
            return array(
                "general" => "general",
                "language" => "general",
                "post_acf" => "acf",
                "user_acf" => "acf",
                "marc21" => "marc21",
                "mods" => "mods",
                "oai_pmh" => "oai_pmh",
                "dc" => "dc",
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

        public function action_init()
        {
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
                'vb-metadata-export-admin-styles',
                plugins_url("css/settings.css", __FILE__),
                array(),
                filemtime(realpath(plugin_dir_path(__FILE__) . "css/settings.css")),
                "screen"
            );
        }

        public function render_general_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'The following options influence how metadata is exported independent of the specific format.',
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_language_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    'All posts are assumed to be written in a default language unless they are assigned to a special
                    category indicating that those post were written in an alternative language.',
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_post_acf_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each individual post via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_user_acf_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings add or overwrite meta data for each author (or user) via the Advanced Custom
                    Fields (ACF) plugin. Each option may specify the ACF field key that contains the relevant information.
                    The corresponding ACF field needs to be assigned to users instead of posts. This can be achieved by
                    an ACF \"location rule\" for the field group: <code>User Role : is equal to : All</code>.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_marc21_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings influence how Marc21 XML is generated.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_mods_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings influence how metadata is exported as MODS.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_oai_pmh_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings influence how metadata is provided via the OAI-PMH 2.0 interface.",
                    "vb-metadata-export"
                );
                ?>
            </p>
            <?php
        }

        public function render_dc_section($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php echo __(
                    "The following settings influence how metadata is exported as Dublin Core.",
                    "vb-metadata-export"
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
                'Verfassungsblog Metadata Export',
                'VB Metadata Export',
                'manage_options',
                $this->common->plugin_name,
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
                foreach ($this->setting_fields->get_list() as $field) {
                    $field_id = $this->common->get_settings_field_id($field["name"]);
                    delete_option($field_id);
                }
            }

            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : "general";

            ?>
            <div class="vb-metadata-export-admin-header">
                <div class="vb-metadata-export-title-section">
                    <h1>
                        <?php echo esc_html(get_admin_page_title()); ?>
                    </h1>
                </div>
                <nav class="vb-metadata-export-admin-header-nav">
                    <?php
                    foreach ($this->get_tab_labels() as $tab_name => $tab_label) {
                        ?>
                        <a class="vb-metadata-export-admin-header-tab <?php echo $current_tab == $tab_name ? "active" : "" ?>"
                            href="?page=<?php echo $this->common->plugin_name ?>&tab=<?php echo $tab_name ?>">
                            <?php echo $tab_label ?>
                        </a>
                        <?php
                    }
                    ?>
                </nav>
            </div>
            <hr class="wp-header-end">
            <div class="vb-metadata-export-admin-content">
                <?php
                $settings_page_id = $this->get_setting_page_id_by_tab($current_tab);
                ?>
                <form action="options.php" method="post">
                    <?php
                    settings_fields($settings_page_id);
                    do_settings_sections($settings_page_id);
                    submit_button(__('Save Settings', "vb-metadata-export"));
                    ?>
                </form>
                <hr />
                <?php
                call_user_func_array($this->get_render_function_by_tab($current_tab), array());
                ?>
            </div>
            <div class="clear"></div>
            <?php
        }

        public function render_general_tab()
        {
            ?>
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
            $renderer = new VB_Metadata_Export_Marc21Xml($this->common->plugin_name);
            $marc21xml = $renderer->render($posts[0]);
            $example_url = $this->common->get_the_permalink("marc21xml", $posts[0]);
            ?>
            <h2>
                <a href="<?php echo $example_url ?>">
                <?php echo __("Example", "vb-metadata-export") ?>
                </a>
            </h2>

            <pre><?php echo esc_html($marc21xml) ?></pre>

            <?php
        }

        public function render_mods_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Marc21Xml($this->common->plugin_name);
            $converter = new VB_Metadata_Export_Converter();

            $marc21xml = $renderer->render($posts[0]);
            $mods_xml = $converter->convertMarc21ToMods($marc21xml);
            $example_url = $this->common->get_the_permalink("mods", $posts[0]);

            ?>
            <h2>
                <a href="<?php echo $example_url ?>">
                <?php echo __("Example", "vb-metadata-export") ?>
                </a>
            </h2>

            <pre><?php echo esc_html($mods_xml) ?></pre>

            <?php
        }

        public function render_oai_pmh_tab()
        {
            $oaipmh_enabled = $this->common->get_settings_field_value("oai-pmh_enabled");
            if ($oaipmh_enabled) {
                $posts = get_posts(array('numberposts' => 1));
                $oaipmh = new VB_Metadata_Export_OaiPmh($this->common->plugin_name);
                $oai_baseurl = $oaipmh->get_base_url();
                $post_identifier = $oaipmh->get_post_identifier($posts[0]);
                ?>
                <h2>
                    Example Requests
                </h2>

                <ul>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=Identify">
                        <?php echo __("Identify", "vb-metadata-export") ?>
                        </a>
                    <li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=ListSets">
                        <?php echo __("ListSets", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=ListMetadataFormats">
                        <?php echo __("ListMetadataFormats", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=GetRecord&identifier=<?php echo $post_identifier ?>&metadataPrefix=oai_dc">
                        <?php echo __("GetRecord", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=ListRecords&metadataPrefix=oai_dc">
                        <?php echo __("ListRecords", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=ListIdentifiers&metadataPrefix=oai_dc">
                        <?php echo __("ListIdentifiers", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=something">
                        <?php echo __("Error: BadVerb", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=GetRecord">
                        <?php echo __("Error: BadArgument", "vb-metadata-export") ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $oai_baseurl ?>?verb=ListIdentifiers&metadataPrefix=bad">
                        <?php echo __("Error: CannotDisseminateFormat", "vb-metadata-export") ?>
                        </a>
                    </li>
                </ul>
            <?php
            }
        }

        public function render_dc_tab()
        {
            $posts = get_posts(array('numberposts' => 1));
            $renderer = new VB_Metadata_Export_Marc21Xml($this->common->plugin_name, true);
            $converter = new VB_Metadata_Export_Converter();

            $marc21xml = $renderer->render($posts[0]);
            $rdf_dc = $converter->convertMarc21ToRdfDc($marc21xml);
            $example_url = $this->common->get_the_permalink("dc", $posts[0]);

            ?>
            <h2>
                <a href="<?php echo $example_url ?>">
                <?php echo __("Example", "vb-metadata-export") ?>
                </a>
            </h2>

            <pre><?php echo esc_html($rdf_dc) ?></pre>
            <?php
        }

        public function render_acf_tab()
        {
            // empty
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