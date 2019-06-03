<div class="wrap">
	<h1>Welcome to the Fresh Connect</h1>
	<p></p>
	
	<?php $active_tab = isset( $_GET[ 'fctab' ] ) ? $_GET[ 'fctab' ] : 'fc_info'; ?>
	
	<h2 class="nav-tab-wrapper">
		<a href="<?= admin_url(); ?>admin.php?page=fc-page" class="nav-tab <?php echo $active_tab == 'fc_info' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Basic Details', FRESH_TEXT_DOMAIN); ?></a>
		<a href="<?= admin_url(); ?>admin.php?page=fc-page&fctab=fc_aboutus" class="nav-tab <?php echo $active_tab == 'fc_aboutus' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('About Us', FRESH_TEXT_DOMAIN); ?></a>
	</h2>
	
	<?php
		if($active_tab == 'fc_aboutus')
		{
			include('about-us.php');
		}
		else
		{
			include('info.php');
		}
	?>
</div>