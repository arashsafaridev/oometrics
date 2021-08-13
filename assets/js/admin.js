// session and relation after click
// is like active but with a chat opened and listening
var current_ses_id = -1; //
var current_rel_id = -1; //

// is the selected session but not neccessarily active on chat
var active_rel_id = -1; //

var admin_ses_id = 1; //
var last_updated = 0; //
// session update interval
var interval = 0; //

// chat and conversation states
// var chat_active = false;
var conversations_opened = false;
var editor_status = 0;
var update_current_conversation = false;
var status_listen_interval = 0;

// ajax xhr object to abort and resend to avoid stacking
var session_xhr;
var chat_xhr;
var chat_status_xhr;
var live_sessions_x;

var chat_s_height = 100;
var chat_height = 0;

// cart state
var oometrics_cart_active = false;
var oometrics_cart_content;
var conversation_end_loading = false;

// to avoid mixing on different websites
var oo_domain = location.hostname.replace(".","_");

// cookie functions
function oo_set_cookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function oo_get_cookie(cname) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
// cookie functions - end

// to make delay on key events to send ajax request
var oo_delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

// detects if the user is on active tab
var oo_active_tab = (function(){
    var stateKey, eventKey, keys = {
        hidden: "visibilitychange",
        webkitHidden: "webkitvisibilitychange",
        mozHidden: "mozvisibilitychange",
        msHidden: "msvisibilitychange"
    };
    for (stateKey in keys) {
        if (stateKey in document) {
            eventKey = keys[stateKey];
            break;
        }
    }
    return function(c) {
        if (c) document.addEventListener(eventKey, c);
        return !document[stateKey];
    }
})();

// the first function to create the session if cookie doesn't exist
function oo_create_session(){
  jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_admin_create_session',
      _wpnonce: oometrics._nonce
    },
    success:function(data){
      if(data.admin_ses_id){
        admin_ses_id = data.admin_ses_id;
        oo_set_cookie(oo_domain+'_oometrics_admin_session',data.admin_ses_id,oometrics.session_lifetime);
        oometrics_init();
      }
    }
  });
}

// check for any messages base on last updated timestamp
function oo_chat_update()
{
  if(current_rel_id != -1){
    chat_xhr = jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_admin_update_chat',
        rel_id : current_rel_id,
        sender_ses_id : admin_ses_id,
        receiver_ses_id : current_ses_id,
        last_updated : last_updated,
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
        if(chat_xhr != null) {
            chat_xhr.abort();
        }
      },
      success:function(data){
        if(current_rel_id != -1){
          if(jQuery(data.chats).length >= 1){
            if(jQuery(data.chats).hasClass('oo-one')){
              if(jQuery('#go-to-new').length < 1){
                jQuery('.oo-chat-conversations').after('<button id="go-to-new"></button>');
              }
            }
          }
          last_updated = data.last_updated;
          jQuery('.tmp-bubble').remove();
          chat_s_height = jQuery('.oo-chat-list').height();
          chat_height = jQuery('.oo-chat-conversations').height();

        var current_count = jQuery('.oo-chat-list li').length;
        var new_count = data.total;
        jQuery('.oo-chat-list').append(data.chats);


        if(chat_s_height <= chat_height){
          mark_as_seen();
        }
      }
    }
    });
  }
}

// main interval function to get live sessions and check for changes: specially chat
// @from will bypass updating conversation on each interval
function oo_get_sessions(from = 'live')
{
  live_sessions_x = jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_get_live_sessions',
      sender_ses_id: admin_ses_id,
      receiver_ses_id: current_ses_id,
      rel_id:active_rel_id,
      _wpnonce: oometrics._nonce
    },
    beforeSend:function(){
      jQuery('.oo-session-list').addClass('oo-loading');
    },
    success:function(data)
    {
      jQuery('.oo-session-list').removeClass('oo-loading');
      jQuery('.oo-session-list').html(data.content);
      jQuery('.oo-dashboard-sidebar-body li[data-sesid="'+current_ses_id+'"]').addClass('active');
      var overview = data.overview;
      jQuery('.oo-total-sales strong').html(overview.total_sales);
      jQuery('.oo-total-online strong').html(overview.online_users);
      jQuery('.oo-total-users strong').html(overview.unique_users);
      jQuery('.oo-total-views strong').html(overview.pageviews);

      // set just active
      active_rel_id = data.rel_id;

      if(data.new_chat == 1){
        if(current_rel_id != -1){
          oo_chat_update();
          oo_chat_status_listen();
        }
      }
      if(current_ses_id != -1){
        update_session_ui(data);
      }
      if(from == 'click'){
        jQuery('.oo-dashboard-reply').addClass('block');
        jQuery('.oo-chat').removeClass('old-conversation');
        conversations_opened = true;
      }
    }
  });
}


