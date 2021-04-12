<?php
$report = new OOReport();
$total_sale = $report->get_total_sales();
$total_sessions = $report->get_total_sessions();
$total_sessions = (empty($total_sessions)) ? 0 : $total_sessions;

$total_uniques = $report->get_total_uniques();
$total_uniques = (empty($total_uniques)) ? 0 : $total_uniques;

$total_orders = $report->get_total_orders();
$total_orders = (empty($total_orders)) ? 0 : $total_orders;

$options = get_option('oometrics_options');
$period_type = $options['period_time']['period_type'];
 ?>

<div class="oo-overview-heading">
  <ul class="oo-reports-overview">
    <?php if ( class_exists( 'WooCommerce' ) ) { ?>
    <li class="oo-total-sales">
      <small><?php _e('Sales','oometrics');?></small>
      <strong><?php echo wc_price($total_sale);?></strong>
    </li>
    <?php } ?>
    <li class="oo-total-sessions">
      <small><?php _e('Sessions','oometrics');?></small>
      <strong><?php echo $total_sessions;?></strong>
    </li>
    <li class="oo-total-uniques">
      <small><?php _e('Unique','oometrics');?></small>
      <strong><?php echo $total_uniques;?></strong>
    </li>
    <li class="oo-total-orders">
      <small><?php _e('Orders','oometrics');?></small>
      <strong><?php echo $total_orders;?></strong>
    </li>
  </ul>

</div>
<div class="oo-time-period">
  <select class="oo-session-options-nav" id="time-period">
    <option<?php echo ($period_type == 'last_hour') ? ' selected="selected"' : '';?> value="last_hour"><?php _e('Last hour','oometrics');?></option>
    <option<?php echo ($period_type == 'today') ? ' selected="selected"' : '';?>  value="today"><?php _e('Today','oometrics');?></option>
    <option<?php echo ($period_type == 'yesterday') ? ' selected="selected"' : '';?>  value="yesterday"><?php _e('Yesterday','oometrics');?></option>
    <option<?php echo ($period_type == 'last_week') ? ' selected="selected"' : '';?>  value="last_week"><?php _e('Last week','oometrics');?></option>
    <option<?php echo ($period_type == 'last_month') ? ' selected="selected"' : '';?>  value="last_month"><?php _e('Last month','oometrics');?></option>
    <option<?php echo ($period_type == 'last_3_months') ? ' selected="selected"' : '';?>  value="last_3_months"><?php _e('Last 3 month','oometrics');?></option>
    <option<?php echo ($period_type == 'last_year') ? ' selected="selected"' : '';?>  value="last_year"><?php _e('Last year','oometrics');?></option>
    <option<?php echo ($period_type == 'custom') ? ' selected="selected"' : '';?>  value="custom"><?php _e('Custom','oometrics');?></option>
  </select>


  <div class="oo-custom-time-period hide">
    <div class="oo-start-date">
      <input type="text" class="oo-datepicker" id="oo-start-date" placeholder="<?php _e('Start date','oometrics');?>" value="<?php echo date("d-m-Y", $options['period_time']['start_time']);?>"/>
    </div>
    <div class="oo-end-date">
      <input type="text" class="oo-datepicker" id="oo-end-date" placeholder="<?php _e('End date','oometrics');?>" value="<?php echo date("d-m-Y", $options['period_time']['end_time']);?>"/>
    </div>
    <button class="button button-primary" id="time-period-button"><?php _e('Go','oometrics');?></button>
  </div>
</div>
