<?php
/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOReport
{

	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'oometrics_nonce';

	/**
	 * The option name
	 *
	 * @var string
	 */

	private $option_name = 'oometrics_options';
	public $table;
	public $start_time;
	public $end_time;
	public $ses_id;
	public $act_id;
	public $sortby;

	/**
	 * OOMetrics constructor.
	 * The main plugin actions registered for WordPress
	 */

	public function __construct()
  {
			$options = get_option($this->option_name);

			$period_time = (empty($options['period_time'])) ? array('period_type'=>'last_3_month') : $options['period_time'];
			$this->sortby = empty($options['report_sort_by']) ? 'value' : $options['report_sort_by'];
			$this->set_period($period_time);
		}

		public function get($ses_id){
			return true;
		}
		public function set_period($time_period = array('period_type'=>'last_3_month')){

			$period_type = $time_period['period_type'];
			$now = time();
			$last_hour = strtotime("-1 hour",$now);
			$today              = strtotime('today');
			$yesterday          = strtotime('-1 day', $today);
			$last_week = strtotime('-1 week', $now);
			$last_3_month = strtotime('-3 months', $now);
			$last_year = strtotime('-1 year', $now);

			// echo $period_type;
			$this->end_time = $now;
			// echo 'aaaaaa'.$period_type;
			if($period_type == 'last_hour'){
				$this->start_time = $last_hour;
			} else if($period_type == 'today'){
				$this->start_time = $today;
			} else if($period_type == 'yesterday'){
				$this->end_time = $yesterday + 86400;
				$this->start_time = $yesterday;
			} else if($period_type == 'last_week'){
				$this->start_time = $last_week;
			} else if($period_type == 'last_month'){
				$this->start_time = $last_month;
			} else if($period_type == 'last_3_months'){
				$this->start_time = $last_3_month;
			} else if($period_type == 'last_year'){
				$this->start_time = $last_year;
			} else if($period_type == 'custom'){
				$this->start_time = $time_period['start_time'];
				$this->end_time = $time_period['end_time'];
			} else{
				$this->start_time = $today;
			}
			return true;
		}


		public function get_total_sales(){

		    global $wpdb;
				$sales = $wpdb->get_var( $wpdb->prepare("
		        SELECT DISTINCT SUM(pm.meta_value)
		        FROM {$wpdb->prefix}posts as p
		        INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
		        WHERE p.post_type LIKE 'shop_order'
		        AND p.post_status IN ('wc-processing','wc-completed')
		        AND UNIX_TIMESTAMP(p.post_date) >= %d AND UNIX_TIMESTAMP(p.post_date) <= %d
		        AND pm.meta_key LIKE '_order_total'
		    " ,array($this->start_time,$this->end_time)));

		    return $sales;

		}

		public function get_total_sessions(){
			global $wpdb;
			$table = $wpdb->prefix.'oometrics_session';
			$sessions = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $table
					WHERE date >= %d AND date <= %d
			" ,array($this->start_time,$this->end_time)));

			return $sessions;
		}

		public function get_total_uniques(){
			global $wpdb;
			$table = $wpdb->prefix.'oometrics_session';
			$meta_table = $wpdb->prefix.'oometrics_session_meta';
			$uniques = $wpdb->get_results( $wpdb->prepare("
					SELECT DISTINCT metas.meta_value
					FROM $table as ses
					INNER JOIN $meta_table as metas ON metas.ses_id = ses.id
					WHERE metas.meta_key = 'ip' AND ses.date >= %d AND ses.date <= %d
			" ,array($this->start_time,$this->end_time)));
			return count($uniques);
		}

		public function get_total_orders(){
			global $wpdb;
			$orders = $wpdb->get_var( $wpdb->prepare("
					SELECT DISTINCT COUNT(*)
					FROM {$wpdb->prefix}posts as p
					INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
					WHERE p.post_type LIKE 'shop_order'
					AND p.post_status IN ('wc-processing','wc-completed')
					AND UNIX_TIMESTAMP(p.post_date) >= %d AND UNIX_TIMESTAMP(p.post_date) <= %d
					AND pm.meta_key LIKE '_order_total'
			" ,array($this->start_time,$this->end_time)));

			return $orders;
		}

		public function get_total_activities(){
			global $wpdb;
			$table = $wpdb->prefix.'oometrics_session';
			$atable = $wpdb->prefix.'oometrics_activity';
			$activities = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $table as ses
					INNER JOIN $atable as act ON ses.id = act.ses
					WHERE ses.date >= %d AND ses.date <= %d
			" ,array($this->start_time,$this->end_time)));
			return $activities;
		}

		public function get_ativities_overview(){

			global $wpdb;
			$table = $wpdb->prefix.'oometrics_session';
			$ctable = $wpdb->prefix.'oometrics_chat';
			$rtable = $wpdb->prefix.'oometrics_chat_rel';
			$atable = $wpdb->prefix.'oometrics_activity';
			$ptable = $wpdb->prefix.'oometrics_push';

			$total_sessions = $this->get_total_sessions();
			$total_uniques = $this->get_total_uniques();
			$total_activities = $this->get_total_activities();

			$settings = get_option($this->option_name);

			$sessions_with_cart = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $table as ses
					INNER JOIN $atable as act ON ses.id = act.ses
					WHERE type = '%s' AND ses.date >= %d AND ses.date <= %d
					" ,array('added_to_cart',$this->start_time,$this->end_time)
				)
			);


			$sessions_with_checkout = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $table as ses
					INNER JOIN $atable as act ON ses.id = act.ses
					WHERE url LIKE '".'%checkout%'."' AND ses.date >= %d AND ses.date <= %d
					" ,array($this->start_time,$this->end_time)
				)
			);

			$sessions_with_chats = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $table as ses
					INNER JOIN $ctable as chat ON ses.id = chat.id
					WHERE ses.date >= %d AND ses.date <= %d
					" ,array($this->start_time,$this->end_time)
				)
			);


			$sessions_duration_10plus = $wpdb->get_results( $wpdb->prepare("
					SELECT last_act,date,last_act-date as duration
					FROM $table
					WHERE last_act-date >= %d AND date >= %d AND date <= %d
			" ,array(600,$this->start_time,$this->end_time)));

			$sessions_duration_10_5 = $wpdb->get_results( $wpdb->prepare("
					SELECT last_act,date,last_act-date as duration
					FROM $table
					WHERE (last_act-date) >= %d AND (last_act-date) <= %d AND date >= %d AND date <= %d
			" ,array(300,600,$this->start_time,$this->end_time)));

			$sessions_duration_1_5 = $wpdb->get_results( $wpdb->prepare("
					SELECT last_act,date,last_act-date as duration
					FROM $table
					WHERE last_act-date >= %d AND last_act-date <= %d AND date >= %d AND date <= %d
			" ,array(60,300,$this->start_time,$this->end_time)));

			$sessions_duration_1_less = $wpdb->get_results( $wpdb->prepare("
					SELECT last_act,date,last_act-date as duration
					FROM $table
					WHERE last_act-date >= %d AND last_act-date <= %d AND date >= %d AND date <= %d
			" ,array(0,60,$this->start_time,$this->end_time)));

			$sessions_duration_30_less = $wpdb->get_results( $wpdb->prepare("
					SELECT last_act,date,last_act-date as duration
					FROM $table
					WHERE last_act-date >= %d AND last_act-date <= %d AND date >= %d AND date <= %d
			" ,array(0,30,$this->start_time,$this->end_time)));

			// $sessions_mobile = $wpdb->get_var( $wpdb->prepare("
			// 		SELECT COUNT(*)
			// 		FROM $table
			// 		WHERE device = 'mobile' AND date >= %d AND date <= %d
			// 		" ,array($this->start_time,$this->end_time)
			// 	)
			// );

			// $sessions_desktop = $wpdb->get_var( $wpdb->prepare("
			// 		SELECT COUNT(*)
			// 		FROM $table
			// 		WHERE device = '%s' AND date >= %d AND date <= %d
			// 		" ,array('desktop',$this->start_time,$this->end_time)
			// 	)
			// );

			// PUSHES
			$total_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE date >= %d AND date <= %d
					" ,array($this->start_time,$this->end_time)
				)
			);
			$sale_price_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE type = '%s' AND date >= %d AND date <= %d
					" ,array('sale_price',$this->start_time,$this->end_time)
				)
			);
			$apply_coupon_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE type = '%s' AND date >= %d AND date <= %d
					" ,array('apply_coupon',$this->start_time,$this->end_time)
				)
			);
			$popup_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE type = '%s' AND date >= %d AND date <= %d
					" ,array('open_popup',$this->start_time,$this->end_time)
				)
			);

			$delivered_popup_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE type = '%s' AND status = '1' AND date >= %d AND date <= %d
					" ,array('open_popup',$this->start_time,$this->end_time)
				)
			);
			$clicked_popup_pushes = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $ptable
					WHERE type = '%s' AND clicked = '1' AND date >= %d AND date <= %d
					" ,array('open_popup',$this->start_time,$this->end_time)
				)
			);

			$successful_sessions_with_push = $wpdb->get_var( $wpdb->prepare("
					SELECT COUNT(*)
					FROM $atable as act
					INNER JOIN $ptable as push ON act.ses = push.ses_id
					WHERE act.url LIKE '".'%checkout%'."' OR act.url LIKE '".'%cart%'."' AND act.date >= '%d' AND act.date <= '%d'
					" ,array($this->start_time,$this->end_time)
				)
			);
			// print_r($sessions_desktop);

			$ses_value_sum = $wpdb->get_var( $wpdb->prepare("
					SELECT SUM(value)
					FROM $table
					WHERE date >= %d AND date <= %d
					" ,array($this->start_time,$this->end_time)
				)
			);


			$durations_sum = $wpdb->get_var( $wpdb->prepare("
					SELECT SUM( ( date - last_act ) ) as total
					FROM $table
					WHERE date >= %d AND date <= %d AND uid != %d
					" ,array($this->start_time,$this->end_time,$settings['main_user'])
				)
			);

			$total_sessions = empty($total_sessions) ? 1 : $total_sessions;
			$total_activities = empty($total_activities) ? 1 : $total_activities;
			$average_ses_value = $ses_value_sum / $total_sessions;

			$average_activities = number_format(($total_activities / $total_sessions),1,".",",");
			$average_ses_value = number_format(($ses_value_sum / $total_sessions),1,".",",");
			$average_duration_sec = ($durations_sum / $total_sessions) / 60;
			$average_duration = number_format($average_duration_sec,1,".",",") .' '.__('mins','oometrics');


			$sessions_with_cart_percent = ($sessions_with_cart / $total_activities) * 100;
			$sessions_with_cart_html = '<strong>'.number_format($sessions_with_cart,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($sessions_with_cart_percent,1,".",",").'%</strong>';

			$sessions_with_chats_percent = ($sessions_with_chats / $total_activities ) * 100;
			$sessions_with_chats_html = '<strong>'.number_format($sessions_with_chats,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($sessions_with_chats_percent,1,".",",").'%</strong>';

			$sessions_with_checkout_percent = ($sessions_with_checkout / $total_activities ) * 100;
			$sessions_with_checkout_html = '<strong>'.number_format($sessions_with_checkout,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($sessions_with_checkout_percent,1,".",",").'%</strong>';

			$ses_dur_10plus_count =  count($sessions_duration_10plus);
			$ses_dur_10plus_percent =  ($ses_dur_10plus_count / $total_sessions ) * 100;
			$ses_dur_10plus_html =  '<strong>'.number_format($ses_dur_10plus_count,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($ses_dur_10plus_percent,1,".",",").'%</strong>';

			$ses_dur_10_5_count =  count($sessions_duration_10_5);
			$ses_dur_10_5_percent =  ( $ses_dur_10_5_count / $total_sessions ) * 100;
			$ses_dur_10_5_html =  '<strong>'.number_format($ses_dur_10_5_count,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($ses_dur_10_5_percent,1,".",",").'%</strong>';

			$ses_dur_1_5_count =  count($sessions_duration_1_5);
			$ses_dur_1_5_percent =  ( $ses_dur_1_5_count / $total_sessions ) * 100;
			$ses_dur_1_5_html =  '<strong>'.number_format($ses_dur_1_5_count,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($ses_dur_1_5_percent,1,".",",").'%</strong>';

			$ses_dur_1_less_count =  count($sessions_duration_1_less);
			$ses_dur_1_less_percent =  ($ses_dur_1_less_count / $total_sessions ) * 100;
			$ses_dur_1_less_html =  '<strong>'.number_format($ses_dur_1_less_count,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($ses_dur_1_less_percent,1,".",",").'%</strong>';

			$ses_dur_30_less_count =  count($sessions_duration_30_less);
			$ses_dur_30_less_percent =  ($ses_dur_30_less_count / $total_sessions ) * 100;
			$ses_dur_30_less_html =  '<strong>'.number_format($ses_dur_30_less_count,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($ses_dur_30_less_percent,1,".",",").'%</strong>';



			$total_pushes = !empty($total_pushes) ? $total_pushes : 1;
			$sale_price_pushes_percent =  ($sale_price_pushes / $total_pushes ) * 100;
			$sale_price_pushes_html =  '<strong>'.number_format($sale_price_pushes,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($sale_price_pushes_percent,1,".",",").'%</strong>';

			$apply_coupon_pushes_percent =  ($apply_coupon_pushes / $total_pushes ) * 100;
			$apply_coupon_pushes_html =  '<strong>'.number_format($apply_coupon_pushes,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($apply_coupon_pushes_percent,1,".",",").'%</strong>';

			$popup_pushes_percent =  ($popup_pushes / $total_pushes ) * 100;
			$popup_pushes_html =  '<strong>'.number_format($popup_pushes,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($popup_pushes_percent,1,".",",").'%</strong>';

			$delivered_popup_pushes_percent =  ($delivered_popup_pushes / $total_pushes ) * 100;
			$delivered_popup_pushes_html =  '<strong>'.number_format($delivered_popup_pushes,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($delivered_popup_pushes_percent,1,".",",").'%</strong>';

			$clicked_popup_pushes_percent =  ($clicked_popup_pushes / $total_pushes ) * 100;
			$clicked_popup_pushes_html =  '<strong>'.number_format($clicked_popup_pushes,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($clicked_popup_pushes_percent,1,".",",").'%</strong>';

			$successful_sessions_with_push_percent =  ($successful_sessions_with_push / $total_sessions ) * 100;
			$successful_sessions_with_push_html =  '<strong>'.number_format($successful_sessions_with_push,0,".",",").'</strong><strong class="oo-to-icon"></strong><strong>'.number_format($successful_sessions_with_push_percent,1,".",",").'%</strong>';


		$data = array(
			'cart_activity' => $sessions_with_cart_html,
			'chat_activity' => $sessions_with_chats_html,
			'checkout_activity' => $sessions_with_checkout_html,
			'sale_price_pushes' => $sale_price_pushes_html,
			'apply_coupon_pushes' => $apply_coupon_pushes_html,
			'popup_pushes' => $popup_pushes_html,
			'delivered_popup_pushes' => $delivered_popup_pushes_html,
			'clicked_popup_pushes' => $clicked_popup_pushes_html,
			'successful_session_pushes' => $successful_sessions_with_push_html,
			'session_durations_10plus' => $ses_dur_10plus_html,
			'sessions_duration_10_5' => $ses_dur_10_5_html,
			'sessions_duration_1_5' => $ses_dur_1_5_html,
			'sessions_duration_1_less' => $ses_dur_1_less_html,
			'sessions_duration_30_less' => $ses_dur_30_less_html,
			'average_ses_value' => $average_ses_value,
			'average_duration' => $average_duration,
			'average_activities' => $average_activities,
			// 'desktop_devices' => $sessions_desktop,
			// 'mobile_devices' => $sessions_mobile
		);

		return $data;
		}

		public function get_sessions($args = array()){
			global $wpdb;
			$table = $wpdb->prefix.'oometrics_session';
			$query = $wpdb->prepare("
					SELECT *
					FROM $table
					WHERE date >= %s AND date <= %s
					ORDER BY {$this->sortby} DESC
				" ,array($this->start_time,$this->end_time)
			);
			$number = isset($args['number']) ? $args['number'] : 20;
			$page = (isset($args['page'])) ? $args['page'] : 1;
			if($page == 1){
				$limit_from = 0;
				$limit_to = $number;
			} else {
				$limit_from = ($page - 1) * $number;
				$limit_to = $number;
			}


			$query .=' LIMIT '.$limit_from.','.$limit_to;
			// echo $query;
			$sessions = $wpdb->get_results( $query);

			return $sessions;
		}



}
