var chat_interval = 0;
var status_listen_interval = 0;
var status_listen_xhr;
var oo_rel_id = -1;


var chat_s_height = 100;
var chat_height = 0;

var chat_xhr;

var conversation_end_loading = false;


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

function oo_chat_update(force = false)
{
  if(oo_rel_id != -1 || force){
    chat_xhr = jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_update_chat',
        sender_ses_id:sender_ses_id,
        receiver_ses_id:receiver_ses_id,
        rel_id:oo_rel_id,
        last_updated : last_updated,
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
        if(chat_xhr != null) {
            chat_xhr.abort();
        }
      },
      success:function(data){
        if(data.chats != ''){
          if(jQuery(data.chats).length >= 1){
            if(jQuery(data.chats).hasClass('oo-one')){
              if(jQuery('#go-to-new').length < 1){
                jQuery('.oo-chat-conversations').after('<button id="go-to-new"></button>');
              }
            }
          }
          if(jQuery('.oo-back-to-conversations').length < 1 && last_updated){
            jQuery('.oo-chat-wrapper header').append('<a href="#back" class="oo-back-to-conversations">'+oometrics.labels.back+'</a>');
          }
          last_updated = data.last_updated;
          jQuery('.tmp-bubble').remove();
        }

        jQuery('.oo-chat-start').remove();
        jQuery('.oo-chat-list').find('.oo-session-profile').remove();
        jQuery('.oo-chat-list').append(data.chats);

          if(jQuery('.oo-chat-conversations').length>0){
            chat_s_height = jQuery('.oo-chat-list').height();
            chat_height = jQuery('.oo-chat-conversations').height();
          }

          jQuery('#oo-attach-message').show();

        if(jQuery('#oometrics-chat').hasClass('opened')){
            oo_chat_status_listen();
        }
        if((chat_s_height <= chat_height) || (jQuery('.oo-chat-conversations').scrollTop() > chat_s_height - chat_height) && jQuery('#oometrics-chat').hasClass('opened')){
          mark_as_seen();
        }

      }
    });
  }
}

