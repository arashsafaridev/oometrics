<?php
// Activity Class runs on each visit
class OOActivity
{

	private $option_name = 'oometrics_options';
	public $table;
	public $id;
	public $ses;
	public $type;
	public $url;
	public $hits;
	public $xid;
	public $date;
	public $session;


	public function __construct()
  {
		global $wpdb;
		$this->table = $wpdb->prefix.'oometrics_activity';
		global $wp;
		$this->hits = 0;
		$this->type = '';

		$this->url = (!empty($wp->request)) ? $wp->request : $_SERVER['REQUEST_URI'];
		$this->url = sanitize_text_field(trim($this->url,'/').'/');
		$this->xid = url_to_postid(get_home_url().'/'.str_replace("oometrics-demo/","",$this->url)); // main $this->xid = url_to_postid(get_home_url().'/'.$this->url);
		$this->date = time();

	}
	public function set_session($session){
		$this->session = $session;
	}
	public function init()
	{

		if(!$this->xid) { $this->xid = 0; }

		if(!oo_is_filtered()){
				if($this->is_visit_exists()){
					$this->add_visit();
					$this->session->add_value($this->sender_ses_id,1);
				} else {
					$this->update_visit();
					$this->session->add_value($this->sender_ses_id,1);
				}
		}

		$this->compare_cart_contents();

	}
	public function action_woocommerce_add_to_cart( $cart_item_key,  $product_id,  $quantity,  $variation_id,  $variation,  $cart_item_data ) {
		// uses try to avoid any errors on adding to cart
		try {
			$this->type = 'added_to_cart';

			$data['ses'] = $this->sender_ses_id;
			$data['type'] = $this->type;
			$data['url'] = $this->url;
			$data['xid'] = $this->xid;
			$data['hits'] = $this->hits;
			$data['date'] = time();

			$this->add_activity($data);

			$this->session->add_value($this->sender_ses_id,3);
			$cart_data = $this->session->get_cart_session();

			if(!empty($cart_data)){
				$this->session->update_meta($this->sender_ses_id,'cart_session',unserialize($cart_data));
			}
			$this->session->update_last_activity($this->sender_ses_id);

		} catch (\Exception $e) {

		}
	}

	public function action_woocommerce_cart_item_removed( $key, $instance ) {
		// using try to avoid removing from cart
		try {
			$cart_data = $this->session->get_cart_session();
			$cart_data_arr = unserialize($cart_data);
			if(!empty($cart_data_arr['session'])){
				$woo_session = $cart_data_arr['session'];
				$cart_items = unserialize($woo_session['cart']);
				if($cart_items){
					foreach ($cart_items as $array_key => $cart_item) {
						if($array_key == $key){
							unset($cart_items[$key]);
							break;
						}
					}
				}
				$woo_session['cart'] = serialize($cart_items);
				$cart_data_arr['session'] = $woo_session;
				// unset($cart_items[$key]);
				$this->session->update_meta($this->sender_ses_id,'cart_session',$cart_data_arr);
			}
			$this->session->update_last_activity($this->sender_ses_id);

		} catch (\Exception $e) {

		}
	}


	// check the cart is updated; in general woocommerce_add_to_cart is one step behond on saving the session content
	public function compare_cart_contents()
	{
		$cart_data = $this->session->get_cart_session();
		$session_cart = $this->session->get_meta($this->sender_ses_id,'cart_session');
		if(serialize($session_cart) !== $cart_data){
			$this->session->update_meta($this->sender_ses_id,'cart_session',unserialize($cart_data));
		}
	}

	public function add_activity($data = array())
	{
		global $wpdb;
		$wpdb->insert($this->table,$data);
		$this->id = $wpdb->insert_id;
	}

	public function update_activity($data = array())
	{
		global $wpdb;
		$wpdb->update($this->table,$data,array('ses'=>$this->sender_ses_id,'url'=>$this->url));
	}

	public function is_landed()
	{
		global $wpdb;
		$table = $this->table;
		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE ses = %d",
						 $this->sender_ses_id
				)
		);
		return (int)$in_db > 0 ? false : true;
	}

	// add hits
	public function is_visit_exists()
	{
		global $wpdb;
		$table = $this->table;
		if(empty($this->url) && is_home()){
			$this->url = '/';
		}
		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT COUNT(*) FROM $table
						 WHERE ses = %d AND url = %s AND type = %s",
						 array($this->sender_ses_id,$this->url,'visited')
				)
		);
		$result = ($in_db > 0) ? false : true;
		return $result;
	}

	private function add_visit()
	{
		$this->type = 'visited';

		$data['ses'] = $this->sender_ses_id;
		$data['type'] = $this->type;
		$data['url'] = $this->url;
		$data['hits'] = $this->hits;
		$data['xid'] = $this->xid;
		$data['date'] = $this->date;
		$this->add_activity($data);
	}

	private function update_visit()
	{
		$this->type = 'visited';

		global $wpdb;
		$table = $this->table;

		$in_db = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table
					   SET hits = hits + 1
						 WHERE ses = %d AND url = %s",
						 array($this->sender_ses_id,$this->url)
				)
		);
		$result = ($in_db > 0) ? false : true;
	}

	private function add_landed()
	{
		$this->type = 'landed';
		$data['ses'] = $this->sender_ses_id;
		$data['type'] = $this->type;
		$data['url'] = $this->url;
		$data['hits'] = $this->hits;
		$data['xid'] = $this->xid;
		$data['date'] = $this->date;
		$this->add_activity($data);

	}

	public function render($act_id = 0)
	{
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_activity';
		$act = $wpdb->get_row(
				$wpdb->prepare(
						"SELECT * FROM $table
						 WHERE id = %d",
						 $act_id
				)
		);

		$html = '';
		$act_title = ''; // activity tile; it is removed by now because of compelication on getting it on different objects
		$act_img = ''; // activity thumbnail; it is removed by now because of compelication and dashboard performance
		$act_xid = ''; // id if the page, posts, prodycts and ... if exists
		$act_url = (!empty($act->url)) ? '<a class="oo-act-url" target="_blank" href="'.esc_url(get_home_url().'/'.$act->url).'">'.urldecode($act->url).'</a>' : '';
		$act_hits = ($act->hits > 1) ? 'X'.$act->hits : '';
		$act_time = human_time_diff( $act->date, time() ).' '.__('Ago','oometrics');
		if(!empty($act))
		{
			$html .= '<li data-type="'.$act->type.'">
									<div class="oo-act-meta">
									<h5 class="oo-act-type">'.$act->type.' '.$act_hits.' - '.$act_time.'</h5>
										'.$act_img.'
										<div class="oo-act-data">
										'.$act_xid.'
										'.$act_title.'
										'.$act_url.'
										</div>
									</div>
								</li>';
		}

		return $html;

	}

}
