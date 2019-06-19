<div class="wrap about-wrap full-width-layout fresh-connect-page">
	<h1>Welcome to the Fresh Connect</h1>
	<p>The Fresh Connect Plugin connects your WordPress blog with the FastPress cloud hosting platform.</p>

	<p>This gives you many powerful features including one click login (no more frustration losing passwords!) and powerful statistics that you can use to drive your website forward.</p>
	
	<?php $active_tab = isset( $_GET[ 'fctab' ] ) ? $_GET[ 'fctab' ] : 'fresh-connect-dash'; ?>
	
	<h2 class="nav-tab-wrapper wp-clearfix">
		<a href="<?= admin_url(); ?>admin.php?page=fresh-connect-dash" class="nav-tab <?php echo $active_tab == 'fresh-connect-dash' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Basic Details', FRESH_TEXT_DOMAIN); ?></a>
		<a href="<?= admin_url(); ?>admin.php?page=fresh-connect-dash&fctab=fresh-connect-aboutus" class="nav-tab <?php echo $active_tab == 'fresh-connect-aboutus' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('About this Plugin', FRESH_TEXT_DOMAIN); ?></a>
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