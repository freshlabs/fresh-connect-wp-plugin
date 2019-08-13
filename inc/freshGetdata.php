<?php
 if (! wp_next_scheduled ( 'fp_daily_report' )) {
	wp_schedule_event(time(), 'daily', 'fp_daily_report');
} 
add_action('fp_daily_report', 'fp_daily_report_callback');  
function fp_daily_report_callback() {
	 global $wpdb;
        $page_stats_table			= $wpdb->prefix . 'gpi_page_stats';
		$page_reports_table			= $wpdb->prefix . 'gpi_page_reports';
		
		$fresh_page_stats_table	= $wpdb->prefix . 'fresh_page_stats';
		$fresh_page_reports_table = $wpdb->prefix . 'fresh_page_reports'; 

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'google-pagespeed-insights/google-pagespeed-insights.php' ) ) {
 
			$currentDate = date('Y-m-d H:i:s');   
			$stats_data = $wpdb->get_results( "SELECT * FROM $page_stats_table", ARRAY_A );
			if(!empty($stats_data)){ 
				foreach($stats_data as $state){
					$stateInsert = array(); 
					$stateInsert['URL'] = $state['URL'];
					$stateInsert['response_code'] = $state['response_code'];
					$stateInsert['desktop_score'] = $state['desktop_score'];
					$stateInsert['mobile_score'] = $state['mobile_score'];
					$stateInsert['desktop_lab_data'] = $state['desktop_lab_data'];
					$stateInsert['mobile_lab_data'] = $state['mobile_lab_data'];
					$stateInsert['desktop_field_data'] = $state['desktop_field_data'];
					$stateInsert['mobile_field_data'] = $state['mobile_field_data'];
					$stateInsert['type'] = $state['type']; 
					$stateInsert['object_id'] = $state['object_id'];
					$stateInsert['term_id'] = $state['term_id'];
					$stateInsert['custom_id'] = $state['custom_id'];
					$stateInsert['desktop_last_modified'] = $state['desktop_last_modified'];
					$stateInsert['mobile_last_modified'] = $state['mobile_last_modified'];
					$stateInsert['force_recheck'] = $state['force_recheck'];
					$stateInsert['created_on'] = $currentDate; 
					
					$wpdb->insert($fresh_page_stats_table, $stateInsert);  
				} 
			}
			$reports_data = $wpdb->get_results( "SELECT * FROM $page_reports_table", ARRAY_A );
			if(!empty($reports_data)){
				foreach($reports_data as $report){ 
					$reportsInsert = array();
						$reportsInsert['page_id'] = $report['page_id'];
						$reportsInsert['strategy'] = $report['strategy'];
						$reportsInsert['rule_key'] = $report['rule_key'];
						$reportsInsert['rule_name'] = $report['rule_name'];
						$reportsInsert['rule_type'] = $report['rule_type'];
						$reportsInsert['rule_score'] = $report['rule_score'];
						$reportsInsert['rule_blocks'] = $report['rule_blocks']; 
 
						$wpdb->insert($fresh_page_reports_table, $reportsInsert);
				}
			}
		}
}  