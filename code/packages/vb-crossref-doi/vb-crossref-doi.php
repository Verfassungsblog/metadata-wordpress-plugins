<?php

/**
 * Plugin Name: Verfassungsblog CrossRef DOI
 * Plugin URI: https://wordpress.org/plugins/vb-crossref-doi/
 * Description: Register posts at CrossRef to get DOIs
 * Version: 0.0.1
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-crossref-doi
 * Domain Path: /languages
 */

define('VB_CROSSREF_DOI_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-crossref-doi.php';


function vb_crossref_doi_uninstall() {

}

function run_vb_crossref_doi()
{
    $vb_crossref_doi = new VB_CrossRef_DOI(__FILE__, "vb-crossref-doi");
    $vb_crossref_doi->run();
}

run_vb_crossref_doi();

