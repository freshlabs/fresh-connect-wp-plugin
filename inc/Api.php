<?php
/**
 * Class API
 *
 *
 */
class API
{
	private $context;
	private $fp_status;
	private $con_key;
	private $getstate;
	private $siteurl;
	private $wp_version;
	private $userdata;
	private $username;
	private $error = false;
	private $post = array();
	
	public function __construct(FreshCloud_Context $context, Fastpress_Action_GetState $getstate, $fp_status, $con_key)
    {
        $this->context = $context;
		$this->fp_status = $fp_status;
		$this->getstate = $getstate;
		$this->con_key = $con_key;
		$this->siteurl = site_url();
		$this->wp_version = $context->getVersion();
    }
	
	public function initialize($parameters)
	{
		if (!isset($parameters) || empty($parameters))
        {
            $this->error = true;
            $this->errormessage = 'No POST data provided';
            return $this->output();
        }
		
		if(!$this->fp_status)
		{
			$this->error = true;
            $this->errormessage = 'It seems Fresh Connect plugin is not activated on wp site. Please activate it.';
            return $this->output();
		}
		
		$this->post = $parameters;
		
		if( isset($this->post['username']) && !empty($this->post['username']) ){
			$this->username = sanitize_user($this->post['username']);
		}else{
			$this->username = null;
		}
		
		if ( ! username_exists( $this->username ) ){
			$users = get_users(array('role' => 'administrator', 'number' => 1, 'orderby' => 'ID'));
			
            if (empty($users[0]->user_login)) {
                $this->username = get_option('fp_main_username');
            }else{
				$this->username = $users[0]->user_login;
			}
		}
		
		$userdata = get_user_by('login', $this->username);
		
		if(!in_array('administrator', $userdata->roles)){
			$this->error = true;
			$this->errormessage = "User {$this->username} have not required permission to access the data.";
			return $this->output();
		}
		
		$this->userdata = $userdata;
		
		if( isset($this->post['timestamp']) ){
			$timestamp = sanitize_text_field($this->post['timestamp']);
		}else{
			$this->error = true;
			$this->errormessage = "timestamp key field is missing.";
			return $this->output();
		}
		
        $hash = md5($this->con_key.$timestamp);
		
		if( isset($this->post['hash']) ){
			$rhash = sanitize_text_field($this->post['hash']);
		}

        if ($hash !== $rhash)
        {
            $this->error = true;
            $this->errormessage = 'Invalid hash - Authentication faild, please check connection key is correct.';
            return $this->output();
        }
		
		if( isset($this->post['request_method']) ){
			$request_method = sanitize_text_field($this->post['request_method']);
		}else{
			$this->error = true;
            $this->errormessage = 'Request method is missing.';
            return $this->output();
		}
		# Check if the method requested exists, and pass on to that
        if (method_exists($this,$request_method))
        {
            $data = $this->{$request_method}();
			return $data;
        }
        else
        {
            $this->error = true;
            $this->errormessage = 'No api method found.';
            return $this->output();
        }
	}
	
	private function output()
	{
		if ($this->error)
        {
            $this->apioutput['status'] = 'error';
            $this->apioutput['errormessage'] = $this->errormessage;
        }
        else
        {
            $this->apioutput['status'] = 'success';
        }
        $this->pluginLogsIntoDB($this->apioutput);
		return $this->apioutput;
	}
	
	public function getSSLStatus($inCall = false)
	{
		$status = $this->context->isSsl();
		
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['ssl_status'] = $status;
		
		if($inCall === false){
			return $this->output();
		}
	}
	
	public function setSSL()
	{
		if( isset($this->post['ssl_status']) && $this->post['ssl_status'] == true ){
			$value = true;
		}else{
			$value = false;
		}
		
		$this->context->setSSL($value);
		
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		return $this->output();
	}
	
	public function getWPVersion()
	{	
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		return $this->output();
    }
    
