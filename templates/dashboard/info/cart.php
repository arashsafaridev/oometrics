<?php
$cart_total = 0;
$cart_items = 0;
?>
<div class="oo-cart-wrapper">
  <div class="oo-cart">
    <small><?php _e('Cart','oometrics');?></small><br />
    <strong class="oo-cart-items"><?php echo $cart_items;?></strong> <?php _e('Items','oometrics');?>  <br /><strong class="oo-cart-total"><?php echo wc_price($cart_total);?></strong>
  </div>
  <div class="oo-purchased">
    <small><?php _e('Purchased','oometrics');?></small><br />
    <strong class="oo-purchased-items"><?php echo $cart_items;?></strong> <?php _e('Items','oometrics');?>  <br /><strong class="oo-purchased-total"><?php echo wc_price($cart_total);?></strong>
  </div>
  <a class="oo-add-tocart-remotely">
    <i class="icon icon-add-to-cart large"></i>
  </a>
</div>
