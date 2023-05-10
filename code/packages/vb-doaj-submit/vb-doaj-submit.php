<?php

/**
 * Plugin Name: Verfassungsblog DOAJ Submit
 * Plugin URI: https://wordpress.org/plugins/vb-doaj-submit/
 * Description: Submit posts to the DOAJ
 * Version: 0.0.1
 * Requires at least: 6.1.1
 * Requires PHP: 8.0.28
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 * Text Domain: vb-doaj-submit
 * Domain Path: /languages
 */

define('VB_DOAJ_SUBMIT_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-doaj-submit.php';


function vb_doaj_submit_uninstall() {

}

function run_vb_doaj_submit()
{
    $vb_doaj_submit = new VB_DOAJ_Submit(__FILE__, "vb-doaj-submit");
    $vb_doaj_submit->run();
}

run_vb_doaj_submit();

