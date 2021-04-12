<?php
$report = new OOReport();
$data = $report->get_ativities_overview();
 ?>
<div class="customer-activities oo-tab active reports" id="tab-default">
  <h3><?php _e('Session With','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="cart-activity">
      <small><?php _e('Cart activity','oometrics');?></small>
      <span><?php echo $data['cart_activity'];?></span>
    </li>
    <li class="chat-activity">
      <small><?php _e('Chats activity','oometrics');?></small>
      <span><?php echo $data['chat_activity'];?></span>
    </li>
    <li class="checkout-activity">
      <small><?php _e('Checkout','oometrics');?></small>
      <span><?php echo $data['checkout_activity'];?></span>
    </li>
  </ul>
  <h3><?php _e('Pushes','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="successful-session-push">
      <small><?php _e('Successful Sessions with Push','oometrics');?></small>
      <span><?php echo $data['successful_session_pushes'];?></span>
    </li>
    <li class="sale-price-push">
      <small><?php _e('Discounts','oometrics');?></small>
      <span><?php echo $data['sale_price_pushes'];?></span>
    </li>
    <li class="apply-coupon-push">
      <small><?php _e('Coupons','oometrics');?></small>
      <span><?php echo $data['apply_coupon_pushes'];?></span>
    </li>
    <li class="open-popups-push">
      <small><?php _e('Popups','oometrics');?></small>
      <span><?php echo $data['popup_pushes'];?></span>
    </li>
    <li class="delivered-popups-push">
      <small><?php _e('Delivered Popups','oometrics');?></small>
      <span><?php echo $data['delivered_popup_pushes'];?></span>
    </li>
    <li class="clicked-popups-push">
      <small><?php _e('Clicked Popups','oometrics');?></small>
      <span><?php echo $data['clicked_popup_pushes'];?></span>
    </li>

  </ul>
  <h3><?php _e('Session Duration','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="session-duration-10plus">
      <small><?php _e('10+ Minutes','oometrics');?></small>
      <span><?php echo $data['session_durations_10plus'];?></span>
    </li>
    <li class="session-duration-10-5">
      <small><?php _e('5 - 10 Minutes','oometrics');?></small>
      <span><?php echo $data['sessions_duration_10_5'];?></span>
    </li>
    <li class="session-duration-1-5">
      <small><?php _e('1 - 5 Minutes','oometrics');?></small>
      <span><?php echo $data['sessions_duration_1_5'];?></span>
    </li>
    <li class="session-duration-1-less">
      <small><?php _e('Less than 1 Minute','oometrics');?></small>
      <span><?php echo $data['sessions_duration_1_less'];?></span>
    </li>
    <li class="session-duration-30-less">
      <small><?php _e('Less than 30 Seconds','oometrics');?></small>
      <span><?php echo $data['sessions_duration_30_less'];?></span>
    </li>
  </ul>
  <h3><?php _e('Session Calculations','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="average-session-value">
      <small><?php _e('Average session Value','oometrics');?></small>
      <strong class="value"><?php echo $data['average_ses_value'];?></strong>
    </li>
    <li class="average-duration">
      <small><?php _e('Average Duration','oometrics');?></small>
      <strong class="value"><?php echo $data['average_duration'];?></strong>
    </li>
    <li class="average-activities">
      <small><?php _e('Average Activities','oometrics');?></small>
      <strong class="value"><?php echo $data['average_activities'];?></strong>
    </li>
    <li class="mobile-activity">
      <small><?php _e('Mobile Devices','oometrics');?></small>
      <strong class="value"><?php echo $data['mobile_devices'];?></strong>
    </li>
    <li class="desktop-activity">
      <small><?php _e('Desktop Devices','oometrics');?></small>
      <strong class="value"><?php echo $data['desktop_devices'];?></strong>
    </li>
  </ul>
</div>


<div class="customer-activities oo-tab" id="customer-activities">
  <h3><?php _e('Activities log','oometrics');?></h3>
  <h2 class="session-value reports"><small><?php _e('Session value','oometrics');?></small><strong>0</strong></h2>
  <ul class="oo-info-details">
  </ul>

</div>
<div class="customer-profile oo-tab" id="customer-profile">
  <h3><?php _e('Billing Info','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="billing_first_name">
      <small><?php _e('First Name','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_last_name">
      <small><?php _e('Last Name','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_company">
      <small><?php _e('Company','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_phone">
      <small><?php _e('Phone','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_email">
      <small><?php _e('Email','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_country">
      <small><?php _e('Country','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_state">
      <small><?php _e('State','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_city">
      <small><?php _e('City','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_address_1">
      <small><?php _e('Address 1','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="billing_address_2">
      <small><?php _e('Address 2','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
  </ul>
  <h3><?php _e('Shipping Info','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="shipping_first_name">
      <small><?php _e('First Name','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_last_name">
      <small><?php _e('Last Name','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_company">
      <small><?php _e('Company','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_country">
      <small><?php _e('Country','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_state">
      <small><?php _e('State','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_city">
      <small><?php _e('City','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_address_1">
      <small><?php _e('Address 1','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
    <li class="shipping_address_2">
      <small><?php _e('Address 2','oometrics');?></small>
      <strong><?php _e('?','oometrics');?></strong>
    </li>
  </ul>
</div>
<div class="digital-profile oo-tab" id="digital-profile">
  <h3><?php _e('Device info','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="device-type">
      <small><?php _e('Type','oometrics');?></small>
      <strong><?php _e('Mobile','oometrics');?></strong>
    </li>
    <li class="device-brand">
      <small><?php _e('Brand','oometrics');?></small>
      <strong><?php _e('Apple','oometrics');?></strong>
    </li>
    <li class="device-browser">
      <small><?php _e('Browser','oometrics');?></small>
      <strong><?php _e('Chrome','oometrics');?></strong>
    </li>
    <li class="device-resolution">
      <small><?php _e('Resolution','oometrics');?></small>
      <strong><?php _e('1920x1080','oometrics');?></strong>
    </li>
  </ul>

  <h3><?php _e('Connection info','oometrics');?></h3>
  <ul class="oo-info-details">
    <li class="connection-ip">
      <small><?php _e('IP','oometrics');?></small>
      <strong><?php _e('127.0.0.1','oometrics');?></strong>
    </li>
    <li class="connection-referrer">
      <small><?php _e('Referrer','oometrics');?></small>
      <strong><?php _e('-','oometrics');?></strong>
    </li>
  </ul>
</div>
