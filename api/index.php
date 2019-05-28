<?php
require('../../../../wp-load.php');
require('api.php');

if( isset($_POST) )
{
	$siteurl = get_option('siteurl');
	$siteurl = str_replace('http://', '', $siteurl);
	$siteurl = str_replace('https://', '', $siteurl);
	$client_id = get_option('fc_client_app_id');
	$secret_id = get_option('fc_client_app_secret_id');
	
	$api = new API($client_id, $secret_id, $siteurl);
}