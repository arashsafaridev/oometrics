var current_ses_id = -1;
var sessions_page = 2;

var chat_conv;
var chat_list;
var chat_conv_height;
var chat_list_height;
var diff;

var sidebar_content;

function get_session(ses_id){
  var ses_data;
  update_session_x = jQuery.ajax({
    url: oometrics.ajaxurl,
    type:'post',
    data:{
      action:'oo_get_report_session',
      ses_id: ses_id,
      _wpnonce: oometrics._nonce
    },
    success:function(data)
    {
      // oo_rel_id = -1;
      jQuery('#oo_ses_id').val(ses_id);
      current_ses_id = ses_id;
      update_session_ui(data);
      sessions_page = 1;

    }
  });
  return ses_data;
}



function update_report_ui(data)
{
  jQuery('.cart-activity span').html(data.cart_activity);
  jQuery('.chat-activity span').html(data.chat_activity);
  jQuery('.checkout-activity span').html(data.checkout_activity);


  jQuery('.sale-price-push span').html(data.sale_price_pushes);
  jQuery('.apply-coupon-push span').html(data.apply_coupon_pushes);
  jQuery('.open-popups-push span').html(data.popup_pushes);
  jQuery('.delivered-popups-push span').html(data.delivered_popup_pushes);
  jQuery('.clicked-popups-push span').html(data.clicked_popup_pushes);
  jQuery('.successful-session-push span').html(data.successful_session_pushes);

  jQuery('.session-duration-10plus span').html(data.session_durations_10plus);
  jQuery('.session-duration-10-5 span').html(data.sessions_duration_10_5);
  jQuery('.session-duration-1-5 span').html(data.sessions_duration_1_5);
  jQuery('.session-duration-1-less span').html(data.sessions_duration_1_less);
  jQuery('.session-duration-30-less span').html(data.sessions_duration_30_less);


  jQuery('.average-session-value strong').html(data.average_ses_value);
  jQuery('.average-activities strong').html(data.average_activities );
  jQuery('.average-duration strong').html(data.average_duration );


  jQuery('.mobile-devices span').html(data.mobile_devices);
  jQuery('.desktop-devices span').html(data.desktop_devices);

  jQuery('.oo-total-sessions strong').html(data.total_sessions);
  jQuery('.oo-total-activities .oo-total-value').html(data.total_activities);
  jQuery('.oo-total-sales strong').html(data.total_sales);
  jQuery('.oo-total-uniques strong').html(data.total_uniques);
  jQuery('.oo-total-orders strong').html(data.total_orders);

  jQuery('.oo-tab').removeClass('active');
  jQuery('#tab-default').addClass('active');


  if(data.sessions != ''){
    jQuery('.oo-chat-conversations .oo-session-list').html(data.sessions);
    jQuery('.oo-chat-conversations').scrollTop(0);
  } else {
    jQuery('.oo-chat-conversations .oo-session-list').html('<li>No Activity!</li>');
  }

  jQuery('.oo-dashboard-sidebar-body').html(sidebar_content);

  sessions_page = 2;
  chat_list_height = chat_list.height();
  diff = chat_list_height - chat_conv_height;

}

