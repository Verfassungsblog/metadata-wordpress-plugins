<?php

/**
 * Plugin Name: Verfassungsblog Marc21Xml Export
 * Plugin URI: https://wordpress.org/plugins/vb-marc21xml-export/
 * Description: Export a post as a Marc21 XML document
 * Version: 0.0.1
 * Requires at least: 6.1.1
 * Requires PHP: 8.0.28
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-marc21xml-export
 * Domain Path: /languages
 */

define('VB_MARC21XML_EXPORT_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-marc21xml-export.php';


function vb_marc21xml_export_uninstall() {

}

function run_vb_marc21xml_export()
{
    $vb_marc21xml_export = new VB_Marc21Xml_Export(__FILE__, "vb-marc21xml-export", "0.0.1");
    $vb_marc21xml_export->run();
}

run_vb_marc21xml_export();