    public function setGoogeAPIKey()
    {
        if( empty($this->post['ga_apikey']) ) {
            $this->error = true;
            $this->errormessage = 'Google Api key is missing';
            
            return $this->output();
        }

        $key = sanitize_text_field($this->post['ga_apikey']);
        
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'google-pagespeed-insights/google-pagespeed-insights.php' ) ) {
            $gaapikey = get_option('gpagespeedi_options');

            if( isset($gaapikey['google_developer_key']) ){
                $gaapikey['google_developer_key'] = $key;
                update_option('gpagespeedi_options', $gaapikey);
            }
        }
        else {
            $this->error = true;
            $this->errormessage = 'Google Pagespeed Insights Plugin is not active';
        }

        return $this->output();
    }

    public function getGoogeAPIKey() 
    {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'google-pagespeed-insights/google-pagespeed-insights.php' ) ) {
            $gaapikey = get_option('gpagespeedi_options');

            if( isset($gaapikey['google_developer_key']) ){
                $this->apioutput['ga_apikey'] = $gaapikey['google_developer_key'];
            }
        }
        else {
            $this->error = true;
            $this->errormessage = 'Google Pagespeed Insights Plugin is not active';
        }

        return $this->output();
    }
	
	public function getOption()
	{
		if( empty($this->post['option_key']) ){
			$this->error = true;
            $this->errormessage = 'option_key is missing.';
            return $this->output();
		}

		$option_key = sanitize_key($this->post['option_key']);

		$option_value = $this->context->optionGet($option_key);
		
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['option_key'] = $option_key;
		$this->apioutput['option_value'] = $option_value;
		
		return $this->output();
	}

	public function setOption()
	{
		if(empty($this->post['option_key']) && empty($this->post['option_value'])){
			$this->error = true;
            $this->errormessage = 'option_key and option_value are missing.';
            return $this->output();
		}

		if(empty($this->post['option_key'])){
			$this->error = true;
            $this->errormessage = 'option_key is missing.';
            return $this->output();
        }
		
		if(empty($this->post['option_value'])){
			$this->error = true;
            $this->errormessage = 'option_value is missing.';
            return $this->output();
        }
        
        $option_key = sanitize_key($this->post['option_key']);
		if( function_exists('sanitize_textarea_field') ){
			$option_value = sanitize_textarea_field($this->post['option_value']);
		}else{
			$option_value = wp_kses_post($this->post['option_value']);
		}

		$this->context->optionSet($option_key, $option_value);

		return $this->output();
	}
	
	public function getGeneralOptions($inCall = false)
	{
		$data = $this->getstate->getSiteInfo();
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
		if(isset($data['error'])){
			$this->error = true;
			$this->errormessage = $data['error'];
		}
		
		$this->apioutput['general_options'] = $data;
		
		if($inCall === false){
			return $this->output();
		}
	}
	
	public function setMultipleOptions()
	{
		if( isset($this->post['options']) && is_array($this->post['options']) ){
			$options = $this->post['options'];
		}else{
			$options = array();
		}
		
		$this->context->setMultipleOptions($options);

		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		return $this->output();
	}
	
	public function createAdminUser()
	{	
		if( empty($this->post['username']) ){
			$this->error = true;
			$this->errormessage = 'user name is empty';
			return $this->output();
		}
		
		$user_name = sanitize_user($this->post['username']);
		$user_email = '';
		
		if( isset($this->post['useremail']) && !empty($this->post['useremail']) ){
			$user_email = sanitize_email($this->post['useremail']);
			if( email_exists($user_email) == true ){
				$this->error = true;
				$this->errormessage = 'user email is already exist.';
				return $this->output();
			}
		}
		
		$user_id = username_exists( $user_name );
		if ( !$user_id ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			
			$user_id = wp_create_user( $user_name, $random_password, $user_email);
			if ( is_int($user_id) )
			{
				$wp_user_object = new WP_User($user_id);
				$wp_user_object->set_role('administrator');
				
				$this->apioutput['result'] = array('user_id' => $user_id, 'user_name' => $user_name);
			}else{
				$this->error = true;
				$this->errormessage = 'Problem in creating a new user.';
			}
			
		} else {
			$this->error = true;
			$this->errormessage = 'user name is already exist.';
		}
		
		return $this->output();
	}
	
	public function getAllThemes($inCall = false)
	{
		if( isset($this->post['theme_options']) && is_array($this->post['theme_options']) ){
			$options = $this->post['theme_options'];
		}else{
			$options = array();
		}
		
		$themes = $this->getstate->execute(array('themes' => array('type' => 'themes', 'options' => $options)));
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
		if(isset($themes['result']['error'])){
			$this->error = true;
			$this->errormessage = $themes['result']['error'];
		}else{
			$this->apioutput['themes'] = $themes;
		}
		
		if($inCall === false){
			return $this->output();
		}
		
	}
	
	public function getAllPlugins($inCall = false)
	{
		if( isset($this->post['plugin_options']) && is_array($this->post['plugin_options']) ){
			$options = $this->post['plugin_options'];
		}else{
			$options = array();
		}
		
		$plugins = $this->getstate->execute(array('plugins' => array('type' => 'plugins', 'options' => $options)));
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
		if(isset($plugins['result']['error'])){
			$this->error = true;
			$this->errormessage = $plugins['result']['error'];
		}else{
			$this->apioutput['plugins'] = $plugins;
		}
		
		if($inCall === false){
			return $this->output();
		}
	}
	
	public function editThemes()
	{
		if( isset($this->post['themes_info']) && is_array($this->post['themes_info']) ){
			$themes_info = $this->post['themes_info'];
		}else{
			$themes_info = array();
		}
		
		$return = $this->getstate->execute(array('themes' => array('type' => 'edit_themes', 'options' => $themes_info)));
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
		if(isset($return['result']['error'])){
			$this->error = true;
			$this->errormessage = $return['result']['error'];
		}
		
		$this->apioutput['data'] = $return;
		return $this->output();
	}
	
	public function editPlugins()
	{
		if( isset($this->post['plugins_info']) && is_array($this->post['plugins_info']) ){
			$plugins_info = $this->post['plugins_info'];
		}else{
			$plugins_info = array();
		}
		
		$return = $this->getstate->execute(array('plugins' => array('type' => 'edit_plugins', 'options' => $plugins_info)));
		
		$this->apioutput['siteurl'] = $this->siteurl;
		
		if(isset($return['result']['error'])){
			$this->error = true;
			$this->errormessage = $return['result']['error'];
		}
		
		$this->apioutput['data'] = $return;
		return $this->output();
    }
    
    public function getSiteHealth()
    {
        $this->apioutput['site_health'] = array();
        if( file_exists( ABSPATH . 'wp-admin/includes/class-wp-site-health.php' ) )
        {
            @require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            @require_once( ABSPATH . 'wp-admin/includes/update.php' );
            @require_once( ABSPATH . 'wp-admin/includes/misc.php' );
            @require_once( ABSPATH . 'wp-admin/includes/file.php' );
            if ( ! class_exists( 'WP_Site_Health' ) ) {
                @require_once( ABSPATH . 'wp-admin/includes/class-wp-site-health.php' );
            }

            $site_health = new WP_Site_Health();

            $health_check = array('wordpress_version', 'plugin_version', 'theme_version', 'php_version', 'php_extensions', 'sql_server', 'utf8mb4_support', 'dotorg_communication', 'is_in_debug_mode', 'https_status', 'ssl_support', 'scheduled_events', 'loopback_requests', 'http_requests');
            $result = array();

            // Don't run https test on localhost
			if ( 'localhost' === preg_replace( '|https?://|', '', get_site_url() ) ) {
				unset( $health_check['https_status'] );
            }
            
            // Conditionally include REST rules if the function for it exists.
            if ( function_exists( 'rest_url' ) ) {
                $health_check[] = 'rest_availability';
            }

            $goodTests = $recommendedTests = $criticalTests = 0;
            if(!empty($health_check))
            {
                foreach ($health_check as $fun) {
                    $function = 'get_test_'.$fun;
                    # Check if the method requested exists
                    if (method_exists($site_health,$function))
                    {
                        if($fun == 'rest_availability'){
                            $result = $this->context->get_test_rest_availability();
                        }else{
                            $result = $site_health->{$function}();
                        }
                        
                        if(isset($result['description'])){
                            $result['description'] = strip_tags($result['description']);
                        }
                        if(isset($result['actions'])){
                            $result['actions'] = strip_tags($result['actions']);
                        }
                        
                        if($result['status'] == 'good')
                        {
                            $goodTests = $goodTests + 1;
                        }

                        if($result['status'] == 'recommended')
                        {
                            $recommendedTests = $recommendedTests + 1;
                        }

                        if($result['status'] == 'critical')
                        {
                            $criticalTests = $criticalTests + 1;
                        }
    
                        $this->apioutput['site_health'][$fun] = $result;
                    }
                }
                $criticalTests = $criticalTests * 1.5;
                $totalTests = $goodTests + $recommendedTests + $criticalTests + 1;
                $failedTests = $recommendedTests + $criticalTests;
                $val = 100 - ceil( ( $failedTests / $totalTests ) * 100 );
                if ( 0 > $val ) {
                    $val = 0;
                }
                if ( 100 < $val ) {
                    $val = 100;
                }

                $this->apioutput['site_health']['progress_count'] = $val;
            }
        }
        return $this->output();
    }
	
	public function updateURLs()
	{
		if(empty($this->post['oldurl'])){
			$this->error = true;
			$this->errormessage = 'Old url is missing.';
			return $this->output();
		}
		
		if(empty($this->post['newurl'])){
			$this->error = true;
			$this->errormessage = 'New url is missing.';
			return $this->output();
		}
		
		$oldurl = sanitize_key($this->post['oldurl']);
		$newurl = sanitize_key($this->post['newurl']);
		
		$result = $this->context->update_urls($oldurl, $newurl);

		$this->apioutput['result'] = $result;
		return $this->output();
	}
	
	public function getAllData()
	{
		$inCall = true;
		$this->apioutput['siteurl'] = $this->siteurl;
		
		$this->getSSLStatus($inCall);
		
		$this->getGeneralOptions($inCall);
		
		$this->getAllThemes($inCall);
		
		$this->getAllPlugins($inCall);
		
		$this->apioutput['wp_version'] = $this->wp_version;
		
		return $this->output();
    }

    private function sort_array( $array, $key, $direction = 'asc' )
	{
		usort( $array, function( $a, $b ) use ( $key, $direction ) {
			if ( abs( $a[ $key ] - $b[ $key ] ) < 0.00000001 ) {
				return 0; // almost equal
			} else if ( ( $a[ $key ] - $b[ $key ] ) < 0 ) {   
				return $direction == 'asc' ? -1 : 1;
			} else {
				return $direction == 'asc' ? 1 : -1;
			}
		});

		return $array;
	}
    
    public function getPageSpeedReport() 
    {
        global $wpdb;
        $gpi_page_stats		= $wpdb->prefix . 'gpi_page_stats';
		$gpi_page_reports	= $wpdb->prefix . 'gpi_page_reports';

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'google-pagespeed-insights/google-pagespeed-insights.php' ) ) {
            $report_list = $wpdb->get_results("SELECT `URL`, `desktop_score`, `mobile_score`, `type`, `desktop_last_modified`, `mobile_last_modified` FROM $gpi_page_stats", ARRAY_A);
        
            $desktop_score = $mobile_score = $pages = 0;

            if($report_list){
                $pages = count( $report_list );
                foreach( $report_list as $list ){
                    $desktop_score += $list['desktop_score'];
                    $mobile_score += $list['mobile_score'];
                }
            }
            
            if($pages > 0){
                $desktop_score = round( $desktop_score / $pages );
                $mobile_score = round( $mobile_score / $pages );
            }
            
            // Desktop 
            $all_page_reports = $wpdb->get_results( $wpdb->prepare(
				"
					SELECT r.rule_key, r.rule_name, r.rule_score
					FROM $gpi_page_stats as s
					INNER JOIN $gpi_page_reports as r
						ON r.page_id = s.ID
						AND r.strategy = %s
						AND r.rule_type = %s
						AND r.rule_score < .9
					WHERE s.desktop_score IS NOT NULL
					ORDER BY r.rule_score DESC
				",
				'desktop',
				'opportunity'
            ), ARRAY_A );
            
            $summary_reports = $desktop_summary_reports = $mobile_summary_reports = array();

            if ( $all_page_reports ) {
                foreach ( $all_page_reports as $page_report ) {
                    if ( isset( $summary_reports[ $page_report['rule_key'] ] ) ) {
                        $summary_reports[ $page_report['rule_key'] ]['avg_score'] += $page_report['rule_score'];
                        $summary_reports[ $page_report['rule_key'] ]['occurances']++;
                    } else {
                        $summary_reports[ $page_report['rule_key'] ] = array(
                            'rule_name'		=> ( 'uses-optimized-images' != $page_report['rule_key'] ) ? $page_report['rule_name'] : $page_report['rule_name'] . '<span class="shortpixel_blurb"> &ndash; <a href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">' . __( 'Auto-Optimize images with ShortPixel. Sign up for 150 free credits!', 'gpagespeedi') . '</a></span>',
                            'avg_score'		=> $page_report['rule_score'],
                            'occurances'	=> 1
                        );
                    }
                }
        
                foreach ( $summary_reports as &$summary_report ) {
                    $summary_report['avg_score'] = round( $summary_report['avg_score'] / $summary_report['occurances'], 2 );
                    $summary_report['avg_score'] = $summary_report['avg_score'] * 100;
                }
    
                $desktop_summary_reports = $this->sort_array( $summary_reports, 'avg_score' );
            }

            // Mobile 
            $all_page_reports = $wpdb->get_results( $wpdb->prepare(
				"
					SELECT r.rule_key, r.rule_name, r.rule_score
					FROM $gpi_page_stats as s
					INNER JOIN $gpi_page_reports as r
						ON r.page_id = s.ID
						AND r.strategy = %s
						AND r.rule_type = %s
						AND r.rule_score < .9
					WHERE s.mobile_score IS NOT NULL
					ORDER BY r.rule_score DESC
				",
				'mobile',
				'opportunity'
            ), ARRAY_A );
            
            $summary_reports = array();

            if ( $all_page_reports ) {
                foreach ( $all_page_reports as $page_report ) {
                    if ( isset( $summary_reports[ $page_report['rule_key'] ] ) ) {
                        $summary_reports[ $page_report['rule_key'] ]['avg_score'] += $page_report['rule_score'];
                        $summary_reports[ $page_report['rule_key'] ]['occurances']++;
                    } else {
                        $summary_reports[ $page_report['rule_key'] ] = array(
                            'rule_name'		=> ( 'uses-optimized-images' != $page_report['rule_key'] ) ? $page_report['rule_name'] : $page_report['rule_name'] . '<span class="shortpixel_blurb"> &ndash; <a href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">' . __( 'Auto-Optimize images with ShortPixel. Sign up for 150 free credits!', 'gpagespeedi') . '</a></span>',
                            'avg_score'		=> $page_report['rule_score'],
                            'occurances'	=> 1
                        );
                    }
                }
        
                foreach ( $summary_reports as &$summary_report ) {
                    $summary_report['avg_score'] = round( $summary_report['avg_score'] / $summary_report['occurances'], 2 );
                    $summary_report['avg_score'] = $summary_report['avg_score'] * 100;
                }
    
                $mobile_summary_reports = $this->sort_array( $summary_reports, 'avg_score' );
            }

            $data = array('report_list' => $report_list, 'avg_desktop_score' => $desktop_score, 'avg_mobile_score' => $mobile_score, 'improvement_area_desktop' => $desktop_summary_reports, 'improvement_area_mobile' => $mobile_summary_reports);

            $this->apioutput['result'] = $data;
        }
        else{
            $this->error = true;
            $this->errormessage = 'Google Pagespeed Insights Plugin is not active';
        }
        
        return $this->output();  
    }
	
	public function getPageSpeedReports() 
    {
		if(empty($this->post['start_date'])){
			$this->error = true;
			$this->errormessage = 'Start Date is empty.';
			return $this->output();
		}
 
		if(empty($this->post['end_date'])){
			$this->error = true;
			$this->errormessage = 'End Date is empty.';
			return $this->output();
		} 

		$startDate = sanitize_text_field($this->post['start_date']);
		$endDate = sanitize_text_field($this->post['end_date']); 
 
		$sDate = strtotime($this->post['start_date']);
		$eDate = strtotime($this->post['end_date']);  
		
		$datediff = $eDate-$sDate; 
		$CountDay = round($datediff / (60 * 60 * 24)); 
		 
		global $wpdb; 
		$fresh_page_stats	= $wpdb->prefix . 'fresh_page_stats';
		$fresh_page_reports = $wpdb->prefix . 'fresh_page_reports';
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'google-pagespeed-insights/google-pagespeed-insights.php' ) ) { 
			$reportData = array(); 
			$summary_reports = $desktop_summary_reports = $mobile_summary_reports = array(); 
			for($i=$CountDay; $i>=0; $i--){  
				 
				$targetDate = date('Y-m-d' , strtotime("-".$i." days", strtotime($endDate)));
				 
				 $report_list = $wpdb->get_results("SELECT `URL`, `desktop_score`, `mobile_score`, `type`, `desktop_last_modified`, `mobile_last_modified`, `created_on` FROM $fresh_page_stats WHERE created_on like '%".$targetDate."%'", ARRAY_A);
 
				$desktop_score = $mobile_score = $pages = 0;
			 

				if($report_list){
					$pages = count( $report_list );
					foreach( $report_list as $list ){
 
						$desktop_score += $list['desktop_score'];
						$mobile_score += $list['mobile_score'];
					}
				}
			
				if($pages > 0){
					$desktop_score = round( $desktop_score / $pages );
					$mobile_score = round( $mobile_score / $pages );
				}
			
            
				// Desktop 
				$all_page_reports = $wpdb->get_results( $wpdb->prepare(
					"
						SELECT r.rule_key, r.rule_name, r.rule_score
						FROM $fresh_page_stats as s
						INNER JOIN $fresh_page_reports as r
							ON r.page_id = s.ID
							AND r.strategy = %s
							AND r.rule_type = %s
							AND r.rule_score < .9
						WHERE s.desktop_score IS NOT NULL AND s.created_on like '%".$targetDate."%' ORDER BY r.rule_score DESC
					",
					'desktop',
					'opportunity'
				), ARRAY_A );


				if ( $all_page_reports ) {
					foreach ( $all_page_reports as $page_report ) {
						if ( isset( $summary_reports[ $page_report['rule_key'] ] ) ) {
							$summary_reports[ $page_report['rule_key'] ]['avg_score'] += $page_report['rule_score'];
							$summary_reports[ $page_report['rule_key'] ]['occurances']++;
						} else { 
							$summary_reports[ $page_report['rule_key'] ] = array(
								'rule_name'		=> ( 'uses-optimized-images' != $page_report['rule_key'] ) ? $page_report['rule_name'] : $page_report['rule_name'] . '<span class="shortpixel_blurb"> &ndash; <a href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">' . __( 'Auto-Optimize images with ShortPixel. Sign up for 150 free credits!', 'gpagespeedi') . '</a></span>',
								'avg_score'		=> $page_report['rule_score'],
								'occurances'	=> 1
							); 
						}   
					}
			  
					foreach ( $summary_reports as &$summary_report ) {
						$summary_report['avg_score'] = round( $summary_report['avg_score'] / $summary_report['occurances'], 2 );
						$summary_report['avg_score'] = $summary_report['avg_score'] * 100;
					}
		
					$desktop_summary_reports = $this->sort_array( $summary_reports, 'avg_score' );
				}

				// Mobile 
				$all_page_reports = $wpdb->get_results( $wpdb->prepare(
					"
						SELECT r.rule_key, r.rule_name, r.rule_score
						FROM $fresh_page_stats as s
						INNER JOIN $fresh_page_reports as r
							ON r.page_id = s.ID
							AND r.strategy = %s
							AND r.rule_type = %s
							AND r.rule_score < .9
						WHERE s.mobile_score IS NOT NULL AND s.created_on like '%".$targetDate."%'
						ORDER BY r.rule_score DESC
					",
					'mobile',
					'opportunity'
				), ARRAY_A ); 
            
				$summary_reports = array();

				if ( $all_page_reports ) {
					foreach ( $all_page_reports as $page_report ) {
						if ( isset( $summary_reports[ $page_report['rule_key'] ] ) ) {
							$summary_reports[ $page_report['rule_key'] ]['avg_score'] += $page_report['rule_score'];
							$summary_reports[ $page_report['rule_key'] ]['occurances']++;
						} else {
							$summary_reports[ $page_report['rule_key'] ] = array(
								'rule_name'		=> ( 'uses-optimized-images' != $page_report['rule_key'] ) ? $page_report['rule_name'] : $page_report['rule_name'] . '<span class="shortpixel_blurb"> &ndash; <a href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">' . __( 'Auto-Optimize images with ShortPixel. Sign up for 150 free credits!', 'gpagespeedi') . '</a></span>',
								'avg_score'		=> $page_report['rule_score'],
								'occurances'	=> 1
							);
						}
					}
 
					foreach ( $summary_reports as &$summary_report ) {
						$summary_report['avg_score'] = round( $summary_report['avg_score'] / $summary_report['occurances'], 2 );
						$summary_report['avg_score'] = $summary_report['avg_score'] * 100;
					}
		
					$mobile_summary_reports = $this->sort_array( $summary_reports, 'avg_score' );
				}

            $data = array('report_list' => $report_list, 'avg_desktop_score' => $desktop_score, 'avg_mobile_score' => $mobile_score, 'improvement_area_desktop' => $desktop_summary_reports, 'improvement_area_mobile' => $mobile_summary_reports); 
				
			array_push($reportData , $data);
			  
			}
			$this->apioutput['result'] = $reportData;
		}
        else{ 
            $this->error = true;
            $this->errormessage = 'Google Pagespeed Insights Plugin is not active';
        } 
		return $this->output();  
    }


	public function pluginLogsIntoDB($apioutput) {
	 	global $wpdb;

		$fresh_connect_requests_log_table	= $wpdb->prefix . 'fresh_connect_requests_log';

		$requestLogData = array(); 
		$requestLogData['activity_type'] = 'api-'. ($this->post['request_method']);
		$requestLogData['request_status'] = $apioutput['status'];
		$requestLogData['response_message'] = $apioutput['errormessage'];
		$this->post['hash'] = "Removed for Security";
		$requestLogData['request_parameters'] = json_encode($this->post);

		$wpdb->insert($fresh_connect_requests_log_table, $requestLogData);  
		
	}
	
}