<?php
require('../../../../wp-load.php');

// Automatic login //
if(isset($_GET['userid']) && isset($_GET['token']))
{
	$userid = $_GET['userid'];
	$fcToken = get_option('fc_current_token');
	
	if($fcToken == $_GET['token'])
	{
		// Redirect URL //
		wp_clear_auth_cookie();
		wp_set_current_user ( $userid );
		wp_set_auth_cookie  ( $userid );

		$redirect_to = user_admin_url();
		wp_safe_redirect( $redirect_to );
	}
	else
	{
		$redirect_to = user_admin_url();
		wp_safe_redirect( $redirect_to );
	}
}
exit();
