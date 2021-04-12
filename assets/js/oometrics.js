var sender_ses_id = -1;
var receiver_ses_id = 1;
var last_updated = 0;
var rel_id = -1;
var interval = 0;
var session_status_listen_xhr = 0;
var chat_badge = '';
var session_xhr;
var chat_update = true;

var oo_domain = location.hostname.replace(".","_");

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

function oo_del_cookie(cname) {
  var expires = "expires=Wed; 01 Jan 1970";
  document.cookie = cname + "=;" + expires + ";path=/";
}

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

function oo_session_update(ses_status = 0)
{

  if(ses_status == 1 && oometrics.chat_enabled == 'yes'){ // has only chat
    if(chat_update){
      oo_get_chat();
    }
  } else  if(ses_status == 2){ // has only popup
    oo_get_popup();
  } else if(ses_status == 3){ // has both chat and popup
    oo_get_chat();
    oo_get_popup();
  }
}

function oo_get_chat(){
  jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_get_chat_rel',
      sender_ses_id:sender_ses_id,
      receiver_ses_id:receiver_ses_id,
      rel_id:rel_id,
      last_updated:last_updated,
      _wpnonce: oometrics._nonce
    },
    beforeSend:function(){
      jQuery('#oo-chat-trigger').addClass('opened');
      jQuery('#oometrics-chat').addClass('opened');
      if(jQuery('#oo-chat-trigger img').length > 0){
        jQuery('#oo-chat-trigger img').attr('src',oometrics.chat_icon_close);
      }
    },
    success:function(data){
      rel_id = data.rel_id;
      oo_rel_id = data.rel_id;
      oo_set_cookie(oo_domain+'_oometrics_rel_id',rel_id,oometrics.session_lifetime);
      oo_chat_update();
    }
  });
}


function oo_get_popup(){
  session_xhr = jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_get_popup',
      sender_ses_id:sender_ses_id,
      receiver_ses_id:receiver_ses_id,
      rel_id:rel_id,
      _wpnonce: oometrics._nonce
    },
    beforeSend:function(){
      if(session_xhr != null) {
          session_xhr.abort();
      }
      if(oo_rel_id != -1){
        rel_id = oo_rel_id;
      }
    },
    success:function(data){
        if(data.popup != 'none'){
          if(jQuery('#oo-popup-wrapper:not(.consent)').length > 0){
            jQuery('#oo-popup-wrapper:not(.consent)').remove();
          }
          content = data.popup;
          content = content.replace(/\\/g, "");
          jQuery('body').append(content);

          setTimeout(function(){
            jQuery('#oo-popup-wrapper:not(.consent)').addClass('show');
          },1000);
        }
      }
    });
}

function oo_create_session(){
  jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_create_session',
      sender_ses_id:sender_ses_id,
      receiver_ses_id:receiver_ses_id,
      rel_id:rel_id,
      _wpnonce: oometrics._nonce
    },
    success:function(data){
      if(data.sender_ses_id){
        sender_ses_id = data.sender_ses_id;
        receiver_ses_id = data.receiver_ses_id;
        rel_id = data.rel_id;
        oo_set_cookie(oo_domain+'_oometrics_session',sender_ses_id,oometrics.session_lifetime);
        oo_set_cookie(oo_domain+'_oometrics_rel_id',rel_id,oometrics.session_lifetime);
        oometrics_init();
      }
    }
  });
}


function oometrics_init(){
  // run interval
  if (!interval)
  {
    oo_session_check();
    interval = setInterval(function(){
          oo_session_check();
    }, oometrics.session_interval);
  }
}

function oo_session_check(){
  session_status_listen_xhr = jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_session_check',
      sender_ses_id:sender_ses_id,
      receiver_ses_id:receiver_ses_id,
      rel_id:rel_id,
      _wpnonce: oometrics._nonce
    },
    success:function(data){
      if(session_status_listen_xhr != null) {
          session_status_listen_xhr.abort();
      }
      if(data.status != 0){
        oo_session_update(data.status);
      }
      if(rel_id != -1 && oometrics.chat_enabled == 'yes'){
        jQuery('#oo-chat-trigger .oo-badge').html(data.chat_badge).addClass('show');
        if(data.chat_badge != '' && jQuery('#go-to-new').length < 1){
          if(rel_id == oo_rel_id){
            oo_chat_update();
          }
        }
        jQuery('.oo-session-profile[data-relid="'+rel_id+'"] .oo-rel-badge').html(data.chat_badge);
      }

    }
  });
}


// check for tab change
oo_active_tab(function(){
if(oo_active_tab())
{
  if (!interval)
  {
    oo_session_check();
    interval = setInterval(function(){
          oo_session_check();
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
    oo_session_check();
    interval = setInterval(function(){
          oo_session_check();
    }, oometrics.session_interval);
  }

});
jQuery(window).blur(function(){
  clearInterval(interval);
  interval = 0;
});

jQuery(document).ready(function($){

  var oometrics_cookie_session = oo_get_cookie(oo_domain+'_oometrics_session');
  var oometrics_cookie_rel_id = oo_get_cookie(oo_domain+'_oometrics_rel_id');
  if(oometrics_cookie_session != ''){
      sender_ses_id = oometrics_cookie_session;
      rel_id = oometrics_cookie_rel_id;
      oometrics_init();
  } else {
    oo_create_session();
  }

  var current_chat_id_attachment = 0;
  $(document).delegate('.oo-upload-media','click', function( event ) {
    var t = $(this);
    var chat_id = t.attr('data-chatid');
    current_chat_id_attachment = chat_id;
    $('#oo-chat-upload').click();
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
    data.append('rel_id', rel_id);
    data.append('sender_ses_id', sender_ses_id);
    data.append('receiver_ses_id', receiver_ses_id);
    // data.append('chat_file', input_id);
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

        if(session_status_listen_xhr != null) {
            session_status_listen_xhr.abort();
        }
        if(status_listen_xhr != null) {
            status_listen_xhr.abort();
        }
      },
      success:function(data){
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

  $(document).delegate('#oo-popup-wrapper .oo-popup-close','click',function(){
    $(this).parents('#oo-popup-wrapper').removeClass('show');
  });

  $(document).delegate('#oo-popup-wrapper','click',function(e){
      if(e.target === e.currentTarget){
        e.preventDefault();
        $(this).removeClass('show');
      };
    });

  $(document).delegate('#oo-popup-wrapper .oo-inner a,#oo-popup-wrapper .oo-inner button','click',function(){
    var push_id = $('#oo-popup-wrapper').attr('data-pushid');
    jQuery.ajax({
      url: oometrics.ajaxurl,
      type:'post',
      data:{
        action:'oo_push_clicked',
        push_id : push_id,
        _wpnonce: oometrics._nonce
      }
    });

  });


} );
