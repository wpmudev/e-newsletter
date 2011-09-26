<?php
    $siteurl = get_option( 'siteurl' );

    $settings = $this->get_settings();

    $page_title =  __( 'Settings of eNewsletter plugin', 'email-newsletter' );

    if ( ! $settings ) {
        $page_title =  __( 'Install Settings of eNewsletter plugin', 'email-newsletter' );

        $mode = "install";

    }

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>

    <script type="text/javascript">

        function simple_tooltip(target_items, name){
            jQuery(target_items).each(function(i){
                jQuery("body").append("<div class='"+name+"' id='"+name+i+"'><p>"+jQuery(this).attr('title')+"</p></div>");
                var my_tooltip = jQuery("#"+name+i);

                jQuery(this).removeAttr("title").mouseover(function(){
                    my_tooltip.css({opacity:0.8, display:"none"}).fadeIn(400);
                }).mousemove(function(kmouse){
                    my_tooltip.css({left:kmouse.pageX+15, top:kmouse.pageY+15});
                }).mouseout(function(){
                    my_tooltip.fadeOut(400);
                });
            });
        }



        jQuery( document ).ready( function() {

            jQuery( "input[type=button][name='save']" ).click( function() {
                if ( "" == jQuery( "#smtp_host" ).val() && jQuery( "#smtp_method" ).attr( 'checked' ) ) {
                    alert('<?php _e( 'Please write SMTP Outgoing Server, or select another Sending Method!', 'email-newsletter' ) ?>');
                    return false;
                }

                jQuery( "#newsletter_action" ).val( "save_settings" );
                jQuery( "#settings_form" ).submit();
            });

            //install plugin data
            jQuery( "#install" ).click( function() {
                if ( "" == jQuery( "#smtp_host" ).val() && jQuery( "#smtp_method" ).attr( 'checked' ) ) {
                    alert('<?php _e( 'Please write SMTP Outgoing Server, or select another Sending Method!', 'email-newsletter' ) ?>');
                    return false;
                }

                jQuery( "#newsletter_action" ).val( "install" );
                jQuery( "#settings_form" ).submit();
                return false;

            });



            //uninstall plugin data
            jQuery( "#uninstall_yes" ).click( function() {
                jQuery( "#newsletter_action" ).val( "uninstall" );
                jQuery( "#settings_form" ).submit();
                return false;

            });

            jQuery( "#uninstall" ).click( function() {
                jQuery( "#uninstall_confirm" ).show( );
                return false;
            });

            jQuery( "#uninstall_no" ).click( function() {
                jQuery( "#uninstall_confirm" ).hide( );
                return false;
            });



            jQuery(".tooltip_img[title]").tooltip();


            //Creating tabs
            jQuery(function() {
                jQuery( "#tabs" ).tabs();
            });



            //Test connection to bounces email
            jQuery( "#test_bounce_conn" ).click( function() {
                var bounce_email    = jQuery( "#bounce_email" ).val();
                var bounce_host     = jQuery( "#bounce_host" ).val();
                var bounce_port     = jQuery( "#bounce_port" ).val();
                var bounce_username = jQuery( "#bounce_username" ).val();
                var bounce_password = jQuery( "#bounce_password" ).val();

                jQuery( "body" ).css( "cursor", "wait" );
                jQuery( "#test_bounce_conn" ).attr( 'disabled', true );
                jQuery.ajax({
                    type: "POST",
                    url: "<?php echo $siteurl;?>/wp-admin/admin-ajax.php",
                    data: "action=test_bounces&bounce_email=" + bounce_email + "&bounce_host=" + bounce_host + "&bounce_port=" + bounce_port + "&bounce_username=" + bounce_username + "&bounce_password=" + bounce_password,
                    success: function(html){
                        jQuery( "body" ).css( "cursor", "default" );
                        jQuery( "#test_bounce_conn" ).attr( 'disabled', false );
                        alert( html );
                    }
                 });
            });







        });


        function set_out_option() {
            jQuery('.email_out_type' ).each( function() {
                if( jQuery( this )[0].checked ){
                    jQuery( '.email_out' ).hide();
                    jQuery( '.email_out_' + jQuery( this ).val() ).show();
                }
            });
        }

        jQuery( function() {
            set_out_option();
            jQuery( '.email_out_type' ).change( function() {
                set_out_option();
                if( jQuery( this )[0].checked ){
                    jQuery( '.email_out' ).hide();
                    jQuery( '.email_out_' + jQuery( this ).val() ).show();
                }
            });
        });




    </script>


    <div class="wrap">
        <h2><?php echo $page_title; ?></h2>

        <form method="post" action="" name="settings_form" id="settings_form" >
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="mode"  value="<?php echo $mode; ?>" />
            <div id="newsletter-tabs" class="newsletter-settings-tabs">
                <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs">
                    <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                        <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tabs-1"><?php _e( 'General Settings', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-2"><?php _e( 'Outgoing Email Settings', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-3"><?php _e( 'Bounce Settings', 'email-newsletter' ) ?></a></li>
                        <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-4"><?php _e( 'Uninstall', 'email-newsletter' ) ?></a></li>
                        <?php endif; ?>
                    </ul>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="tabs-1">
                        <h3><?php _e( 'General Settings', 'email-newsletter' ) ?></h3>
                        <table class="settings-form">
                            <tr>
                                <td>
                                    <?php _e( 'Double Opt In:', 'email-newsletter' ) ?>
                                </td>
                                <td>
                                    <input type="checkbox" name="settings[double_opt_in]" value="1" <?php echo (isset($settings['double_opt_in'])&&$settings['double_opt_in']) ? ' checked':'';?> />
                                    <span class="description"><?php _e( 'Yes, members will get confirmation email to subscribe to newsletters (only for not registered users)', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'From email:', 'email-newsletter' ) ?>
                                </td>
                                <td>
                                    <input type="text" name="settings[from_email]" value="<?php echo htmlspecialchars( $settings['from_email'] ? $settings['from_email'] : get_option( 'admin_email' ) );?>" />
                                    <span class="description"><?php _e( 'Default "from" email address when sending newsletters.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'From name:', 'email-newsletter' ) ?>
                                </td>
                                <td>
                                    <input type="text" name="settings[from_name]" value="<?php echo htmlspecialchars( $settings['from_name'] ? $settings['from_name'] : get_option( 'blogname' ) );?>" />
                                    <span class="description"><?php _e( 'Default "from" name when sending newsletters.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'Contact information:', 'email-newsletter' ) ?>
                                </td>
                                <td>
                                    <textarea name="settings[contact_info]" class="contact-information" ><?php echo $settings['contact_info'] ? $settings['contact_info'] : "";?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Default contact information will be added to the bottom of each email', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-2">
                        <h3><?php _e( 'Outgoing Email Settings', 'email-newsletter' ) ?></h3>
                        <table class="settings-form">
                            <tbody>
                                <tr>
                                    <td>
                                        <?php echo _e( 'Email Sending Method:', 'email-newsletter' );?>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="radio" name="settings[outbound_type]" id="smtp_method" value="smtp" class="email_out_type" <?php echo ( $settings['outbound_type'] == 'smtp' || ! $settings['outbound_type']) ? 'checked="checked"' : '';?> /><?php echo _e( 'SMTP (recommended)', 'email-newsletter' );?>
                                            <img class="tooltip_img" src="<?php echo $this->plugin_url . "email-newsletter-files/images/"; ?>info_small.png" title="<?php echo _e( "The SMTP method allows you to use your SMTP server (or Gmail, Yahoo, Hotmail etc. ) for sending newsletters and emails. It's usually the best choice, especially if your host has restrictions on sending email and to help you to avoid being blacklisted as a SPAM sender.", 'email-newsletter' );?>"/>
                                        </label>
                                        &nbsp;&nbsp;&nbsp;
                                        <label>
                                            <input type="radio" name="settings[outbound_type]" value="mail" class="email_out_type" <?php echo $settings['outbound_type'] == 'mail' ? 'checked="checked"' : '';?> /><?php echo _e( 'php mail', 'email-newsletter' );?>
                                            <img class="tooltip_img" src="<?php echo $this->plugin_url . "email-newsletter-files/images/"; ?>info_small.png" title="<?php echo _e( "This method uses php functions for sending newsletters and emails. Be careful because some hosts may set restrictions on using this method. If you can't edit settings of your server, we recommend to use the SMTP method for optimal results!", 'email-newsletter' );?>"/>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>

                            <tbody class="email_out email_out_smtp">
                                <tr>
                                    <td><?php _e( 'SMTP Outgoing Server', 'email-newsletter' ) ?>:</td>
                                    <td>
                                        <input type="text" id="smtp_host" name="settings[smtp_host]" value="<?php echo htmlspecialchars($settings['smtp_host']);?>" />
                                        <span class="description"><?php _e( '(eg: ssl://smtp.gmail.com:465)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'SMTP Username:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[smtp_user]" value="<?php echo htmlspecialchars($settings['smtp_user']);?>" />
                                        <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'SMTP Password:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[smtp_pass]" value="<?php echo htmlspecialchars($settings['smtp_pass']);?>" />
                                        <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                                <tr>
                                    <td>
                                        <?php _e( 'Limit send:', 'email-newsletter' ) ?>
                                        <span class="description"><?php _e( ' (CRON)', 'email-newsletter' ) ?></span>
                                    </td>
                                    <td>
                                        <input type="text" name="settings[send_limit]" value="<?php echo htmlspecialchars($settings['send_limit']);?>" />
                                        <span class="description"><?php _e( '(0 or blank for unlimited)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Emails per', 'email-newsletter' ) ?>
                                        <span class="description"><?php _e( ' (CRON)', 'email-newsletter' ) ?></span>
                                    </td>
                                    <td>
                                        <select name="settings[cron_time]" >
                                            <option value="1" <?php echo ( 1 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >Never</option>
                                            <option value="2" <?php echo ( 2 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >5 mins</option>
                                            <option value="3" <?php echo ( 3 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >10 mins</option>
                                            <option value="4" <?php echo ( 4 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >15 mins</option>
                                            <option value="5" <?php echo ( 5 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >30 mins</option>
                                            <option value="6" <?php echo ( 6 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >1 hour</option>
                                            <option value="7" <?php echo ( 7 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >3 hours</option>
                                            <option value="8" <?php echo ( 8 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >6 hours</option>
                                            <option value="9" <?php echo ( 9 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >12 hours</option>
                                            <option value="10" <?php echo ( 10 == $settings['cron_time'] ) ? 'selected="selected"' : ''; ?> >1 day</option>
                                        </select>
                                        <span class="description"><?php _e( "('Never' - not use CRON for sending emails)", 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                        </table>
                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-3">
                        <h3><?php _e( 'Bounce Settings', 'email-newsletter' ) ?></h3>
                        <p><?php _e( 'This controls how bounce emails are handled by the system. Please create a new separate POP3 email account to handle bounce emails. Enter these POP3 email details below.', 'email-newsletter' ) ?></p>
                        <table cellpadding="5">
                            <tbody>
                                <tr>
                                    <td><?php _e( 'Email Address:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_email]" id="bounce_email" value="<?php echo htmlspecialchars($settings['bounce_email']);?>" />
                                        <span class="description"><?php _e( 'email address where bounce emails will be sent by default', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'POP3 Host:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_host]" id="bounce_host" value="<?php echo htmlspecialchars($settings['bounce_host']);?>" />:
                                        <input type="text" name="settings[bounce_port]" id="bounce_port" value="<?php echo htmlspecialchars($settings['bounce_port']?$settings['bounce_port']:110);?>" size="2" />
                                        <span class="description"><?php _e( 'the hostname for the POP3 account, eg: mail.', 'email-newsletter' ) ?><?php echo $_SERVER['HTTP_HOST'];?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'POP3 Username:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_username]" id="bounce_username" value="<?php echo htmlspecialchars($settings['bounce_username']);?>" />
                                        <span class="description"><?php _e( 'username for this bounce email account (usually the same as the above email address) ', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'POP3 Password:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_password]" id="bounce_password" value="<?php echo htmlspecialchars($settings['bounce_password']);?>" />
                                        <span class="description"><?php _e( 'password to access this bounce email account', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <br />
                                        <input type="button" name="" id="test_bounce_conn" value="<?php _e( 'Test Connection', 'email-newsletter' ) ?>" />
                                        <span class="description"><?php _e( 'We will send test email on Bounce address and will try read this email', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-4">
                        <h3><?php _e( 'Uninstall', 'email-newsletter' ) ?></h3>
                        <p><?php _e( 'Here you can delete all data associated with the plugin from the database.', 'email-newsletter' ) ?></p>
                        <table cellpadding="5">
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td>
                                        <br />
                                        <input type="button" name="uninstall" id="uninstall" value="<?php _e( 'Delete data', 'email-newsletter' ) ?>" />
                                        <span class="description" style="color: red;"><?php _e( "Delete all plugin's data from DB.", 'email-newsletter' ) ?></span>
                                        <div id="uninstall_confirm" style="display: none;">
                                            <span class="description"><?php _e( 'Are you sure?', 'email-newsletter' ) ?></span>
                                            <br />
                                            <input type="button" name="uninstall" id="uninstall_no" value="<?php _e( 'No', 'email-newsletter' ) ?>" />
                                            <input type="button" name="uninstall" id="uninstall_yes" value="<?php _e( 'Yes', 'email-newsletter' ) ?>" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div><!--/#tabs-->

            </div><!--/#newsletter-tabs-->

            <br />
            <?php if ( isset( $mode ) && "install" == $mode ) { ?>
                <input type="button" name="install" id="install" value="<?php _e( 'Install', 'email-newsletter' ) ?>" />
            <?php } else { ?>
                <input type="button" name="save" value="<?php _e( 'Save all Settings', 'email-newsletter' ) ?>" />
            <?php } ?>

        </form>

    </div><!--/wrap-->