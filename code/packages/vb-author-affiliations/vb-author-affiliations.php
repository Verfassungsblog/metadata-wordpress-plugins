<?php

/**
 * Plugin Name: Verfassungsblog Author Affiliations
 * Plugin URI: https://wordpress.org/plugins/vb-author-affiliations/
 * Description: Saves author affiliations with posts
 * Version: 0.0.1
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-author-affiliations
 * Domain Path: /languages
 */

define('VB_AUTHOR_AFFILIATIONS_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-author-affiliations.php';
require_once plugin_dir_path(__FILE__) . 'includes/template.php';

function vb_author_affiliations_uninstall() {

}

function run_vb_author_affiliations()
{
    $vb_author_affiliations = new VB_Author_Affiliations(__FILE__, "vb-author-affiliations");
    $vb_author_affiliations->run();
}

run_vb_author_affiliations();

