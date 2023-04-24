<?php

require_once plugin_dir_path(__FILE__) . 'class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . 'class-vb-metadata-export_converter.php';
require_once plugin_dir_path(__FILE__) . 'class-vb-metadata-export_renderer.php';

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
                ));

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
            $admin_page_hook = add_submenu_page(
                'options-general.php',
                'Verfassungsblog Metadata Export',
                'VB Metadata Export',
                'manage_options',
                'vb_metadata_export',
                array($this, 'render')
            );

            add_action("load-" . $admin_page_hook, array($this, "help_tab"));
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

            ?>

            <div class="wrap">
                <h1>
                    <?php echo esc_html(get_admin_page_title()); ?>
                </h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields($this->common->settings_page_name);
                    do_settings_sections($this->common->settings_page_name);
                    submit_button(__('Save Settings', "vb-metadata-export"));
                    ?>
                </form>
                <hr />
                <?php

                $args = array(
                    'numberposts' => 1,
                );
                $posts = get_posts($args);
                $renderer = new VB_Metadata_Export_Renderer($this->common->plugin_name);
                $converter = new VB_Metadata_Export_Converter();

                $marc21xml = $renderer->render($posts[0]);
                $mods_xml = $converter->convertMarc21ToMods($marc21xml);
                $rdf_dc = $converter->convertMarc21ToRdfDc($marc21xml);
                $oai_dc = $converter->convertMarc21ToOaiDc($marc21xml);

                ?>
                <h2>
                    <?php echo __("Marc21 Example", "vb-metadata-export") ?>
                </h2>

                <pre><?php echo esc_html($marc21xml) ?></pre>

                <h2>
                    <?php echo __("MODS Example", "vb-metadata-export") ?>
                </h2>

                <pre><?php echo esc_html($mods_xml) ?></pre>

                <h2>
                    <?php echo __("RDF Dublin Core Example", "vb-metadata-export") ?>
                </h2>

                <pre><?php echo esc_html($rdf_dc) ?></pre>

                <h2>
                    <?php echo __("OAI DC Example", "vb-metadata-export") ?>
                </h2>

                <pre><?php echo esc_html($oai_dc) ?></pre>

                <hr>
                <form method="post" onsubmit="return confirm('Are you sure?');">
                    <input type="hidden" name="reset" value="true" />
                    <?php
                    submit_button(__('Reset Settings to Default', "vb-metadata-export"), "secondary", "reset");
                    ?>
                </form>
            </div>
            <?php
        }

        public function run()
        {
            add_action('admin_init', array($this, 'action_init'));
            add_action('admin_menu', array($this, 'action_admin_menu'));
        }

    }

}