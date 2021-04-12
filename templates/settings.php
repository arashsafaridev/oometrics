<?php
  $oo_settings = get_option('oometrics_options');
 ?>
<div class="wrap">
  <div id="oometrics-settings">
    <form method="post" id="oometrics-admin-form">
      <input type="hidden" name="action" id="oometrics-action" value="oo_store_admin_data" />
      <input type="hidden" name="_wpnonce" id="oometrics-security" value="" />
        <div class="inside oo-settings">
            <div class="postbox">
              <img width="200" src="<?php echo OOMETRICS_URL;?>assets/images/oometrics-logo.svg"/>
              <br />
              <h1><?php _e('OOMetrics Settings','oometrics');?></h1>

              <hr/>
              <?php
              $main_user_value = $oo_settings['main_user'];
              $users = get_users(array('role__in'=>array('administrator','contributor'),'number'=>-1));
               ?>
               <h2><?php _e('Essesntial Settings','oometrics');?></h2>
               <p><?php _e('Customize more to collect your real feedback!','oometrics');?></p>

            <table class="form-table">

                <tr valign="top">
                <th scope="row"><?php _e('Main User','oodev');?></th>
                <td>
                  <select name="oometrics_main_user">
                    <option value=""><?php _e('Choose an admin');?></option>
                    <?php if(!empty($users)) {
                      foreach ($users as $key => $user) {
                        ?>
                        <option <?php echo ($main_user_value == $user->ID) ? 'selected="selected" ' : '';?>value="<?php echo $user->ID;?>"><?php echo $user->user_login;?></option>
                    <?php }
                    } ?>

                  </select> <button class="button button-secondary" type="button" id="oometrics-reset-admin-session"><?php _e('Reset Admin Session','oometrics');?></button>
                </td>
                </tr>

                <tr valign="top">
                <th scope="row"><?php _e('Admin interval','oodev');?></th>
                <td>
                  <input type="text" name="oometrics_admin_interval" value="<?php echo esc_attr( $oo_settings['admin_interval'] ); ?>" />
                  <p><?php _e('Back-end interval, how frequent the dashboard should be updated. It should be more than chat interval. Give it at least 3 or 4 times bigger','oometrics');?></p>
                </td>
                </tr>

                <tr valign="top">
                <th scope="row"><?php _e('Chat interval','oodev');?></th>
                <td><input type="text" name="oometrics_chat_interval" value="<?php echo esc_attr( $oo_settings['chat_interval'] ); ?>" /></td>
                </tr>

                <tr valign="top">
                <th scope="row"><?php _e('Session interval','oodev');?></th>
                <td>
                  <input type="text" name="oometrics_session_interval" value="<?php echo esc_attr( $oo_settings['session_interval'] ); ?>" />
                  <p><?php _e('Front-end interval, how frequent the users should be updated. It should be more than chat interval. Give it at least 3 or 4 times bigger','oometrics');?></p>
                </td>
                </tr>

                <tr valign="top">
                  <th scope="row"><?php _e('Session lifetime (seconds)','oodev');?></th>
                  <td>
                    <input type="text" name="oometrics_session_lifetime" value="<?php echo esc_attr( $oo_settings['session_lifetime'] ); ?>" />
                    <p><?php _e('Expire sessions after one day. Both cookie life time and database session will use this value. 86400 seconds(1 day)','oometrics');?></p>
                  </td>
                </tr>

                <tr valign="top">
                  <th scope="row"><?php _e('Show on live(seconds)','oodev');?></th>
                  <td>
                    <input type="text" name="oometrics_live_lifetime" value="<?php echo esc_attr( $oo_settings['live_lifetime'] ); ?>" />
                    <p><?php _e('Show session on dashboard which has last activity less than 300 seconds (5 minutes)','oometrics');?></p>
                  </td>
                </tr>

            </table>
            <hr />
            <h2><?php _e('Chat User Interface','oometrics');?></h2>
            <p><?php _e('You can set your interface options here','oometrics');?></p>

            <table class="form-table">
              <tr valign="top">
                <th scope="row"><?php _e('Welcome Message','oodev');?></th>
                <td>
                  <textarea rows="3" name="oometrics_chat_welcome_message" style="width:100%">
                    <?php echo esc_attr( $oo_settings['chat_welcome_message'] ); ?>
                  </textarea>
                  <p><?php _e('A small text in chat content','oometrics');?></p>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Chat open icon','oodev');?></th>
                <td>
                  <input type="text" name="oometrics_chat_icon_open" value="<?php echo esc_attr( $oo_settings['chat_icon_open'] ); ?>" style="width:100%"/>
                  <p><?php _e('The icon image URL when chat is closed.  SVG format recommended','oometrics');?></p>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Chat close icon','oodev');?></th>
                <td>
                  <input type="text" name="oometrics_chat_icon_close" value="<?php echo esc_attr( $oo_settings['chat_icon_close'] ); ?>" style="width:100%"/>
                  <p><?php _e('The icon image URL when chat is opened.  SVG format recommended','oometrics');?></p>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Icon Position','oodev');?></th>
                <td>
                  <select name="oometrics_chat_position"/>
                    <option value='bottom-left' <?php echo ($oo_settings['chat_position'] == 'bottom-left' ) ? ' selected="selected"' : '' ?>><?php _e('Bottom Left','oometrics');?></option>
                    <option value='bottom-right' <?php echo ($oo_settings['chat_position'] == 'bottom-right' ) ? ' selected="selected"' : '' ?>><?php _e('Bottom Right','oometrics');?></option>
                    <option value='top-left' <?php echo ($oo_settings['chat_position'] == 'top-left' ) ? ' selected="selected"' : '' ?>><?php _e('Top Left','oometrics');?></option>
                    <option value='top-right' <?php echo ($oo_settings['chat_position'] == 'top-right' ) ? ' selected="selected"' : '' ?>><?php _e('Top Right','oometrics');?></option>
                  </select>
                </td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Icon vertical and horizontal distance','oodev');?></th>
                <td>
                  <div style="display:flex">
                    <div style="margin-right:2rem">
                      <input type="text" name="oometrics_chat_position_v" placeholder="<?php _e('Vertical','oometrics');?>" value="<?php echo esc_attr( $oo_settings['chat_position_v'] ); ?>" />
                      <p><?php _e('Use with units like 30px, 2rem, 3em and ...','oometrics');?></p>
                    </div>
                    <div>
                    <input type="text" name="oometrics_chat_position_h" placeholder="<?php _e('Horizontal','oometrics');?>" value="<?php echo esc_attr( $oo_settings['chat_position_h'] ); ?>" />
                    <p><?php _e('Use with units like 30px, 2rem, 3em and ...','oometrics');?></p>
                    </div>
                  </div>
                </td>
              </tr>
                <tr valign="top">
                  <th scope="row"><?php _e('Enable Chat?','oodev');?></th>
                  <td>
                    <select name="oometrics_chat_enabled"/>
                      <option value='yes' <?php echo ($oo_settings['chat_enabled'] == 'yes' ) ? ' selected="selected"' : '' ?>><?php _e('Yes','oometrics');?></option>
                      <option value='no' <?php echo ($oo_settings['chat_enabled'] == 'no' ) ? ' selected="selected"' : '' ?>><?php _e('No','oometrics');?></option>
                    </select>
                  </td>
                </tr>
                <tr valign="top">
                  <th scope="row"><?php _e('Admin Chat reply text eidtor?','oodev');?></th>
                  <td>
                    <select name="oometrics_chat_editor"/>
                      <option value='tinyMCE' <?php echo ($oo_settings['chat_editor'] == 'tinymce' ) ? ' selected="selected"' : '' ?>><?php _e('tinyMCE','oometrics');?></option>
                      <option value='simple' <?php echo ($oo_settings['chat_editor'] == 'simple' ) ? ' selected="selected"' : '' ?>><?php _e('simple','oometrics');?></option>
                    </select>
                  </td>
                </tr>
            </table>
            <hr />
            <h2><?php _e('Add Filters','oometrics');?></h2>
            <p><?php _e('Currently bots/crawlers, internal requests like cronjobs and unknown visits are filtering by OOMetrics, soon there will be an option to activate or deactivate manually!','oometrics');?></p>
            <table class="form-table">
                <tr valign="top">
                  <th scope="row"><?php _e('Remove Zero Value Sessions (like most bots and failures; For more real data)?','oodev');?></th>
                  <td>
                    <select name="oometrics_clean_zero_values"/>
                      <option value='yes' <?php echo ($oo_settings['clean_zero_values'] == 'yes' ) ? ' selected="selected"' : '' ?>><?php _e('Yes','oometrics');?></option>
                      <option value='no' <?php echo ($oo_settings['clean_zero_values'] == 'no' ) ? ' selected="selected"' : '' ?>><?php _e('No','oometrics');?></option>
                    </select>
                  </td>
                </tr>

            </table>
            <hr/>
            <h2><?php _e('Privacy','oometrics');?></h2>
            <p><?php _e('For your users privacy','oometrics');?></p>
            <table class="form-table">
                <tr valign="top">
                  <th scope="row"><?php _e('Notify them about tracking','oodev');?></th>
                  <td>
                    <select name="oometrics_tracking_notification"/>
                      <option value='yes' <?php echo ($oo_settings['tracking_notification'] == 'yes' ) ? ' selected="selected"' : '' ?>><?php _e('Yes','oometrics');?></option>
                      <option value='no' <?php echo ($oo_settings['tracking_notification'] == 'no' ) ? ' selected="selected"' : '' ?>><?php _e('No','oometrics');?></option>
                    </select>
                  </td>
                </tr>
                <tr valign="top">
                  <th scope="row"><?php _e('The message','oodev');?></th>
                  <td>
                      <textarea rows="5" name="oometrics_tracking_message" style="width:100%"><?php echo $oo_settings['tracking_message'];?></textarea>
                  </td>
                </tr>
            </table>
            <hr />

            <p><?php _e('Back to','oometrics');?> <a href="<?php echo admin_url('admin.php?page=oometrics');?>"><?php _e('Dashboard','oometrics');?></a></p>
          </div>
        </div>
        <div class="oo-settings-notification"></div>
        <button class="button button-primary button-hero" id="oometrics-admin-save" type="submit">
            <?php _e( 'Save Settings', 'oometrics' ); ?>
        </button>


    </form>
</div>
