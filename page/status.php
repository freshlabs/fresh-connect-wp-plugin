<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<div class="card">
		<h2 class="title"><?php esc_html_e('Connection Status', FRESH_TEXT_DOMAIN); ?></h2>
		<p>This page shows if your website is connected to FastPress. If your website is not connected some features will not work - please <a href="<?= FRESH_CONNECT_PLUGIN_URL ?>support" target="_blank">Contact Support</a> if this is the case.</p>
        <div class="fc-status-btn red">Disconnected</div>
	</div>
	
	<div class="card">
		<h2 class="title"><?php esc_html_e('Update the Fresh Connect Key', FRESH_TEXT_DOMAIN); ?></h2>
		<p>Please note this is for the support team to use only. Don't proceed unless you have been instructed to do so.</p>
		
		<form action="" method="post" class="fc_key_form">
			<p><label for="title"><?php esc_html_e('Enter the Fresh Connect Key:', FRESH_TEXT_DOMAIN); ?></label></p>
			<p><input type="text" name="fc_key" /></p>
			<p><input type="submit" class="button button-primary" value="Test and Save" /></p>
		</form>
	</div>
</div>