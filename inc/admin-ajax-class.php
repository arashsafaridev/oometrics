<?php
	// ajax class from dashboard
class OOAdminAjax
{
	private $_nonce = 'oometrics_nonce';
	private $option_name = 'oometrics_options';

	public $session;
	public function __construct(){}

	private function verify_nonce($nonce){
		if(wp_verify_nonce($nonce, $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}
	}

	public function set_session($session){
		$this->session = $session;
	}

	public function get_live_sessions()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$rel_id = (int)(sanitize_text_field($_POST['rel_id']));
		$settings = get_option($this->option_name);
		$obj = $this->session;

		$sessions = $obj->get_live(array(
				'live_lifetime' => $settings['live_lifetime'],
				'live_sort_by' => $settings['live_sort_by'],
				'main_user' => $settings['main_user'],
			));

		$chat = new OOChat();
		$chat->set_session($this->session);
		if($rel_id == -1){
			$active_rel = $chat->get_active_rel_by_ses_id($sender_ses_id,$receiver_ses_id);
		} else {
			$active_rel = -1;
		}

		$set_rel = $active_rel->id ? $active_rel->id : $rel_id;

		$html = '';
		if($sessions){
			foreach ($sessions as $key => $session) {
				$ses_rel = $chat->get_active_rel_by_ses_id($sender_ses_id,$session->id);
				$html .= $obj->render($session,$ses_rel->id,true);
			}
		} else {
			$html = '<div class="oo-no-live-session">'.__("No one is online now",'oometrics').'</div>';
		}

		$total_sale = $obj->get_total_sales_day();
		$overview['total_sales'] = empty($total_sale) ? 0 : $total_sale;

		$overview['online_users'] = $obj->get_online(
				array(
						'live_lifetime' => $settings['live_lifetime'],
						'main_user' => $settings['main_user'],
					)
		);

		$overview['unique_users'] = $obj->get_unique_users();
		$overview['pageviews'] = $obj->get_pageviews();

		$session_data = [];
		if($receiver_ses_id != -1 ){
			$session = $this->session->get_by('id',$receiver_ses_id);
			$session_data = (array)$session;
			$activities = $this->session->get_activities(true,$receiver_ses_id);
			$profile_data = $this->session->get_profile($receiver_ses_id,$session->uid);


			$profile_clean = OOHelper::customer_info($profile_data->uid);
			// $session_data = array_merge($session_data,$customer_info);

			$profile_clean['display_name'] = isset($profile_data->display_name) ? $profile_data->display_name : __('Guest User','oometrics');
			$profile_clean['user_email'] = isset($profile_data->user_email) ? $profile_data->user_email : '';
			$profile_clean['user_id'] = isset($profile_data->uid) ? $profile_data->uid : 0;
			$profile_clean['avatar'] = isset($profile_data->uid) ? get_avatar_url($profile_data->uid) : OOMETRICS_URL.'/assets/images/anon-avatar.svg';


			$cart = [];

			$session_content_raw = $this->session->get_meta($receiver_ses_id,'cart_session');
			if(!empty($session_content_raw)){
				$session_obj = $session_content_raw;
				$session_key = $session_obj['key_hash'];
				$session_content = $session_obj['session'];

				$cart_data = empty($session_content['cart']) ? [] : unserialize($session_content['cart']);

				$totals = !empty($session_content['cart_totals']) ? unserialize($session_content['cart_totals']) : 0;
				$cart['cart_items'] = (empty($cart_data)) ? 0 : count($cart_data);
				$cart['cart_total'] = wc_price($totals['total']);

				if($session->uid == 0 ){
					$cart['purchased_items'] = '?';
					$cart['purchased_total'] = '?';
				} else {
					$customer_orders = get_posts( array(
							'numberposts' => -1,
							'meta_key'    => '_customer_user',
							'meta_value'  => $session->uid,
							'post_type'   => wc_get_order_types(),
							'post_status' => array_keys( wc_get_order_statuses() ),
					) );

					$cart['purchased_items'] = count($customer_orders);
					$cart['purchased_total'] = wc_price(wc_get_customer_total_spent( $session->uid ));
				}

				if(!empty($cart_data)){
						$cart_html = '';
					foreach ($cart_data as $key => $cart_item) {
						$quantity = $cart_item['quantity'];
						// simple product
						if($cart_item['variation_id'] == 0){
							$pid = $cart_item['product_id'];
							$product = wc_get_product( $pid ); // The WC_Product object
							if( ! $product->is_on_sale() ){
									$price = get_post_meta( $pid, '_price', true ); // Update active price
									$sale_price = get_post_meta($pid,'_sale_price',true);
							} else {
								$price = get_post_meta( $pid, '_regular_price', true ); // Update active price
								$sale_price = '';
							}

							if(!empty($sale_price)){
								$price_html = wc_price($price).'-'.wc_price($sale_price);
							} else {
								$price_html = wc_price($price);
							}
							$post_title = $product->get_title();
							$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
							$cart_html .='<div data-pid="'.$pid.'" data-vid="0" data-key="'.$cart_item['key'].'" data-qty="'.$quantity.'" class="oo-search-result-item"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="'.$quantity.'"/>'.$p_thumb.'<h5>'.$post_title.'</h5><br />'.$price_html.'</div>';
						} else {
							$pid = $cart_item['product_id'];
							$vid = $cart_item['variation_id'];
							$product = wc_get_product( $pid ); // The WC_Product object
							$atts = $cart_item['variation'];
							foreach ($atts as $key => $att) {
								$term = ltrim($key,'attribute_');

								$att_term = get_term_by('id',$att,$term);

								$variation_selected = $att_term->name;
							}
							if( ! $product->is_on_sale() ){
									$price = get_post_meta( $pid, '_price', true ); // Update active price
									$sale_price = get_post_meta($pid,'_sale_price',true);
							} else {
								$price = get_post_meta( $pid, '_regular_price', true ); // Update active price
								$sale_price = '';
							}
							if(!empty($sale_price)){
								$price_html = wc_price($price).'-'.wc_price($sale_price);
							} else {
								$price_html = wc_price($price);
							}
							$post_title = $product->get_title();
							$p_thumb = get_the_post_thumbnail($vid,'thumbnail');
							if(empty($p_thumb)){
								$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
							}
							$cart_html .='<div data-pid="'.$pid.'" data-vid="'.$vid.'" data-key="'.$cart_item['key'].'" data-qty="'.$quantity.'" class="oo-search-result-item"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="'.$quantity.'"/>'.$p_thumb.'<h5>'.$post_title.'</h5><br />'.$variation_selected.' '.$price_html.'</div>';
						}
					}
					$cart['cart_items_html'] = $cart_html;
				}

			}


			if(empty($cart['cart_items_html'])){
				$cart['cart_items_html'] = '<div>'.__("Cart is empty for now",'oometrics').'</div>';
			}


			$rels = $chat->get_conversations(true,array('id'=>$receiver_ses_id,'admin' => true));
			$new_chat_count = $this->session->new_chat_count($receiver_ses_id,$set_rel);
			$new_chat = $new_chat_count > 0 ? 1 : 0;
		}

		$session_meta = $this->session->get_meta($receiver_ses_id,'digital_info');
		$session_ip = $this->session->get_meta($receiver_ses_id,'ip',false);
		$session_data['ses_device'] = $session_meta['device'];
		$session_data['ses_browser'] = $session_meta['browser'];
		$session_data['ses_ip'] = $session_ip;
		$session_data['ses_referrer'] = $session_meta['referer'];

		wp_send_json( array(
			'content' => $html,
			'overview' => $overview,
			'session' => $session_data,
			'rels' => $rels,
			'activity' => $activities,
			'cart' => $cart,
			'info' => array(),
			'chats' => 'empty',
			'new_chat' => $new_chat,
			'profile' => $profile_clean,
			'overview' => $overview,
			'rel_id' => $set_rel
		) );
	}