// updates the entire dashboard ui
function update_session_ui(data)
{

  var session_data = data.session;
  var info_html = data.info;
  var activity_html = data.activity;
  var cart_html = data.cart !== null ? data.cart.cart_items_html : '';
  // var overview = data.overview;
  var profile = data.profile;
  if(data.rels == 'empty'){
    var chat_html = data.chats.html;
  } else {
    var chat_html = data.rels;
  }


  if(conversations_opened){
      if(current_rel_id == -1 && current_ses_id == -1){
        jQuery('.oo-dashboard-reply').removeClass('block');
        jQuery('.oo-dashboard-reply').addClass('hide');
      } else if(current_rel_id != -1){
        jQuery('.oo-dashboard-reply').removeClass('hide block');
      }
  }

  jQuery('.session-value strong').html(session_data.value);
  jQuery('.device-type strong').html(session_data.ses_device);
  jQuery('.device-browser strong').html(session_data.ses_browser);
  jQuery('.connection-ip strong').html(session_data.ses_ip);
  jQuery('.connection-referrer strong').html(session_data.ses_referrer);
  jQuery('#customer-profile .billing_first_name strong').html(profile.billing_first_name ? profile.billing_first_name : '?');
  jQuery('#customer-profile .billing_last_name strong').html(profile.billing_last_name ? profile.billing_last_name : '?');
  jQuery('#customer-profile .billing_phone strong').html(profile.billing_phone ? profile.billing_phone : '?');
  jQuery('#customer-profile .billing_email strong').html(profile.billing_email ? profile.billing_email : '?');
  jQuery('#customer-profile .billing_company strong').html(profile.billing_company ? profile.billing_company : '?');
  jQuery('#customer-profile .billing_country strong').html(profile.billing_country ? profile.billing_country : '?');
  jQuery('#customer-profile .billing_state strong').html(profile.billing_state ? profile.billing_state : '?');
  jQuery('#customer-profile .billing_city strong').html(profile.billing_city ? profile.billing_city : '?');
  jQuery('#customer-profile .billing_address_1 strong').html(profile.billing_address_1 ? profile.billing_address_1 : '?');
  jQuery('#customer-profile .billing_address_2 strong').html(profile.billing_address_2 ? profile.billing_address_2 : '?');
  jQuery('#customer-profile .billing_postcode strong').html(profile.billing_postcode ? profile.billing_postcode : '?');

  jQuery('#customer-profile .shipping_first_name strong').html(profile.shipping_first_name ? profile.shipping_first_name : '?');
  jQuery('#customer-profile .shipping_last_name strong').html(profile.shipping_last_name ? profile.shipping_last_name : '?');
  jQuery('#customer-profile .shipping_company strong').html(profile.shipping_company ? profile.shipping_company : '?');
  jQuery('#customer-profile .shipping_country strong').html(profile.shipping_country ? profile.shipping_country : '?');
  jQuery('#customer-profile .shipping_state strong').html(profile.shipping_state ? profile.shipping_state : '?');
  jQuery('#customer-profile .shipping_city strong').html(profile.shipping_city ? profile.shipping_city : '?');
  jQuery('#customer-profile .shipping_address_1 strong').html(profile.shipping_address_1 ? profile.shipping_address_1 : '?');
  jQuery('#customer-profile .shipping_address_2 strong').html(profile.shipping_address_2 ? profile.shipping_address_2 : '?');
  jQuery('#customer-profile .shipping_postcode strong').html(profile.shipping_postcode ? profile.shipping_postcode : '?');
  // //
  jQuery('.oo-profile-data .name').html(profile.display_name);
  jQuery('.oo-profile-info .session-avatar').attr('src',profile.avatar);
  jQuery('.oo-profile-data .state').html(profile.shipping_state);
  jQuery('.oo-profile-data .city').html(profile.shipping_city);
  jQuery('.oo-profile-action .oo-call').attr('href','tel:'+profile.billing_phone);
  jQuery('.oo-profile-data .email').html(profile.user_email ? profile.user_email : oometrics.labels.notAvailableYet);

  // cart
  jQuery('.location .state').html(profile.shipping_state);
  jQuery('.location .city').html(profile.shipping_city);
  jQuery('.oo-cart-items').html(data.cart.cart_items);
  jQuery('.oo-cart-total').html(data.cart.cart_total);

  var current_items_count = oometrics_cart_content;
  var current_items_count_keys = '';
  var customer_items_count = (jQuery.parseHTML(cart_html));
  var customer_items_count_keys = '';
  jQuery(current_items_count).each(function(i,v){
    current_items_count_keys += jQuery(this).attr('data-key') +':'+jQuery(this).attr('data-qty');
  });

  jQuery(customer_items_count).each(function(i,v){
    customer_items_count_keys += jQuery(this).attr('data-key')+':'+jQuery(this).attr('data-qty');
  });

  if(!oometrics_cart_active || current_items_count_keys != customer_items_count_keys){
    jQuery('.oo-cart-overlay .oo-current-cart-items').html('');
    jQuery('.oo-cart-overlay .oo-search-selected').html('');
    jQuery('.oo-cart-overlay .oo-current-cart-items').html(cart_html);
    jQuery('.oo-cart-changed-badge').html('Changed!').show();
    oometrics_cart_content = jQuery.parseHTML(cart_html);
  } else {
    jQuery('.oo-cart-changed-badge').html('').hide();
  }
  jQuery('.oo-purchased-items').html(data.cart.purchased_items);
  jQuery('.oo-purchased-total').html(data.cart.purchased_total);

  if(!conversations_opened && current_ses_id != -1){
    jQuery('.oo-chat-list').html(chat_html);
  }
  if(oo_get_cookie('oo_tracking_consent') == 'disagreed'){
    activity_html = 'Said NO to tracking';
  }
  jQuery('#customer-activities .oo-info-details').html(activity_html);
}

// oometrics starts with this function if session is defined
function oometrics_init(){
  // run interval
  if (!interval)
  {
    interval = setInterval(function(){
          oo_get_sessions('live');
    }, oometrics.interval);
  }
}


