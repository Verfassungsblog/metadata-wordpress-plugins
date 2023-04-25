<?php

/**
 * Plugin Name: Verfassungsblog Metadata Export
 * Plugin URI: https://wordpress.org/plugins/vb-metadata-export/
 * Description: Export post metadata as Marc21 / MODS / OAI
 * Version: 0.0.2
 * Requires at least: 6.1.1
 * Requires PHP: 8.0.28
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-metadata-export
 * Domain Path: /languages
 */

define('VB_METADATA_EXPORT_VERSION', '0.0.2');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-metadata-export.php';


function vb_metadata_export_uninstall() {

}

function run_vb_metadata_export()
{
    $vb_metadata_export = new VB_Metadata_Export(__FILE__, "vb-metadata-export", "0.0.2");
    $vb_metadata_export->run();
}

run_vb_metadata_export();

