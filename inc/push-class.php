<?php
// push class
class OOPush
{

	private $option_name = 'oometrics_options';
	public $table;
	public $start_time;
	public $end_time;
	public $ses_id;
	public $act_id;
	public $sortby;
	public $session;

	public function __construct()
  {
		global $wpdb;
		$this->table = $wpdb->prefix.'oometrics_push';
	}

	public function set_session($session){
		$this->session = $session;
		$this->ses_id = $session->ses_id;
	}

	public function get_defaults(){
		$now = time();
		$data = [];
		$data['type'] = 'sale_price';
		$data['time_gap'] = 300;
		$data['xid'] = 0;
		$data['run_time'] = $now + 180;
		$data['status'] = 0;
		$data['args'] = '';
		$data['params'] = '';
		$data['alt'] = '';
		$data['date'] = $now;
		return $data;
	}
	public function add($session_id,$xid,$args = array()){
		global $wpdb;
		$data['ses_id'] = $session_id;
		$data['type'] = $args['type'];
		$data['xid'] = $args['xid'];
		$data['run_time'] = $args['run_time'];
		$data['time_gap'] = $args['time_gap'];
		$data['status'] = $args['status'];
		$data['args'] = $args['args'];
		$data['params'] = $args['params'];
		$data['alt'] = $args['alt'];
		$data['date'] = $args['date'];

		$wpdb->insert($this->table,$data);
		return $wpdb->insert_id;
	}

	public function delete($id){
		global $wpdb;
		$result = $wpdb->delete($this->table,array('id'=>$id));
		return $result;
	}

	public function cancel($id){
		$this->change_status($id,1);
	}

	public function get_pushes($id = 0,$session_id = 0,$status = 0){
		global $wpdb;
		$now = time();
		$pushes = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE id = %d AND ses_id = %d AND status = %d",
					 array($id,$session_id,$status)
			)
		);
		return $pushes;
	}

	public function get_session_global_sale_price_push($session_id = 0,$status = 0){
		global $wpdb;
		$push = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE type = %s AND xid = %d AND ses_id = %d AND status = %d",
					 array('sale_price',-1,$session_id,$status)
			)
		);
		return $push;
	}

	public function get_session_sale_price_push($session_id = 0,$xid = 0,$status = 0){
		global $wpdb;
		$push = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE type = %s AND xid = %d AND ses_id = %d AND status = %d",
					 array('sale_price',$xid,$session_id,$status)
			)
		);
		return $push;
	}

	public function get_session_apply_coupon_push($session_id = 0){
		global $wpdb;
		$now = time();
		$push = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE type = %s AND ses_id = %d AND status = %d",
					 array('apply_coupon',$session_id,0)
			)
		);
		return $push;
	}
	public function get_session_open_popup_push($session_id = 0){
		global $wpdb;
		$push = $wpdb->get_row(
			$wpdb->prepare(
					"SELECT * FROM {$this->table}
					 WHERE type = %s AND ses_id = %d AND status = %d",
					 array('open_popup',$session_id,0)
			)
		);
		return $push;
	}

	public function change_status($id,$status = 0){
		global $wpdb;
		$result = $wpdb->get_var(
			$wpdb->prepare(
					"UPDATE {$this->table}
					 SET status = %d
					 WHERE id = %d",
					 array($status,$id)
			)
		);
		return $result;
	}

	public function set_clicked($id){
		global $wpdb;
		$result = $wpdb->get_var(
			$wpdb->prepare(
					"UPDATE {$this->table}
					 SET clicked = %d
					 WHERE id = %d",
					 array(1,$id)
			)
		);
		return $result;
	}

	private function get_sale_push($session_id,$price,$product){

		if(empty($price)){
			return '';
		}
		$now = time();
		$product_id = $product->get_id();
		// check for global sesson sale price first
		$ses_push = $this->get_session_global_sale_price_push($session_id,0);
		if(empty($ses_push)){
			// then checks for specific product
			$ses_push = $this->get_session_sale_price_push($this->ses_id,$product_id,0);
		}
		if(!empty($ses_push)){
			if($ses_push->time_gap < $now ){
				$this->change_status($ses_push->id,1);
				return '';
			}
			$args = unserialize($ses_push->args);
			// print_r($args);
			$sale_amount = $args['sale_amount'];
			$sale_percent = $args['sale_percent'];
			if(!empty($sale_percent)){
				$new_price = ( $sale_percent * $price ) / 100;
				$new_price = $price - $new_price;
			} else {
				$new_price = $price - $sale_amount;
			}
			wc_delete_product_transients($product->get_id());
			return $new_price;
		}	else {
			return '';
		}
	}

	public function cart_calculate_price( $cart ) {

			// Loop Through cart items
			foreach ( $cart->get_cart() as $cart_item ) {
					// Get the product id (or the variation id)
						$product_id = $cart_item['data']->get_id();

						$sales_push = $this->get_sale_push($this->ses_id,$cart_item['data']->get_regular_price(),$cart_item['data']);
						if(!empty($sales_push)){
							$new_price = $sales_push;
							$cart_item['data']->set_price( $new_price );
						}
						// Updated cart item price
				}
	}

	## This goes outside the constructor ##

	// Utility function to change the prices with a multiplier (number)
	public function get_price_multiplier() {
	    return 2; // x2 for testing
	}