// mark messaages as read state number 3
function mark_as_seen(){
  var chat_ids = '';
  jQuery('.oo-chat-list li.oo-one:not(.seen):not(.oo-loading):not(.oo-session-profile):not(.tmp-bubble)').each(function(i,v){
    var elm = jQuery(this);
      var chat_id = elm.attr('data-chatid');
      chat_ids += chat_id+',';
  });
  chat_ids = chat_ids.trimRight(',');
  if(chat_ids != ''){
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_mark_as_seen',
        chat_ids : chat_ids,
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
      },
      success:function(data){
        conversation_end_loading = false;
        if(data != ''){
          jQuery(data).each(function(i,v){
            var tmp_elm = jQuery('.oo-chat-list li[data-chatid="'+v.id+'"]');
            tmp_elm.find('.oo-chat-status').replaceWith(v.status_html);
            if(!tmp_elm.hasClass('tmp-bubble') && !tmp_elm.hasClass('oo-start-inner')){
              if(tmp_elm.hasClass('oo-one')){
                tmp_elm.attr('class','oo-one '+v.status_class);
              } else {
                tmp_elm.attr('class','oo-two '+v.status_class);
              }
            }
          });
        }

      }
    });
  } else {
    conversation_end_loading = false;
  }

}


// check for tab change
oo_active_tab(function(){
  if(oo_active_tab())
  {
    if(!interval)
    {
      oo_get_sessions();
      interval = setInterval(function(){
            oo_get_sessions();
      }, oometrics.session_interval);
    }
  }
  else
  {
    clearInterval(interval);
    interval = 0;
  }
});

// check for window change
jQuery(window).focus(function(){
  if (!interval)
  {
      oo_get_sessions();
    interval = setInterval(function(){
          oo_get_sessions();
    }, oometrics.session_interval);
  }
});

jQuery(window).blur(function(){
  clearInterval(interval);
  interval = 0;
});


function oo_chat_status_listen(){
    status_listen_interval = setInterval(function(){
      var chat_ids = '';
      jQuery('.oo-chat-list li.oo-two:not(.seen):not(.oo-loading):not(.oo-session-profile):not(.tmp-bubble)').each(function(i,v){
        var elm = jQuery(this);
          var chat_id = elm.attr('data-chatid');
          chat_ids += chat_id+',';
      });
      chat_ids = chat_ids.trimRight(",");
      if(chat_ids != ''){
        chat_status_xhr = jQuery.ajax({
          url: oometrics.ajaxurl,
          type:'post',
          data:{
            action:'oo_update_chat_status',
            chat_ids : chat_ids,
            _wpnonce: oometrics._nonce
          },
          beforeSend:function(){
            if(chat_xhr != null) {
                chat_xhr.abort();
            }
          },
          success:function(data){
            if(data != ''){
              jQuery(data).each(function(i,v){
                var tmp_elm = jQuery('.oo-chat-list li[data-chatid="'+v.id+'"]');
                tmp_elm.find('.oo-chat-status').replaceWith(v.status_html);
                if(!tmp_elm.hasClass('tmp-bubble') && !tmp_elm.hasClass('oo-start-inner')){
                  if(tmp_elm.hasClass('oo-one')){
                    tmp_elm.attr('class','oo-one '+v.status_class);
                  } else {
                    tmp_elm.attr('class','oo-two '+v.status_class);
                  }
                }
              });
            }
            if(chat_ids == ''){
              clearInterval(status_listen_interval);
              status_listen_interval = 0;
            }

          }
        });
      } else {
        clearInterval(status_listen_interval);
        status_listen_interval = 0;
      }
    },oometrics.chat_interval);
}


