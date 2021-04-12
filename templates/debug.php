<?php
  $oo_settings = get_option('oometrics_options');
  global $wpdb;
  $debug_table = $wpdb->prefix.'oometrics_debug';

  $debugs = $wpdb->get_results(
      $wpdb->prepare(
          "SELECT * FROM $debug_table",
           array()
      )
  );

 ?>

<div class="wrap">
  <div id="oometrics-settings">
        <div class="inside oo-debug">
            <div class="postbox">
              <?php
              foreach ($debugs as $key => $debug) {
                $server_var = unserialize($debug->debug_server_var);
                $request_var = unserialize($debug->debug_requestr_var);
                ?>
                <a href="#debug-id-<?php echo $debug->debug_id;?>" class="oo-debug-details" style="display:inline-block;width:100%;padding:1em"><?php echo $debug->debug_ses_id;?></a>
                <pre id="debug-id-<?php echo $debug->debug_id;?>" style="display:none;padding:0 2em;background:#f9f9f9;font-size:11px;line-height:18px;">
                  <div class="server-var">
<?php print_r($server_var);?>
                  </div>
                  <div class="request-var">
<?php print_r($request_var);?>
                  </div>
                </pre>
                <?php
              }
               ?>
            </div>
    </div>
  </div>
</div>
<script>
  jQuery(document).ready(function($){
    $('.oo-debug-details').click(function(e){
      e.preventDefault();
      var id = $(this).attr('href');
      if($(id).hasClass('show')){
        $(id).hide();
      } else {
        $(id).show();
      }
      $(id).toggleClass('show');
    })
  });
</script>
