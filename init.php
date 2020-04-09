<?php
/**
 * Plugin Name:       Fresh Connect
 * Plugin URI:        https://freshlabs.link/freshconnect
 * Description:       The Fresh Connect plugin connects your blog with the FastPress cloud hosting platform, allowing 1 click logins and powerful statistics. Please see the about page for more information.
 * Version:           1.2.0
 * Author:            Fresh Labs
 * Author URI:        https://freshlabs.link/freshlabs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fresh-connect
 */

define( 'FRESH_CONNECT_VERSION', '1.1.2' );
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
	global $wpdb;
	
	$current_key = get_option('fp_connection_keys', array());
	update_option('fresh_connect_status', 1);
	
	$mainuser = get_option('fp_main_username');
	if(empty($mainuser)){
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

	$fresh_connect_requests_log_table	= $wpdb->prefix . 'fresh_connect_requests_log';
		
	$requestLogData = array(); 
	$requestLogData['activity_type'] = 'user-activated-plugin';

	$wpdb->insert($fresh_connect_requests_log_table, $requestLogData);  
	
	update_option('fresh_connect_help', 1);	
}



function fp_create_table(){
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$fresh_page_stats_table	= $wpdb->prefix . 'fresh_page_stats';
	$fresh_page_reports_table = $wpdb->prefix . 'fresh_page_reports'; 
	$fresh_connect_requests_log_table = $wpdb->prefix . 'fresh_connect_requests_log'; 

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

	$fresh_connect_requests_log = "CREATE TABLE $fresh_connect_requests_log_table (
		ID bigint(20) NOT NULL AUTO_INCREMENT,
		activity_type varchar(200) DEFAULT NULL,
		request_status varchar(50) DEFAULT NULL,
		response_message text DEFAULT NULL,
		request_parameters text DEFAULT NULL,
		requested_at datetime NOT NULL DEFAULT current_timestamp(),
		PRIMARY KEY (ID)
		) $charset_collate;";
 
	dbDelta( $fresh_page_stats );
	dbDelta( $fresh_page_reports );
	dbDelta( $fresh_connect_requests_log );
}


function fp_disable_plugin() {
	global $wpdb;

	update_option('fresh_connect_status', 0);
	
	$fresh_connect_requests_log_table	= $wpdb->prefix . 'fresh_connect_requests_log';
		
	$requestLogData = array(); 
	$requestLogData['activity_type'] = 'user-deactivated-plugin';

	$wpdb->insert($fresh_connect_requests_log_table, $requestLogData);  
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

if(get_option('fresh_connect_help') == 1){
	add_action( 'admin_footer', 'fp__wp_admin_footer_beacon' );
}

function fp__wp_admin_footer_beacon(){
  ?>
  <script type="text/javascript">1
	  	! function(e, t, n) {
	    function a() {
	        var e = t.getElementsByTagName("script")[0],
	            n = t.createElement("script");
	        n.type = "text/javascript", n.async = !0, n.src = "https://beacon-v2.helpscout.net", e.parentNode.insertBefore(n, e)
	    }
	    if (e.Beacon = n = function(t, n, a) {
	            e.Beacon.readyQueue.push({
	                method: t,
	                options: n,
	                data: a
	            })
	        }, n.readyQueue = [], "complete" === t.readyState) return a();
	    e.attachEvent ? e.attachEvent("onload", a) : e.addEventListener("load", a, !1)
	}(window, document, window.Beacon || function() {});
	</script>
	<script type="text/javascript">
	window.Beacon('init', '11c0e4b8-22a6-454e-85cb-748cb045c851')
	</script>
  <?php
}