jQuery(document).ready( function ($) {

  oometrics_init();
  oo_get_sessions();

  if(jQuery('.oo-chat-conversations').length > 0){
    chat_s_height = jQuery('.oo-chat-conversations').get(0).clientHeight;
    chat_height = jQuery('.oo-chat-conversations').get(0).scrollHeight;
  }

  $('.oo-modal-overlay').click(function(e){
      if(e.target === e.currentTarget){
        e.preventDefault();
        $('.oo-modal-overlay').toggleClass('show');
      };
    });

    $('.oo-close-modal').click(function(e){
      e.preventDefault();
      $('.oo-modal-overlay').toggleClass('show');
    });

  $(document).on('click',function(e){
    if(!$(e.target).hasClass('oo-search-field')){
      $('.oo-search-results').removeClass('show loading');
    }
  })
  $(document).delegate('.oo-product-search','keyup',function(e){
		var t = $(this);
			var query = $(this).val();
			if(query.length >= 3){
				oo_delay(function(){
					$.ajax({
						url: oometrics.ajaxurl,
						type:'post',
						data:{
							'action': 'oo_product_search',
							'query': query,
							'_wpnonce': oometrics._nonce
						},
						beforeSend:function(){
							t.parents('.oo-search-field').find('.oo-search-results').html('').addClass('loading show');
						},
						success:function(data){
							if(data.suggestions != ''){
								t.parents('.oo-search-field').find('.oo-search-results').addClass('show');
								t.parents('.oo-search-field').find('.oo-search-results').removeClass('loading');
								t.parents('.oo-search-field').find('.oo-search-results').html(data.suggestions);
							}

						}
					});
				},500);
			}
		});

    $(document).delegate('.oo-search-result-item:not(.selected)','click',function(e){
  		var t = $(this);
  			var id = t.attr('data-pid');
  			var vid = t.attr('data-vid');
  			var kid = t.attr('data-kid');
  			var qty = t.attr('data-qty');
        if(typeof kid === 'undefined') kid = 0;
        $('#oo-product-id').val(id);
        var html = t.html();
        html = '<div data-pid="'+id+'" data-vid="'+vid+'" data-key="'+kid+'" data-qty="'+qty+'" class="oo-search-result-item selected"><span class="oo-remove-selected">x</span><input type="number" class="oo-quantity" value="1"/>'+html+'</div>';
        t.parents('.oo-search-field').find('.oo-search-selected').append(html);
        t.parents('.oo-search-field').find('.oo-search-results').removeClass('show')

  		});
    $(document).delegate('.oo-remove-selected','click',function(e){
  		  var t = $(this);
  			var p = t.parent();
        $('#oo-product-id').val(0);
        p.remove();

  		});
    $(document).delegate('.oo-quantity','change',function(e){
  		  var t = $(this);
  			var p = t.parent();
        var val = t.val();
        p.attr('data-qty',val);
  		});

      $(document).delegate('#oo_change_cart','click',function(e){
  		  var t = $(this);

        var pid_str = '';
        var vid_str = '';
        var key_str = '';
        var qty_str = '';
        $('.oo-cart-overlay .oo-current-cart-items .oo-search-result-item,.oo-cart-overlay .oo-search-selected .oo-search-result-item').each(function(i,v){
           pid_str += $(this).attr('data-pid')+',';
           vid_str += $(this).attr('data-vid')+',';
           key_str += $(this).attr('data-key')+',';
           qty_str += $(this).attr('data-qty')+',';
        });
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: {
              action:'oo_change_cart',
              pid_str:pid_str,
              ses_id:current_ses_id,
              vid_str:vid_str,
              key_str:key_str,
              qty_str:qty_str,
              _wpnonce:oometrics._nonce
            },
            beforeSend:function(){jQuery('.oo-cart-overlay').addClass('loading lock');},
            success: function (response) {
               jQuery('.oo-cart-overlay .oo-notification').html(response.message).addClass('show');
               jQuery('.oo-cart-overlay').removeClass('loading lock');
               if(response.status != 'danger'){
                 setTimeout(function(){
                   $('.oo-add-tocart-remotely').click();
                   oometrics_cart_active = false;
                 },1000);
               }

            }
        } );

  		});
    $(document).delegate('#oo-open-push-to-session','click',function(e){
      e.preventDefault();
  		  $('.oo-push-overlay').removeClass('hide');
  		});
    $(document).delegate('.oo-add-tocart-remotely','click',function(e){
      e.preventDefault();
      if($('.oo-cart-overlay').hasClass('hide')){
        oometrics_cart_active = true;
      } else {
        oometrics_cart_active = false;
      }
  		  $('.oo-cart-overlay').toggleClass('hide');
  		});

      $(document).delegate('#oo-close-send-the-push','click',function(e){
        e.preventDefault();
        $('.oo-push-overlay').addClass('hide');
        $('#oo-send-the-push').removeClass('yes button-primary');
        $('#oo-send-the-push').html('Push to the session');

      });
      $(document).delegate('.oo-push-delete','click',function(e){
        var t = $(this);
        e.preventDefault();
        var pushid = $(this).attr('data-pushid');
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: {
              action:'oo_delete_push',
              push_id: pushid,
              _wpnonce:oometrics._nonce
            },
            beforeSend:function(){
              t.addClass('rotating');
            },
            success: function (response) {
               $('#oo-push-item-'+pushid).remove();
            }
        } );

      });



      $(document).delegate('.oo-live-popup-shortcut','click',function(e){
        e.preventDefault();
        var ses_id = $(this).attr('data-sesid');
        $('.oo-modal-overlay').addClass('show');
          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: {
                action:'oo_get_templates',
                extra_class:'shortcut',
                _wpnonce:oometrics._nonce
              },
              beforeSend:function(){
                $('.oo-modal-title').html('Send templated popup');
                $('.oo-modal-content').html('loading ...');
              },
              success: function (response) {
                $('.oo-modal-content').html('<input type="hidden" id="oo_popup_shortcut_template_id" value="0"/>'+response.html);
                $('.oo-modal-actions').html('<a class="button button-hero button-primary oo-send-popup-shortcut-push" data-sesid="'+ses_id+'" href="#">Send to session</a>');
              }
          } );
      });

      $(document).delegate('.oo-send-popup-shortcut-push','click',function(e){
        e.preventDefault();
        var t = $(this);
        var ses_id = t.attr('data-sesid');
          var oo_popup_tid = $('#oo_popup_shortcut_template_id').val();
          if(oo_popup_tid == '0'){
            $('.oo-modal-content').append('<div class="oo-shortcut-modal-error">Please choose a template first</div>');
            return false;
          } else {
            $('.oo-shortcut-modal-error').remove();
          }
          data = {
            action:'oo_send_push',
            push_type:'open_popup',
            ses_id:ses_id,
            push_duration:'end',
            popup_type:'templates',
            popup_tid :oo_popup_tid,
            _wpnonce:oometrics._nonce
          }

          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: data,
              beforeSend:function(){
                t.html('Sending ...');
              },
              success: function (response) {
                 if(response.status == 1){
                   $('.oo-modal-inner,.oo-modal-actions').html('');
                   $('.oo-modal-overlay').removeClass('show');
                 }
              }
          } );
      });

      $(document).delegate('.oo-live-sale-price-shortcut','click',function(e){
        e.preventDefault();
        var ses_id = $(this).attr('data-sesid');
        $('.oo-modal-overlay').addClass('show');
        $('.oo-modal-title').html('Apply global sale price');
        $('.oo-modal-content').html('<div class="oo-shortcut-input-wrapper oo-send-global-sale-shortcut-push-wrapper"><label for="oo_sale_amount_shortcut">Discount amount</label><input type="text" id="oo_sale_amount_shortcut" placeholder="$"/><br /><label for="oo_sale_percent_shortcut">Price percent</label><input type="text" id="oo_sale_percent_shortcut" placeholder="%"/><p>You should fill only one item,percent or amount.</p></div>');
        $('.oo-modal-actions').html('<a class="button button-hero button-primary oo-send-global-sale-shortcut-push" data-sesid="'+ses_id+'" href="#">Apply to session</a>');
      });

      $(document).delegate('.oo-send-global-sale-shortcut-push','click',function(e){
        e.preventDefault();
        var t = $(this);
        var ses_id = t.attr('data-sesid');
        var sale_amount = $('#oo_sale_amount_shortcut').val();
        var sale_percent = $('#oo_sale_percent_shortcut').val();
        if(sale_amount == '' && sale_percent == ''){
          if(sale_amount == ''){
            $('#oo_sale_amount_shortcut').addClass('danger');
            return false;
          } else {
            $('#oo_sale_amount_shortcut').removeClass('danger');
          }
          if(sale_percent == ''){
            $('#oo_sale_percent_shortcut').addClass('danger');
            return false;
          } else {
            $('#oo_sale_percent_shortcut').removeClass('danger');
          }

        } else {
          $('#oo_sale_percent_shortcut').removeClass('danger');
          $('#oo_sale_amount_shortcut').removeClass('danger');
        }
          data = {
            action:'oo_send_push',
            push_type:'sale_price',
            push_duration:'end',
            ses_id:ses_id,
            pid_str:'-1,',
            sale_amount:sale_amount,
            sale_percent:sale_percent,
            _wpnonce:oometrics._nonce
          }

          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: data,
              beforeSend:function(){
                t.html('Sending ...');
              },
              success: function (response) {
                 if(response.status == 1){
                   $('.oo-modal-inner,.oo-modal-actions,.oo-modal-title').html('');
                   $('.oo-modal-overlay').removeClass('show');
                 }
              }
          } );
      });

      $(document).delegate('.oo-send-global-sale-shortcut-push-wrapper','keydown',function (e){
        if(e.keyCode == 13){
            $('.oo-send-global-sale-shortcut-push').click();
            e.preventDefault();
        }
      });

      $(document).delegate('#oo_popup_type','change',function(e){
        e.preventDefault();
        var popup_type = $(this).val();
        $('.popup-types').hide();
        var active_popup = '.'+popup_type+'.popup-types';
        var active_popup_inner = '.'+popup_type+'.popup-types .popup-types-inner';
        $(active_popup).show();
        if(popup_type == 'templates'){
          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: {
                action:'oo_get_templates',
                _wpnonce:oometrics._nonce
              },
              beforeSend:function(){
                $(active_popup_inner).html('loading ...');
              },
              success: function (response) {
                $(active_popup_inner).html(response.html);
              }
          } );
        }
      });

      $(document).delegate('#oo-save-template-popup','click',function(e){
        e.preventDefault();
        var t = $(this);

        if(!t.hasClass('clicked')){
          $('.oo-popup-template-name').addClass('show');
          t.addClass('clicked');
          t.html('Done');
          return false;
        }

        var oo_popup_template_title = $('#oo_popup_template_title').val();
        if(oo_popup_template_title == '' || typeof oo_popup_template_title === 'undefined'){
          $('#oo_popup_template_title').addClass('danger');
          return false;
        } else {
          $('#oo_popup_template_title').removeClass('danger');
        }

        var data;
        var push_duration = $('#oo_push_duration').val();
        var push_type = $('#oo-choose-push').val();
        var popup_type = $('#oo_popup_type').val()
        var oo_popup_btn_1_label = $('#oo_popup_btn_1_label').val();
        var oo_popup_btn_2_label = $('#oo_popup_btn_2_label').val();
        var oo_popup_btn_1_href = $('#oo_popup_btn_1_href').val();
        var oo_popup_btn_2_href = $('#oo_popup_btn_2_href').val();
        var content = tinymce.activeEditor.getContent();
        data = {
          action:'oo_save_template',
          popup_type:popup_type,
          oo_popup_template_title:oo_popup_template_title,
          oo_popup_btn_1_label :oo_popup_btn_1_label,
          oo_popup_btn_2_label : oo_popup_btn_2_label,
          oo_popup_btn_1_href : oo_popup_btn_1_href,
          oo_popup_btn_2_href : oo_popup_btn_2_href,
          popup_content:content,
          _wpnonce:oometrics._nonce
        }
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: data,
            beforeSend:function(){
              t.html('Saving ...');
              $('.oo-popup-template-name').removeClass('show');
            },
            success: function (response) {
               if(response.tid > 0){
                 t.html('Saved Successfully ...');
                 // $('.oo-popup-template-name').addClass('show');
                 t.removeClass('clicked');
                 setTimeout(function(){
                  t.html('Save as template');
                 },3000);
               }
            }
        } );

      });

      $(document).delegate('.oo-popup-templates a','click',function(e){
        e.preventDefault();
        var t = $(this);
        var tid = t.attr('data-tid');
        if(t.hasClass('shortcut')){
          $('#oo_popup_shortcut_template_id').val(tid);
        } else {
          $('#oo_popup_template_id').val(tid);
        }
        $('.oo-popup-templates a').removeClass('active');
        t.addClass('active');
      });

      $(document).delegate('.oo-delete-popup-template','click',function(e){
        e.preventDefault();
        var t = $(this);
        var tid = t.attr('data-tid');
        jQuery.ajax( {
            url: oometrics.ajaxurl,
            type: 'post',
            data: {
              action:'oo_delete_template',
              tid:tid,
              _wpnonce:oometrics._nonce
            },
            beforeSend:function(){
              t.addClass('deleting');
            },
            success: function (response) {
               if(response.status == 1){
                 t.parent().remove();
               }
            }
        } );
      });

      $(document).delegate('#oo-send-the-push','click',function(e){
        e.preventDefault();
        var t = $(this);
        var wrapper = $('.oo-push-options');
        var data;
        var push_duration = $('#oo_push_duration').val();
        var push_type = $('#oo-choose-push').val();
        if(push_type == ''){
          $('#oo-choose-push').addClass('danger');
          return false;
        } else {
          $('#oo-choose-push').removeClass('danger');
        }
        if(push_duration == ''){
          $('#oo_push_duration').addClass('danger');
          return false;
        } else {
          $('#oo_push_duration').removeClass('danger');
        }
        var pid_str = '';
        var vid_str = '';

        if(push_type == 'sale_price'){
          if(wrapper.find('.oo-product-search').val() == ''){
            wrapper.find('.oo-product-search').addClass('danger');
            return false;
          } else {
            wrapper.find('.oo-product-search').removeClass('danger');
          }
          $('.oo-search-selected .oo-search-result-item').each(function(i,v){
             pid_str += $(this).attr('data-pid')+',';
             vid_str += $(this).attr('data-vid')+',';
          });
          var sale_amount = $('#oo_sale_amount').val();
          var sale_percent = $('#oo_sale_percent').val();
          if(sale_amount == '' && sale_percent == ''){
            if(sale_amount == ''){
              $('#oo_sale_amount').addClass('danger');
              return false;
            } else {
              $('#oo_sale_amount').removeClass('danger');
            }
            if(sale_percent == ''){
              $('#oo_sale_percent').addClass('danger');
              return false;
            } else {
              $('#oo_sale_percent').removeClass('danger');
            }

          } else {
            $('#oo_sale_percent').removeClass('danger');
            $('#oo_sale_amount').removeClass('danger');
          }
          data = {
            action:'oo_send_push',
            push_type:push_type,
            push_duration:push_duration,
            ses_id:current_ses_id,
            pid_str:pid_str,
            vid_str:vid_str,
            sale_amount:sale_amount,
            sale_percent:sale_percent,
            _wpnonce:oometrics._nonce
          }
        } else if(push_type == 'apply_coupon'){
          var push_coupons = $('#oo-coupons').val();
          if(push_coupons == ''){
            $('#oo-coupons').addClass('danger');
            return false;
          } else {
            $('#oo-coupons').removeClass('danger');
          }
          data = {
            action:'oo_send_push',
            push_type:push_type,
            push_duration:push_duration,
            ses_id:current_ses_id,
            push_coupons:push_coupons,
            _wpnonce:oometrics._nonce
          }
        } else if(push_type == 'open_popup'){
          var popup_type = $('#oo_popup_type').val();
          if(popup_type == 'templates'){
            var oo_popup_tid = $('#oo_popup_template_id').val();
            data = {
              action:'oo_send_push',
              push_type:push_type,
              ses_id:current_ses_id,
              push_duration:push_duration,
              popup_type:popup_type,
              popup_tid :oo_popup_tid,
              _wpnonce:oometrics._nonce
            }
          } else {
            var oo_popup_btn_1_label = $('#oo_popup_btn_1_label').val();
            var oo_popup_btn_2_label = $('#oo_popup_btn_2_label').val();
            var oo_popup_btn_1_href = $('#oo_popup_btn_1_href').val();
            var oo_popup_btn_2_href = $('#oo_popup_btn_2_href').val();
            var content = tinymce.activeEditor.getContent();
            data = {
              action:'oo_send_push',
              push_type:push_type,
              ses_id:current_ses_id,
              push_duration:push_duration,
              popup_type:popup_type,
              oo_popup_btn_1_label :oo_popup_btn_1_label,
              oo_popup_btn_2_label : oo_popup_btn_2_label,
              oo_popup_btn_1_href : oo_popup_btn_1_href,
              oo_popup_btn_2_href : oo_popup_btn_2_href,
              popup_content:content,
              _wpnonce:oometrics._nonce
            }
          }

        }


        if(t.hasClass('yes')){

          jQuery.ajax( {
              url: oometrics.ajaxurl,
              type: 'post',
              data: data,
              beforeSend:function(){
                t.html('Sending ...');
              },
              success: function (response) {
                 if(response.status == 1){
                   $('.oo-push-overlay').addClass('hide');
                   $('#oo-choose-push').val('');
                   $('.oo-push-option').removeClass('active');
                   t.toggleClass('yes button-primary');
                     t.html('Push to the session');
                 }
              }
          } );
        } else {
          t.toggleClass('yes button-primary');
          t.html('Really sure? click for yes');
        }
    		});

        $(document).delegate('#oo-attach-message','click', function( event ) {
          $(this).attr('disabled','disabled');
          $('#oo-chat-upload').click();
        });

        $(document).delegate('#oo-chat-upload','change', function( event ) {

          var input_id = $('#oo-chat-upload')[0];
          var data = new FormData();
          var file = event.target.files;

      		$.each(file, function(key, value)
      			{
        			data.append("chat_file", value);
      			});
          data.append('action', 'oo_chat_add_attachment');
          data.append('rel_id', current_rel_id);
          data.append('sender_ses_id', admin_ses_id);
          data.append('receiver_ses_id', current_ses_id);
          data.append('admin', 1);
          data.append('_wpnonce', oometrics._nonce);



          jQuery.ajax({
            url: oometrics.ajaxurl,
            method:'post',
            type:'post',
            processData: false,
            // cache: false,
            contentType: false, //'multipart/form-data; charset=utf-8; boundary=' + Math.random().toString().substr(2),
            data:data,
            beforeSend:function(){
              $('.oo-chat-list').append('<li class="oo-two sent tmp-bubble oo-loading"><div class="oo-chat-bubble"><div class="oo-chat-content">Uploading</div><div class="oo-chat-meta"><span class="oo-chat-status sent" title="Sent"></span><em>1 second</em></div></div></li>');
              $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
              $('#chat-footer').addClass('oo-loading');

              // abort all the xhr request and interval to avoid any complications
              clearInterval(status_listen_interval);
              status_listen_interval = 0;
              clearInterval(interval);
              interval = 0;

              if(chat_status_xhr != null) {
                  chat_status_xhr.abort();
              }
              if(chat_xhr != null) {
                  chat_xhr.abort();
              }
              if(live_sessions_x != null) {
                  live_sessions_x.abort();
              }

            },
            success:function(data){
              // jQuery('.tmp-bubble').remove();
              $('#oo-attach-message').removeAttr('disabled');
              $('.oo-chat-list .tmp-bubble.oo-loading').html($(data.html).html()).removeClass('oo-loading');
              if(data.status == 'not_allowed'){
                setTimeout(function(){
                  oo_chat_update();
                },3000);
              } else {
                oo_chat_update();
              }
              oometrics_init();
              oo_chat_status_listen();
              $('#oo-chat-upload').val('');
              $('#chat-footer').removeClass('oo-loading');
            }
          });

        });
  $(document).delegate('.oo-session-list li','click',function(e){

    if($(e.target).hasClass('icon-close-popup') ||
        $(e.target).hasClass('icon-open_popup') ||
        $(e.target).hasClass('icon-sale_price') ||
        $(e.target).hasClass('oo-live-popup-shortcut') ||
        $(e.target).hasClass('oo-live-sale-price-shortcut') ){
      return;
    }
    if(live_sessions_x != null) {
        live_sessions_x.abort();
    }

    var t = $(this);


    conversations_opened = false;
    current_rel_id = -1;
    active_rel_id = -1;
    editor_status = 0;

    // its ui update
    $('.oo-session-list li').removeClass('active');
    t.addClass('active');

    // set li session id to current_ses_id
    var ses_id = t.attr('data-sesid');
    current_ses_id = ses_id;

    jQuery('.oo-close-session').addClass('show');
    // get data for selected session , click argument says that needs update on conversations
    oo_get_sessions('click');

    // tab ui update
    jQuery('.oo-tab').removeClass('active');
    jQuery('#customer-activities').addClass('active');
  });

  $(document).delegate('.oo-refresh-now','click',function(e){
    e.preventDefault();
    if(live_sessions_x != null) {
        live_sessions_x.abort();
    }
    oo_get_sessions();
  });

  $(document).delegate('.oo-close-session','click',function(e){
    e.preventDefault();
    // chat_active = false;
    jQuery('.oo-close-session').removeClass('show');
    if(live_sessions_x != null) {
        live_sessions_x.abort();
    }
    conversations_opened = false;
    current_rel_id = -1;
    active_rel_id = -1;
    current_ses_id = -1;
    editor_status = 0;
    jQuery('.oo-session-list li').removeClass('active');
    jQuery('.oo-tab').removeClass('active');
    jQuery('.oo-cart-overlay').addClass('hide');
    jQuery('#tab-default').addClass('active');
    jQuery('.oo-chat-list').html('<li class="oo-chat-start"><div class="oo-start-inner"><i class="icon icon-default-chat big"></i><br />'+oometrics.labels.startChatOrPushToSession+'</div></li>');
    jQuery('.oo-dashboard-reply').removeClass('block');
    jQuery('.oo-dashboard-reply').addClass('hide');
    jQuery('.oo-profile-info .session-avatar').attr('src',oometrics.deafult_avatar);
    jQuery('.oo-profile-data .name').html(oometrics.labels.guestUser);
    jQuery('.oo-profile-data .email').html(oometrics.labels.notAvailableYet);
    jQuery('.oo-profile-data .location .state').html(oometrics.labels.location);
    jQuery('.oo-profile-data .location .city').html('');
    oometrics_init();
  });

  $(document).delegate('.oo-info-nav li a','click',function(e){
    e.preventDefault();
    if(!$('#tab-default').hasClass('active')){
      var t = $(this);
      $('.oo-info-nav li').removeClass('active');
      $('.oo-cart-overlay').addClass('hide');
      t.parent().addClass('active');
      var id = t.attr('href');
      $('.oo-tab').removeClass('active');
      $(id).addClass('active');
    }

  });

  $(document).delegate('.start-new-chat,.start-new-conv','click',function(e){
    e.preventDefault();
    var t = $(this);
    $('.oo-dashboard-reply').removeClass('block hide');
    editor_status = 1;
    $('#oo-message-text').focus();
  });

  $(document).delegate('#oo-choose-push','change',function(e){
    e.preventDefault();
    var val = $(this).val();
    $('.oo-push-option').removeClass('active');
    $('#'+val).addClass('active');
  });

  jQuery( document ).on( 'click', '.oo-session-list-nav a', function ( e ) {

       e.preventDefault();
       var t = $(this);
       var order_by = t.attr('data-orderby');
       $('.oo-session-list-nav li').removeClass('active');
       t.parent().addClass('active');
       // We make our call
       jQuery.ajax( {
           url: oometrics.ajaxurl,
           type: 'post',
           data: {
             action: 'oo_set_global_order_by',
             orderby: order_by,
             _wpnonce:oometrics._nonce
           },
           success: function (response) {
              if(response.status == 1){
                oo_get_sessions();
              }
           }
       } );

   } );


     //frontend
     $(document).delegate('#oo-chat-trigger','click',function(e){
       e.preventDefault();
       $('#oometrics-chat').toggleClass('opened');
       $('#oo-message-text').focus();
     });

     $(document).delegate('#oo-send-message','click',function(e){
       e.preventDefault();
       var t = $(this);
       var message = $('#oo-message-text').val();
       if(typeof message === 'undefined' || message == ''){
         message = tinymce.activeEditor.getContent();
         if(typeof message === 'undefined' || message == ''){
           return false;
         }
       }

       if(typeof chat_xhr !== 'undefined') chat_xhr.abort();
       var chat_id = t.attr('data-chatid');
       if($(this).hasClass('edit')){
         jQuery.ajax({
           url: oometrics.ajaxurl,
           type:'post',
           data:{
             action:'oo_edit_chat',
             chat_id : chat_id,
             sender_ses_id : current_ses_id,
             message: message,
             _wpnonce: oometrics._nonce
           },
           beforeSend:function(){
             $('#oo-message-text').blur();
             $('#oo-message-text').val('');
           },
           success:function(data){
             $('#oo-message-text').val('');
             t.removeClass('edit');
             t.removeAttr('data-chatid');
             $('.oo-chat-list li[data-chatid="'+chat_id+'"]').html($(data.bubble).html());
             // $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
           }
         });
       } else {
         $('.oo-chat-list').append('<li class="oo-two sent tmp-bubble"><div class="oo-chat-bubble"><div class="oo-chat-content">'+message+'</div><div class="oo-chat-meta"><span class="oo-chat-status sent" title="Sent"></span><em>1 second</em></div></div></li>');
         $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
         jQuery.ajax({
           url: oometrics.ajaxurl,
           type:'post',
           data:{
             action:'oo_admin_send_message',
             rel_id : active_rel_id,
             sender_ses_id : admin_ses_id,
             receiver_ses_id : current_ses_id,
             message:message,
             _wpnonce: oometrics._nonce
           },
           beforeSend:function(){
             // $('#oo-message-text').blur();
             $('#oo-message-text').val('');
             if(current_rel_id == -1){
               last_updated = 0;
             }
             if(jQuery('.oo-chat-list .oo-session-profile').length > 0){
               jQuery('.oo-session-profile[data-relid="'+active_rel_id+'"]').click();
             }
           },
           success:function(data){
             current_rel_id = data.rel_id;
             oo_chat_update();
             oo_chat_status_listen();
           }
         });
       }

     });

     $('#oo-message-text').keydown(function (e){
       if(e.keyCode == 13){
           $('#oo-send-message').click();
           e.preventDefault();
       }
   })

     $(document).delegate('.oo-chat-conversations .oo-session-profile','click',function(e){
       e.preventDefault();
       var t = $(this);
       var relid = t.attr('data-relid');
       var ses_id = t.attr('data-ses_id');
       var stop_interval = false;
       if(t.hasClass('new') || (relid == active_rel_id)){
         // keep messaging open and listens to chat updates
         current_rel_id = active_rel_id;
       }
       jQuery.ajax({
         url: oometrics.ajaxurl,
         type:'post',
         data:{
           action:'oo_admin_get_session_chats',
           rel_id : relid,
           sender_ses_id : admin_ses_id,
           receiver_ses_id : current_ses_id,
           last_updated : 0,
           _wpnonce: oometrics._nonce
         },
         beforeSend:function(){
           t.addClass('loading');
           if(relid == active_rel_id){
             $('.oo-dashboard-reply').removeClass('hide block');
             $('#oo-message-text').focus();
             $('.oo-chat').removeClass('old-conversation');
           } else {
             $('.oo-chat').addClass('old-conversation');
             $('.oo-dashboard-reply').addClass('hide');
           }
         },
         success:function(data){
           t.removeClass('loading');
           $('.oo-chat-list').html(data.chats);
           if(relid == active_rel_id){
             last_updated = data.last_updated;
             $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
           }
         }
       });
     });


     $('.oo-chat-conversations').scroll(function(){

         var stop = $(this).scrollTop() + chat_height;
         if(stop >= (chat_s_height - 100)){
           if(!conversation_end_loading){
             if($('#go-to-new').length > 0){
               $('#go-to-new').remove();
             }
             conversation_end_loading = true;
             mark_as_seen();
           }
         }
     });

     $(document).delegate('.oo-chat-action .delete','click',function(e){
       e.preventDefault();
       var t = $(this);
       var chat_id = $(this).attr('data-chatid');
       jQuery.ajax({
         url: oometrics.ajaxurl,
         type:'post',
         data:{
           action:'oo_delete_chat',
           chat_id : chat_id,
           _wpnonce: oometrics._nonce
         },
         beforeSend:function(){
           t.addClass('fadeOutLoading');
         },
         success:function(data){
           if(data.status == '1' || data.status == 1){
             $('.oo-chat-list li[data-chatid="'+chat_id+'"]').remove();
           }

         }
       });
     });

     $(document).delegate('.oo-chat-action .edit','click',function(e){
       e.preventDefault();
       var chat_id = $(this).attr('data-chatid');
       var content = $('.oo-chat-list li[data-chatid="'+chat_id+'"]').find('.oo-chat-content').text();
       $('#oo-message-text').val(content.trim());
       $('#oo-send-message').addClass('edit');
       $('#oo-send-message').attr('data-chatid',chat_id);
     });

     $(document).delegate('#go-to-new','click',function(e){
       e.preventDefault();

       var v = $('.oo-chat-list').height();
       $('.oo-chat-conversations').scrollTop(v);
       $(this).remove();
     });

} );
