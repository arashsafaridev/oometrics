<?php
/**
 * Plugin Name:      OOMetrics
 * Description:       WooCommerce Smart Metrics and Live Customer Channel; Set discounts, coupons and pop ups remotely for each customer individually while you are watching statistics!
 * Version:            1.3.0
 * Author:             OOMetrics
 * Author URI:       https://github.com/arashsafaridev
 * Text Domain:     oometrics
 * License:             GPL-2.0+
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/arashsafaridev/oometrics
 */


/*
 * Plugin constants
 */

// Crawler Detect
use Jaybizzle\CrawlerDetect\CrawlerDetect;

if(!defined('OOMETRICS_PLUGIN_VERSION'))
	define('OOMETRICS_PLUGIN_VERSION', '1.3.0');
if(!defined('OOMETRICS_URL'))
	define('OOMETRICS_URL', plugin_dir_url( __FILE__ ));
if(!defined('OOMETRICS_PATH'))
	define('OOMETRICS_PATH', plugin_dir_path( __FILE__ ));

// Crawler Detect libraries
if(!class_exists('Jaybizzle\\CrawlerDetect\\CrawlerDetect')){
	require_once(OOMETRICS_PATH.'/inc/Fixtures/AbstractProvider.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Crawlers.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Exclusions.php');
	require_once(OOMETRICS_PATH.'/inc/Fixtures/Headers.php');
	require_once(OOMETRICS_PATH.'/inc/crawlerdetect.php');
}
// oometrics libraries
require_once(OOMETRICS_PATH.'/inc/oometrics-class.php');
require_once(OOMETRICS_PATH.'/inc/session-class.php');
require_once(OOMETRICS_PATH.'/inc/activity-class.php');
require_once(OOMETRICS_PATH.'/inc/chat-class.php');
require_once(OOMETRICS_PATH.'/inc/helper-class.php');
require_once(OOMETRICS_PATH.'/inc/ajax-class.php');
if(is_admin()){
	require_once(OOMETRICS_PATH.'/inc/admin-ajax-class.php');
	require_once(OOMETRICS_PATH.'/inc/report-class.php');
}
require_once(OOMETRICS_PATH.'/inc/push-class.php');



