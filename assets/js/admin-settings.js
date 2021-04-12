jQuery(document).ready(function($){
  jQuery( document ).on( 'submit', '#oometrics-admin-form', function ( e ) {

       e.preventDefault();

       // We inject some extra fields required for the security
       jQuery('#oometrics-security').val(oometrics._nonce);

       // We make our call
       jQuery.ajax( {
           url: oometrics.ajaxurl,
           type: 'post',
           data: jQuery(this).serialize(),
           beforeSend:function(){
             jQuery('.oo-settings-notification').html('Saving ...').addClass('loading');
           },
           success: function (response) {
              jQuery('.oo-settings-notification').html(response).removeClass('loading').addClass('show');
           }
       } );

   } );

   $(document).delegate('#oometrics-reset-admin-session','click',function(e){
     e.preventDefault();
     var t = $(this);
     var inner_caption = t.html();
     var user_id = $('select[name="oometrics_main_user"]').val();
     jQuery.ajax({
       url: oometrics.ajaxurl,
       type:'post',
       data:{
         action:'oo_reset_admin_session',
         admin_user_id : user_id,
         _wpnonce: oometrics._nonce
       },
       beforeSend:function(){
         t.attr('disabled','disabled');
         t.html('Resetting...');
       },
       success:function(data){
         t.html(inner_caption);
       }
     });
   });
});