public function product_sale_price( $price, $product ) {

			$sales_push = $this->get_sale_push($this->ses_id,$product->get_regular_price(),$product);
			if(empty($sales_push)){
				return $price;
			} else {
				$product->set_sale_price($sales_push);
				return $sales_push;
			}
}

public function sale_price_html( $price_html, $product ) {
	if($product->is_type('variable') ) return $price_html;
	$sales_push = $this->get_sale_push($this->ses_id,$product->get_regular_price(),$product);
	if(empty($sales_push)){
		return $price_html;
	} else {
		$price_html = wc_format_sale_price(
			wc_get_price_to_display( $product,
			array( 'price' => $product->get_price() ) ),
			wc_get_price_to_display(  $product, array( 'price' => $sales_push ) )
			) . $product->get_price_suffix();
	}


  return $price_html;

}

// apply sales badgge for the variable product parent
public function check_for_sales_badge( $on_sale, $product ) {
	if($product->is_type('variable') && !$on_sale){
			$variations = $product->get_available_variations();
			$variations_id = wp_list_pluck( $variations, 'variation_id' );
			if($variations_id){
				foreach ($variations_id as $key => $vid) {
					$ses_push = $this->get_session_global_sale_price_push($this->ses_id,0);
					if(empty($ses_push)){
						$ses_push = $this->get_session_sale_price_push($this->ses_id,$vid,0);
						if($ses_push){
								return true;
						}
					} else {
						return true;
					}
				}
			}
	}

	return $on_sale;
}

	public function custom_variable_price( $price, $variation, $product ) {
	    return $price * $this->get_price_multiplier();
	}

	public function add_price_multiplier_to_variation_prices_hash( $hash ) {
	    $hash[] = $this->get_price_multiplier();
	    return $hash;
	}
	public function add_coupon($cart){
		global $woocommerce;
		$now = time();
		$ses_push = $this->get_session_apply_coupon_push($this->ses_id);
		if(!empty($ses_push)){
			$args = unserialize($ses_push->args);
			$coupon_code = $args['coupon_code'];
			if($ses_push->time_gap < $now ){
				if($woocommerce->cart->has_discount(sanitize_text_field($coupon_code))){
					$woocommerce->cart->remove_coupons(sanitize_text_field($coupon_code));
				}
				$this->change_status($ses_push->id,1);
				return true;
			}
			$args = unserialize($ses_push->args);

			if(!empty($coupon_code)){
				if(!$woocommerce->cart->has_discount(sanitize_text_field($coupon_code))){
					$woocommerce->cart->add_discount( sanitize_text_field( $coupon_code ));
				}
			}
		}

	}

	public function add_popup(){
		$ses_push = $this->get_session_open_popup_push($this->ses_id);
		if(!empty($ses_push)){
			if($ses_push->time_gap < $now ){
				$this->change_status($ses_push->id,1);
				return true;
			}
			$args = unserialize($ses_push->args);

			$popup_type = $args['popup_type'];
			$popup_content = stripslashes($args['popup_content']);
			$popup_btn_1_label = $args['popup_btn_1_label'];
			$popup_btn_2_label = $args['popup_btn_2_label'];
			$popup_btn_1_href = $args['popup_btn_1_href'];
			$popup_btn_2_href = $args['popup_btn_2_href'];

			if($popup_type == 'promotional' || $popup_type == 'templates'){
				$this->render_promotianl_popup($ses_push->id,$popup_content,false,$args);
				$this->change_status($ses_push->id,1);
			} else if($popup_type == 'register'){
				$this->render_register_popup($ses_push->id);
				$this->change_status($ses_push->id,1);
			} else if($popup_type == 'ooarea'){
				$this->render_ooarea_popup($ses_push->id);
				$this->change_status($ses_push->id,1);
			}
			?>
			<script>
			jQuery(document).ready(function($){
				setTimeout(function(){
					$('#oo-popup-wrapper').addClass('show');
				},3000);


			});
			</script>
			<?php
		}

	}

	public function render_promotianl_popup($push_id,$popup_content,$html = false,$args = array()){
		if(!empty($args)){
			$actoin_html = '<div class="oo-popup-action">';
			$popup_btn_1_label = $args['popup_btn_1_label'];
			$popup_btn_1_href = $args['popup_btn_2_href'];
			if(!empty($popup_btn_1_label)){
				$actoin_html .='<a href="'.$popup_btn_1_href.'" class="oo-popup-action-primary btn btn-primary">'.$popup_btn_1_label.'</a>';
			}

			$popup_btn_2_href = $args['popup_btn_2_href'];
			$popup_btn_2_label = $args['popup_btn_2_label'];
			if(!empty($popup_btn_2_label)){
				$actoin_html .='<a href="'.$popup_btn_2_href.'" class="oo-popup-action-secondary btn btn-default">'.$popup_btn_2_label.'</a>';
			}
		}
		$actoin_html .= '</div>';
		$html_content = '
		<div id="oo-popup-wrapper" data-pushid="'.$push_id.'">
			<div class="oo-overlay"></div>
			<div class="oo-inner">
				<div class="oo-popup-body">'.$popup_content.'</div>
				<span class="oo-popup-close"><img src="'.OOMETRICS_URL.'assets/images/close-popup.svg" alt="'.__('close','oometrics').'"/></span>';
			if(!empty($args)){
				$html_content .= $actoin_html;
			}


			$html_content .= '</div>
		</div>';

		if($html){
			return $html_content;
		} else {
			echo $html_content;
		}

	}

	public function render_register_popup($id,$html = false){
		if(!is_user_logged_in()){
		$html_content = '
		<div id="oo-popup-wrapper" data-pushid="'.$id.'">
			<div class="oo-overlay"></div>
			<div class="oo-inner">
				<div class="oo-popup-login active">
					<h3>'.__('Please Login','oometrics').'</h3>
					<div class="oo-form-field">
						<label for="oo-login-username" >'.__('Username','oometrics').'</label>
						<input type="text" id="oo-login-username" placeholder="'.__('or Email','oometrics').'">
					</div>
					<div class="oo-form-field">
						<label for="oo-login-passwrod" >'.__('Password','oometrics').'</label>
						<input type="password" id="oo-login-passwrod" placeholder="'.__('******','oometrics').'">
					</div>
					<button type="button" class="button button-primary" id="oo-popup-login">'.__('Login','oometrics').'</button>
					<a href="#" id="oo-show-register">'.__('or Register','oometrics').'</a>
				</div>
				<div class="oo-popup-register">
					<h3>'.__('Please Register','oometrics').'</h3>
					<div class="oo-form-field">
						<label for="oo-register-username" >'.__('Username','oometrics').'</label>
						<input type="text" id="oo-register-username" placeholder="'.__('or Email','oometrics').'">
					</div>
					<div class="oo-form-field">
						<label for="oo-register-passwrod" >'.__('Password','oometrics').'</label>
						<input type="password" id="oo-register-passwrod" placeholder="'.__('******','oometrics').'">
					</div>
					<button type="button" class="button button-primary" id="oo-popup-login">'.__('Register','oometrics').'</button>
					<a href="#" id="oo-show-login">'.__('or Login','oometrics').'</a>
				</div>
				<span class="oo-popup-close"><i class="icon icon-popup-close large"></i></span>
			</div>

		</div>';
		if($html){
			return $html_content;
		} else {
			echo $html_content;
		}
		}
	}

	public function render_ooarea_popup($id,$html = false){

		$html_content = '<div id="oo-popup-wrapper" data-pushid="'.$id.'">
			<div class="oo-overlay"></div>
			<div class="oo-inner">';
				ob_start();
				dynamic_sidebar( 'ooarea-1' );
				$html_content .= ob_get_contents();
				ob_end_clean();
				$html_content .='<span class="oo-popup-close"><i class="icon icon-popup-close large"></i></span>
			</div>

		</div>';
		if($html){
			return $html_content;
		} else {
			echo $html_content;
		}

	}
}

// new OOPush();
