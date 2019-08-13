<?php
/**
 * Plugin Name:       Fresh Connect
 * Plugin URI:        https://freshlabs.link/freshconnect
 * Description:       The Fresh Connect plugin connects your blog with the FastPress cloud hosting platform, allowing 1 click logins and powerful statistics. Please see the about page for more information.
 * Version:           1.1.1
 * Author:            Fresh Labs
 * Author URI:        https://freshlabs.link/freshlabs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fresh-connect
 */

define( 'FRESH_CONNECT_VERSION', '1.1.1' );
define( 'FRESH_TEXT_DOMAIN', 'fresh-connect' );
define( 'FRESH_CONNECT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FRESH_CONNECT_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'FRESH_CONNECT_PLUGIN_NAME', 'Fresh Connect' );
define( 'FRESH_CONNECT_PLUGIN_URL', 'https://freshlabs.link/' );
define( 'FRESH_CONNECT_PLUGIN_FILE_PATH', plugin_basename( __FILE__ ) );

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

function fp_setup_plugin() {
	$current_key = get_option('fp_connection_keys', array());
	update_option('fresh_connect_status', 1);
	
	$mainuser = get_option('fp_main_username');
	if(empty($mainuser)){
		global $wpdb;
		$sql = "SELECT {$wpdb->users}.user_login FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND {$wpdb->users}.user_login LIKE 'cust%' AND {$wpdb->usermeta}.meta_value LIKE '%administrator%' LIMIT 1";
		
		$mainuser = $wpdb->get_results($sql);
		if(!empty($mainuser)){
			$username = $mainuser[0]->user_login;
			update_option('fp_main_username', $username);
		}
	}
	
	if(empty($current_key)){
		$connection_key = fp_generate_uuid4();
		update_option( 'fp_connection_keys', $connection_key );
    }
	fp_create_table();
	
}
function fp_create_table(){
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$fresh_page_stats_table	= $wpdb->prefix . 'fresh_page_stats';
	$fresh_page_reports_table = $wpdb->prefix . 'fresh_page_reports'; 

	$charset_collate = $wpdb->get_charset_collate();

	$fresh_page_stats = "CREATE TABLE $fresh_page_stats_table (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		URL text NULL,
		response_code int(10) DEFAULT NULL,
		desktop_score int(10) DEFAULT NULL,
		mobile_score int(10) DEFAULT NULL,
		desktop_lab_data longtext,
		mobile_lab_data longtext,
		desktop_field_data longtext,
		mobile_field_data longtext,
		type varchar(200) DEFAULT NULL,
		object_id bigint(20) DEFAULT NULL,
		term_id bigint(20) DEFAULT NULL,
		custom_id bigint(20) DEFAULT NULL,
		desktop_last_modified varchar(20) NOT NULL,
		mobile_last_modified varchar(20) NOT NULL,
		force_recheck int(1) NOT NULL,
		created_on datetime NOT NULL,
		PRIMARY KEY  (ID),
		KEY object_id (object_id),
		KEY term_id (term_id),
		KEY custom_id (custom_id)
	) $charset_collate;";

	$fresh_page_reports = "CREATE TABLE $fresh_page_reports_table (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		page_id bigint(20) NOT NULL,
		strategy varchar(20) NOT NULL,
		rule_key varchar(200) NOT NULL,
		rule_name varchar(200) DEFAULT NULL,
		rule_type varchar(200) DEFAULT NULL,
		rule_score decimal(5,2) DEFAULT NULL,
		rule_blocks longtext,
		PRIMARY KEY  (ID),
		KEY page_id (page_id)
	) $charset_collate;";
 
	dbDelta( $fresh_page_stats );
	dbDelta( $fresh_page_reports );
}


function fp_disable_plugin() {
	update_option('fresh_connect_status', 0);
}

require_once( FRESH_CONNECT_DIR_PATH . 'inc/loader.php' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fresh-connect', '/v1', array(
		'methods' => 'POST',
		'callback' => 'fp_api_callback'
	) );
} );

function fp_api_callback( $request ) {
	
	$parameters = $request->get_params();
	
	$fp_status = get_option('fresh_connect_status');
	$con_key = get_option('fp_connection_keys');
	
	$context = new FastPress_Context();
	$getstate = new Fastpress_Action_GetState($context);
	$api = new API($context, $getstate, $fp_status, $con_key);
	$data = $api->initialize( $parameters );
	
	return $data;
}