register_activation_hook( __FILE__, 'oo_do_on_activation');
// register_deactivation_hook( __FILE__, array($this,'do_on_deactivation') );
register_uninstall_hook( __FILE__, 'oo_do_on_uninstallation' );
function oo_do_on_activation()
	{
		// Require WooCommerce plugin
	  if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
			 // Stop activation redirect and show error
			 wp_die('Sorry, but this plugin requires the WooCommerce Plugin to be installed and actived. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>','oometrics');
	  }

	  if ( isset($settings['oometrics_plugin_version']) && $settings['oometrics_plugin_version'] < OOMETRICS_PLUGIN_VERSION) {
			 // Stop activation redirect and show error
			 wp_die('OOMetrics have a major update in this version and you have a prior one. Please uninstall it first then install this version.','oometrics');
	  }

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$session_table_name = $wpdb->prefix . "oometrics_session";
		$session_meta_table_name = $wpdb->prefix . "oometrics_session_meta";
		$activity_table_name = $wpdb->prefix . "oometrics_activity";
		$push_table_name = $wpdb->prefix . "oometrics_push";
		$chat_table_name = $wpdb->prefix . "oometrics_chat";
		$rel_table_name = $wpdb->prefix . "oometrics_chat_rel";
		$template_table_name = $wpdb->prefix . "oometrics_template";
		$sql = "CREATE TABLE $session_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
		  uid bigint(20) NOT NULL DEFAULT '0',
		  value tinyint(4) NOT NULL DEFAULT '0',
		  expired tinyint(1) NOT NULL DEFAULT '0',
		  status tinyint(1) NOT NULL DEFAULT '0',
		  date bigint(20) NOT NULL DEFAULT '0',
		  last_act bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $session_meta_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
		  ses_id bigint(20) NOT NULL DEFAULT '0',
		  meta_key varchar(300) NOT NULL,
		  meta_value text NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;


		CREATE TABLE $activity_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ses bigint(20) NOT NULL,
			type varchar(50) NOT NULL DEFAULT '',
			url tinytext,
			xid tinytext,
			hits int(11) NOT NULL DEFAULT '0',
			date bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $chat_table_name (
		 id bigint(20) NOT NULL AUTO_INCREMENT,
		 sender_id int(11) DEFAULT '0',
		 receiver_id int(11) DEFAULT '0',
		 ses_id bigint(20) NOT NULL DEFAULT '0',
		 rel_id bigint(20) NOT NULL DEFAULT '0',
		 content text CHARACTER SET utf8 COLLATE utf8_general_ci,
		 content_before text CHARACTER SET utf8 COLLATE utf8_general_ci,
		 attachment int(11) DEFAULT NULL,
		 status tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=unknown, 1=sent,2=delivered,3=seen',
		 edited tinyint(1) DEFAULT NULL,
		 date bigint(20) NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;
		CREATE TABLE $push_table_name (
		  id int(11) NOT NULL AUTO_INCREMENT,
		  ses_id int(11) NOT NULL,
		  type varchar(100) NOT NULL,
		  xid int(11) DEFAULT '0',
		  run_time int(11) NOT NULL,
		  time_gap int(11) DEFAULT NULL,
		  status tinyint(4) NOT NULL DEFAULT '0',
		  clicked tinyint(4) DEFAULT '0',
		  args text NOT NULL,
		  params text,
		  alt text,
		  date int(11) DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $rel_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
		  sender_ses_id bigint(20) NOT NULL,
		  receiver_ses_id bigint(20) NOT NULL,
		  date bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (id)
		) $charset_collate;

		CREATE TABLE $template_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
		  type varchar(500) DEFAULT NULL,
		  title varchar(500) DEFAULT NULL,
		  params text DEFAULT NULL,
		  vars text DEFAULT NULL,
		  date bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (id)
		) $charset_collate;
		";

		dbDelta($sql);

		add_option('oometrics_plugin_version',OOMETRICS_PLUGIN_VERSION);

		$admin_ses_exists = $wpdb->get_var("SELECT COUNT(*) FROM $session_table_name WHERE id = 1");
		if($admin_ses_exists){
			$wpdb->get_var("UPDATE $session_table_name SET expired = 0 WHERE id = 1");
		} else {
			$admin_session_data = array(
				'uid' => get_current_user_id(),
				'value' => 1,
				'expired' => 0,
				'last_act' => time(),
				'date' => time(),
			);
			$wpdb->insert($session_table_name,$admin_session_data);
		}

		$now = time();

		$settings = array(
			'main_user' => get_current_user_id(),
			'admin_interval' => 10000,
			'chat_interval' => 5000,
			'session_interval' => 10000,
			'live_lifetime' => 300,
			'session_lifetime' => 86400,
			'session_value_base' => 15,
			'clean_zero_values' => 'yes',
			'live_sort_by' => 'last_act',
			'chat_welcome_message' => __('Tell us how can we help you and give you better shopping experience','oometrics'),
			'chat_icon_open' => OOMETRICS_URL. 'assets/images/start-chat.svg',
			'chat_icon_close' => OOMETRICS_URL. 'assets/images/stop-chat.svg',
			'chat_position' => 'bottom-left',
			'chat_position_h' => '3rem',
			'chat_position_v' => '3rem',
			'chat_editor' => 'simple',
			'chat_enabled' => 'yes',
			'tracking_notification' => 'no',
			'tracking_message' => __('For better shopping experience, we will collect none personal data...','oometrics'),
			'period_time' => array(
				'period_type' => 'last_week',
				'start_time' => $now - 604800,
				'end_time' => $now
			)
		);
		update_option('oometrics_options',$settings);

		// to be sure if something went wrong with the table creation
		try {
			$helper = new OOHelper();
			$params = array(
				'popup_content' => '<h2>Welcome to OOMetrics</h2><p>This is just a sample pop up to send!</p><p>You can create your own pop up or you can use OOArea widget sidebar to put any content via third party plugins and shortcodes!</p>',
				'popup_btn_1_label' => __('OK, I got it!','oometrics'),
				'popup_btn_2_label' => __('Back to cart','oometrics'),
				'popup_btn_1_href' => '/',
				'popup_btn_2_href' => '/cart',
			);
			$args = array(
				'type' => 'popup',
				'title' => __('Sample Pop Up','oometrics'),
				'params' => serialize($params),
				'vars' => serialize([]),
				'date' => time()
			);
			$helper->save_template($args);
		} catch (\Exception $e) {

		}

		// $domain = str_replace(".","_",$_SERVER['SERVER_NAME']);
		// setcookie($domain.'_oometrics_session', "", time() - 604800);
		// setcookie($domain.'_oometrics_admin_session', "", time() - 604800);

	}

	function oo_do_on_uninstallation()
	{
		global $wpdb;

		$session_table_name = $wpdb->prefix . "oometrics_session";
		$session_meta_table_name = $wpdb->prefix . "oometrics_session_meta";
		$activity_table_name = $wpdb->prefix . "oometrics_activity";
		$chat_table_name = $wpdb->prefix . "oometrics_chat";
		$push_table_name = $wpdb->prefix . "oometrics_push";
		$rel_table_name = $wpdb->prefix . "oometrics_chat_rel";
		$template_table_name = $wpdb->prefix . "oometrics_template";

		$sql = "DROP TABLE  $session_table_name, $session_meta_table_name, $chat_table_name, $activity_table_name,$rel_table_name,$push_table_name,$template_table_name";
		$wpdb->query( $sql );

		dbDelta( $sql );

		//global options
		delete_option('oometrics_options');
	}

	// remove bot and crawler requests
	function oo_is_bot()
	{
		$CrawlerDetect = new CrawlerDetect();
		 // Check the user agent of the current 'visitor'
		 if($CrawlerDetect->isCrawler()) {
			 return true;
		 }
		if(
			empty($_SERVER['HTTP_USER_AGENT']) ||
			preg_match('/bot|crawl|spider|mediapartners|slurp|patrol/i', $_SERVER['HTTP_USER_AGENT'])
		)
		{
			return true;
		}
		return false;
	}


	// remove filtered request like cronjobs, ajax requests and manually added URLs
	function oo_is_filtered()
	{

		 $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
		 if(isset($referer) && preg_match('/cron|cronjob|wp_cron|get_refreshed_fragments|ajax/i', $referer))
		 {
			 return true;
		 }
		 $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-';
		 if(isset($url) && preg_match('/cron|cronjob|wp_cron|get_refreshed_fragments|ajax/i', $url))
		 {
			 return true;
		 }

		 if(stripos($url, "xmlrpc.php") !== false){
			 return true;
		 }

		 return false;
	}

	if(!oo_is_bot()){
		add_action('init',array(new OOMetrics(),'init'),100);
	}
