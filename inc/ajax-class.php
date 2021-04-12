<?php
// front-end and common back-end ajax functions
class OOAjax
{

	private $_nonce = 'oometrics_nonce';
	private $option_name = 'oometrics_options';

	public $session;
	public function __construct(){

	}

	private function verify_nonce($nonce){
		if(wp_verify_nonce($nonce, $this->_nonce ) === false)
		{
			die('Invalid Request! Reload your page please.');
		}
	}

	public function set_session($session){
		$this->session = $session;
	}
	public function create_session()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$session_defaults = $this->session->get_defaults();
		$session_defaults['value'] = 1;
		$session_id = $this->session->add($session_defaults);

		$session_meta = [];
		$session_meta['device'] = wp_is_mobile() ? __('Mobile','oometrics') : __('Desktop','oometrics');
		$request_info = OOHelper::get_request_info();
		$session_meta['browser'] = $request_info['browser'];
		$session_meta['referer'] = $request_info['referer'];
		$this->session->update_meta($session_id,'digital_info',$session_meta);
		$this->session->update_meta($session_id,'ip',$request_info['ip'],false);

		wp_send_json(
			array(
				'sender_ses_id' => $session_id,
				'receiver_ses_id' => 1,
				'rel_id' => -1,
			)
		);
	}

	public function session_check()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$rel_id = sanitize_text_field($_POST['rel_id']);

		$session_status = $this->session->get_status($sender_ses_id);
		$this->session->update_last_activity($sender_ses_id);
		if($rel_id > 0){
			$new_chat_count = $this->session->new_chat_count($receiver_ses_id,$rel_id);
		}
		$chat_badge = '';
		if($new_chat_count > 0){
			$chat_badge = '<span class="oo-new-chat-badge">'.$new_chat_count.'</span>';
		}
		wp_send_json(array( 'status' => $session_status,'chat_badge' => $chat_badge));
	}

	public function get_chat_rel_id()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$rel_id = sanitize_text_field($_POST['rel_id']);

		if($rel_id == -1){
			$chat_obj = new OOChat();
			$crel = $chat_obj->get_active_rel_by_ses_id($sender_ses_id,$receiver_ses_id);
			$rel_id = $crel->id;
		}

		$chat_count = $this->session->chat_count($sender_ses_id,$rel_id);
		$new_chat_count = $this->session->new_chat_count($sender_ses_id,$rel_id);

		$session_status = $this->session->get_status($sender_ses_id);
		if($session_status == 1){
			$this->session->set_status($sender_ses_id,0);
		} else if($session_status == 3){
			$this->session->set_status($sender_ses_id,2);
		}
		wp_send_json(array( 'rel_id' => $rel_id));
	}

	public function get_popup()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$now = time();
		$push = new OOPush();
		$ses_push = $push->get_session_open_popup_push($sender_ses_id);
		if(!empty($ses_push)){
			if($ses_push->time_gap < $now ){
				$this->change_status($ses_push->id,1);
			} else {
				$args = unserialize($ses_push->args);
				$popup_type = $args['popup_type'];
				$popup_content = $args['popup_content'];
				if($popup_type == 'promotional' || $popup_type == 'templates'){
					$popup =  $push->render_promotianl_popup($ses_push->id,$popup_content,true,$args);
					$push->change_status($ses_push->id,1);
				} else if($popup_type == 'register'){
					$popup = $push->render_register_popup($ses_push->id,true);
					$push->change_status($ses_push->id,1);
				} else if($popup_type == 'ooarea'){
					$popup = $push->render_ooarea_popup($ses_push->id,true);
					$push->change_status($ses_push->id,1);
				}
			}

		}
		$popup = empty($popup) ? 'none' : $popup;

		$session_status = $this->session->get_status($sender_ses_id);
		if($session_status == 2){
			$this->session->set_status($sender_ses_id,0);
		} else if($session_status == 3){
			$this->session->set_status($sender_ses_id,1);
		}

		wp_send_json(array( 'popup' => $popup));
	}


	public function send_message()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = (int)(sanitize_text_field($_POST['rel_id']));
		$sender_ses_id = (int)$_POST['sender_ses_id'];
		$receiver_ses_id = (int)$_POST['receiver_ses_id'];
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
		wp_send_json( array('status'=>$status,'status_label'=>$status_label,'rel_id'=>$rel_id,'bubble'=>$bubble,'chat_id'=>$chat_id,'last_updated' => time()));
	}
	public function get_session_chats()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$last_updated = (int)($_POST['last_updated']);
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);

		$chats = $chat_obj->get_session_chats($rel_id,$sender_ses_id,$receiver_ses_id,$last_updated,true);

		wp_send_json( array('chats'=>$chats['html'],'total'=>$chats['total'],'last_updated' => time()));
	}

	public function update_chat()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$rel_id = sanitize_text_field($_POST['rel_id']);

		$last_updated = (int)sanitize_text_field($_POST['last_updated']);
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);
		$chats = [];
		$chat_badge = '';

		if($rel_id == -1){
			$rels = $chat->get_conversations(true,array('id'=>$receiver_ses_id,'admin' => false));
			$chats['html'] = $rels;
			$chats['total'] = '';
		} else {
			$chats = $chat_obj->get_session_chats($rel_id,$sender_ses_id,$receiver_ses_id,$last_updated,true);
		}

		wp_send_json( array('chats'=>empty($chats['html']) ? '' : $chats['html'],'total'=>$chats['total'],'last_updated' => time()));
	}

	public function get_conversations()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$sender_ses_id = $_POST['sender_ses_id'];
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);
		$rels = $chat_obj->get_conversations(true,array('id'=>$sender_ses_id,'admin' => false));
		wp_send_json( array('rels'=>$rels));
	}

	public function mark_as_seen()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$chat_ids_str = sanitize_text_field($_POST['chat_ids']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		if($chat_ids_str){
			$chat_ids = explode(",",$chat_ids_str);
			$chat_obj = new OOChat();
			$output = [];
			foreach ($chat_ids as $key => $chat_id) {
				$chat_status = $chat_obj->mark_as_seen($chat_id);
				$chat_status = $chat_obj->get_status($chat_id);

				$status_html = $chat_obj->get_status_label($chat_status,'html');
				$status_class = $chat_obj->get_status_label($chat_status,'class');

				$output[] = array('id'=>$chat_id,'status_class'=>$status_class,'status_html'=>$status_html);
			}
			wp_send_json($output);
		}
	}

	public function update_chat_status()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$chat_ids_str = sanitize_text_field($_POST['chat_ids']);
		if($chat_ids_str){
			$chat_ids = explode(",",$chat_ids_str);
			$chat_obj = new OOChat();
			$output = [];
			foreach ($chat_ids as $key => $chat_id) {
				$chat_status = $chat_obj->get_status($chat_id);
				$status_html = $chat_obj->get_status_label($chat_status,'html');
				$status_class = $chat_obj->get_status_label($chat_status,'class');
				$output[] = array('id'=>$chat_id,'status_class'=>$status_class,'status_html'=>$status_html);
			}
			wp_send_json($output);
		}

		die('');
	}

	public function delete_chat()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$chat_id = (int)(sanitize_text_field($_POST['chat_id']));
		$chat_obj = new OOChat();
		$chat_obj->set_session($this->session);
		$chat = $chat_obj->delete($chat_id);
		$status = (empty($chat)) ? 0 : 1;
		wp_send_json( array('status'=>$status));
	}
	public function edit_chat()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$chat_id = sanitize_text_field($_POST['chat_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$message = htmlentities(stripslashes(sanitize_text_field($_POST['message'])));
		$chat_obj = new OOChat();
		$chat_status = $chat_obj->edit_chat($chat_id,$message);
		$chat = $chat_obj->get($chat_id);
		$bubble = $chat_obj->render_chat($chat,$sender_ses_id);
		$status = empty($chat_status) ? 0 : 1;
		wp_send_json( array('status'=>$status,'bubble'=>$bubble));
	}

	public function chat_add_attachment()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$rel_id = sanitize_text_field($_POST['rel_id']);
		$sender_ses_id = sanitize_text_field($_POST['sender_ses_id']);
		$receiver_ses_id = sanitize_text_field($_POST['receiver_ses_id']);
		$admin = sanitize_text_field($_POST['admin']);

		$receiver_ses = $this->session->get_by('id',$receiver_ses_id);
		if ($_FILES) {


			// EACH ULOADED FILE
			foreach ($_FILES as $file => $array) {

				// IF CONTAINS ERROR DIE
				if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
					ajax_response('danger',__("<strong>Error!</strong> upload failed.",'rotail'));
				}


				$mimes = get_allowed_mime_types();

				if(!in_array($_FILES[$file]['type'],$mimes)){
					wp_send_json( array('status'=>'not_allowed','chat_id'=>0,'html'=>'<li class="oo-two sent tmp-bubble"><div class="oo-chat-bubble"><div class="oo-chat-content">'.sprintf(__('File type %s is not allowed!','oometrics'),$_FILES[$file]['type']).'</div><div class="oo-chat-meta"><span class="oo-chat-status sent" title="Sent"></span><em>1 second</em></div></div></li>'));
				}
				$attach_id = media_handle_upload( $file,0,array('test_form' => true));

					// IF FILE COULDNT BE UPLOAD
					if(empty($attach_id)){
						wp_send_json( array('status'=>0,'chat_id'=>$chat_id));
					} else {
						$chat_obj = new OOChat();
						$chat_data = [];
						$chat_data['sender_id'] = get_current_user_id();
						$chat_data['receiver_id'] = $receiver_ses->uid;
						$chat_data['ses_id'] = $sender_ses_id;
						$chat_data['content'] = '';
						$chat_data['content_before'] = '';
						$chat_data['attachment'] = $attach_id;
						$chat_data['edited'] = 0;
						$chat_data['rel_id'] = $rel_id;
						$chat_data['status'] = 1;
						$chat_data['date'] = time();
						$chat_id = $chat_obj->add_chat($rel_id,$chat_data);
						$chat = $chat_obj->get($chat_id);
						$html = $chat_obj->render_chat($chat,$sender_ses_id);
						if($admin == 1){
							$session_status = $this->session->get_status($receiver_ses_id);
							if($session_status == 2){
								$this->session->set_status($receiver_ses_id,3);
							} else {
								$this->session->set_status($receiver_ses_id,1);
							}
						}
						wp_send_json( array('status'=>1,'chat_id'=>$chat_id,'html'=>$html));

					}
				}
			}


		wp_send_json( array('status'=>$status,'bubble'=>$bubble));
	}

	public function set_global_order_by()
	{
		$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));

		$orderby = sanitize_text_field($_REQUEST['orderby']);
		if($orderby == 'live'){
			$orderby = 'last_act';
		} else if($orderby == 'value'){
			$orderby = 'ses_value';
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


			public function push_clicked(){
				$this->verify_nonce(sanitize_text_field($_POST['_wpnonce']));
				$push_id = (int)(sanitize_text_field($_POST['push_id']));
				$push = new OOPush();
				$push->set_clicked($push_id);
				die();
			}

}

new OOAjax();
