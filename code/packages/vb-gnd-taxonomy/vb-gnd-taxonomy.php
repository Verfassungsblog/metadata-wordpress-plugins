<?php
/**
 * Verfassungsblog GND Taxonomy
 *
 * @package vb-gnd-taxonomy
 *
 * @wordpress-plugin
 * Plugin Name: Verfassungsblog GND Taxonomy
 * Plugin URI: https://github.com/Verfassungsblog/metadata-wordpress-plugins
 * Description: GND taxonomy for posts including autocomplete suggestions based on lobid.org
 * Version: 0.2.1
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vb-gnd-taxonomy.php';

/**
 * Uninstall hook.
 */
function vb_gnd_taxonomy_uninstall() {
	// empty.
}

/**
 * Main run method.
 */
function run_vb_gnd_taxonomy() {
	$vb_gnd_taxonomy = new VB_GND_Taxonomy( __FILE__, 'vb-gnd-taxonomy' );
	$vb_gnd_taxonomy->run();
}

run_vb_gnd_taxonomy();
