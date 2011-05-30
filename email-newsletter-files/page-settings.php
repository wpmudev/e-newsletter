<?php

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

    <script language="JavaScript">

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



        jQuery(".tooltip_img[title]").tooltip();
        });


    </script>


    <div class="wrap">
        <h2><?php echo $page_title; ?></h2>

        <form method="post" action="" name="settings_form" id="settings_form" >
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="mode"  value="<?php echo $mode; ?>" />
            <h3><?php _e( 'General Settings', 'email-newsletter' ) ?></h3>
            <table class="settings-form">
                <tr>
                    <td>
                        <?php _e( 'Double Opt In:', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <input type="checkbox" name="settings[double_opt_in]" value="1" <?php echo (isset($settings['double_opt_in'])&&$settings['double_opt_in']) ? ' checked':'';?>>
                        <span class="description"><?php _e( 'Yes, members will get confirmation email upon signing up to newsletters (only for not registered users)', 'email-newsletter' ) ?></span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php _e( 'From email:', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <input type="text" name="settings[from_email]" value="<?php echo htmlspecialchars( $settings['from_email'] ? $settings['from_email'] : get_option( 'admin_email' ) );?>">
                        <span class="description"><?php _e( 'Default "from" email address when sending newsletters.', 'email-newsletter' ) ?></span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php _e( 'From name:', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <input type="text" name="settings[from_name]" value="<?php echo htmlspecialchars( $settings['from_name'] ? $settings['from_name'] : get_option( 'blogname' ) );?>">
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


            <script type="text/javascript">
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

            <br />
            <h3><?php _e( 'Outgoing Email Settings', 'email-newsletter' ) ?></h3>
            <table class="settings-form">
                <tbody>
                    <tr>
                        <td>
                            <?php echo _e( 'Email Sending Method:', 'email-newsletter' );?>
                        </td>
                        <td>
                            <label>
                                <input type="radio" name="settings[outbound_type]" id="smtp_method" value="smtp" class="email_out_type" <?php echo ( $settings['outbound_type'] == 'smtp' || ! $settings['outbound_type']) ? 'checked="checked"' : '';?>><?php echo _e( 'SMTP (recommended)', 'email-newsletter' );?>
                                <img class="tooltip_img" src="<?php echo $this->plugin_url . "email-newsletter-files/images/"; ?>info_small.png" title="<?php echo _e( "The SMTP method allows you to use your SMTP server (or Gmail, Yahoo, Hotmail etc. ) for sending newsletters and emails. It's usually the best choice, especially if your host has restrictions on sending email and to help you to avoid being blacklisted as a SPAM sender.", 'email-newsletter' );?>"/>
                            </label>
                            &nbsp;&nbsp;&nbsp;
                            <label>
                                <input type="radio" name="settings[outbound_type]" value="mail" class="email_out_type" <?php echo $settings['outbound_type'] == 'mail' ? 'checked="checked"' : '';?>><?php echo _e( 'php mail', 'email-newsletter' );?>
                                <img class="tooltip_img" src="<?php echo $this->plugin_url . "email-newsletter-files/images/"; ?>info_small.png" title="<?php echo _e( "This method uses php functions for sending newsletters and emails. Be careful because some hosts may set restrictions on using this method. If you can't edit settings of your server, we recomended using the SMTP method for optimal results!", 'email-newsletter' );?>"/>
                            </label>
                        </td>
                    </tr>
                </tbody>

                <tbody class="email_out email_out_smtp">
                    <tr>
                        <td><?php _e( 'SMTP Outgoing Server', 'email-newsletter' ) ?>:</td>
                        <td>
                            <input type="text" id="smtp_host" name="settings[smtp_host]" value="<?php echo htmlspecialchars($settings['smtp_host']);?>">
                            <span class="description"><?php _e( '(eg: relay-hosting.secureserver.net, mail.yoursite.com:1234, ssl://smtp.gmail.com:465)', 'email-newsletter' ) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e( 'SMTP Username:', 'email-newsletter' ) ?></td>
                        <td>
                            <input type="text" name="settings[smtp_user]" value="<?php echo htmlspecialchars($settings['smtp_user']);?>">
                            <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e( 'SMTP Password:', 'email-newsletter' ) ?></td>
                        <td>
                            <input type="text" name="settings[smtp_pass]" value="<?php echo htmlspecialchars($settings['smtp_pass']);?>">
                            <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ) ?></span>
                        </td>
                    </tr>
                </tbody>
                <tbody>
                    <tr>
                        <td>
                        </td>
                        <td>
                            <br />
                            <?php if ( "install" != $mode ) { ?>
                                <input type="button" name="save" value="<?php _e( 'Save Settings', 'email-newsletter' ) ?>">
                            <?php } else { ?>
                                <input type="button" name="save" value="<?php _e( 'Save and Continue', 'email-newsletter' ) ?>">
                            <?php } ?>
                        </td>
                    </tr>
                </tbody>
            </table>

        </form>
    </div><!--/wrap-->