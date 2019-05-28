<?php

/**
 * Fired during plugin activation
 *
 * @link       https://freshlabs.link/
 * @since      1.0.0
 *
 * @package    Fresh_Connect
 * @subpackage Fresh_Connect/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fresh_Connect
 * @subpackage Fresh_Connect/includes
 * @author     Fresh Labs <freshlabs@gmail.com>
 */
class Fresh_Connect_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$client_id = substr(md5(time().rand(0,1000)), 0, 10);
		$secret_id = substr(md5(time().rand(1001,2000)), 0, 30);
		
		update_option('fc_client_app_id', $client_id);
		update_option('fc_client_app_secret_id', $secret_id);
		
		$postdata = array('client_id' => $client_id, 'secret_id' => $secret_id, 'siteurl' => SITE_URL_WITHOUT_HTTP);
		
		$result = self::sendPostData($postdata, FASTPRESS_API_URL);
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
