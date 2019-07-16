<div class="wrap">
	<h1><?php esc_html_e( get_admin_page_title() ); ?></h1>
	<div class="card">
        <h2 class="title"><?php esc_html_e('FastPress Connection Status', FRESH_TEXT_DOMAIN); ?></h2>
		<p><?php printf( __('This page is for manually fixing the connection between FastPress and your website. This is typically only used by the FastPress support team - please do not enter anything on this page unless requested to do so. If you think the connection to your site is broken, please <a href="%s" target="_blank">Contact Support</a>', FRESH_TEXT_DOMAIN), esc_url( FRESH_CONNECT_PLUGIN_URL .'support' ) ); ?></p>
        <?php if($fastpress_status): ?>
			<div class="fc-status-btn green"><?php esc_html_e('Connected', FRESH_TEXT_DOMAIN); ?></div>
        <?php else: ?>
            <div class="fc-status-btn orange"><?php esc_html_e('Unknown', FRESH_TEXT_DOMAIN); ?></div>
        <?php endif; ?>
	</div>
	
	<div class="card">
		<h2 class="title"><?php esc_html_e('Update the Fresh Connect Key', FRESH_TEXT_DOMAIN); ?></h2>
		<p><?php _e('Please note this is for the support team to use only. Don\'t proceed unless you have been instructed to do so.', FRESH_TEXT_DOMAIN); ?></p>
		
		<form action="<?php echo esc_url( admin_url( '/admin.php?page=fresh-connect-status' ) ); ?>" method="post" class="fc_key_form">
			<p><label for="fc_key"><?php esc_html_e('Enter the Fresh Connect Key:', FRESH_TEXT_DOMAIN); ?></label></p>
			<p><input type="text" name="fc_key" /></p>
			<p><input type="submit" class="button button-primary" value="<?php esc_attr_e('Save the New Key', FRESH_TEXT_DOMAIN); ?>" /></p>
		</form>
	</div>
</div>