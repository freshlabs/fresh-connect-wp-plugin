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
	private $post = array();
	
	public function __construct(FastPress_Context $context, Fastpress_Action_GetState $getstate, $fp_status, $con_key)
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
		
		$username = empty($this->post['username']) ? null : $this->post['username'];
		
		if ( ! username_exists( $username ) ){
			$users = get_users(array('role' => 'administrator', 'number' => 1, 'orderby' => 'ID'));
			
            if (empty($users[0]->user_login)) {
                $this->error = true;
				$this->errormessage = 'We could not find an administrator user to use. Please contact support.';
				return $this->output();
            }
			
            $this->post['username'] = $users[0]->user_login;
		}
		
		$userdata = get_user_by('login', $this->post['username']);
		
		if(!in_array('administrator', $userdata->roles)){
			$this->error = true;
			$this->errormessage = "User {$this->post['username']} have not required permission to access the data.";
			return $this->output();
		}
		
		$this->userdata = $userdata;
		
        $hash = md5($this->con_key.$this->post['timestamp']);

        if ($hash !== $this->post['hash'])
        {
            $this->error = true;
            $this->errormessage = 'Invalid hash - Authentication faild, please check connection key is correct.';
            return $this->output();
        }
		
		# Check if the method requested exists, and pass on to that
        if (method_exists($this,$this->post['request_method']))
        {
            $data = $this->{$this->post['request_method']}();
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

        //echo json_encode($this->apioutput);
		return $this->apioutput;
	}
	
	public function getSSLStatus($inCall = false)
	{
		$status = $this->context->isSsl();
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['ssl_status'] = $status;
		
		if($inCall === false){
			return $this->output();
		}
	}
	
	public function setSSL()
	{
		$value = empty($this->post['ssl_status']) ? false : $this->post['ssl_status'];
		
		$this->context->setSSL($value);
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		
		return $this->output();
	}
	
	public function getWPVersion()
	{	
		$this->apioutput['username'] = $this->post['username'];
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

        $key = trim( $this->post['ga_apikey'] );
        
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
		$option = empty($this->post['option_key']) ? null : $this->post['option_key'];
		$value = $this->context->optionGet($option);
		
		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['option_key'] = $option;
		$this->apioutput['option_value'] = $value;
		
		return $this->output();
	}
	
	public function getGeneralOptions($inCall = false)
	{
		$data = $this->getstate->getSiteInfo();
		
		$this->apioutput['username'] = $this->post['username'];
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
		$options = is_array($this->post['options']) ? $this->post['options'] : array();
		
		$this->context->setMultipleOptions($options);

		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['siteurl'] = $this->siteurl;
		$this->apioutput['wp_version'] = $this->wp_version;
		return $this->output();
	}
	
	public function getAllThemes($inCall = false)
	{
	    $options = is_array($this->post['theme_options']) ? $this->post['theme_options'] : array();
		
		$themes = $this->getstate->execute(array('themes' => array('type' => 'themes', 'options' => $options)));
		
		$this->apioutput['username'] = $this->post['username'];
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
	    $options = is_array($this->post['plugin_options']) ? $this->post['plugin_options'] : array();
		
		$plugins = $this->getstate->execute(array('plugins' => array('type' => 'plugins', 'options' => $options)));
		
		$this->apioutput['username'] = $this->post['username'];
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
		$themes_info = is_array($this->post['themes_info']) ? $this->post['themes_info'] : array();
		
		$return = $this->getstate->execute(array('themes' => array('type' => 'edit_themes', 'options' => $themes_info)));
		
		$this->apioutput['username'] = $this->post['username'];
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
		$plugins_info = is_array($this->post['plugins_info']) ? $this->post['plugins_info'] : array();
		
		$return = $this->getstate->execute(array('plugins' => array('type' => 'edit_plugins', 'options' => $plugins_info)));
		
		$this->apioutput['username'] = $this->post['username'];
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
		
		if(function_exists('esc_attr')){
			$oldurl = esc_attr(trim($this->post['oldurl']));
			$newurl = esc_attr(trim($this->post['newurl']));
		}else{
			$oldurl = trim($this->post['oldurl']);
			$newurl = trim($this->post['newurl']);
		}
		
		$result = $this->context->update_urls($oldurl, $newurl);

		$this->apioutput['username'] = $this->post['username'];
		$this->apioutput['result'] = $result;
		return $this->output();
	}
	
	public function getAllData()
	{
		$inCall = true;
		$this->apioutput['username'] = $this->post['username'];
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

            $desktop_score = round( $desktop_score / $pages );
            $mobile_score = round( $mobile_score / $pages );

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
}