<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-vb-marc21xml-export_renderer.php';

header('Content-Type: application/xml');


function vb_marc21xml_export_render() {

    if (!is_single()) {
        return;
    }

    global $post;

    $renderer = new VB_Marc21Xml_Export_Renderer("vb-marc21xml-export");
    echo $renderer->render($post);
}

vb_marc21xml_export_render();