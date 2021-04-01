<div class="wrap about-wrap full-width-layout fresh-connect-page">
	<h1><?php esc_html_e('Welcome to the Fresh Connect', FRESH_TEXT_DOMAIN); ?></h1>
	<p><?php _e('The Fresh Connect Plugin connects your WordPress blog with the Fresh Cloud platform.', FRESH_TEXT_DOMAIN); ?></p>

	<p><?php _e('This gives you many powerful features including one click login (no more frustration losing passwords!) and powerful statistics that you can use to drive your website forward.', FRESH_TEXT_DOMAIN); ?></p>
	
	<?php 
	if( isset( $_GET[ 'fctab' ] ) ): 
		$active_tab = sanitize_html_class($_GET[ 'fctab' ]);
	else:
		$active_tab = 'fresh-connect-aboutus';
	endif;
	?>
	
	<h2 class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo esc_url( admin_url('admin.php?page=fresh-connect-aboutus') ); ?>" class="nav-tab <?php echo esc_attr($active_tab == 'fresh-connect-aboutus' ? 'nav-tab-active' : ''); ?>"><?php esc_html_e('About this Plugin', FRESH_TEXT_DOMAIN); ?></a>
        <?php /*<a href="<?php echo esc_url( admin_url('admin.php?page=fresh-connect-aboutus&fctab=fresh-connect-dash') ); ?>" class="nav-tab <?php echo $active_tab == 'fresh-connect-dash' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Basic Details', FRESH_TEXT_DOMAIN); ?></a>*/ ?>
	</h2>
	
	<?php
		if($active_tab == 'fresh-connect-aboutus')
		{
			include('about-us.php');
		}
		else
		{
			include('info.php');
		}
	?>
</div>