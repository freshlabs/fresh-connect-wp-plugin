<?php

if( !class_exists( 'FC_Plugin_Updater' ) )
{
    // load our custom updater if it doesn't already exist
    include( FRESH_CONNECT_DIR_PATH . 'inc/FC_Plugin_Updater.php' );
}

function fc_fastpress_plugin_checkforupdate($check_now=false){

    $is_admin_page = ( isset($_GET['page']) && $_GET['page'] == 'fc-page' ) ? true : false;
    $is_update_page = ( isset($_SERVER['PHP_SELF']) && stripos($_SERVER['PHP_SELF'], 'wp-admin/update-core.php') ) ? true : false;
    $is_plugins_page = ( isset($_SERVER['PHP_SELF']) && stripos($_SERVER['PHP_SELF'], 'wp-admin/plugins.php') ) ? true : false;
    
    if($is_update_page) $check_now = true;

    if($is_admin_page OR $is_plugins_page OR $check_now){
        
        # Get the license key
        $license_key = get_option('fp_connection_keys');
        $remote_file = get_option('fresh_connect_remote_url');
        $item_name = FRESH_CONNECT_PLUGIN_NAME;
        $timestamp = get_option('fc_fastpress_plugin_update_last_checked');

        $expiry_date = $timestamp + 86400;
        $license_expired = $timestamp > $expiry_date;

        # if item_name has been set then look for updates
        if (($item_name AND $license_expired) OR $check_now){
            
            $url = base64_decode( $remote_file );
            
            $edd_updater = new FC_Plugin_Updater( $url, FRESH_CONNECT_PLUGIN_FILE_PATH, array(
                    'version'   => FRESH_CONNECT_VERSION,       // current version number
                    'license'   => $license_key,                // license key (used get_option above to retrieve from DB)
                    'item_name'     => $item_name,              // name of this plugin
                    'author'    => 'Fastpress'          // author of this plugin
                )
            );
            update_option('fc_fastpress_plugin_update_last_checked', time());
        }
    }
}

fc_fastpress_plugin_checkforupdate(true);