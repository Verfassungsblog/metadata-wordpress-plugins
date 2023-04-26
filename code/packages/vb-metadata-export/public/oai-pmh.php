<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_oaipmh.php';

if (!function_exists('vb_metadata_export_render_oaipmh')) {
    function vb_metadata_export_render_oaipmh() {

        $common = new VB_Metadata_Export_Common("vb-metadata-export");
        if ($common->get_settings_field_value("oai-pmh_enabled")) {
            header('Content-Type: application/xml');
            $oaipmh = new VB_Metadata_Export_OaiPmh($common->plugin_name);
            echo $oaipmh->render();
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit();
        }
    }
}

vb_metadata_export_render_oaipmh();