<?php
/**
 * Verfassungsblog DOAJ Submit
 *
 * @package vb-doaj-submit
 *
 * @wordpress-plugin
 * Plugin Name: Verfassungsblog DOAJ Submit
 * Plugin URI: https://github.com/Verfassungsblog/metadata-wordpress-plugins
 * Description: Automates the submission of posts to the DOAJ
 * Version: 0.2.1
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-doaj-submit
 * Domain Path: /languages
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vb-doaj-submit.php';

/**
 * WordPress uninstall hook
 */
function vb_doaj_submit_uninstall() {
	// empty.
}

/**
 * Main run method.
 */
function run_vb_doaj_submit() {
	$vb_doaj_submit = new VB_DOAJ_Submit( __FILE__, 'vb-doaj-submit' );
	$vb_doaj_submit->run();
}

run_vb_doaj_submit();
