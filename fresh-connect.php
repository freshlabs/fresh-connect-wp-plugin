<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://freshlabs.link/
 * @since             1.0.0
 * @package           Fresh_Connect
 *
 * @wordpress-plugin
 * Plugin Name:       Fresh Connect
 * Plugin URI:        https://freshlabs.link/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Fresh Labs
 * Author URI:        https://freshlabs.link/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fresh-connect
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FRESH_CONNECT_VERSION', '1.0.0' );
define( 'FRESH_TEXT_DOMAIN', 'fresh-connect' );
define( 'FRESH_CONNECT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FRESH_CONNECT_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'FASTPRESS_API_URL', 'http://freshstore.space/test/' );

$fc_siteurl = site_url();
$fc_siteurl = str_replace('http://', '', $fc_siteurl);
$fc_siteurl = str_replace('https://', '', $fc_siteurl);
define( 'SITE_URL_WITHOUT_HTTP', $fc_siteurl );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fresh-connect-activator.php
 */
function activate_fresh_connect() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fresh-connect-activator.php';
	Fresh_Connect_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fresh-connect-deactivator.php
 */
function deactivate_fresh_connect() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fresh-connect-deactivator.php';
	Fresh_Connect_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fresh_connect' );
register_deactivation_hook( __FILE__, 'deactivate_fresh_connect' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fresh-connect.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fresh_connect() {

	$plugin = new Fresh_Connect();
	$plugin->run();

}
run_fresh_connect();

