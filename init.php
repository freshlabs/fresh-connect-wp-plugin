<?php
/**
 * Plugin Name:       Fresh Connect
 * Plugin URI:        https://freshlabs.link/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Fastpress
 * Author URI:        https://freshlabs.link/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

define( 'FRESH_CONNECT_VERSION', '1.0.0' );
define( 'FRESH_TEXT_DOMAIN', 'fresh-connect' );
define( 'FRESH_CONNECT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FRESH_CONNECT_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'FRESH_CONNECT_PLUGIN_NAME', 'Fresh Connect' );
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
	if(empty($current_key)){
		$connection_key = fp_generate_uuid4();
		update_option( 'fp_connection_keys', $connection_key );
	}	
}

function fp_disable_plugin() {
	update_option('fresh_connect_status', 0);
}

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

function fc_fastpress_custom_links($links) {

    $aboutus_link = admin_url().'admin.php?page=fresh-connect-dash&fctab=fresh-connect-aboutus';
    $abt_link = '<a href="' . $aboutus_link . '">About Us</a>';
    array_unshift($links, $abt_link);
	unset($links['deactivate']);
	
    return $links;
} 

# Adds custom link to Plugins Activation Page
add_filter("plugin_action_links_$plugin", 'fc_fastpress_custom_links' );

add_action('admin_notices', 'fc_fastpress_plugin_notice');
function fc_fastpress_plugin_notice() {
	global $pagenow;
	
	if ( $pagenow == 'plugins.php' ) {
		$aboutus_link = admin_url().'admin.php?page=fresh-connect-dash&fctab=fresh-connect-aboutus';

		printf( '<div class="notice notice-warning is-dismissible"><p>Fresh Connect plugin shouldn\'t be removed or disabled. Please go to the <a href="%1$s">About Us</a> page for more info.</p></div>', esc_url( $aboutus_link ) );
	}
}

require_once('inc/AutoLogin.php');
require_once('inc/updater.php');
