<?php
// caht class
class OOChat
{
	private $option_name = 'oometrics_options';
	public $table;
	public $session;
	public $id;
	public $sender_id;
	public $receiver_id;
	public $receiver_ses_id;
	public $sender_ses_id;
	public $ses_id;
	public $rel_id;
	public $content;
	public $content_before;
	public $attachment;
	public $status;
	public $edited;
	public $date;


	public function __construct()
  {

		$settings = get_option($this->option_name);
		if(get_current_user_id() != $settings['main_user']){
			$this->receiver_id = $settings['main_user'];
			$this->receiver_ses_id = 1;
		}
		$this->status = 0;
		$this->attachment = '0:{}';
	}

	public function set_session($session){
		$this->session = $session;
	}

	public function init(){}

	public function get($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';

		$chat = $wpdb->get_row(
		    $wpdb->prepare(
		        "SELECT * FROM $table
		         WHERE id = %d",
		         $chat_id
		    )
		);

		return $chat;
	}

	public function get_rel_by_id($crel_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat_rel';

		$crel = $wpdb->get_row(
		    $wpdb->prepare(
		        "SELECT * FROM $table
		         WHERE id = %d",
		         $crel_id
		    )
		);

		return $crel;
	}
	public function get_active_rel_by_ses_id($sender_ses_id,$receiver_ses_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat_rel';
		$stable = $wpdb->prefix.'oometrics_session';

		$crel = $wpdb->get_row(
		    $wpdb->prepare(
		        "SELECT rels.* FROM $table as rels
						 INNER JOIN $stable as sessions ON rels.receiver_ses_id = sessions.id OR rels.sender_ses_id = sessions.id
		         WHERE ((rels.sender_ses_id = %d AND rels.receiver_ses_id = %d) OR (rels.sender_ses_id = %d AND rels.receiver_ses_id = %d)) AND sessions.expired = 0",
		         array($sender_ses_id,$receiver_ses_id,$receiver_ses_id,$sender_ses_id)
		    )
		);

		return $crel;
	}
	public function add_conversation($sender_ses_id,$receiver_ses_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat_rel';

		$data['sender_ses_id'] = $sender_ses_id;
		$data['receiver_ses_id'] =  $receiver_ses_id;
		$data['date'] = time();
		$result = $wpdb->insert($table,$data);
		if($result){
			// add value if the user (not admin) started the conversation
			if($sender_ses_id != 1) $this->session->add_value($sender_ses_id,2);
			return $wpdb->insert_id;
		} else{
			return false;
		}

  }
	public function add_chat($rel_id,$args)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';


