<?php

/**
 * Plugin Name: Verfassungsblog DOAJ
 * Plugin URI: https://wordpress.org/plugins/vb-doaj/
 * Description: Submit posts to DOAJ
 * Version: 0.0.1
 * Requires at least: 6.1.1
 * Requires PHP: 8.0.28
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-doaj
 * Domain Path: /languages
 */

define('VB_DOAJ_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-doaj.php';


function vb_doaj_uninstall() {

}

function run_vb_doaj()
{
    $vb_doaj = new VB_DOAJ(__FILE__, "vb-doaj", "0.0.1");
    $vb_doaj->run();
}

run_vb_doaj();

