<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';

if (!function_exists('get_the_vb_metadata_export_marc21xml_permalink')) {
    function get_the_vb_metadata_export_marc21xml_permalink()
    {
        $common = new VB_Metadata_Export_Common("vb-metadata-export");
        return $common->get_the_permalink("marc21xml");
    }
}

if (!function_exists('get_the_vb_metadata_export_mods_permalink')) {
    function get_the_vb_metadata_export_mods_permalink()
    {
        $common = new VB_Metadata_Export_Common("vb-metadata-export");
        return $common->get_the_permalink("mods");
    }
}

if (!function_exists('vb_metadata_export_render_format')) {
    function vb_metadata_export_render_format($format)
    {
        global $post;

        $common = new VB_Metadata_Export_Common("vb-metadata-export");

        if (!is_single() || !$common->is_format_available($format, $post)) {
            return;
        }

        header('Content-Type: application/xml');

        if ($format == "marc21xml") {
            $marc21xml = new VB_Metadata_Export_Marc21Xml($common->plugin_name);
            echo $marc21xml->render($post);
            return;
        }

        if ($format == "mods") {
            $converter = new VB_Metadata_Export_Converter();
            $marc21xml = new VB_Metadata_Export_Marc21Xml($common->plugin_name);
            $mods = $converter->convertMarc21ToMods($marc21xml->render($post));
            echo $mods;
            return;
        }

        if ($format == "dc") {
            $converter = new VB_Metadata_Export_Converter();
            $marc21xml = new VB_Metadata_Export_Marc21Xml($common->plugin_name, true);
            $dc = $converter->convertMarc21ToRdfDc($marc21xml->render($post));
            echo $dc;
            return;
        }

        if ($format == "oai-pmh") {
            $converter = new VB_Metadata_Export_Converter();
            $marc21xml = new VB_Metadata_Export_Marc21Xml($common->plugin_name, true);
            $oai = $converter->convertMarc21ToOaiDc($marc21xml->render($post));
            echo $oai;
            return;
        }

        return "unkown format";
    }
}