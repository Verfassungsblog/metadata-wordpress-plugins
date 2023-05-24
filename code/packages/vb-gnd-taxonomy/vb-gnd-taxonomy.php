<?php

/**
 * Plugin Name: Verfassungsblog GND Taxonomy
 * Plugin URI: https://wordpress.org/plugins/vb-gnd-taxonomy/
 * Description: GND taxonomy for posts including autocomplete suggestions
 * Version: 0.0.1
 * Requires at least: 5.9.3
 * Requires PHP: 7.4.29
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Verfassungsblog
 * Author URI: https://verfassungsblog.de/
 */

define('VB_GND_TAXONOMY_VERSION', '0.0.1');

require_once plugin_dir_path(__FILE__) . 'includes/class-vb-gnd-taxonomy.php';


function vb_gnd_taxonomy_uninstall() {

}

function run_vb_gnd_taxonomy()
{
    $vb_gnd_taxonomy = new VB_GND_Taxonomy(__FILE__, "vb-gnd-taxonomy", "0.0.1");
    $vb_gnd_taxonomy->run();
}

run_vb_gnd_taxonomy();