		$data['sender_id'] = $args['sender_id'];
		$data['receiver_id'] = $args['receiver_id'];
		$data['ses_id'] = $args['ses_id'];
		$data['content'] = $args['content'];
		$data['content_before'] = '';
		$data['attachment'] = $args['attachment'];
		$data['edited'] = 0;
		$data['rel_id'] = $rel_id;
		$data['status'] = 1;
		$data['date'] = time();
		$result = $wpdb->insert($table,$data);
		if($result){
			return $wpdb->insert_id;
		} else{
			return false;
		}

  }
	public function send_message($args)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';

		$rel_id = $args['rel_id'];
		$sender_ses_id = $args['sender_ses_id'];
		$receiver_ses_id = $args['receiver_ses_id'];
		$this->rel_id = $rel_id;

		$seneder_session_data = $this->session->get_by('id',$sender_ses_id);
		$receiver_session_data = $this->session->get_by('id',$receiver_ses_id);
		$data['ses_id'] = $sender_ses_id;
		$data['receiver_id'] = $receiver_session_data->uid;
		$data['sender_id'] = $seneder_session_data->uid;
		$data['content'] = $args['content'];
		$data['attachment'] = $this->attachment;

		$new_chat_id = $this->add_chat($this->rel_id,$data);
		return array('rel_id'=>$this->rel_id,'chat_id'=>$new_chat_id);
	}
	public function remove_chat($args)
  {

  }
	public function update_chat($args)
  {

  }
	public function get_conversations($html = false,$args = array())
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat_rel';
		$stable = $wpdb->prefix.'oometrics_session';

		$ses_id = $args['id'];
		$session_data = $this->session->get_by('id',$ses_id);
		if($session_data->uid == 0){
				$rels = $wpdb->get_results(
						$wpdb->prepare(
								"SELECT id FROM $table WHERE sender_ses_id = %d OR receiver_ses_id = %d",
								 array($ses_id,$ses_id)
						)
				);
			} else {
					$rels = $wpdb->get_results(
						$wpdb->prepare(
								"SELECT rels.id as id FROM $stable as sessions
								INNER JOIN $table as rels ON sessions.id = rels.sender_ses_id OR sessions.id = rels.receiver_ses_id
								WHERE sessions.uid = %d
								GROUP BY rels.id
								ORDER BY rels.date DESC",
								 array($session_data->uid)
						)
					);
		}

		if(!$html){
			return $rels;
		} else{
			$html = '';
			if(!empty($rels)){
				foreach ($rels as $key => $rel) {
					$html .= $this->render_rels($rel->id,$ses_id,true,$args['admin']);
				}
			}
			return $html;
		}
	}
	public function render_rels($rel_id,$ses_id,$html = false,$admin = false)
	{
		$crel = $this->get_rel_by_id($rel_id);
		if($ses_id == $crel->sender_ses_id){
			$receiver_ses_id = $crel->receiver_ses_id;
		} else {
			$receiver_ses_id = $crel->sender_ses_id;
		}

		$session_data = $this->session->get_by('id',$admin ? $ses_id : $receiver_ses_id);

		if( (int)$session_data->uid > 0)
		{
			$user = get_user_by('id',$session_data->uid);
			$ses_name = $user->display_name;
			if(empty($ses_name))
			{
				$ses_name = $user->user_login;
			}
			$ses_name = '<small>'.__('Chat with:','oometrics').'</small> '.$ses_name;


			$ses_avatar = get_avatar($session_data->uid,40);
		} else {
			$ses_name = __('Conversation','oometrics');
			$ses_avatar = '<i class="icon icon-anon-avatar large"></i>';
		}

		$time = human_time_diff( $crel->date, time() ).' '.__('Ago','oometrics');
		$new_chat_count = $this->session->new_chat_count($admin ? $ses_id : $receiver_ses_id,$rel_id); //$admin ? $sender_ses_id : $receiver_ses_id
		$new_class = $new_chat_count > 0 ? ' new' : '';
		$html = '
		<li data-relid="'.$rel_id.'" data-ses_id="'.$receiver_ses_id.'" class="oo-session-profile'.$new_class.'">
      '.$ses_avatar.'
      <div class="oo-session-info">
        <strong>'.$ses_name.'</strong>';

				if($new_chat_count > 0){
					$html .= '<span class="oo-rel-badge"><span class="oo-new-chat-badge">'.$new_chat_count.'</span></span>';
				} else {
					$html .= '<span class="oo-rel-badge"></span>';
				}
		$html .= '
        <em>'.$time.'</em>
      </div>
    </li>
		';
		return $html;
	}
	public function get_current_chats($html = false)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';

		if(!empty($this->sender_id)){
			$sessions = $wpdb->get_results(
			    $wpdb->prepare(
			        "SELECT chat_ses_id FROM $table
			         WHERE chat_sender_id = '%d' OR chat_receiver_id = '%d'
							 GROUP BY chat_ses_id",
			         array($this->sender_id,$this->sender_id)
			    )
			);
		} else {
			$sessions = $wpdb->get_results(
			    $wpdb->prepare(
			        "SELECT chat_ses_id FROM $table
			         WHERE chat_ses_id = '%d'",
			         array($this->ses_id)
			    )
			);
		}
		if(!$html){
			return $sessions;
		} else{
			$html = '';
			foreach ($sessions as $key => $session) {
				$session_data = $this->session->get_by('ses_id',$session->ses_id);
				$html .= $this->session->render($session_data->ses_id,false);
			}
			return $html;
		}

  }
	public function render_chat($chat,$sender_ses_id = 0)
  {
		$class = ($chat->ses_id == $sender_ses_id) ? "two" : "one";
		$edited = ($chat->edited == 1) ? '<span class="edited">'.__('Edited','oometrics').'</span>' : "";
		$status = $this->get_status_label($chat->status,'html');
		$status_class = $this->get_status_label($chat->status,'class');
		$chat_date = human_time_diff( $chat->date, time() );

		$attach_html = '';
		if((int)($chat->attachment) > 0){
				$html = '
				<li data-chatid="'.$chat->id.'" class="oo-'.$class.' '.$status_class.'">
					<div class="oo-chat-bubble attach">
						<div class="oo-chat-content attach">
							'.$this->render_attachments($chat->attachment).'
						</div>
						<div class="oo-chat-meta">
						'.$status.'
						'.$edited.'
						<em>'.$chat_date.'</em>
						</div>';
						if($class == 'two' || current_user_can('manage_options')){
							$html .='
							<div class="oo-chat-action">
								<span class="oo-icon icon-edit edit" data-chatid="'.$chat->id.'"></span>
								<span class="oo-icon icon-delete delete" data-chatid="'.$chat->id.'"></span>
							</div>';
						}
						$html.='</div>';
						$html .= '</li>';
		} else {
			$html = '
			<li data-chatid="'.$chat->id.'" class="oo-'.$class.' '.$status_class.'">
				<div class="oo-chat-bubble">
					<div class="oo-chat-content">
						'.make_clickable(esc_html($chat->content)).'
					</div>
					<div class="oo-chat-meta">
					'.$status.'
					'.$edited.'
					<em>'.$chat_date.'</em>
					</div>';
					if($class == 'two' || current_user_can('manage_options')){
						$html .='
						<div class="oo-chat-action">
							<span class="oo-icon icon-edit edit" data-chatid="'.$chat->id.'"></span>
							<span class="oo-icon icon-delete delete" data-chatid="'.$chat->id.'"></span>
						</div>';
					}
					$html.='</div>';
					$html .= '</li>';
		}



		return $html;
  }
	public function get_status_label($c_status,$type = 'html')
  {
		if($type == 'html'){
			if($c_status == 0){
				return '<span class="oo-chat-status icon-unknown unknown" title="'.__('Unknow','oometrics').'"></span>';
			} else if($c_status == 1){
				return '<span class="oo-chat-status icon-sent sent" title="'.__('Sent','oometrics').'"></span>';
			} else if($c_status == 2){
				return '<span class="oo-chat-status icon-seen delivered" title="'.__('Delivered','oometrics').'"></span>';
			} else if($c_status == 3){
				return '<span class="oo-chat-status icon-seen seen" title="'.__('Seen','oometrics').'"></span>';
			}
		} else if($type == 'label') {
			if($c_status == 0){
				return __('Unknown','oometrics');
			} else if($c_status == 1){
				return __('Sent','oometrics');
			} else if($c_status == 2){
				return __('Delivered','oometrics');
			} else if($c_status == 3){
				return __('Seen','oometrics');
			}
		} else if($type == 'class') {
			if($c_status == 0){
				return 'unknown';
			} else if($c_status == 1){
				return 'sent';
			} else if($c_status == 2){
				return 'delivered';
			} else if($c_status == 3){
				return 'seen';
			}
		}
	}
	public function get_session_chats($rel_id,$sender_ses_id, $receiver_ses_id,$last_updated = 0,$html = false,$where = '')
  {

		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat_rel';
		$ctable = $wpdb->prefix.'oometrics_chat';

		$chats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $ctable WHERE rel_id = %d AND date >= %d",
				array($rel_id,$last_updated))
		);

		$delivered = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $ctable SET status = 2 WHERE status < 3 AND (ses_id != %d OR ses_id != %d) AND rel_id = %d",
						 array($sender_ses_id,$receiver_ses_id,$rel_id)
				)
		);
		if(!$html){
			return $chats;
		} else {
			foreach ($chats as $key => $chat) {
				$html_code .= $this->render_chat($chat,$sender_ses_id);
			}
			return array('html'=>$html_code,'total'=>count($chats));
		}

  }
	public function mark_as_seen($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$seen = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET status = 3 WHERE id = %d",
						 array($chat_id)
				)
		);

		if($seen){
			return true;
		} else {
			return false;
		}


  }

	public function get_status($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$status = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT status FROM $table WHERE id = %d",
						 array($chat_id)
				)
		);

		if($status){
			return $status;
		} else {
			return false;
		}


  }

	public function delete($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$seen = $wpdb->delete($table,array('id'=>$chat_id));
		if($seen > 0){
			return true;
		} else {
			return false;
		}
  }
	public function edit_chat($chat_id,$message)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$delivered = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET content = %s, edited = 1, date = %d WHERE id = %d",
						 array($message,time(),$chat_id)
				)
		);

		if(empty($delivered)){
			return true;
		} else {
			return false;
		}
  }
	public function get_attachments($chat_id)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$chat = $wpdb->get_var(
				$wpdb->prepare(
						"SELECT attachment FROM $table WHERE id = %d",
						 array($chat_id)
				)
		);

		if(!empty($chat)){
			return unserialize($chat);
		} else {
			return false;
		}
  }
	public function update_attachments($chat_id,$chat_attachments)
  {
		global $wpdb;
		$table = $wpdb->prefix.'oometrics_chat';
		$chat = $wpdb->get_var(
				$wpdb->prepare(
						"UPDATE $table SET attachment = %s WHERE id = %d",
						 array(serialize($chat_attachments),$chat_id)
				)
		);

		if($chat > 0){
			return true;
		} else {
			return false;
		}
  }
	public function render_attachments($attach_id)
  {

		$attach_url = wp_get_attachment_image_src($attach_id,'full');
		if(empty($attach_url)){
			$attach_url = wp_get_attachment_url($attach_id,'full');
		} else {
			$attach_url = $attach_url[0];
		}

		$medium_url = wp_get_attachment_image_src($attach_id,'medium');
		if(empty($medium_url)){
			$medium_url = wp_get_attachment_url($attach_id,'medium');
		} else {
			$medium_url = $medium_url[0];
		}

		$format = explode('.', $attach_url);
		$format = end($format);
		if(
				preg_match('/jpg|JPG|jpeg|JPEG|png|PNG|SVG|svg|gif|GIF/i', $format)
		){
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><img src="'.$medium_url.'" /></a>';
		}else if(
				preg_match('/pdf|PDF/i', $format)
		){
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><i class="oo-icon icon-pdf oo-pdf imged"></i></a>';
		} else {
			$html = '<a target="_blank" class="oo-chat-attach-dl" href="'.$attach_url.'" title="'.__("Download",'oometrics').'"><i class="oo-icon icon-download oo-download"></i></a>';
		}

		return $html;
  }

}
