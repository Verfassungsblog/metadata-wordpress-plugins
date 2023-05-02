<?php

require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_common.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_marc21xml.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_converter.php';
require_once plugin_dir_path(__FILE__) . '/class-vb-metadata-export_oaipmh.php';

if (!function_exists('get_the_vb_metadata_export_permalink')) {
    function get_the_vb_metadata_export_permalink($format)
    {
        global $post;
        $common = new VB_Metadata_Export_Common("vb-metadata-export");
        return $common->get_the_permalink($format, $post);
    }
}

if (!function_exists('get_the_vb_metadata_export_link')) {
    function get_the_vb_metadata_export_link($format, $title = "", $extra_class = "", $unavailable = "") {
        global $post;
        $common = new VB_Metadata_Export_Common("vb-metadata-export");

        if (!$common->is_valid_format($format)) {
            return "invalid format";
        }

        $permalink = $common->get_the_permalink($format, $post);
        $title = empty($title) ? $common->get_format_labels()[$format] : $title;

        $classes = implode(" ", array(
            $common->plugin_name . "-link",
            $common->plugin_name . "-" . $format . "-link",
            empty($marc21_permalink) ? $common->plugin_name . "-unavailable" : "",
            $extra_class,
        ));

        if (empty($permalink)) {
            return "<a class=\"{$classes}\">" . $unavailable . "</a>";
        }
        return "<a class=\"{$classes}\" href=\"{$permalink}\">" . $title . "</a>";
    }
}

if (!function_exists('the_vb_metadata_export_link')) {
    function the_vb_metadata_export_link($format, $title = "", $extra_class = "", $unavailable = "") {
        echo get_the_vb_metadata_export_link($format, $title, $extra_class, $unavailable);
    }
}

if (!function_exists('vb_metadata_export_render_format')) {
    function vb_metadata_export_render_format($format)
    {
        global $post;

        $common = new VB_Metadata_Export_Common("vb-metadata-export");

        if (!is_post_publicly_viewable() || !$common->is_format_available($format, $post) || !is_single()) {
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

        // should not happen
        return "unkown format";
    }
}