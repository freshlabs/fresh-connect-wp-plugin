<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<div class="card">
        <h2 class="title"><?php esc_html_e('FastPress Connection Status', FRESH_TEXT_DOMAIN); ?></h2>
        <p>This page is for manually fixing the connection between FastPress and your website. This is typically only used by the FastPress support team - please do not enter anything on this page unless requested to do so. If you think the connection to your site is broken, please <a href="<?= FRESH_CONNECT_PLUGIN_URL ?>support" target="_blank">Contact Support</a></p>
        <?php
            if($fastpress_status):
                echo '<div class="fc-status-btn green">Connected</div>';
            else:
                echo '<div class="fc-status-btn orange">Unknown</div>';
            endif;
        ?>
	</div>
	
	<div class="card">
		<h2 class="title"><?php esc_html_e('Update the Fresh Connect Key', FRESH_TEXT_DOMAIN); ?></h2>
		<p>Please note this is for the support team to use only. Don't proceed unless you have been instructed to do so.</p>
		
		<form action="<?php echo admin_url( '/admin.php?page=fresh-connect-status' ); ?>" method="post" class="fc_key_form">
			<p><label for="title"><?php esc_html_e('Enter the Fresh Connect Key:', FRESH_TEXT_DOMAIN); ?></label></p>
			<p><input type="text" name="fc_key" /></p>
			<p><input type="submit" class="button button-primary" value="Save the New Key" /></p>
		</form>
	</div>
</div>