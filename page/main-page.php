<div class="wrap">
	<h1>Welcome to the Fresh Connect</h1>
	<p></p>
	
	<?php $active_tab = isset( $_GET[ 'fctab' ] ) ? $_GET[ 'fctab' ] : 'fresh-connect-dash'; ?>
	
	<h2 class="nav-tab-wrapper">
		<a href="<?= admin_url(); ?>admin.php?page=fresh-connect-dash" class="nav-tab <?php echo $active_tab == 'fresh-connect-dash' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Basic Details', FRESH_TEXT_DOMAIN); ?></a>
		<a href="<?= admin_url(); ?>admin.php?page=fresh-connect-dash&fctab=fresh-connect-aboutus" class="nav-tab <?php echo $active_tab == 'fresh-connect-aboutus' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('About Us', FRESH_TEXT_DOMAIN); ?></a>
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