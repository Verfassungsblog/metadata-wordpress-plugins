<?php
/**
 * Verfassungsblog Metadata Export
 *
 * @package vb-metadata-export
 *
 * @wordpress-plugin
 * Plugin Name: Verfassungsblog Metadata Export
 * Plugin URI: https://wordpress.org/plugins/vb-metadata-export/
 * Description: Export post metadata as Marc21 XML, MODS 3.7 XML, Dublin Core XML and via OAI-PMH 2.0
 * Version: 0.1.0
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-metadata-export
 * Domain Path: /languages
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vb-metadata-export.php';

/**
 * Uninstall hook.
 */
function vb_metadata_export_uninstall() {
	// empty.
}

/**
 * Main run method.
 */
function run_vb_metadata_export() {
	$vb_metadata_export = new VB_Metadata_Export( __FILE__, 'vb-metadata-export' );
	$vb_metadata_export->run();
}

run_vb_metadata_export();
