<?php
// session class
class OOSession
{

	public $table;

	public $id;
	public $uid;
	public $value;
	public $expired;
	public $status;
	public $date;
	public $last_act;
	public $receiver_id;

	private $option_name = 'oometrics_options';


	public function __construct(){
		global $wpdb;
		$this->table = $wpdb->prefix.'oometrics_session';
	}

	public function get_defaults(){
		$data = [];
		$data['uid'] = get_current_user_id();
		$data['value'] = 0;
		$data['expired'] = 0;
		$data['status'] = 0;
		$data['date'] = time();
		$data['last_act'] = time();
		return $data;
	}

	public function add($data){
		global $wpdb;
		$wpdb->insert($this->table,$data);
		return $wpdb->insert_id;
	}

	public function get_by($column,$value,$where = []){
		global $wpdb;

		$where_prepared = empty($where) ? '' : $wpdb->prepare($where['query'],$where['args']);
		if($column == 'id') {
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE id = %d".$where_prepared,
					 $value
			);
		} else if($column == 'uid') {
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE uid = %d".$where_prepared,
					 $value
			);
		}

		$session_data = $wpdb->get_row($query);

		return $session_data;
	}


	public function get_status($session_id)
	{
		global $wpdb;
		$status = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT status FROM $this->table
		         WHERE id = %d",
		         $session_id
		    )
		);

		return empty($status) ? 0 : (int)$status;
	}

	public function set_status($id = 0,$status = 0)
	{
		global $wpdb;
		$status = $wpdb->get_var(
		    $wpdb->prepare(
		        "UPDATE $this->table
						 SET status = '%s'
		         WHERE id = %d",
		         $status, $id
		    )
		);
	}

	public function update_last_activity($session_id){
		global $wpdb;
		$wpdb->get_var(
			$wpdb->prepare("UPDATE $this->table
			SET last_act = %d WHERE id = %d",
			array(time(),$session_id)
			)
		);
	}

	public function set_user_id($user_login,$user){
				global $wpdb;
				$session_id = $this->id;
				$s_table = $wpdb->prefix.'oometrics_chat';
				$in_db = $wpdb->get_var(
						$wpdb->prepare(
								"UPDATE $s_table
								 SET sender_id = %d
								 WHERE id = %d AND sender_id = %d",
								 array($user->ID,$session_id,0)
						)
				);
				$in_db = $wpdb->get_var(
						$wpdb->prepare(
								"UPDATE $this->table
								 SET uid = %d
								 WHERE id = %d",
								 array($user->ID,$session_id)
						)
				);
	}

	public function update($data){

		global $wpdb;
		$result = $wpdb->update( $this->table, $data, $where );

		if( (int)$result > 0){
			return true;
		} else {
			return false;
		}

	}

	public function update_all($session_lifetime)
	{

		$now = time();
		$last_update = get_option('OOMetrics_last_run',0);
		if(($now - $last_update) > 50 )
		{
			global $wpdb;
			$push_table = $wpdb->prefix.'oometrics_push';
			$wpdb->query(
			    $wpdb->prepare(
			        "UPDATE $push_table
			         SET status = 1 WHERE time_gap < %d",
			         array($now)
			    )
			);

			$wpdb->query(
			    $wpdb->prepare(
			        "UPDATE $this->table
			         SET expired = 1 WHERE last_act < %d",
			         array($now - $session_lifetime)
			    )
			);
			update_option('OOMetrics_last_run',$now);
		}

		return true;

	}

	public function add_value($session_id = 0,$value = 1)
	{
		global $wpdb;
		$wpdb->get_var(
			$wpdb->prepare("UPDATE $this->table
				SET value = value + %d WHERE id = %d",
					array($value,$session_id)
				)
		);
	}

	public function update_meta($session_id,$key,$value = '',$serialize = true){
		if(empty($session_id) || empty($key))
			return false;
		$final_value = $serialize ? serialize($value) : $value;
		global $wpdb;
		$meta_table = $wpdb->prefix.'oometrics_session_meta';
		$exists_query = $wpdb->prepare("SELECT COUNT(meta_key) FROM $meta_table WHERE ses_id = %d AND meta_key = %s",array($session_id,$key));
		$count = $wpdb->get_var($exists_query);

		if($count){
			$wpdb->get_var(
				$wpdb->prepare("UPDATE $meta_table
					SET meta_value = %s WHERE ses_id = %d AND meta_key = %s",
						array($final_value,$session_id,$key)
					)
			);
			return true;
		} else {
			$data = [];
			$data['ses_id'] = $session_id;
			$data['meta_key'] = $key;
			$data['meta_value'] = $final_value;
			$wpdb->insert($meta_table,$data);
			return $wpdb->insert_id;
		}
		return false;
	}

	public function get_meta($session_id,$key,$unserialize = true){
		global $wpdb;
		$meta_table = $wpdb->prefix.'oometrics_session_meta';
		$meta_query = $wpdb->prepare("SELECT meta_value FROM $meta_table WHERE ses_id = %d AND meta_key = %s",array($session_id,$key));
		$value_serialized = $wpdb->get_var($meta_query);
		$value = $unserialize ? unserialize($value_serialized) : $value_serialized;
		return empty($value) ? [] : $value;
	}

	public function add_activity_init()
	{
		$activity = new OOActivity();
		$activity->init();
	}

	private function activities_count($session_id)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activity';
		$count = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE ses = '%d'",
						 $session_id
				)
		);
		return $count;
	}
	public function chat_count($session_id = 0,$rel_id = -1)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$count = 0;
		if( $rel_id > 0){
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE ses_id = %d AND rel_id = %d",
							 $session_id,$rel_id
					)
			);
		} else {
			$session_id = ($id > 0) ? $id : $this->id;
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE ses_id = %d",
							 $session_id
					)
			);
		}
		return $count;

	}
	public function new_chat_count($session_id = 0, $rel_id = -1)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$count = 0;
		if( $rel_id > 0){
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE ses_id = %d AND rel_id = %d AND status != %d",
							 array($session_id,$rel_id,3)
					)
			);

		} else {
			$count = $wpdb->get_var(
					$wpdb->prepare(
							"SELECT COUNT(*) FROM $table
							 WHERE ses_id = %d AND status != %d",
							 array($session_id,3)
					)
			);
		}

		return $count;
	}


		public function render($session_data,$rel_id = -1, $admin = true)
		{
			$session = $session_data;
		  if(!empty($session->uid) && $session->uid > 0)
		  {
		    $user = get_user_by('id',$session->uid);
		    $name = $user->display_name;
		    if(empty($name))
		    {
		      $name = $user->user_login;
		    }

		    $avatar = get_avatar($session->uid,40);
		  } else {
		    $name = __('Session','oometrics').' '.$session->id;
		    $avatar = '<i class="icon icon-anon-avatar"></i>';
		  }

		  $session_pushes = $this->get_session_pushes($session->id,0);
		  if(!empty($session_pushes)){
		    $pushes_html = '<hr /><div class="oo-push-items">';
		    foreach ($session_pushes as $key => $session_push) {

		      $time_left = human_time_diff( $session_push->time_gap, time() );
		      $pushes_html .= '<div class="oo-push-item '.$session_push->type.'" id="oo-push-item-'.$session_push->id.'">
					<span>
		        <i class="icon icon-'.$session_push->type.' small"></i>'.
		        $time_left.' '.__('left','oometrics').
						'</span>'.
		        '<div class="oo-push-delete" data-pushid="'.$session_push->id.'">
		          <i class="icon icon-close-popup small"></i>
		        </div>
		      </div>';
		    }
		    $pushes_html .= '</div>';
		  }

		  $time = human_time_diff( $session->last_act, time() );

		  $value = $session->value;
		  $activities_count = $this->activities_count($session->id);
		  $chat_count = $this->chat_count($session->id,$rel_id);
		  $new_chat_count = $this->new_chat_count($session->id,$rel_id);
		  if($chat_count > 0){
		    if($new_chat_count > 0){
		      $chat = '<span class="oo-new-chat-badge">'.$new_chat_count.'</span>';
		    } else {
		      $chat = '<span class="oo-new-chat-badge off">'.$chat_count.'</span>';
		    }

		  }

		  $shortcut_actions = '
		  <div class="oo-live-shortcuts">
		    <a class="oo-live-popup-shortcut" data-sesid="'.$session->id.'"><i class="icon icon-open_popup"></i></a>
		    <a class="oo-live-sale-price-shortcut" data-sesid="'.$session->id.'"><i class="icon icon-sale_price"></i></a>
		  </div>
		  ';

		  $html = '
		  <li data-sesid="'.$session->id.'" class="oo-session-profile">
		    '.$avatar.'
		    <div class="oo-session-info">
		      '.$chat;
					if($name){
						$html .= '<strong>'.$name.'</strong>';
					}
		      if($admin){
		        $html .= '
						<span>Value: <b>'.$value.'</b></span>
						<span>Activities: <b>'.$activities_count.'</b></span>';
		      }

		      $html .= '
		      <em><i class="oo-icon oo-icon-time"></i>'.$time.'</em>
		      '.$pushes_html.'
		    </div>
				'.$shortcut_actions.'
		  </li>
		  ';
		  return $html;
		}

	public function get_total_sales_day()
	{
    global $wpdb;
		$sales = $wpdb->get_var( "
        SELECT DISTINCT SUM(pm.meta_value)
        FROM {$wpdb->prefix}posts as p
        INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
        WHERE p.post_type LIKE 'shop_order'
        AND p.post_status IN ('wc-processing','wc-completed')
        AND UNIX_TIMESTAMP(p.post_date) >= (UNIX_TIMESTAMP(NOW()) - (86400))
        AND pm.meta_key LIKE '_order_total'
    ");
    return wc_price($sales);
	}
	public function get_woo_session_value($session_hash = ''){
		global $wpdb;
		$table = $wpdb->prefix.'woocommerce_sessions';
			$woo_session = $wpdb->get_row(
					$wpdb->prepare(
							"SELECT session_value FROM $table
							 WHERE session_key = '%s'",
							 array($session_hash)
					)
			);

			return $woo_session;

	}
	public function update_actual_cart($session_hash,$session_data){
		global $wpdb;
		$table = $wpdb->prefix.'woocommerce_sessions';
			$woo_session = $wpdb->get_row(
					$wpdb->prepare(
							"UPDATE $table
							SET session_value = '%s'
							 WHERE session_key = '%s'",
							 array($session_data,$session_hash)
					)
			);
			return $woo_session;
	}
	public function get_cart_session(){
		if(class_exists('WooCommerce')){
			$woo_session = WC()->session;
			$oo_cart_ses = serialize(array());
			if(!empty($woo_session)){
				$session_value_content = [];
				$woo_session_id = $woo_session->get_customer_id();
				$oo_session_value_content['key_hash'] = $woo_session_id;
				$session_value = $this->get_woo_session_value($woo_session_id);
				if(isset($session_value->session_value) && !empty($session_value->session_value) && !empty(unserialize($session_value->session_value)	)){
					$session_value_content = unserialize($session_value->session_value);
					if(!empty($session_value_content))
					{
						$oo_session_value_content['session'] = $session_value_content;
					} else {
						$oo_session_value_content['session'] = '0:{}';
					}
					$oo_cart_ses = serialize($oo_session_value_content);
				}

			}


			return $oo_cart_ses;
		}
		return [];
	}
	public function get_activities($html = true,$session_id = 0)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activity';

		$activities = $wpdb->get_results(
				$wpdb->prepare(
						"SELECT * FROM $table
						 WHERE ses = %d
						 ORDER BY
						 date
						 DESC",
						 $session_id
				)
		);

		if($html) {
			$html_code = '';
			foreach ($activities as $key => $act) {
				$act_obj = new OOActivity();
				$act_obj->sender_ses_id = $session_id;
				$html_code .= $act_obj->render($act->id);
			}
			return $html_code;
		}
		return $activities;
	}

	public function get_profile($session_id,$uid = 0)
	{
		global $wpdb;
		if(empty($uid)){
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table
					 WHERE id = %d",
					 array($session_id)
			);
			$profile_data = $wpdb->get_row($query);
		} else {
			$utable = $wpdb->prefix.'users';
			$query = $wpdb->prepare(
					"SELECT * FROM $this->table as ses
					 INNER JOIN $utable as user ON ses.uid = user.ID
					 WHERE ses.id = %d",
					 array($session_id)
			);
			$profile_data = $wpdb->get_row($query);
		}
		return $profile_data;
	}
	private function get_session_pushes($session_id,$status){
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_push';
		$now = time();
		$pushes = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * FROM $table
					 WHERE ses_id = '%d' AND status = '%d'",
					 array($session_id,$status)
			)
		);
		return $pushes;
	}

	public function render_profile($uid = 0,$html = false)
	{
		if($uid > 0){
			$user = get_user_by('id',$uid);
			$profile_data['display_name'] = $user->display_name;
			$profile_data['avatar'] = get_avatar($uid);
			$activity = '';
		} else {
			$profile_data['display_name'] = __('You','oometrics');
			$profile_data['avatar'] = '<i class="icon icon-anon-avatar large"></i>';
			$activity = '';
		}
		$rendered = '
		<div class="oo-profile-info">
			'.$profile_data['avatar'].'
			<ul class="oo-profile-data">
				<li class="name"><strong>'.$profile_data['display_name'].'</strong></li>
				<li class="name">'.$activity.'</li>
			</ul>
		</div>
		';
		if(!$html){
			return $profile_data;
		} else {
			return $rendered;
		}

	}

	public function get_live($args = [])
	{
		global $wpdb;
		$lifetime = $args['live_lifetime'];
		$diff = time() - $lifetime;
		$order_by = $args['live_sort_by'];
		$sessions = $wpdb->get_results(
		    $wpdb->prepare(
		        "SELECT * FROM $this->table
		         WHERE expired = %d AND uid != %d AND last_act > %d AND value >= %d
						 ORDER BY $order_by DESC",
		         array(0,$args['main_user'],$diff,0)
		    )
		);

		return $sessions;
	}

	public function get_online($args = [])
	{
		global $wpdb;
		$lifetime = $args['live_lifetime'];
		$diff = time() - $lifetime;
		$online = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT COUNT(*) FROM $this->table
		         WHERE expired = %d AND uid != %d AND last_act > %d AND value >= %d
						 ORDER BY last_act DESC",
		         array(0,$args['main_user'],$diff,0)
		    )
		);
		return $online;
	}
	public function get_pageviews()
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activity';
		$day = time() - 86400;
		$count = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE date > %d
						 ",
						 $day
				)
		);
		return $count;
	}

	public function get_unique_users()
	{
		global $wpdb;
		$day = time() - 86400;
		$unique = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT COUNT(*) FROM $this->table
		         WHERE date > %d AND value > %d
						 ORDER BY last_act DESC",
		         array($day,0)
		    )
		);
		return $unique;
	}

	public function reset_admin_session($user_id)
	{
		global $wpdb;
		$admin_ses_exists = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE id = 1");
		if((int)$admin_ses_exists > 0){
			$wpdb->get_var("UPDATE {$this->table} SET expired = 0, uid = $user_id WHERE id = 1");
		} else {
			$admin_session_data = array(
				'uid' => $user_id,
				'value' => 1,
				'expired' => 0,
				'status' => 0,
				'last_act' => time(),
				'date' => time(),
			);
			$wpdb->insert($this->table,$admin_session_data);
		}
	}
}
