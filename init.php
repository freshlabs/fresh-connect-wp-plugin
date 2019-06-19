<?php
/**
 * Plugin Name:       Fresh Connect
 * Plugin URI:        https://freshlabs.link/freshconnect
 * Description:       The Fresh Connect plugin connects your blog with the FastPress cloud hosting platform, allowing 1 click logins and powerful statistics. Please see the about page for more information.
 * Version:           1.0.0
 * Author:            Fresh Labs
 * Author URI:        https://freshlabs.link/freshlabs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

define( 'FRESH_CONNECT_VERSION', '1.0.0' );
define( 'FRESH_TEXT_DOMAIN', 'fresh-connect' );
define( 'FRESH_CONNECT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FRESH_CONNECT_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'FRESH_CONNECT_PLUGIN_NAME', 'Fresh Connect' );
define( 'FRESH_CONNECT_PLUGIN_URL', 'https://freshlabs.link/' );
define( 'FRESH_CONNECT_PLUGIN_FILE_PATH', plugin_basename( __FILE__ ) );
$plugin = plugin_basename( __FILE__ );

function fp_generate_uuid4()
{
    $data = null;
    if (function_exists('openssl_random_pseudo_bytes')) {
        $data = @openssl_random_pseudo_bytes(16);
    }

    if (empty($data)) {
        $data = '';
        for ($i = 0; $i < 16; ++$i) {
            $data .= chr(mt_rand(0, 255));
        }
    }

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

register_activation_hook( __FILE__, 'fp_setup_plugin' );
register_deactivation_hook( __FILE__, 'fp_disable_plugin' );

function fp_setup_plugin(){
	$current_key = get_option('fp_connection_keys', array());
	update_option('fresh_connect_status', 1);
	update_option('fresh_connect_remote_url', 'aHR0cDovL2ZyZXNoc3RvcmUuc3BhY2UvdGVzdC91cGRhdGUucGhw');
	if(empty($current_key)){
		$connection_key = fp_generate_uuid4();
		update_option( 'fp_connection_keys', $connection_key );
	}
}

function fp_disable_plugin() {
	update_option('fresh_connect_status', 0);
}

function fp_admin_enqueue_scripts() {
	wp_enqueue_style( 'fp-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/fp-admin.css', array(), '1.0', 'all' );
	wp_enqueue_script('fp-deactivation-js', FRESH_CONNECT_URL_PATH . 'assets/js/fp-admin.js', array(), '1.0', false );
}
add_action('admin_enqueue_scripts', 'fp_admin_enqueue_scripts');

// Hook for adding admin menus
add_action('admin_menu', 'fc_fastpress_admin_menu_page');
function fc_fastpress_admin_menu_page()
{
	add_menu_page( 
        __( 'Fresh Connect', FRESH_TEXT_DOMAIN ),
        'Fresh Connect',
        'manage_options',
        'fresh-connect-dash',
        'fc_fastpress_custom_menu_page',
		'dashicons-menu',
        4
    ); 
}

function fc_fastpress_custom_menu_page()
{
	include('page/main-page.php');
}

add_action('admin_footer', 'fp_deactivation_warning_dialog_box');
function fp_deactivation_warning_dialog_box()
{
	require('page/templates/display_deactivation_popup.php');
}

function fc_fastpress_custom_links($links) {

    $aboutus_link = admin_url().'admin.php?page=fresh-connect-dash&fctab=fresh-connect-aboutus';
    $abt_link = '<a href="' . $aboutus_link . '">About Us</a>';
    array_unshift($links, $abt_link);
	//unset($links['deactivate']);
	
	$deactivate_url =
	add_query_arg(
		array(
			'action' => 'deactivate',
			'plugin' => FRESH_CONNECT_PLUGIN_FILE_PATH,
			'_wpnonce' => wp_create_nonce( 'deactivate-plugin_' . FRESH_CONNECT_PLUGIN_FILE_PATH )
		),
		admin_url( 'plugins.php' )
	);

	$links["deactivate"] = '<a href="'.$deactivate_url.'" class="fc_deactivate_link">Deactivate</a>';
	
    return $links;
} 

# Adds custom link to Plugins Activation Page
add_filter("plugin_action_links_$plugin", 'fc_fastpress_custom_links' );

require_once('inc/AutoLogin.php');
require_once('inc/updater.php');