function update_session_ui(data)
{
  var session_data = data.session;
  var info_html = data.info;
  var activity_html = data.activity;
  var profile = data.profile;

  jQuery('.session-value strong').html(session_data.value);
  jQuery('#customer-profile .billing_first_name strong').html(profile.billing_first_name);
  jQuery('#customer-profile .billing_last_name strong').html(profile.billing_last_name);
  jQuery('#customer-profile .billing_phone strong').html(profile.billing_phone);
  jQuery('#customer-profile .billing_email strong').html(profile.user_email);
  jQuery('#customer-profile .billing_company strong').html(profile.billing_company);
  jQuery('#customer-profile .billing_country strong').html(profile.billing_country);
  jQuery('#customer-profile .billing_state strong').html(profile.billing_state);
  jQuery('#customer-profile .billing_city strong').html(profile.billing_city);
  jQuery('#customer-profile .billing_address_1 strong').html(profile.billing_address_1);
  jQuery('#customer-profile .billing_address_2 strong').html(profile.billing_address_2);
  jQuery('#customer-profile .billing_postcode strong').html(profile.billing_postcode);

  jQuery('#customer-profile .shipping_first_name strong').html(profile.shipping_first_name);
  jQuery('#customer-profile .shipping_last_name strong').html(profile.shipping_last_name);
  jQuery('#customer-profile .shipping_company strong').html(profile.shipping_company);
  jQuery('#customer-profile .shipping_country strong').html(profile.shipping_country);
  jQuery('#customer-profile .shipping_state strong').html(profile.shipping_state);
  jQuery('#customer-profile .shipping_city strong').html(profile.shipping_city);
  jQuery('#customer-profile .shipping_address_1 strong').html(profile.shipping_address_1);
  jQuery('#customer-profile .shipping_address_2 strong').html(profile.shipping_address_2);
  jQuery('#customer-profile .shipping_postcode strong').html(profile.shipping_postcode);
  // cart

  jQuery('.oo-cart-items').html(data.cart.cart_items);
  jQuery('.oo-cart-total').html(data.cart.cart_total);
  jQuery('.oo-purchased-items').html(data.cart.purchased_items);
  jQuery('.oo-purchased-total').html(data.cart.purchased_total);


  jQuery('#customer-activities .oo-info-details').html(activity_html);

  if(data.rels != ''){
    jQuery('.oo-dashboard-sidebar-body').html(data.rels);
  }
}
jQuery(document).ready(function($){
  sidebar_content = jQuery('.oo-dashboard-sidebar-body').html();
  jQuery( ".oo-datepicker" ).datepicker({
       dateFormat : "dd-mm-yy"
   });
   $(document).delegate('.oo-dashboard-sidebar-body .oo-chat-list li.oo-session-profile','click',function(e){
     e.preventDefault();
     var t = $(this);
     oo_rel_id = t.attr('data-relid');
     sender_ses_id = t.attr('data-senderid');
     receiver_ses_id = t.attr('data-receiverid');
     $('#oo_chat_rel_id').val(oo_rel_id);
     jQuery.ajax({
       url: oometrics.ajaxurl,
       type:'post',
       data:{
         action:'oo_report_get_session_chats',
         rel_id : oo_rel_id,
         report:true,
         _wpnonce: oometrics._nonce
       },
       beforeSend:function(){},
       success:function(data){
         $('.oo-dashboard-sidebar-body .oo-chat-list').html(data.chats);
         // $('.oo-chat-conversations').scrollTop(jQuery('.oo-chat-list').height());
       }
     });
   });
   $(document).delegate('#time-period','click',function(e){
     e.preventDefault();
     // get_sessions();
     var val = $(this).val();
     if(val == 'custom'){
       $('.oo-custom-time-period').removeClass('hide');
     } else {
       $('.oo-custom-time-period').addClass('hide');
     }
   });

   $(document).delegate('.oo-session-list li','click',function(){
     editor_status = 0;
     var t = $(this);
     $('.oo-session-list li').removeClass('active');
     t.addClass('active');
     var ses_id = t.attr('data-sesid');
     get_session(ses_id);
     jQuery('.oo-tab').removeClass('active');
     jQuery('#customer-activities').addClass('active');
   });



     $(document).delegate('.oo-info-nav li a','click',function(e){
       e.preventDefault();
       var t = $(this);
       $('.oo-info-nav li').removeClass('active');
       t.parent().addClass('active');
       var id = t.attr('href');
       $('.oo-tab').removeClass('active');
       $(id).addClass('active');
     });

     $(document).delegate('#time-period','change',function(e){
       e.preventDefault();
       var val = $(this).val();
       start_date = $('#oo-start-date').val();
       end_date = $('#oo-end-date').val();
       jQuery.ajax({
         url: oometrics.ajaxurl,
         type:'post',
         data:{
           action:'oo_get_report',
           period: val,
           start_date:start_date,
           end_date:end_date,
           _wpnonce: oometrics._nonce
         },
         success:function(data)
         {
           update_report_ui(data);
         }
       });
     });

     $(document).delegate('#time-period-button','click',function(e){
       e.preventDefault();
       var val = $('#time-period').val();
       var start_date = $('#oo-start-date').val();
       var end_date = $('#oo-end-date').val();
       jQuery.ajax({
         url: oometrics.ajaxurl,
         type:'post',
         data:{
           action:'oo_get_report',
           period: val,
           start_date:start_date,
           end_date:end_date,
           _wpnonce: oometrics._nonce
         },
         success:function(data)
         {
           update_report_ui(data);
         }
       });
     });

     chat_conv = $('.oo-chat-conversations');
     chat_list = $('.oo-chat-conversations .oo-session-list');
     chat_conv_height = chat_conv.height();
     chat_list_height = chat_list.height();
     diff = chat_list_height - chat_conv_height;

     chat_list.resize(function(){

       chat_list_height = chat_list.height();
       diff = chat_list_height - chat_conv_height;


     });
     $('.oo-chat-conversations:not(.oo-loading)').scroll(function(){
       if(sessions_page != -1){
         var stop = $(this).scrollTop();
         if(stop >= diff){
           jQuery.ajax({
             url: oometrics.ajaxurl,
             type:'post',
             data:{
               action:'oo_get_report_sessions',
               page: sessions_page,
               _wpnonce: oometrics._nonce
             },
             beforeSend:function(){
               chat_conv.addClass('oo-loading');
             },
             success:function(data)
             {
               chat_conv.removeClass('oo-loading');
               chat_list.append(data.sessions);
               sessions_page = data.page;
               chat_list_height = chat_list.height();
               diff = chat_list_height - chat_conv_height;
             }
           });
         }
       }

     });

     $(document).delegate('.oo-chat-action .delete','click',function(e){
       e.preventDefault();
       var chat_id = $(this).attr('data-chatid');
       jQuery.ajax({
         url: oometrics.ajaxurl,
         type:'post',
         data:{
           action:'oo_delete_chat',
           chat_id : chat_id,
           _wpnonce: oometrics._nonce
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

});
