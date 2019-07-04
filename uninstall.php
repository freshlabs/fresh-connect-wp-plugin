<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

printf( 'The Fresh Connect plugin provides a secure connection between your FastPress account and your website. You can not delete this plugin. If you need any help, please contact the Fresh Team for support' );

die;