<?php
$report = new OOReport();
$total_activities = $report->get_total_activities();
$total_activities = (empty($total_activities)) ? 0 : $total_activities;
 ?>
 <ul class="oo-info-nav reports">
   <li class="oo-total-activities">
         <span class="oo-total-label"><?php _e('Activities','oometrics');?></span><br />
         <span class="oo-total-value"><?php echo $total_activities;?></span>
   </li>
   <li>
     <a href="#customer-activities">
       <i class="icon icon-activity-log large"></i>
       <strong><?php _e('Customer Activities','oometrics');?></strong>
     </a>
   </li>
   <li>
     <a href="#customer-profile">
       <i class="icon icon-customer-profile large"></i>
       <strong><?php _e('Customer Profile','oometrics');?></strong>
     </a>
   </li>
   <li>
     <a href="#digital-profile">
       <i class="icon icon-digital-profile large"></i>
       <strong><?php _e('Digital Profile','oometrics');?></strong>
     </a>
   </li>
 </ul>
