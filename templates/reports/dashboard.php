<?php
$settings = get_option('oometrics_options');
$report = new OOReport();
// $current_session_obj = new OOSession();
// $current_session = $current_session_obj->ses_get_by('ses_hash','1bdd74b0c5a1eb241365f0bb27a2daf7');
// $car_caonten_raw = $current_session->ses_cart_session;
// $cart_content = unserialize($car_caonten_raw);
// print_r($cart_content);
?>
<input id="oo_ses_id" value="-1" type="hidden"/>
<div class="wrap">
  <div class="oo-dashboard-wrapper oo-reports">
    <div class="oo-dashboard-header">
    </div>
    <div class="oo-dashboard-main">
      <div class="oo-dashboard-left">
        <div class="oo-dashboard-left-left oo-chat">
          <div class="oo-dashboard-left-left-header">
            <?php require_once(OOMETRICS_PATH.'/templates/reports/chats/profile-info.php'); ?>
          </div>
          <div class="oo-dashboard-left-left-body">
            <?php require_once(OOMETRICS_PATH.'/templates/reports/chats/conversations.php'); ?>
          </div>

        </div>
        <div class="oo-dashboard-left-right reports">
          <div class="oo-dashboard-left-right-header">
            <?php require_once(OOMETRICS_PATH.'/templates/reports/info/header.php'); ?>
          </div>
          <div class="oo-dashboard-left-right-body oo-tab-content reports">
            <?php require_once(OOMETRICS_PATH.'/templates/reports/info/digital-tab.php'); ?>
          </div>
          <div class="oo-dashboard-left-right-footer">
            <?php if ( class_exists( 'WooCommerce' ) ) { require_once(OOMETRICS_PATH.'/templates/reports/info/cart.php');} ?>
          </div>

        </div>

      </div>
      <div class="oo-dashboard-sidebar">
        <div class="oo-dashboard-sidebar-header">
          <?php require_once(OOMETRICS_PATH.'/templates/reports/sidebar/header.php'); ?>
        </div>
        <div class="oo-dashboard-sidebar-body  oo-chat-conversations">
          <?php require_once(OOMETRICS_PATH.'/templates/reports/sidebar/body.php'); ?>
        </div>
        <div class="oo-dashboard-sidebar-footer">
          <?php require_once(OOMETRICS_PATH.'/templates/reports/sidebar/footer.php'); ?>
        </div>
      </div>
    </div>
    <div class="oo-dashboard-footer">
      <ul class="oo-nav list">
        <li class="oo-footer-logo"><a href="#"><img width="100" src="<?php echo OOMETRICS_URL;?>assets/images/oometrics-logo.svg"/></a></li>
        <li><a class="button button-link" href="<?php echo admin_url('admin.php?page=oometrics');?>"><?php _e('Dashboard', 'oometrics'); ?></a></li>
        <li><a class="button button-link" href="<?php echo admin_url('admin.php?page=oometrics-settings');?>"><?php _e('Settings', 'oometrics'); ?></a></li>
      </ul>
      <ul class="oo-nav list right">
      <li><a target="_blank" class="button button-link" href="https://github.com/arashsafaridev/oometrics"><?php _e('Get Started', 'oometrics'); ?></a></li>
      <li><a target="_blank" class="oo-get-pro button button-default button-small" href="https://github.com/arashsafaridev/oometrics"><strong>🤘 <?php _e('Donate!', 'oometrics'); ?></strong></a></li>
      </ul>
    </div>
  </div>

</div>
