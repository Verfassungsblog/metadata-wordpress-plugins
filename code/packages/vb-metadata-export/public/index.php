<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-metadata-export_renderer.php';

header('Content-Type: application/xml');


function vb_metadata_export_render() {

    if (!is_single()) {
        return;
    }

    global $post;

    $renderer = new VB_Metadata_Export_Renderer("vb-metadata-export");
    echo $renderer->render($post);
}

vb_metadata_export_render();