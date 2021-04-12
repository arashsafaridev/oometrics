<?php
$settings = get_option('oometrics_options');
$orderby = $settings['live_sort_by'];
 ?>
<ul class="oo-session-list-nav">
  <li <?php echo ($orderby != 'value' && $orderby != 'live') ? 'class="active"': '';?>><a href="#" data-orderby="live"><?php _e('Live','oometrics');?></a></li>
  <li <?php echo ($orderby == 'value') ? 'class="active"': '';?>><a href="#" data-orderby="value"><?php _e('Value','oometrics');?></a></li>
  <li class="intelligence" <?php echo ($orderby == 'intelligence') ? 'class="active"': '';?>><a data-orderby="intelligence"><?php _e('Intelligence','oometrics');?></a></li>
</ul>

<ul class="oo-session-list">

</ul>
