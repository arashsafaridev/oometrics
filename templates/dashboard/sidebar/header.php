<ul class="oo-overview">
  <?php if ( class_exists( 'WooCommerce' ) ) { ?>
  <li class="oo-total-sales">
    <small><?php _e('24h Sales','oometrics');?></small>
    <strong><?php echo wc_price($total_sale ? $total_sale : 0);?></strong>
  </li>
  <?php } ?>
  <li class="oo-total-online">
    <small><?php _e('Online','oometrics');?></small>
    <strong><?php echo $online_users;?></strong>
  </li>
  <li class="oo-total-users">
    <small><?php _e('Users','oometrics');?></small>
    <strong><?php echo $unique_users;?></strong>
  </li>
  <li class="oo-total-views">
    <small><?php _e('Views','oometrics');?></small>
    <strong><?php echo $pageviews;?></strong>
  </li>
</ul>