	public function update_chat()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);

		$last_updated = (int)sanitize_text_field($_POST['last_updated']);
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);
		$chats = [];
		$chat_badge = '';
		$chats = $chat_obj->get_session_chats($rel_id,$sender_ses_id,$receiver_ses_id,$last_updated,true);

		wp_send_json( array('chats'=>empty($chats['html']) ? '' : $chats['html'],'total'=>$chats['total'],'last_updated' => time()));
	}

	public function get_session_chats()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$last_updated = (int)sanitize_text_field($_POST['last_updated']);
		$chat_obj = new OOChat();
		$chats = $chat_obj->get_session_chats($rel_id,$sender_ses_id,$receiver_ses_id,$last_updated,true);
		wp_send_json( array('chats'=>$chats['html'],'total'=>$chats['total'],'last_updated' => time()));
	}


	public function send_message()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$chat_message = htmlentities(stripslashes(sanitize_text_field($_POST['message'])));
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);

		if(empty($rel_id) || $rel_id == -1){
				$rel_id = $chat_obj->add_conversation($sender_ses_id,$receiver_ses_id);
		}

		$chat_id = $chat_obj->send_message(array(
			'rel_id'=>$rel_id,
			'sender_ses_id'=>$sender_ses_id,
			'receiver_ses_id'=>$receiver_ses_id,
			'content'=>$chat_message
		));

		$bubble = empty($chat_id) ? __('Sending Failed! Please try again','oometrics') : $chat_obj->render_chat($chat_id,$receiver_ses_id);

		$session_status = $this->session->get_status($receiver_ses_id);
		if($session_status == 2){
			$this->session->set_status($receiver_ses_id,3);
		} else {
			$this->session->set_status($receiver_ses_id,1);
		}

		wp_send_json( array('rel_id'=>$rel_id,'bubble'=>$bubble,'chat_id'=>$chat_id,'last_updated' => time()));
	}

	public function search_product(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$query = sanitize_text_field($_POST['query']);
		$args = array(
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'post_type'        => 'product',
				'post_status'      => 'publish',
				's' => $query
		);

		$products = get_posts( $args );
		$html = '';
		if($products){
			foreach ($products as $key => $product) {
				$pid = $product->ID;
				$product = wc_get_product( $pid ); // The WC_Product object
				if( ! $product->is_on_sale() ){
						$price = $product->get_price(); // Update active price
						$sale_price = $product->get_sale_price();
				} else {
					$price = $product->get_price(); // Update active price
					$sale_price = '';
				}

				if(!empty($sale_price)){
					$price_html = wc_price($price).'-'.wc_price($sale_price);
				} else {
					$price_html = wc_price($price);
				}
				if($product->is_type( 'variable' )){

					$variations = $product->get_available_variations();
					foreach ($variations as $key => $variation) {
						$cart = $woocommerce->cart;
						$vid = $variation['variation_id'];
						$variation_obj = wc_get_product($vid);
						$v_thumb = $variation['image']['url'];
						if( ! $variation_obj->is_on_sale() ){
								$price = $variation_obj->get_price(); // Update active price
								$sale_price = $variation_obj->get_sale_price();
						} else {
							$price = $variation_obj->get_price(); // Update active price
							$sale_price = '';
						}

						if(!empty($sale_price)){
							$price_html = wc_price($price).'-'.wc_price($sale_price);
						} else {
							$price_html = wc_price($price);
						}
						if(!empty($v_thumb)){
							$v_thumb = '<img src="'.$v_thumb.'" />';
						} else{
							$v_thumb = get_the_post_thumbnail($pid,'thumbnail');
						}
						foreach ($variation['attributes'] as $key => $att) {
							$term = ltrim($key,'attribute_');
							$att_term = get_term_by('id',$att,$term);
							$variation_selected = $att_term->name;
						}
						$post_title = $variation_obj->get_title();
						$html .= '<div data-pid="'.$pid.'" data-vid="'.$vid.'" class="oo-search-result-item"> '.$v_thumb.'<h5>'.$post_title.'</h5><br />'.$variation_selected.' '.$price_html.'</div>';
					}
				} else {
					$html .='<div data-pid="'.$pid.'" data-vid="0" data-key="0" data-qty="1" class="oo-search-result-item">';
					$post_title = $product->get_title();
					$p_thumb = get_the_post_thumbnail($pid,'thumbnail');
					$html .= $p_thumb.'<h5>'.$post_title.'</h5><br />'.$price_html.'</div>';
				}
			}
		} else {
			$html = '<div class="oo-search-result-not-found">'.__('No products found','oometrics').'</div>';
		}
		wp_send_json(array('suggestions'=>$html));
	}

	public function send_push(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$push_type = sanitize_text_field($_POST['push_type']);
		$ses_id = sanitize_text_field($_POST['ses_id']);
		$push_duration = sanitize_text_field($_POST['push_duration']);
		$sale_amount = sanitize_text_field($_POST['sale_amount']);
		$sale_percent = sanitize_text_field($_POST['sale_percent']);

		$now = time();

		if($push_duration == 'end'){
			$push_run_time = strtotime('+1 day',$now);
			$push_time_gap = $now + 86400;
		} else if($push_duration == 'fivemin'){
			$push_run_time = strtotime('+5 minutes',$now);
			$push_time_gap = $now + 300;
		} else if($push_duration == 'tenmin'){
			$push_run_time = strtotime('+10 minutes',$now);
			$push_time_gap = $now + 600;
		} else if($push_duration == 'onehour'){
			$push_run_time = strtotime('+1 hour',$now);
			$push_time_gap = $now + 3600;
		}

		$push = new OOPush();
		$push->ses_id = $ses_id;
		$args = $push->get_defaults();

			$args['ses_id'] = $ses_id;
			$args['run_time'] = $push_run_time;
			$args['time_gap'] = $push_time_gap;
			$args['status'] = 0;
			$args['date'] = $now;

		if($push_type == 'sale_price'){
			$pid_str = rtrim(sanitize_text_field($_POST['pid_str']),',');
			$vid_str = rtrim(sanitize_text_field($_POST['vid_str']),',');
			$pids = explode(',',$pid_str);
			$vids = explode(',',$vid_str);
			$args['args'] = serialize(array('sale_amount'=>$sale_amount,'sale_percent'=>$sale_percent));
			if(!empty($pids)){
				$args['type'] = 'sale_price';

				foreach ($pids as $key => $pid) {
					if(!empty($vids[$key])){
						$args['xid'] = $vids[$key];
					} else {
						$args['xid'] = $pid;
					}
					$push->add($ses_id,$pid,$args);
				}
			}
		} else if($push_type == 'apply_coupon'){
			$args['type'] = 'apply_coupon';
			$push_coupons = sanitize_text_field($_POST['push_coupons']);
			$args['args'] = serialize(array('coupon_code'=>$push_coupons));
			$push->add($ses_id,0,$args);
		} else if($push_type == 'open_popup'){
			$args['type'] = 'open_popup';
			$popup_type = sanitize_text_field($_POST['popup_type']);
			if($popup_type == 'templates'){
				$popup_tid = sanitize_text_field($_POST['popup_tid']);
				$helper = new OOHelper();
				$template = $helper->get_template($popup_tid);
				$params = unserialize($template->params);

				$popup_content = wp_kses_post($params['popup_content']);
				$popup_btn_1_label = sanitize_text_field($params['popup_btn_1_label']);
				$popup_btn_1_href = sanitize_text_field($params['popup_btn_1_href']);

				$popup_btn_2_href = sanitize_text_field($params['popup_btn_2_href']);
				$popup_btn_2_label = sanitize_text_field($params['popup_btn_2_label']);

				$args['args'] = serialize(array('popup_type'=>$popup_type,'popup_content'=>$popup_content,'popup_btn_1_label'=>$popup_btn_1_label,'popup_btn_2_label'=>$popup_btn_2_label,'popup_btn_1_href'=>$popup_btn_1_href,'popup_btn_2_href'=>$popup_btn_2_href));
			} else {
				$popup_content = sanitize_text_field($_POST['popup_content']);

				$popup_btn_1_label = sanitize_text_field($_POST['oo_popup_btn_1_label']);
				$popup_btn_1_href = sanitize_text_field($_POST['oo_popup_btn_1_href']);

				$popup_btn_2_href = sanitize_text_field($_POST['oo_popup_btn_2_href']);
				$popup_btn_2_label = sanitize_text_field($_POST['oo_popup_btn_2_label']);

				$args['args'] = serialize(array('popup_type'=>$popup_type,'popup_content'=>$popup_content,'popup_btn_1_label'=>$popup_btn_1_label,'popup_btn_2_label'=>$popup_btn_2_label,'popup_btn_1_href'=>$popup_btn_1_href,'popup_btn_2_href'=>$popup_btn_2_href));
			}

			$push->add($ses_id,0,$args);
			$session_status = $this->session->set_status();
			if(empty($session_status) || $session_status == 0){
				$this->session->set_status($ses_id,2);
			} else if($session_status == 1){
				$this->session->set_status($ses_id,3);
			}
		}

		wp_send_json(array('status'=>1));
	}
	public function change_cart()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		global $woocommerce;

		$ses_id = sanitize_text_field($_POST['ses_id']);
		$pid_str = sanitize_text_field(rtrim($_POST['pid_str'],','));
		$vid_str = sanitize_text_field(rtrim($_POST['vid_str'],','));
		$key_str = sanitize_text_field(rtrim($_POST['key_str'],','));
		$qtys_str = sanitize_text_field(rtrim($_POST['qty_str'],','));

		$session_obj = $this->session->get_meta($ses_id,'cart_session');
		if(!empty($session_obj['session'])){
			$woo_session = $session_obj['session'];
			$session_key = $session_obj['key_hash'];
			$session_content = $session_obj['session'];
			$session_cart = empty($session_content['cart']) ? null : unserialize($session_content['cart']);

			if(!empty($session_cart)){
				foreach ($session_cart as $key => $cart_item) {
					$cart_item_keys[$cart_item['key']] = $cart_item['key'];
				}

				if($pid_str == ',' || empty($pid_str)){
					foreach ($cart_item_keys as $key => $cart_item_key) {
							unset($session_cart[$cart_item_key]);
					}
					$result = $this->session->update_actual_cart($session_key,$data);
					wp_send_json( array('status'=>$result) );
				}
				$pids = explode(',',$pid_str);
				$vids = explode(',',$vid_str);
				$keys = explode(',',$key_str);
				$qtys = explode(',',$qtys_str);


				reset($session_cart);
				$first_key = key($session_cart);
				$clone_item = $session_cart[$first_key];

				foreach ($keys as $key => $item_key) {

					// update the item
					if(in_array($item_key,$cart_item_keys)){
						$session_cart[$item_key]['quantity'] = $qtys[$key];
						unset($cart_item_keys[$item_key]);
					} else if($item_key == 0){
						$random_number = mt_rand(1111111,99999999);
						$now = time();
						$new_cart_key = wp_hash($now.'X'.$random_number);
						$session_cart[$new_cart_key] = $clone_item;

						if($vids[$key]){
							$product = wc_get_product($vids[$key]);
							$atts = $product->get_variation_attributes();
							$session_cart[$new_cart_key]['variation'] = $atts;
						} else {
							$product = wc_get_product($pids[$key]);
						}

						$sale_price = $product->get_sale_price();
						if(empty($sale_price)){
							$price = $product->get_price();
						} else {
							$price = $sale_price;
						}
						$session_cart[$new_cart_key]['key'] = $new_cart_key;
						$session_cart[$new_cart_key]['data_hash'] = wc_get_cart_item_data_hash($product);
						$session_cart[$new_cart_key]['product_id'] = $pids[$key];
						if($vids[$key] > 0){
							$session_cart[$new_cart_key]['variation_id'] = $vids[$key];
						}
						$d_qty = (int)$qtys[$key] > 0 ? $qtys[$key] : 1;
						$price = empty($price) ? 0 : $price;
						$session_cart[$new_cart_key]['line_subtotal'] = $price * $d_qty;
						$session_cart[$new_cart_key]['line_total'] = $price * $d_qty;
						$session_cart[$new_cart_key]['quantity'] = $d_qty;
					}
				}



				// remove existing item
				foreach ($cart_item_keys as $key => $cart_item_key) {
					if(!in_array($cart_item_key,$keys)){
						unset($session_cart[$cart_item_key]);
					}
				}



				$session_content['cart'] = serialize($session_cart);
				$data = serialize($session_content);

				$result = $this->session->update_actual_cart($session_key,$data);
				if(isset($result) && $result > 0){
					$status = 1;
				} else {
					$status = 0;
				}

				wp_send_json( array('status'=>$status,'message' => __('Cart Updated Successfully!','oometrics')) );
			} else {
				wp_send_json( array('status'=>'danger', 'message' => __('Customer doesn\'t have any cart session yet!','oometrics')) );
			}
		}
		wp_send_json( array('status'=>'danger', 'message' => __('Customer doesn\'t have any cart session yet!','oometrics')) );

	}

	public function delete_push(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$push_id = (int)(sanitize_text_field($_POST['push_id']));
		$push = new OOPush();
		$push->cancel($push_id);
		die();
	}

	public function get_templates(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$type = sanitize_text_field($_POST['type']);
		$extra_class = sanitize_text_field($_POST['extra_class']);
		$type = empty($type) ? 'popup' : $type;
		$helper = new OOHelper();
		$templates = $helper->get_templates(array('type'=>$type));
		$html = '<ul class="oo-popup-templates">';
		$class = empty($extra_class) ? '' : ' class="shortcut"';
		if(empty($templates)){
			$html .= '<li><strong>'.__('No template found!','oometrics').'</strong></li>';
			$html .= '<li>'.__('After clicking on any session, you can add your template via left panel / push / promotional / save as template','oometrics').'</li>';
		} else {
			foreach ($templates as $key => $tmpl) {
				$html .= '<li><a'.$class.' href="#" data-tid="'.$tmpl->id.'">'.esc_html($tmpl->title).'</a><span class="oo-delete-popup-template" data-tid="'.$tmpl->id.'">'.__('Delete','oometrics').'</span></li>';
			}
		}

		$html .= '</ul>';
		wp_send_json( array('html'=>$html) );
		die();
	}

	public function save_template(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$args = array();
		$args['type'] = 'popup';
		$args['title'] = sanitize_text_field($_POST['oo_popup_template_title']);

		$popup_content = wp_kses_post($_POST['popup_content']);

		$popup_btn_1_label = sanitize_text_field($_POST['oo_popup_btn_1_label']);
		$popup_btn_1_href = sanitize_text_field($_POST['oo_popup_btn_1_href']);

		$popup_btn_2_href = sanitize_text_field($_POST['oo_popup_btn_2_href']);
		$popup_btn_2_label = sanitize_text_field($_POST['oo_popup_btn_2_label']);

		$args['params'] = array('popup_content'=>$popup_content,'popup_btn_1_label'=>$popup_btn_1_label,'popup_btn_2_label'=>$popup_btn_2_label,'popup_btn_1_href'=>$popup_btn_1_href,'popup_btn_2_href'=>$popup_btn_2_href);
		$args['vars'] = array();
		$helper = new OOHelper();
		$tmpl_id = $helper->save_template($args);
		wp_send_json( array('tid'=>$tmpl_id) );
		die();
	}

	public function delete_template(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$tid = sanitize_text_field($_POST['tid']);

		$helper = new OOHelper();
		$result = $helper->delete_template($tid);
		wp_send_json( array('status'=> ($result ? 1 : 0) ) );
		die();
	}

	public function reset_admin_session(){
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$user_id = sanitize_text_field($_POST['admin_user_id']);
		$this->session->reset_admin_session($user_id);
		die();
	}


	public function set_global_order_by()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$orderby = sanitize_text_field($_REQUEST['orderby']);
		if($orderby == 'live'){
			$orderby = 'last_act';
		} else if($orderby == 'value'){
			$orderby = 'value';
		}else if($orderby == 'intelligence'){
			$orderby = 'last_act';
		}else{
			$orderby = 'last_act';
		}
		$options = get_option($this->option_name);

		$options['live_sort_by'] = $orderby;

		update_option($this->option_name,$options);
		wp_send_json( array('status'=>1));
	}


		public function get_report_session()
		{
			$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));


			$id = (int)(sanitize_text_field($_POST['ses_id']));
			$obj = $this->session;
			$session_data = $obj->get_by('id',$id);
			$session = $obj->get_by('id',$session_data);
			$activities = $obj->get_activities(true,$id);
			$profile_data = $obj->get_profile($id,$session_data->uid);

			$session_content_raw = $session->cart_session;
			$session_obj = unserialize($session_content_raw);
			$session_key = $session_obj['key_hash'];
			$session_content = $session_obj['session'];
			$cart['cart_items'] = 0;
			$cart['cart_totals'] = 0;
			if(!empty($session_content['cart']) && !empty($session_content['cart_totals'])){
				$cart_data = unserialize($session_content['cart']);

				$totals = unserialize($session_content['cart_totals']);
				$cart['cart_items'] = (empty($cart_data)) ? 0 : count($cart_data);
				$cart['cart_total'] = wc_price($totals['total']);
			}

			if($session->uid == 0 ){
				$cart['purchased_items'] = '?';
				$cart['purchased_total'] = '?';
			} else {
				$customer_orders = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $session->uid,
						'post_type'   => wc_get_order_types(),
						'post_status' => array_keys( wc_get_order_statuses() ),
				) );

				$cart['purchased_items'] = count($customer_orders);
				$cart['purchased_total'] = wc_price(wc_get_customer_total_spent( $session->uid ));
			}

			$profile_clean = [];

			if(isset($profile_data->uid)){
				$billing_first_name = get_user_meta( $profile_data->uid, 'billing_first_name', true );
				$profile_clean['billing_first_name'] = isset($billing_first_name) ? $billing_first_name : '';
				$billing_last_name = get_user_meta( $profile_data->uid, 'billing_last_name', true );
				$profile_clean['billing_last_name'] = isset($billing_last_name) ? $billing_last_name : '';
				$phone = get_user_meta( $profile_data->uid, 'billing_phone', true );
				$profile_clean['billing_phone'] = isset($phone) ? $phone : '';
				$billing_company = get_user_meta( $profile_data->uid, 'billing_company', true );
				$profile_clean['billing_company'] = isset($billing_company) ? $billing_company : '';
				$billing_country = get_user_meta( $profile_data->uid, 'billing_country', true );
				$profile_clean['billing_country'] = isset($billing_country) ? $billing_country : '';
				$billing_state = get_user_meta( $profile_data->uid, 'billing_state', true );
				$profile_clean['billing_state'] = isset($billing_state) ? $billing_state : '';
				$billing_city = get_user_meta( $profile_data->uid, 'billing_city', true );
				$profile_clean['billing_city'] = isset($billing_city) ? $billing_city : '';
				$billing_address_1 = get_user_meta( $profile_data->uid, 'billing_address_1', true );
				$profile_clean['billing_address_1'] = isset($billing_address_1) ? $billing_address_1 : '';
				$billing_address_2 = get_user_meta( $profile_data->uid, 'billing_address_2', true );
				$profile_clean['billing_address_2'] = isset($billing_address_2) ? $billing_address_2 : '';
				$billing_postcode = get_user_meta( $profile_data->uid, 'billing_postcode', true );
				$profile_clean['billing_postcode'] = isset($billing_postcode) ? $billing_postcode : '';

				$shipping_first_name = get_user_meta( $profile_data->uid, 'shipping_first_name', true );
				$profile_clean['shipping_first_name'] = isset($shipping_first_name) ? $shipping_first_name : '';
				$shipping_last_name = get_user_meta( $profile_data->uid, 'shipping_last_name', true );
				$profile_clean['shipping_last_name'] = isset($shipping_last_name) ? $shipping_last_name : '';
				$shipping_company = get_user_meta( $profile_data->uid, 'shipping_company', true );
				$profile_clean['shipping_company'] = isset($shipping_company) ? $shipping_company : '';
				$shipping_country = get_user_meta( $profile_data->uid, 'shipping_country', true );
				$profile_clean['shipping_country'] = isset($shipping_country) ? $shipping_country : '';
				$shipping_state = get_user_meta( $profile_data->uid, 'shipping_state', true );
				$profile_clean['shipping_state'] = isset($shipping_state) ? $shipping_state : '';
				$shipping_city = get_user_meta( $profile_data->uid, 'shipping_city', true );
				$profile_clean['shipping_city'] = isset($shipping_city) ? $shipping_city : '';
				$shipping_address_1 = get_user_meta( $profile_data->uid, 'shipping_address_1', true );
				$profile_clean['shipping_address_1'] = isset($shipping_address_1) ? $shipping_address_1 : '';
				$shipping_address_2 = get_user_meta( $profile_data->uid, 'shipping_address_2', true );
				$profile_clean['shipping_address_2'] = isset($shipping_address_2) ? $shipping_address_2 : '';
				$shipping_postcode = get_user_meta( $profile_data->uid, 'shipping_postcode', true );
				$profile_clean['shipping_postcode'] = isset($shipping_postcode) ? $shipping_postcode : '';

			}

			$profile_clean['display_name'] = isset($profile_data->display_name) ? $profile_data->display_name : $profile_data->hash;
			$profile_clean['user_email'] = isset($profile_data->user_email) ? $profile_data->user_email : '';
			$profile_clean['user_id'] = isset($profile_data->uid) ? $profile_data->uid : 0;

			$chat = new OOChat();
			$chat->set_session($this->session);
			$rels = $chat->get_conversations(true,array('id'=>$id));
			$rels = '<h3 class="oo-reports-sidebar-title">'.__('Conversations','oometrics').'</h3><ul class="oo-chat-list">'.$rels.'</ul>';

			wp_send_json( array('session'=>$session_data,'activity'=>$activities,'cart'=>$cart,'info'=>array(),'profile'=>$profile_clean,'overview'=>$overview,'rels'=>$rels) );
		}



			public function get_report(){

				$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

				$period = sanitize_text_field($_POST['period']);
				$start = strtotime(sanitize_text_field($_POST['start_date']));
				$end = strtotime(sanitize_text_field($_POST['end_date']));

				$options = get_option('oometrics_options');
				$options['period_time']['period_type'] = $period;
				if($period == 'custom'){
						$options['period_time']['start_time'] = $start;
						$options['period_time']['end_time'] = $end;
				}
				update_option('oometrics_options',$options);

				$report = new OOReport();
				$total_sales = wc_price($report->get_total_sales());
				$total_sessions = $report->get_total_sessions();
				$total_sessions = (empty($total_sessions)) ? 0 : $total_sessions;

				$total_uniques = $report->get_total_uniques();
				$total_uniques = (empty($total_uniques)) ? 0 : $total_uniques;

				$total_orders = $report->get_total_orders();
				$total_orders = (empty($total_orders)) ? 0 : $total_orders;

				$total_activities = $report->get_total_activities();
				$total_activities = (empty($total_activities)) ? 0 : $total_activities;

				$session_html = '';
				$ses = $this->session;
		    $sessions = $report->get_sessions();
		    foreach ($sessions as $key => $session) {
		      $session_data = $ses->get_by('id',$session->id);
					$session_html .= $ses->render($session);
		    }

				$activity_overview = $report->get_ativities_overview();

				$data = $activity_overview;
				$data['sessions'] = $session_html;

				$data['total_sessions'] = $total_sessions;
				$data['total_sales'] = $total_sales;
				$data['total_uniques'] = $total_uniques;
				$data['total_orders'] = $total_orders;
				$data['total_activities'] = $total_activities;
				wp_send_json($data);

			}
			public function get_report_sessions(){

				$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

				$page = (int)(sanitize_text_field($_POST['page']));
				$number = 20;
				$report = new OOReport();
				$ses = $this->session;

		    $sessions = $report->get_sessions(array('page'=>$page));
		    foreach ($sessions as $key => $session) {
		      $session_data = $ses->get_by('id',$session->id);
					$session_html .= $ses->render($session_data,true);
		    }
				$total_sessions = $report->get_total_sessions();
				// echo $total_sessions;
				if(($number * ($page + 1)) >= $total_sessions ){
					$page = -1;
				} else {
					$page++;
				}
				wp_send_json(array('sessions'=>$session_html,'page'=>$page));
			}

			public function report_get_session_chats()
			{
				$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

				$rel_id = sanitize_text_field($_POST['rel_id']);
				$chat_obj = new OOChat();

				$rel = $chat_obj->get_rel_by_id($rel_id);
				$chats = $chat_obj->get_session_chats($rel_id,$rel->sender_ses_id,$rel->receiver_ses_id,0,true);
				wp_send_json( array('chats'=>$chats['html'],'total'=>$chats['total'],'last_updated' => time()));
			}


}

new OOAdminAjax();
