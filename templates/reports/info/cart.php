<?php
$cart_total = $cart_content['cart_contents_total'];
$cart = $cart_content['cart'];
// reset($cart);
// $cart_session = key($cart);
$cart_items = empty($cart) ? 0 : count($cart);
?>
<div class="oo-cart-wrapper hide">
  <div class="oo-cart">
    <small><?php _e('Cart','oometrics');?></small><br />
    <strong class="oo-cart-items"><?php echo $cart_items;?></strong> items  <strong class="oo-cart-total"><?php echo wc_price($cart_total);?></strong> <?php echo get_option('woocommerce_currency'); ?>
  </div>
  <div class="oo-purchased">
    <small><?php _e('Purchased','oometrics');?></small><br />
    <strong class="oo-purchased-items"><?php echo $cart_items;?></strong> items  <strong class="oo-purchased-total"><?php echo wc_price($cart_total);?></strong> <?php echo get_option('woocommerce_currency'); ?>
  </div>
  <a class="oo-add-tocart-remotely">
    <i class="icon-add-to-cart large"></i>
  </a>
</div>