function oo_chat_status_listen(){
  clearInterval(status_listen_interval);
  status_listen_interval = 0;

    status_listen_interval = setInterval(function(){
      var chat_ids = '';
      jQuery('.oo-chat-list li.oo-two:not(.seen):not(.oo-loading):not(.oo-session-profile):not(.tmp-bubble)').each(function(i,v){
        var elm = jQuery(this);
          var chat_id = elm.attr('data-chatid');
          chat_ids += chat_id+',';
      });
      if(chat_ids != ''){
        status_listen_xhr = jQuery.ajax({
          url: oometrics.ajaxurl,
          type:'post',
          data:{
            action:'oo_update_chat_status',
            chat_ids : chat_ids,
            _wpnonce: oometrics._nonce
          },
          beforeSend:function(){
            if(status_listen_xhr != null) {
                status_listen_xhr.abort();
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

  if(jQuery('.oo-chat-conversations').length>0){
    chat_s_height = jQuery('.oo-chat-conversations').get(0).clientHeight;
    chat_height = jQuery('.oo-chat-conversations').get(0).scrollHeight;

  }

  //frontend
  $(document).delegate('#oo-chat-trigger','click',function(e){
    e.preventDefault();
    $(this).toggleClass('opened');
    $('#oometrics-chat').toggleClass('opened');
    var img = $(this).find('img')
    var src = img.attr('src');
    if($('#oometrics-chat').hasClass('opened')){
      src = oometrics.chat_icon_close;
    } else{
      src = oometrics.chat_icon_open;
    }
    img.attr('src',src)
    oo_chat_update();
    $('#oo-message-text').focus();
  });

  $(document).delegate('#oo-send-message','click',function(e){
    e.preventDefault();
    var t = $(this);
    var message = $('#oo-message-text').val();

    if(typeof message === 'undefined' || message == ''){
      return false;
    }

    if(typeof chat_xhr !== 'undefined') chat_xhr.abort();
    if(typeof session_xhr !== 'undefined') session_xhr.abort();

    var chat_id = t.attr('data-chatid');
    if($(this).hasClass('edit')){
      jQuery.ajax({
        url: oometrics.ajaxurl,
        type:'post',
        data:{
          action:'oo_edit_chat',
          chat_id : chat_id,
          message: message,
          _wpnonce: oometrics._nonce
        },
        beforeSend:function(){
          $('#oo-message-text').blur();
        },
        success:function(data){
          $('#oo-message-text').val('');
          t.removeClass('edit');
          t.removeAttr('data-chatid');
          $('.oo-chat-list li[data-chatid="'+chat_id+'"]').html($(data.bubble).html());
        }
      });
    } else {
      $('.oo-chat-list').append('<li class="oo-two sent tmp-bubble"><div class="oo-chat-bubble"><div class="oo-chat-content">'+message+'</div><div class="oo-chat-meta"><span class="oo-chat-status sent" title="Sent"></span><em>1 second</em></div></div></li>');
      $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
      jQuery.ajax({
        url: oometrics.ajaxurl,
        type:'post',
        data:{
          action:'oo_send_message',
          rel_id : rel_id,
          sender_ses_id : sender_ses_id,
          receiver_ses_id : receiver_ses_id,
          message:message,
          _wpnonce: oometrics._nonce
        },
        beforeSend:function(){
          $('#oo-message-text').val('');
          if(oo_rel_id == -1 || jQuery('.oo-chat-list .oo-session-profile').length > 0){
            jQuery('.oo-session-profile[data-relid="'+rel_id+'"]').click();
          }
        },
        success:function(data){
          oo_rel_id = data.rel_id;
          rel_id = data.rel_id;
          oo_rel_id = rel_id;
          oo_chat_update();
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

  $(document).delegate('.oo-session-profile','click',function(e){
    e.preventDefault();
    var t = $(this);
    var switch_rel_id = oo_rel_id;
    var relid = t.attr('data-relid');

    var ses_id = t.attr('data-ses_id');
    var stop_interval = false;
    oo_rel_id = relid;

    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_get_session_chats',
        sender_ses_id:sender_ses_id,
        receiver_ses_id:receiver_ses_id,
        rel_id:oo_rel_id,
        last_updated:0,
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
        t.addClass('loading');
      },
      success:function(data){
        t.removeClass('loading');
        last_updated = data.last_updated;
        $('.oo-chat-list').html(data.chats);
        $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
        $('.oo-chat-wrapper header').append('<a href="#back" data-relid="'+switch_rel_id+'" class="oo-back-to-conversations">'+oometrics.labels.back+'</a>');
        if(!t.hasClass('new') && (relid!= rel_id)){
          jQuery('#oometrics-chat footer').hide();
          jQuery('#oometrics-chat').addClass('no-send');
        } else {
          jQuery('#oometrics-chat footer').show();
          jQuery('#oometrics-chat').removeClass('no-send');
        }
        oo_chat_update();
      }
    });
  });

  $(document).delegate('.oo-back-to-conversations','click',function(e){
    e.preventDefault();
    var t = $(this);
    if(chat_xhr != null) {
        chat_xhr.abort();
    }
    if(session_status_listen_xhr != null) {
        session_status_listen_xhr.abort();
    }
    clearInterval(status_listen_interval);
    status_listen_interval = 0;
    oo_rel_id = -1;
    chat_update = false;
    last_updated = 0;
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_get_conversations',
        sender_ses_id:sender_ses_id,
        _wpnonce: oometrics._nonce
      },
      beforeSend:function(){
        jQuery('#oo-attach-message').hide();
        $('.oo-chat-conversations').addClass('loading');
        jQuery('#oometrics-chat footer').show();
        jQuery('#oometrics-chat').removeClass('no-send');
        clearInterval(status_listen_interval);
        status_listen_interval = 0;
      },
      success:function(data){
        $('.oo-chat-conversations').removeClass('loading');
        $('.oo-chat-list').html(data.rels);
      }
    });
    t.remove();
  });


  $('.oo-chat-conversations').scroll(function(){
    var stop = $(this).scrollTop() + chat_height;
    if( stop >= (chat_s_height - 100)){
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
