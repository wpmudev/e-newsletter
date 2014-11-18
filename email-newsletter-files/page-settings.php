<?php
    $page_title =  __( 'eNewsletter Settings', 'email-newsletter' );

    if ( !$this->settings ) {
        $page_title =  __( 'eNewsletter plugin Installation', 'email-newsletter' );
        $mode = "install";
    }

    $default_tab = isset($mode) ? 'tabs-2' : 'tabs-1';

	global $email_newsletter;
	if (!class_exists('WpmuDev_HelpTooltips')) require_once $email_newsletter->plugin_dir . '/email-newsletter-files/class.wd_help_tooltips.php';
	$tips = new WpmuDev_HelpTooltips();
	$tips->set_icon_url($email_newsletter->plugin_url.'/email-newsletter-files/images/information.png');


    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }
?>


    <div class="wrap">
        <h2><?php echo $page_title; ?></h2>

        <form method="post" name="settings_form" id="settings_form" action"admin_url( 'admin.php?page=newsletters-settings');">
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="newsletter_setting_page" id="newsletter_setting_page" value="#tabs-1" />
            <?php if(isset($mode)) echo '<input type="hidden" name="mode"  value="'.$mode.'" />'; ?>

            <div class="newsletter-settings-tabs">

					<h3 id="newsletter-tabs" class="nav-tab-wrapper">
						<a href="#tabs-1" class="nav-tab nav-tab-active"><?php _e( 'General Settings', 'email-newsletter' ) ?></a>
						<a href="#tabs-2" class="nav-tab"><?php _e( 'Outgoing Email Settings', 'email-newsletter' ) ?></a>
						<a href="#tabs-3" class="nav-tab"><?php _e( 'Bounce Settings', 'email-newsletter' ) ?></a>
						<a href="#tabs-4" class="nav-tab"><?php _e( 'User Permissions', 'email-newsletter' ) ?></a>
						 <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
						 	<a class="nav-tab" href="#tabs-5"><?php _e( 'Uninstall', 'email-newsletter' ) ?></a>
						 <?php endif; ?>
					</h3>
                    <div id="tabs-1" class="tab">
						<h3><?php _e( 'Default Info Settings', 'email-newsletter' ) ?></h3>

						<table class="settings-form form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'From name:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" name="settings[from_name]" value="<?php echo isset($this->settings['from_name']) ? esc_attr($this->settings['from_name']) : get_option( 'blogname' );?>" />
                                    <span class="description"><?php _e( 'Default "from" name when sending newsletters.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Branding:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[branding_html]" class="branding-html" ><?php echo isset($this->settings['branding_html']) ? esc_textarea($this->settings['branding_html']) : "";?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Default branding html/text will be added to the top of each email.', 'email-newsletter' ) ?> <?php _e( 'It can be easily changed for each newsletter', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Contact information:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[contact_info]" class="contact-information" ><?php echo isset($this->settings['contact_info']) ? esc_textarea($this->settings['contact_info']) : "";?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Default contact information will be added to the bottom of each email.', 'email-newsletter' ) ?> <?php _e( 'It can be easily changed for each newsletter', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'View email in browser:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[view_browser]" class="view-browser" ><?php echo isset($this->settings['view_browser']) ? esc_textarea($this->settings['view_browser']) : __( '<a href="{VIEW_LINK}" title="View e-mail in browser">View e-mail in browser</a>', 'email-newsletter' ); ?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'This HTML message will be visible before newsletter starts so user have ability to display email in browser. Use "{VIEW_LINK}" as link. Leave blank to disable.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Preview mail:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" name="settings[preview_email]" value="<?php echo isset($this->settings['preview_email']) ? esc_attr($this->settings['preview_email']) : $this->settings['from_email'];?>" />
                                    <span class="description"><?php _e( 'Default email adress to send previews to.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>

                        <h3><?php _e( 'Default User Subscribe/Unsubscribe Settings', 'email-newsletter' ) ?></h3>

                        <table class="settings-form form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Double Opt In:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <label for="settings[double_opt_in]"><?php _e( 'Enable:', 'email-newsletter' ) ?></label>
                                    <input type="checkbox" name="settings[double_opt_in]" value="1" <?php checked('1',$this->settings['double_opt_in']); ?> />
                                    <label for="settings[double_opt_in]"><?php _e( 'Subject:', 'email-newsletter' ) ?></label>
                                    <input type="text" class="regular-text" name="settings[double_opt_in_subject]" value="<?php echo (isset($this->settings['double_opt_in_subject']) && !empty($this->settings['double_opt_in_subject'])) ? esc_attr($this->settings['double_opt_in_subject']) : __( 'Please confirm your email', 'email-newsletter' ).' ('.get_bloginfo('name').')'; ?>" />
                                    <span class="description"><?php _e( 'If enabled, members will get confirmation email with configured subject to subscribe to newsletters (only for not registered users)', 'email-newsletter' ) ?>. <?php _e( 'Do not leave subject blank.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Default Groups:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <?php
                                    $groups = !isset($mode) ? $this->get_groups() : 0;

                                    if ( $groups ) {
                                        $this->settings['subscribe_groups'] = isset($this->settings['subscribe_groups']) ? explode(',', $this->settings['subscribe_groups']) : array();
                                    ?>
                                        <?php foreach( $groups as $group ) : ?>
                                            <label for="member[groups_id][]">
                                                <input type="checkbox" name="settings[subscribe_groups][<?php echo $group['group_id'];?>]" value="<?php echo $group['group_id'];?>" <?php if(in_array($group['group_id'], $this->settings['subscribe_groups'])) echo 'checked'; ?>/>
                                                <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                                            </label>
                                            <br />
                                        <?php endforeach; ?>
                                    <?php
                                    }
                                    else {
                                    ?>
                                        <p><?php _e( 'You have not created any member groups yet.', 'email-newsletter' ); ?></p>
                                    <?php
                                    }
                                    ?>
                                    <span class="description"><?php _e( 'Default groups to add user to after subscription (even if nothing is selected in subscribe widget).', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Welcome Newsletter:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <select name="settings[subscribe_newsletter]">
                                        <option value=""><?php _e( 'Disable', 'email-newsletter' ) ?></option>
                                        <?php
                                        $newsletters = ($mode != 'install') ? $this->get_newsletters() : 0;

                                        if($newsletters)
                                            foreach( $newsletters as $key => $newsletter ) {
                                                if (strlen($newsletter['subject']) > 30)
                                                $newsletter['subject'] = substr($newsletter['subject'], 0, 27) . '...';
                                                echo '<option value="'.$newsletter['newsletter_id'].'" '.selected( $this->settings['subscribe_newsletter'], $newsletter['newsletter_id'], false).'>'.$newsletter['newsletter_id'].': '.$newsletter['subject'].'</option>';
                                            }
                                        ?>
                                    </select>
                                    <span class="description"><?php _e( 'Default newsletter that will be sent on user subscription.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'WordPress User registration:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <?php
                                    if(!isset($this->settings['wp_user_register_subscribe']))
                                        $this->settings['wp_user_register_subscribe'] = 1;
                                    ?>
                                    <select name="settings[wp_user_register_subscribe]">
                                        <option value="1"<?php selected( $this->settings['wp_user_register_subscribe'], 1); ?>><?php _e( 'Subscribe', 'email-newsletter' ) ?></option>
                                        <option value="0"<?php selected( $this->settings['wp_user_register_subscribe'], 0); ?>><?php _e( 'Disable', 'email-newsletter' ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'Choose if user registering(with WordPress) to your site is automatically subscribed to newsletter.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Subscribed Page ID:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input class="small-text" type="number" name="settings[subscribe_page_id]" value="<?php echo isset($this->settings['subscribe_page_id']) ? esc_attr($this->settings['subscribe_page_id']) : '';?>" />
                                    <span class="description"><?php _e( 'Add ID of page that you want to display after user subscribes. You can use [enewsletter_subscribe_message] shortcode inside it to display subscribe status message. Leave blank to disable.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Unsubscribe Page ID:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input class="small-text" type="number" name="settings[unsubscribe_page_id]" value="<?php echo isset($this->settings['unsubscribe_page_id']) ? esc_attr($this->settings['unsubscribe_page_id']) : '';?>" />
                                    <span class="description"><?php _e( 'Add ID of page that you want to display after user unsubscribes. You can use [enewsletter_unsubscribe_message] shortcode inside it to display unsubscribe status message. Leave blank to disable.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>

                    </div>

                    <div id="tabs-2" class="tab">
                        <h3><?php _e( 'Outgoing SMTP Email Settings', 'email-newsletter' ) ?></h3>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo _e( 'Email Sending Method:', 'email-newsletter' ); ?>
                                    </th>
                                    <td>
                                        <label id="tip_smtp">
                                            <input type="radio" name="settings[outbound_type]" id="smtp_method" value="smtp" class="email_out_type" <?php echo (!isset($this->settings['outbound_type']) || $this->settings['outbound_type'] == 'smtp') ? 'checked="checked"' : '';?> /><?php echo _e( 'SMTP (recommended)', 'email-newsletter' );?>
                                        </label>

										<?php $tips->bind_tip(__("The SMTP method allows you to use your SMTP server (or Gmail, Yahoo, Hotmail etc. ) for sending newsletters and emails. It's usually the best choice, especially if your host has restrictions on sending email and to help you to avoid being blacklisted as a SPAM sender",'email-newsletter'), '#tip_smtp'); ?>

                                        <label id="tip_php">
                                            <input type="radio" name="settings[outbound_type]" value="mail" class="email_out_type" <?php echo (isset($this->settings['outbound_type']) && $this->settings['outbound_type'] == 'mail') ? 'checked="checked"' : '';?> /><?php echo _e( 'PHP mail', 'email-newsletter' );?>
                                        </label>
										<?php $tips->bind_tip(__( "This method uses php functions for sending newsletters and emails. Be careful because some hosts may set restrictions on using this method. If you can't edit settings of your server, we recommend to use the SMTP method for optimal results!", 'email-newsletter' ), '#tip_php'); ?>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'From email:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <input type="text" id="smtp_from" class="regular-text" name="settings[from_email]" value="<?php $default_domain = parse_url(home_url()); echo esc_attr( (isset($this->settings['from_email']) && !empty($this->settings['from_email'])) ? $this->settings['from_email'] : 'newsletter@'.$default_domain['host'] );?>" />
                                        <span class="description"><?php _e( 'Default "from" email address when sending newsletters.', 'email-newsletter' ) ?></span><br/>
                                        <span class="red description"><?php _e( 'Note: for SMTP method - in "From email" you should only use email related with your SMTP server!', 'email-newsletter' ) ?></span><br/>
                                        <span class="red description"><?php _e( 'Note2: for PHP mail method - in "From email" you should only use email with domain configured for your server!', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>

                            <tbody class="email_out email_out_smtp">
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Outgoing Server', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" id="smtp_host" class="regular-text" name="settings[smtp_host]" value="<?php echo isset($this->settings['smtp_host']) ? esc_attr($this->settings['smtp_host']) : '';?>" />
                                        <span class="description"><?php _e( 'The hostname for the SMTP account, eg: mail.', 'email-newsletter' ) ?><?php echo $_SERVER['HTTP_HOST'];?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Username:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" id="smtp_username" class="regular-text" name="settings[smtp_user]" value="<?php echo isset($this->settings['smtp_user']) ? esc_attr($this->settings['smtp_user']) : '';?>" />
                                        <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Password:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="password" id="smtp_password" class="regular-text" name="settings[smtp_pass]" value="<?php echo ( isset( $this->settings['smtp_pass'] ) && '' != $this->settings['smtp_pass'] ) ? '********' : ''; ?>" />
                                        <span class="description"><?php _e( '(leave blank for none)', 'email-newsletter' ); if(isset( $this->settings['smtp_pass'] ) && '' != $this->settings['smtp_pass']) _e( ' (For security, saved password lenght does not match preview)', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Port', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" id="smtp_port" name="settings[smtp_port]" value="<?php echo isset($this->settings['smtp_port']) ? esc_attr($this->settings['smtp_port']) : '';?>" />
                                        <span class="description"><?php _e( 'Defaults to 25.  Gmail uses 465 or 587', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Secure SMTP?', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['smtp_secure_method']))
                                            $this->settings['smtp_secure_method'] = 0;
                                        ?>
                                        <select id="smtp_security" name="settings[smtp_secure_method]" >
                                            <option value="0" <?php selected('0',$this->settings['smtp_secure_method']); ?>><?php _e( 'None', 'email-newsletter' ) ?></option>
                                            <option value="ssl" <?php selected('ssl',$this->settings['smtp_secure_method']); ?>><?php _e( 'SSL', 'email-newsletter' ) ?></option>
                                            <option value="tls" <?php selected('tls',$this->settings['smtp_secure_method']); ?>><?php _e( 'TLS', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( 'Choose an optional type of connection', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><div id="test_smtp_loading"></div></th>
                                    <td>
                                        <input class="button button-secondary" type="button" name="" id="test_smtp_conn" value="<?php _e( 'Test Connection', 'email-newsletter' ) ?>" />
                                        <span class="description"><?php _e( 'We will send test email on configured from email address.', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="settings-form form-table">
                            <h3><?php _e( 'CRON Email Sending Settings', 'email-newsletter' ) ?></h3>
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'CRON Email Sending:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['cron_enable']))
                                            $this->settings['cron_enable'] = 1;
                                        ?>
                                        <select name="settings[cron_enable]" >
                                            <option value="1" <?php selected('1',esc_attr($this->settings['cron_enable'])); ?>><?php _e( 'Enable', 'email-newsletter' ) ?></option>
                                            <option value="2" <?php selected('2',esc_attr($this->settings['cron_enable'])); ?>><?php _e( 'Disable', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( "('Disable' - not use CRON for sending emails)", 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Limitations:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <?php _e( 'Send', 'email-newsletter' ) ?>
                                        <input class="small-text" type="number" name="settings[send_limit]" value="<?php echo isset($this->settings['send_limit']) ? esc_attr($this->settings['send_limit']) : '';?>" />
                                        <small class="description"><?php _e( '(0 or blank for unlimited)', 'email-newsletter' ) ?></small>
                                        <?php _e( 'emails per', 'email-newsletter' ) ?>
                                        <?php
                                        if(!isset($this->settings['cron_time']))
                                            $this->settings['cron_time'] = 1;
                                        ?>
                                        <select name="settings[cron_time]" >
                                            <option value="1" <?php echo ( 1 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Hour', 'email-newsletter' ) ?></option>
                                            <option value="2" <?php echo ( 2 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Day', 'email-newsletter' ) ?></option>
                                            <option value="3" <?php echo ( 3 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Month', 'email-newsletter' ) ?></option>
                                        </select>
                                        <?php _e( 'and wait', 'email-newsletter' ) ?>
                                        <input class="small-text" type="number" name="settings[cron_wait]" value="<?php echo isset($this->settings['cron_wait']) ? esc_attr($this->settings['cron_wait']) : 1;?>" />
                                        <?php _e( 'second(s) between each email', 'email-newsletter' ) ?>.
                                    </td>
                                </tr>
							</tbody>
                        </table>
                    </div>

                    <div id="tabs-3" class="tab">
                        <h3><?php _e( 'Bounce Settings', 'email-newsletter' ) ?></h3>
						<?php
						if(!function_exists('imap_open')) {
						?>

	                    <p><?php _e( 'Please enable "IMAP" PHP extension for bounce to work.', 'email-newsletter' ) ?></p>

						<?php
						}
						else {
						?>
                        <p><?php _e( 'This controls how bounce emails are handled by the system. Please create a new separate POP3 email account to handle bounce emails. Enter these POP3 email details below.', 'email-newsletter' ) ?></p>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Email Address:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_email]" id="bounce_email" class="regular-text" value="<?php echo isset($this->settings['bounce_email']) ? esc_attr($this->settings['bounce_email']) : '';?>" />
                                        <span class="description"><?php _e( 'Email address where bounce emails will be sent by default (might be overwritten by server)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Host:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" name="settings[bounce_host]" id="bounce_host" class="regular-text" value="<?php echo isset($this->settings['bounce_host']) ? esc_attr($this->settings['bounce_host']) : '';?>" />
                                        <span class="description"><?php _e( 'The hostname for the POP3 account, eg: mail.', 'email-newsletter' ) ?><?php echo $_SERVER['HTTP_HOST'];?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Port', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" name="settings[bounce_port]" id="bounce_port" value="<?php echo isset($this->settings['bounce_port']) ? esc_attr($this->settings['bounce_port']) : '110';?>" size="2" />
                                        <span class="description"><?php _e( 'Defaults to 110 or 995 with SSL enabled', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Username:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" name="settings[bounce_username]" id="bounce_username" class="regular-text" value="<?php echo isset($this->settings['bounce_username']) ? esc_attr($this->settings['bounce_username']) : '';?>" />
                                        <span class="description"><?php _e( 'Username for this bounce email account (usually the same as the above email address) ', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Password:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="password" name="settings[bounce_password]" id="bounce_password" class="regular-text" value="<?php echo ( isset( $this->settings['bounce_password'] ) && '' != $this->settings['bounce_password'] ) ? '********' : ''; ?>" />
                                        <span class="description"><?php _e( 'Password to access this bounce email account', 'email-newsletter' ); if(isset( $this->settings['bounce_password'] ) && '' != $this->settings['bounce_password']) _e( ' (For security, saved password lenght does not match preview)', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Secure POP3?:', 'email-newsletter' );?>
                                    </th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['bounce_security']))
                                            $this->settings['bounce_security'] = '';
                                        ?>
                                        <select name="settings[bounce_security]" id="bounce_security" >
                                            <option value="" <?php echo ( '' == $this->settings['bounce_security'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'None', 'email-newsletter' ) ?></option>
                                            <option value="/ssl" <?php echo ( '/ssl' == $this->settings['bounce_security'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'SSL', 'email-newsletter' ) ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><div id="test_bounce_loading"></div></th>
                                    <td>
                                        <input class="button button-secondary" type="button" name="" id="test_bounce_conn" value="<?php _e( 'Test Connection', 'email-newsletter' ) ?>" />
                                        <span class="description"><?php _e( 'We will send test email on Bounce address and will try read this email and delete after(this part might not be possible)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
						<?php
						}
						?>
                    </div>
					<div id="tabs-4" class="tab">
						<?php global $wp_roles; ?>
						<h3><?php _e('User Permissions','email-newsletter'); ?></h3>
						<p><?php _e('Here you can set your desired permissions for each user role on your site','email-newsletter'); ?></p>
						<div class="metabox-holder" id="newsletter_user_permissions">
							<?php foreach($wp_roles->get_names() as $name => $label) : ?>
								<?php if($name == 'administrator') continue; ?>
								<?php $role_obj = get_role($name); ?>
								<div class="postbox">
									<h3 class="hndle"><span><?php echo $label; ?></span></h3>
									<div class="inside">
										<table class="widefat permissionTable">
											<thead>
												<tr valign="top">
													<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
													<th><?php _e('Capability','email-newsletter'); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($this->capabilities as $key => $label) : ?>
													<tr valign="top">
														<th class="check-column" scope="row">
															<input id="<?php echo $name.'_'.$key; ?>" type="checkbox" value="1" name="settings[email_caps][<?php echo $key; ?>][<?php echo $name; ?>]" <?php checked(isset($wp_roles->roles[$name]['capabilities'][$key]) ? $wp_roles->roles[$name]['capabilities'][$key] : '',true); ?> />
														</th>
														<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col">
															<label for="<?php echo $name.'_'.$key; ?>"><?php echo $label; ?></label>
														</th>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
                        <h3><?php _e( 'Groups permissions', 'email-newsletter' ) ?></h3>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Public group access:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['non_public_group_access']))
                                            $this->settings['non_public_group_access'] = 'registered';
                                        ?>
                                        <select id="non_public_group_access" name="settings[non_public_group_access]" >
                                            <option value="registered" <?php selected('registered',$this->settings['non_public_group_access']); ?>><?php _e( 'Registered users', 'email-newsletter' ) ?></option>
                                            <option value="nobody" <?php selected('nobody',$this->settings['non_public_group_access']); ?>><?php _e( 'Nobody', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( 'Choose what type of user can subscribe to non public groups. <small>Keep in mind that users can still be added to all type of groups in eNewsletter members admin page.</small>', 'email-newsletter' ) ?></span>
                                   </td>
                                </tr>
                            </tbody>
                        </table>
					</div>
                    <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
                    <div id="tabs-5" class="tab">
                        <h3><?php _e( 'Uninstall', 'email-newsletter' ) ?></h3>
                        <p><?php _e( 'Here you can delete all data associated with the plugin from the database.', 'email-newsletter' ) ?></p>
                        <p>
                            <input class="button button-secondary" type="button" name="uninstall" id="uninstall" value="<?php _e( 'Delete data', 'email-newsletter' ) ?>" />
                            <span class="description" style="color: red;"><?php _e( "Delete all plugin's data from DB and remove enewsletter-custom-themes folder.", 'email-newsletter' ) ?></span>
                            <div id="uninstall_confirm" style="display: none;">
								<p>
									<span class="description"><?php _e( 'Are you sure?', 'email-newsletter' ) ?></span>
									<br />
									<input class="button button-secondary" type="button" name="uninstall" id="uninstall_no" value="<?php _e( 'No', 'email-newsletter' ) ?>" />
									<input class="button button-secondary" type="button" name="uninstall" id="uninstall_yes" value="<?php _e( 'Yes', 'email-newsletter' ) ?>" />
								</p>
                            </div>
                        </p>
                    </div>
                    <?php endif; ?>

            </div><!--/.newsletter-tabs-settings-->

            <p class="submit">
            <?php if ( isset( $mode ) && "install" == $mode ) { ?>
                <input class="button button-primary" type="button" name="install" id="install" value="<?php _e( 'Install', 'email-newsletter' ) ?>" />
            <?php } else { ?>
                <input class="button button-primary" type="button" name="save" value="<?php _e( 'Save all Settings', 'email-newsletter' ) ?>" />
            <?php } ?>
			</p>

        </form>

    </div><!--/wrap-->