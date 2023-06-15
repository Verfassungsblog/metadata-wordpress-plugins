<?php
/**
 * Verfassungsblog CrossRef DOI
 *
 * @package vb-crossref-doi
 *
 * @wordpress-plugin
 * Plugin Name: Verfassungsblog CrossRef DOI
 * Plugin URI: https://wordpress.org/plugins/vb-crossref-doi/
 * Description: Automates the registration of DOIs for posts using CrossRef
 * Version: 0.1.0
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-crossref-doi
 * Domain Path: /languages
 */

/**
 * Class imports
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vb-crossref-doi.php';

/**
 * WordPress uninstall hook
 */
function vb_crossref_doi_uninstall() {

}

/**
 * Main run method
 */
function run_vb_crossref_doi() {
	$vb_crossref_doi = new VB_CrossRef_DOI( __FILE__, 'vb-crossref-doi' );
	$vb_crossref_doi->run();
}

run_vb_crossref_doi();
