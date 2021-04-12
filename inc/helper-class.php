<?php
// if(!class_exists('Mobile_Detect')){
// 	require_once('mobile-detect.php');
// }
class OOHelper
{

	private $ses_hash;
	private $user_id;
	private $logged;

	public function __construct(){

	}

	public function get_request_info($args = array())
	{
		$info = [];

		// getting correct IP
		$ip_tmp = $_SERVER['SERVER_ADDR'];
		if(empty($ip_tmp)){
			$info['ip'] = $ip_tmp;
		}
		else {
			$info['ip'] = $_SERVER['REMOTE_ADDR'];
			$info['ip'] = !empty($info['ip']) ? $info['ip'] : '-';
		}

		// getting correct IP
		$info['referer'] = isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : '-';
		$info['resolution'] = '-';

		$browser = get_browser(null, true);
		$info['browser'] = isset($browser['browser']) ? $browser['browser'] : '?';

		return $info;
	}


	public function customer_info($uid){

		if(!empty($uid)){
			$billing_first_name = get_user_meta( $uid, 'billing_first_name', true );
			$profile_data['billing_first_name'] = isset($billing_first_name) ? $billing_first_name : '';
			$billing_last_name = get_user_meta( $uid, 'billing_last_name', true );
			$profile_data['billing_last_name'] = isset($billing_last_name) ? $billing_last_name : '';
			$phone = get_user_meta( $uid, 'billing_phone', true );
			$profile_data['billing_phone'] = isset($phone) ? $phone : '';
			$billing_company = get_user_meta( $uid, 'billing_company', true );
			$profile_data['billing_company'] = isset($billing_company) ? $billing_company : '';
			$billing_email = get_user_meta( $uid, 'billing_email', true );
			$profile_data['billing_email'] = isset($billing_email) ? $billing_email : '';
			$billing_country = get_user_meta( $uid, 'billing_country', true );
			$profile_data['billing_country'] = isset($billing_country) ? $billing_country : '';
			$billing_state = get_user_meta( $uid, 'billing_state', true );
			$profile_data['billing_state'] = isset($billing_state) ? $billing_state : '-';
			$billing_city = get_user_meta( $uid, 'billing_city', true );
			$profile_data['billing_city'] = isset($billing_city) ? $billing_city : '';
			$billing_address_1 = get_user_meta( $uid, 'billing_address_1', true );
			$profile_data['billing_address_1'] = isset($billing_address_1) ? $billing_address_1 : '';
			$billing_address_2 = get_user_meta( $uid, 'billing_address_2', true );
			$profile_data['billing_address_2'] = isset($billing_address_2) ? $billing_address_2 : '';
			$billing_postcode = get_user_meta( $uid, 'billing_postcode', true );
			$profile_data['billing_postcode'] = isset($billing_postcode) ? $billing_postcode : '';

			$shipping_first_name = get_user_meta( $uid, 'shipping_first_name', true );
			$profile_data['shipping_first_name'] = isset($shipping_first_name) ? $shipping_first_name : '';
			$shipping_last_name = get_user_meta( $uid, 'shipping_last_name', true );
			$profile_data['shipping_last_name'] = isset($shipping_last_name) ? $shipping_last_name : '';
			$shipping_company = get_user_meta( $uid, 'shipping_company', true );
			$profile_data['shipping_company'] = isset($shipping_company) ? $shipping_company : '';
			$shipping_country = get_user_meta( $uid, 'shipping_country', true );
			$profile_data['shipping_country'] = isset($shipping_country) ? $shipping_country : '';
			$shipping_state = get_user_meta( $uid, 'shipping_state', true );
			$profile_data['shipping_state'] = isset($shipping_state) ? $shipping_state : '';
			$shipping_city = get_user_meta( $uid, 'shipping_city', true );
			$profile_data['shipping_city'] = isset($shipping_city) ? $shipping_city : '';
			$shipping_address_1 = get_user_meta( $uid, 'shipping_address_1', true );
			$profile_data['shipping_address_1'] = isset($shipping_address_1) ? $shipping_address_1 : '';
			$shipping_address_2 = get_user_meta( $uid, 'shipping_address_2', true );
			$profile_data['shipping_address_2'] = isset($shipping_address_2) ? $shipping_address_2 : '';
			$shipping_postcode = get_user_meta( $uid, 'shipping_postcode', true );
			$profile_data['shipping_postcode'] = isset($shipping_postcode) ? $shipping_postcode : '';
		} else {
			$profile_data['billing_first_name'] = '';
			$profile_data['billing_last_name'] =  '';
			$profile_data['billing_phone'] =  '';
			$profile_data['billing_company'] =  '';
			$profile_data['billing_country'] =  '';
			$profile_data['billing_state'] = '';
			$profile_data['billing_city'] =  '';
			$profile_data['billing_address_1'] = '';
			$profile_data['billing_address_2'] = '';
			$profile_data['billing_postcode'] = '';

			$profile_data['shipping_first_name'] = '';
			$profile_data['shipping_last_name'] = '';
			$profile_data['shipping_company'] = '';
			$profile_data['shipping_country'] = '';
			$profile_data['shipping_state'] = '';
			$profile_data['shipping_city'] = '';
			$profile_data['shipping_address_1'] =  '';
			$profile_data['shipping_address_2'] = '';
			$profile_data['shipping_postcode'] = '';
		}
		return $profile_data;
	}

	public function get_templates($args = array())
	{
		global $wpdb;
		$type = empty($args['type']) ? 'popup' : $args['type'];
		$table = $wpdb->prefix.'oometrics_template';
			$templates = $wpdb->get_results(
			    $wpdb->prepare(
			        "SELECT * FROM $table
			         WHERE type = %s",
			         $type
			    )
			);
			return $templates;

	}

	public function get_template($tid = 0)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_template';
			$template = $wpdb->get_row(
			    $wpdb->prepare(
			        "SELECT * FROM $table
			         WHERE id = %d",
			         $tid
			    )
			);
			return $template;

	}

	public function save_template($args = array())
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_template';
		$data = array();
		$data['type'] = $args['type'];
		$data['title'] = $args['title'];
		$data['params'] = $args['params'];
		$data['vars'] = $args['vars'];
		$data['date'] = time();
		$result = $wpdb->insert($table,$data);

		return $wpdb->insert_id;

	}

	public function delete_template($tid = 0)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_template';
		$result = $wpdb->delete($table,array('id'=>$tid));
		if($result > 0){
			return true;
		} else {
			return false;
		}

	}

}
