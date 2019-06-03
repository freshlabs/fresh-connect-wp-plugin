<?php
require_once('../../../../wp-load.php');
require_once('../inc/WordPress/Context.php');
$context = new FastPress_Context();

require_once('../inc/WordPress/GetState.php');
require_once('api.php');

if(isset($_POST))
{
	$fp_status = get_option('fresh_connect_status');
	$con_key = get_option('fp_connection_keys');
	
	$getstate = new Fastpress_Action_GetState($context);
	$api = new API($context, $getstate, $fp_status, $con_key);
}