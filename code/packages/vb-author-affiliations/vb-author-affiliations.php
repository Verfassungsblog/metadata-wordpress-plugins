<?php
/**
 * Verfassungsblog Author Affiliations
 *
 * @package vb-author-affiliations
 *
 * @wordpress-plugin
 * Plugin Name: Verfassungsblog Author Affiliations
 * Plugin URI: https://github.com/Verfassungsblog/metadata-wordpress-plugins
 * Description: Saves author affiliations and ROR-ID as post metadata
 * Version: 0.2.2
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-author-affiliations
 * Domain Path: /languages
 */

/**
 * Class imports.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vb-author-affiliations.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/vb-author-affiliations-template.php';

/**
 * WordPress uninstall hook.
 */
function vb_author_affiliations_uninstall() {

}

/**
 * Main run method.
 */
function run_vb_author_affiliations() {
	$vb_author_affiliations = new VB_Author_Affiliations( __FILE__, 'vb-author-affiliations' );
	$vb_author_affiliations->run();
}

run_vb_author_affiliations();
