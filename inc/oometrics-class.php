<?php
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/*
 * Main class
 */
/**
 * Class OOMetrics
 *
 * This class creates the option page and add the web app script
 */
class OOMetrics
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
	public $session;

	/**
	 * OOMetrics constructor.
	 * The main plugin actions registered for WordPress
	 */

	public function __construct()
    {
			// Admin page calls
			add_action('admin_menu',                array($this,'add_admin_menu'));
			add_action('wp_ajax_oo_store_admin_data',  array($this,'store_admin_data'));
			add_action('admin_enqueue_scripts',     array($this,'add_admin_scripts'));
			add_action('wp_enqueue_scripts',     array($this,'add_scripts'));

			add_action( 'widgets_init', array($this,'ooarea_sidebar') );
	}



	/**
	 * Returns the saved options data as an array
     *
     * @return array
	 */

	private function get_data()
  {
    return get_option($this->option_name, array());
  }
  public function init()
	{

			if ( class_exists( 'WooCommerce' ) ) {

				$settings = get_option($this->option_name);

				// create or get session
				// checks for cookie if user already has a session (lifetime: 7 days )
				$session = new OOSession();
				$domain = str_replace(".","_",$_SERVER['SERVER_NAME']);

				if(isset($_COOKIE[$domain.'_oometrics_session']) || $settings['main_user'] == get_current_user_id()){
					$current_session_id = $_COOKIE[$domain.'_oometrics_session'];
					$session_data = $session->get_by('id',$current_session_id);
					$session->id = $current_session_id;
				} else {
					$ajax = new OOAjax();
					$ajax->set_session($session);
					add_action( 'wp_ajax_oo_create_session', array( $ajax, 'create_session' ) );
					add_action( 'wp_ajax_nopriv_oo_create_session', array( $ajax, 'create_session' ) );
					add_action( 'wp_ajax_oo_session_check', array( $ajax, 'session_check' ) );
					add_action( 'wp_ajax_nopriv_oo_session_check', array( $ajax, 'session_check' ) );

					add_action('wp_footer',     array($this,'oo_add_footer_chat_button'));
					return false;
				}

				$this->session = $session;
				$session->update_all($settings['session_lifetime'] ? $settings['session_lifetime'] : 86400);

				// Checks tracking consent permission
				if(!isset($_COOKIE[$domain.'_oo_tracking_consent']) || $_COOKIE[$domain.'_oo_tracking_consent'] == 'agreed'){
					$activity = new OOActivity();
					$activity->set_session($session);
					$activity->sender_ses_id = $current_session_id;
					add_action( 'woocommerce_add_to_cart', array($activity,'action_woocommerce_add_to_cart'), 10, 6 );
					add_action( 'woocommerce_cart_item_removed', array($activity,'action_woocommerce_cart_item_removed'), 10, 2 );
					add_action( 'wp_login', array($session,'set_user_id'), 10, 2 );
					if(!current_user_can('manage_options')){
						add_action( 'wp_loaded', array($activity,'init'),99);
					}
				}

				$ajax = new OOAjax();
				$ajax->set_session($session);

				add_action( 'wp_ajax_oo_session_check', array( $ajax, 'session_check' ) );
				add_action( 'wp_ajax_nopriv_oo_session_check', array( $ajax, 'session_check' ) );

				add_action( 'wp_ajax_oo_get_chat_rel', array( $ajax, 'get_chat_rel_id' ) );
				add_action( 'wp_ajax_nopriv_oo_get_chat_rel', array( $ajax, 'get_chat_rel_id' ) );

				add_action( 'wp_ajax_oo_get_popup', array( $ajax, 'get_popup' ) );
				add_action( 'wp_ajax_nopriv_oo_get_popup', array( $ajax, 'get_popup' ) );

				add_action( 'wp_ajax_oo_update_session', array( $ajax, 'update_session' ) );
				add_action( 'wp_ajax_nopriv_oo_update_session', array( $ajax, 'update_session' ) );

				add_action( 'wp_ajax_oo_send_message', array( $ajax, 'send_message' ) );
				add_action( 'wp_ajax_nopriv_oo_send_message', array( $ajax, 'send_message' ) );

				add_action( 'wp_ajax_oo_get_session_chats', array( $ajax, 'get_session_chats' ) );
				add_action( 'wp_ajax_nopriv_oo_get_session_chats', array( $ajax, 'get_session_chats' ) );

				add_action( 'wp_ajax_oo_get_conversations', array( $ajax, 'get_conversations' ) );
				add_action( 'wp_ajax_nopriv_oo_get_conversations', array( $ajax, 'get_conversations' ) );

				add_action( 'wp_ajax_oo_mark_as_seen', array( $ajax, 'mark_as_seen' ) );
				add_action( 'wp_ajax_nopriv_oo_mark_as_seen', array( $ajax, 'mark_as_seen' ) );

				add_action( 'wp_ajax_oo_update_chat_status', array( $ajax, 'update_chat_status' ) );
				add_action( 'wp_ajax_nopriv_oo_update_chat_status', array( $ajax, 'update_chat_status' ) );

				add_action( 'wp_ajax_oo_update_chat', array( $ajax, 'update_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_update_chat', array( $ajax, 'update_chat' ) );

				add_action( 'wp_ajax_oo_delete_chat', array( $ajax, 'delete_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_delete_chat', array( $ajax, 'delete_chat' ) );

				add_action( 'wp_ajax_oo_edit_chat', array( $ajax, 'edit_chat' ) );
				add_action( 'wp_ajax_nopriv_oo_edit_chat', array( $ajax, 'edit_chat' ) );

				add_action( 'wp_ajax_oo_chat_add_attachment', array( $ajax, 'chat_add_attachment' ) );
				add_action( 'wp_ajax_nopriv_oo_chat_add_attachment', array( $ajax, 'chat_add_attachment' ) );


				if(is_admin()){

					$admin_ajax = new OOAdminAjax();
					$admin_ajax->set_session($session);

					// FRONT-END and BACK-END

					add_action( 'wp_ajax_oo_admin_create_session', array( $admin_ajax, 'admin_create_session' ) );
					add_action( 'wp_ajax_oo_admin_send_message', array( $admin_ajax, 'send_message' ) );

					add_action( 'wp_ajax_oo_get_admin_session', array( $admin_ajax, 'get_admin_session' ) );
					add_action( 'wp_ajax_oo_set_global_order_by', array( $admin_ajax, 'set_global_order_by' ) );

					add_action( 'wp_ajax_oo_get_live_sessions', array( $admin_ajax, 'get_live_sessions' ) );
					// add_action( 'wp_ajax_oo_get_session', array( $admin_ajax, 'get_session' ) );

					add_action( 'wp_ajax_oo_admin_update_chat', array( $admin_ajax, 'update_chat' ) );
					add_action( 'wp_ajax_oo_admin_get_session_chats', array( $admin_ajax, 'get_session_chats' ) );
					add_action( 'wp_ajax_oo_report_get_session_chats', array( $admin_ajax, 'report_get_session_chats' ) );

					// PUSH
					add_action( 'wp_ajax_oo_product_search', array( $admin_ajax, 'search_product' ) );
					add_action( 'wp_ajax_oo_send_push', array( $admin_ajax, 'send_push' ) );
					add_action( 'wp_ajax_oo_delete_push', array( $admin_ajax, 'delete_push' ) );
					add_action( 'wp_ajax_oo_change_cart', array( $admin_ajax, 'change_cart' ) );

					// REPORTS
					add_action( 'wp_ajax_oo_get_report_session', array( $admin_ajax, 'get_report_session' ) );
					add_action( 'wp_ajax_oo_get_report_sessions', array( $admin_ajax, 'get_report_sessions' ) );
					add_action( 'wp_ajax_oo_get_report', array( $admin_ajax, 'get_report' ) );

					add_action( 'wp_ajax_oo_get_templates', array( $admin_ajax, 'get_templates' ) );
					add_action( 'wp_ajax_oo_save_template', array( $admin_ajax, 'save_template' ) );
					add_action( 'wp_ajax_oo_delete_template', array( $admin_ajax, 'delete_template' ) );
					add_action( 'wp_ajax_oo_reset_admin_session', array( $admin_ajax, 'reset_admin_session' ) );
				}

					$push = new OOPush();
					$push->set_session($session);
					$push->ses_id = $current_session_id;

					add_filter('woocommerce_product_get_sale_price', array($push,'product_sale_price'), 99, 2 );
					// Variable
					add_filter('woocommerce_product_variation_get_sale_price', array($push,'product_sale_price'), 99, 2 );

					add_filter( 'woocommerce_get_price_html', array($push,'sale_price_html'), 99, 2 );


					add_filter( 'woocommerce_product_is_on_sale', array($push,'check_for_sales_badge'), 99, 2 );

					add_filter( 'woocommerce_before_calculate_totals', array($push,'cart_calculate_price'), 99, 2 );

					add_filter( 'woocommerce_before_calculate_totals', array($push,'add_coupon'), 99, 2 );
					add_filter( 'wp_footer', array($push,'add_popup'), 99, 2 );
					add_action( 'wp_ajax_nopriv_oo_push_clicked', array( $ajax, 'push_clicked' ) );

					add_action('wp_footer',     array($this,'oo_add_footer_chat_button'));


					if($settings['tracking_notification'] == 'yes' && !isset($_COOKIE[$domain.'_oo_tracking_consent'])){
						add_action('wp_footer',     array($this,'oo_add_consent_notification'));
					}
			}

  }

	// registers a new sidebar
	public function ooarea_sidebar() {
	    register_sidebar( array(
	        'name' => __( 'OOArea Sidebar', 'oometrics' ),
	        'id' => 'ooarea-1',
	        'description' => __( 'Widgets in this area will be shown as pushed popup content', 'oometrics' ),
	        'before_widget' => '<div id="%1$s" class="%2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h2 class="oo-widge-ttitle">',
					'after_title'   => '</h2>',
				    ) );
	}

	// saving admin settings data
	public function store_admin_data()
    {
		if (wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), $this->_nonce ) === false)
			{
				die('sssInvalid Request! Reload your page please.');
			}

		global $wpdb;
		$data = $this->get_data();

		foreach ($_POST as $field=>$value) {
		    if (substr($field, 0, 10) !== "oometrics_")
				continue;

		    if (empty($value))
		        unset($data[$field]);

		    // We remove the oometrics_ prefix to clean things up
		    $field = substr($field, 10);

			$data[$field] = esc_attr__(sanitize_text_field($value));

		}

		$session_table = $wpdb->prefix.'oometrics_session';
		$wpdb->get_var(
			$wpdb->prepare("UPDATE $session_table
			SET uid = %d
			WHERE id = %d",array($data['main_user'],1))
		);

		update_option($this->option_name, $data);

		echo esc_html(__('Settings saved successfully!', 'oometrics'));
		die();

	}

	/**
	 * Adds Admin Scripts for the Ajax call
	 */
	public function add_admin_scripts()
    {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker');
			$screen = get_current_screen();
			if(strpos($screen->id,'oometrics-reports') || $screen->id == 'toplevel_page_oometrics'){
			  wp_enqueue_style('oometrics-admin', OOMETRICS_URL. 'assets/css/admin.css', false, OOMETRICS_PLUGIN_VERSION);
			}

			if($screen->id == 'toplevel_page_oometrics'){
				wp_enqueue_script('oometrics-admin', OOMETRICS_URL. 'assets/js/admin.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);
				// wp_enqueue_script('oometrics-chats', OOMETRICS_URL. 'assets/js/admin-chats.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);
			}

			if(strpos($screen->id,'oometrics-reports')){
				wp_enqueue_script('oometrics-reports', OOMETRICS_URL. 'assets/js/admin-reports.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);
			}
			if(strpos($screen->id,'oometrics-settings')){
				wp_enqueue_style('oometrics-settings', OOMETRICS_URL. 'assets/css/admin-settings.css', false, OOMETRICS_PLUGIN_VERSION);
				wp_enqueue_script('oometrics-settings', OOMETRICS_URL. 'assets/js/admin-settings.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);
			}


			$settings = get_option('oometrics_options');
			$admin_interval = empty($settings['admin_interval']) ? 20000 : $settings['admin_interval'];
			$session_interval = empty($settings['session_interval']) ? 20000 : $settings['session_interval'];
			$chat_interval = empty($settings['chat_interval']) ? 5000 : $settings['chat_interval'];
			$session_lifetime = empty($settings['session_lifetime']) ? 86400 : $settings['session_lifetime'];
			$admin_options = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'interval' => $admin_interval,
				'session_interval' => $session_interval,
				'chat_interval' => $chat_interval,
				'session_lifetime' => $session_lifetime,
				'delay' => 1000,
				'chat_icon_open' => empty($settings['chat_icon_open']) ? OOMETRICS_URL. 'assets/images/start-chat.svg' : $settings['chat_icon_open'],
				'chat_icon_close' => empty($settings['chat_icon_close']) ? OOMETRICS_URL. 'assets/images/stop-chat.svg' : $settings['chat_icon_close'],
				'_nonce'   => wp_create_nonce( $this->_nonce ),
				'deafult_avatar' => OOMETRICS_URL.'/assets/images/anon-avatar.svg',
				'labels' => array(
					'back' => __('Back','oometrics'),
					'upoading' => __('Uploading','oometrics'),
					'notAvailableYet' => __('Not Availabel Yet','oometrics'),
					'startChatOrPushToSession' => __('Start chat or Push to session','oometrics'),
				)
			);

			wp_localize_script('oometrics-admin', 'oometrics', $admin_options);
			wp_localize_script('oometrics-reports', 'oometrics', $admin_options);
			wp_localize_script('oometrics-settings', 'oometrics', $admin_options);

	}

	public function add_scripts()
    {
			wp_enqueue_style('oometrics-style', OOMETRICS_URL. 'assets/css/oometrics.css', false, OOMETRICS_PLUGIN_VERSION);
			wp_enqueue_script('oometrics-script', OOMETRICS_URL. 'assets/js/oometrics.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);
			wp_enqueue_script('oometrics-chats', OOMETRICS_URL. 'assets/js/chats.js', array('jquery'), OOMETRICS_PLUGIN_VERSION);

			$settings = get_option('oometrics_options');
			$session_interval = empty($settings['session_interval']) ? 20000 : $settings['session_interval'];
			$chat_interval = empty($settings['chat_interval']) ? 5000 : $settings['chat_interval'];
			$session_lifetime = empty($settings['session_lifetime']) ? 86400 : $settings['session_lifetime'];
			$chat_enabled = empty($settings['chat_enabled']) ? 86400 : $settings['chat_enabled'];

			$options = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'homeurl' => get_home_url(),
				'session_interval' => $session_interval,
				'chat_interval' => $chat_interval,
				'session_lifetime' => $session_lifetime,
				'delay' => 1000,
				'chat_enabled' => $chat_enabled,
				'chat_icon_open' => empty($settings['chat_icon_open']) ? OOMETRICS_URL. 'assets/images/start-chat.svg' : $settings['chat_icon_open'],
				'chat_icon_close' => empty($settings['chat_icon_close']) ? OOMETRICS_URL. 'assets/images/stop-chat.svg' : $settings['chat_icon_close'],
				'_nonce'   => wp_create_nonce( $this->_nonce ),
				'labels'   => array(
					'back' => __('Back','oometrics')
				),
			);

			wp_localize_script('oometrics-script', 'oometrics', $options);

	}

	/**
	 * Adds the OOMetrics label to the WordPress Admin Sidebar Menu
	 */
	public function add_admin_menu()
    {
		add_menu_page(
			__( 'OOMetrics', 'oometrics' ),
			__( 'OOMetrics', 'oometrics' ),
			'manage_options',
			'oometrics',
			array($this, 'dashboard_layout'),
			OOMETRICS_URL.'/assets/images/oometrics-dashicon.svg?v='.OOMETRICS_PLUGIN_VERSION,
			2
		);
    add_submenu_page(
      'oometrics',
			__( 'Reports', 'oometrics' ),
			__( 'Reports', 'oometrics' ),
			'manage_options',
			'oometrics-reports',
			array($this, 'reports_layout')
		);
		add_submenu_page(
      'oometrics',
			__( 'Settings', 'oometrics' ),
			__( 'Settings', 'oometrics' ),
			'manage_options',
			'oometrics-settings',
			array($this, 'settings_layout')
		);
	}

	/**
     * Get a Dashicon for a given status
     *
	 * @param $valid boolean
     *
     * @return string
	 */
    private function get_status_icon($valid)
    {

        return ($valid) ? '<span class="dashicons dashicons-yes success-message"></span>' : '<span class="dashicons dashicons-no-alt error-message"></span>';

    }

	/**
	 * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
	 */
   public function dashboard_layout()
     {
       require_once(OOMETRICS_PATH.'/templates/dashboard/dashboard.php');
     }
	public function settings_layout()
    {

		$data = $this->get_data();


      require_once(OOMETRICS_PATH.'/templates/settings.php');
	}

	public function reports_layout()
    {

		$data = $this->get_data();

      require_once(OOMETRICS_PATH.'/templates/reports/dashboard.php');
	}
	public function debug_layout()
    {

      require_once(OOMETRICS_PATH.'/templates/debug.php');
	}



	public function oo_add_footer_chat_button() {
		$settings = get_option('oometrics_options');
		if($this->session){
			$session = $this->session;
			$id = $session->id;
			$chat = new OOChat();
			$chat->set_session($session);
			$chats = $chat->get_conversations(true,array('id'=>$session->id));
		} else {
			$chats = '';
		}

		$user = get_user_by('id',$settings['main_user']);
		$profile = '<div class="oo-profile-info">
			'.get_avatar($user->ID,100).'
			<ul class="oo-profile-data">
				<li class="name"><strong>'.$user->display_name.'</strong></li>
			</ul>
		</div>';

		if($settings['chat_enabled'] == 'yes'){
			if($settings['chat_icon_open']){
				$icon_img = $settings['chat_icon_open'];
			} else {
				$icon_img = OOMETRICS_URL.'/assets/images/start-chat.svg';
			}
				$position_class = $settings['chat_position'];
				$position_style_h = $settings['chat_position_h'];
				$position_style_v = $settings['chat_position_v'];
				$position_style_h = empty($position_style_h) ? '2rem' : $position_style_h;
				$position_style_v = empty($position_style_v) ? '2rem' : $position_style_v;
				if($position_style_h && $position_style_v){
					if($position_class == 'bottom-left'){
						$position_style = ' style="left: '.$position_style_h.';top: calc( 100% - ( 50px + '.$position_style_v.' ) );bottom: '.$position_style_v.';"';
					} else if($position_class == 'bottom-right'){
						$position_style = ' style="right: '.$position_style_h.';top: calc( 100% - ( 50px + '.$position_style_v.' ) );bottom: '.$position_style_v.';"';
					} else if($position_class == 'top-right'){
						$position_style = ' style="right: '.$position_style_h.';bottom: calc( 100% - ( 50px + '.$position_style_v.' ) );top: '.$position_style_v.';"';
					} else if($position_class == 'top-left'){
						$position_style = ' style="left: '.$position_style_h.';bottom: calc( 100% - ( 50px + '.$position_style_v.' ) );top: '.$position_style_v.';"';
					}
				}
				$welcome_message = empty($settings['chat_welcome_message']) ? __('Tell us how can we help you and give you better shopping experience','oometrics') : $settings['chat_welcome_message'];

				if(current_user_can('manage_options')){
					$chats = __('You are main user. Chat is only available through WordPress Dashboard for this user. To test use incognito or private browsing or another device! You can not chat with yourself :)!','oometrics');
				}
		    echo '
				<input type="file" class="oo-chat-upload-input" id="oo-chat-upload"/>
				<button class="'.$position_class.'"'.$position_style.' id="oo-chat-trigger" title="'.__('Ask Something').'"><i class="oo-icon start-chat"><img src="'.$icon_img.'" /></i><span class="oo-badge"></span></button>
				<div id="oometrics-chat" class="'.$position_class.'">
					<div class="oo-chat-wrapper oo-box">
						<header>'.$profile.'</header>
							<div class="oo-chat-conversations">
							  <ul class="oo-chat-list">';
								if(!current_user_can('manage_options')){
									echo '<li class="oo-chat-start">
												<div class="oo-start-inner">
													'.$welcome_message.'
												</div>
											</li>';
								}
									echo $chats.'
							  </ul>
							</div>';
							if(!current_user_can('manage_options')){
								echo '<footer id="chat-footer">
								<textarea id="oo-message-text"></textarea>
								<button id="oo-send-message">'.__('Send','oometrics').'</button>
								<button id="oo-attach-message" title="'.__('Attach','oometrics').'"><i class="oo-icon icon-attach"></i></button>
								</footer>';
							}

						echo '</div>
				</div>
				';
		}
	}

	public function oo_add_consent_notification() {
		$settings = get_option('oometrics_options');
		?>
		<div id="oo-popup-wrapper" class="consent">
			<div class="oo-overlay"></div>
			<div class="oo-inner">
				<div class="oo-inner-content">
					<?php echo $settings['tracking_message'];?>
				</div>
				<br />
				<button type="button" class="button button-primary" id="oo-i-agree"><?php _e('Yes','oometrics');?></button>
				<a id="oo-i-disagree" href="#"><?php _e('No','oometrics');?></a>

			</div>
		</div>
		<script>
		jQuery(document).ready(function($){
			var current_domain = location.hostname.replace(".","_");
			console.log(oo_get_cookie(current_domain+'_oo_tracking_consent','agreed'));
			jQuery('#oo-popup-wrapper').addClass('show');
			$(document).delegate('#oo-i-agree','click',function(){
				oo_set_cookie(current_domain+'_oo_tracking_consent','agreed',365);
		    $('#oo-popup-wrapper').removeClass('show');
		  });
			$(document).delegate('#oo-i-disagree','click',function(){
				oo_set_cookie(current_domain+'_oo_tracking_consent','disagreed',7);
		    $('#oo-popup-wrapper').removeClass('show');
		  });
		});
		</script>
		<?php
	}
}
