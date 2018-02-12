<?php
    $siteurl = get_option( 'siteurl' );
    $check_key = wp_create_nonce('newsletter_send');

    $newsletter_data = $this->get_newsletter_data( $_REQUEST['newsletter_id'] );

    $groups = $this->get_groups();

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2><?php _e( 'Send Newsletter:', 'email-newsletter' ) ?> "<?php echo htmlspecialchars( $newsletter_data['subject'] );?>" <a href="?page=newsletters&amp;newsletter_builder_action=edit_newsletter&amp;newsletter_id=<?php echo $newsletter_data['newsletter_id'];?>&amp;template=<?php echo $newsletter_data['template'];?>&amp;return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="add-new-h2"><?php _e( 'Edit', 'email-newsletter' ) ?></a></h2>

        <p><?php _e( 'At this page you can send newsletter to the selected groups.', 'email-newsletter' ) ?></p>

        <?php

        if ( isset( $_REQUEST['send_id'] ) && 0 < $_REQUEST['send_id'] ) {
            $send_id        = $_REQUEST['send_id'];
            $newsletter_id  = $_REQUEST['newsletter_id'];

            $count_send_members = $this->get_count_send_members( $send_id );
        ?>
            <center>
                <p>The Newsletter was sent to <span id="count_sent">0</span> out of <?php echo $count_send_members; ?> members</p>
                <div class="enewsletter_progressbar">
                    <div id="progressbar">
                        <span id="progressbar_text">
                            <?php echo _e( "Sending", 'email-newsletter' ) ?>
                        </span>
                    </div>
                </div>
                <form method="post" action="" id="sending_form" >
                    <input type="hidden" name="newsletter_id" value="<?php echo $newsletter_id; ?>">
                    <input type="hidden" name="send_id" value="<?php echo $send_id; ?>">
                    <input type="hidden" name="action" value="send_newsletter">
                    <input type="hidden" name="cron" value="add_to_cron" />
					<p class="submit">
                    <input class="button button-secondary" type="button" id="send_pause" value="<?php echo _e( 'Pause', 'email-newsletter' ) ?>" />
                    <input class="button button-secondary" type="button" id="send_cron" value="<?php echo _e( 'Pause, and send by WP-CRON', 'email-newsletter' ) ?>" />
                    <input class="button button-secondary" type="button" id="send_cancel" value="<?php echo _e( 'Go Back', 'email-newsletter' ) ?>" />
					</p>
                </form>
            </center>

            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    var pause = 0;

                    jQuery( function() {
                        jQuery( "#progressbar" ).progressbar({
                            value: 0
                        });
                    });

                    jQuery( '#send_cron' ).click( function () {
                        pause = 1;
                        jQuery( '#sending_form' ).submit();
                    });

                    jQuery( '#send_cancel' ).click( function () {
                        pause = 1;
                        window.location.href = "?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&newsletter_id=<?php echo $newsletter_id; ?>";
                    });

                    jQuery( '#send_pause' ).click( function () {
                        if ( 1 == pause ) {
                            pause = 0;
                            jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Sending', 'email-newsletter' ) ?>' );
                            jQuery( this ).val( '<?php echo _e( "Pause", 'email-newsletter' ) ?>' );
                            jQuery( this ).send_email();
                        } else {
                            pause = 1;
                            jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Pause', 'email-newsletter' ) ?>' );
                            jQuery( this ).val( '<?php echo _e( "Continue", 'email-newsletter' ) ?>' );
                        }

                    });

                    var count_email = <?php echo $count_send_members ; ?>;
//                    var step = Math.round( 100 / count_email + 0.4 )
                    var step = 100 / count_email
                    var send = 1;

                    jQuery.fn.send_email = function ( ) {
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: 'action=send_email_to_member&send_id=<?php echo $send_id ; ?>&check_key=<?php echo $_REQUEST["check_key"] ; ?>',
                            success: function( html ){
                                if ( 'ok' == html.trim() ) {

                                    jQuery( '#count_sent' ).html( send );

                                    value = step * send;

                                    jQuery( "#progressbar" ).progressbar( "option", "value", value );

                                    send++;
                                    if ( 1 != pause )
                                        jQuery( this ).send_email();


                                } else if ( 'end' == html.trim()) {
                                     jQuery( "#send_pause" ).hide();
                                     jQuery( "#send_cron" ).hide();
                                     jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Done', 'email-newsletter' ) ?>' );
									 jQuery( "#send_cancel" ).val('finish');
                                     jQuery( ".ui-progressbar-value" ).fadeOut();
                                } else {
                                    alert( html );
                                }
                            }
                        });
                    };

                    jQuery( this ).send_email();

                });

            </script>

        <?php
        } else {
        ?>

        <form action="" method="post" id="send_form">
            <input type="hidden" name="newsletter_id" value="<?php echo $newsletter_data["newsletter_id"];?>">
            <input type="hidden" name="cron" id="cron" value="">
            <input type="hidden" name="cron_time" id="cron_time" value="" />
            <input type="hidden" name="check_key" id="check_key" value="<?php echo $check_key; ?>">
            <input type="hidden" name="action" value="send">
            <table cellpadding="10" cellspacing="10" class="widefat post table_slim">
				<thead>
					<tr>
						<th>
							<?php _e( 'Select which groups you would like to send to:', 'email-newsletter' ) ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
    						<p>
    							<label><input type="checkbox" name="all_members" value="1" /> <strong><?php _e( 'All Active Members', 'email-newsletter' ) ?></strong> (<?php echo $this->get_count_members();?>)</label>
    						</p>
                            <p>
    							<?php
    								$this->the_targets();
                                ?>
                            </p>
                            <p class="description">
                                <?php _e( 'Please keep in mind emails are not being sent to unsubscribed users.', 'email-newsletter' ) ?>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="dont_send_duplicate" value="1" checked="checked" />
                                    <?php _e( "Don't resend to people that had this newsletter sent to.", 'email-newsletter' ); ?>
                                </label>

                                <label>
                                    <input type="checkbox" name="send_to_bounced" value="1" />
                                    <?php _e( "Send to bounced members.", 'email-newsletter' ); ?>
                                </label>
                            </p>
						</td>
					</tr>
					<tr>
						<td>
                            <p>
                                <input class="button button-primary" type="submit" name="send" value="<?php echo _e( 'Send newsletter now', 'email-newsletter' ) ?>" />
                                <input class="button button-secondary" type="button" name="send" id="add_cron" value="<?php echo _e( 'Send in background (by CRON)', 'email-newsletter' ) ?>" />
                                <span id="timestamp">
                                    <?php _e( "Send:", 'email-newsletter' ); ?> <b><?php _e( "As fast as possible.", 'email-newsletter' ); ?></b>
                                </span>
                                <a href="#edit_timestamp" class="edit-timestamp" style="display: inline;">Edit</a>
                            </p>
                                <div id="timestampdiv">
                                    <div class="timestamp-wrap">
                                        <?php
                                        global $wp_locale;

                                        $time_adj = current_time('timestamp');
                                        $cur_jj = gmdate( 'd', $time_adj );
                                        $cur_mm = gmdate( 'm', $time_adj );
                                        $cur_aa = gmdate( 'Y', $time_adj );
                                        $cur_hh = gmdate( 'H', $time_adj );
                                        $cur_mn = gmdate( 'i', $time_adj );

                                        $month = "";
                                        for ( $i = 1; $i < 13; $i = $i +1 ) {
                                            $monthnum = zeroise($i, 2);
                                            $month .= "\t\t\t" . '<option value="' . $monthnum . '"';
                                            if ( $i == $cur_mm )
                                                $month .= ' selected="selected"';
                                            /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
                                            $month .= '>' . sprintf( __( '%1$s-%2$s' ), $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ) . "</option>\n";
                                        }
                                        ?>
                                        <select id="mm" name="mm">
                                            <?php echo $month; ?>
                                        </select>
                                        <input type="text" id="jj" name="jj" value="<?php echo $cur_jj; ?>" size="2" maxlength="2" autocomplete="off">,
                                        <input type="text" id="aa" name="aa" value="<?php echo $cur_aa; ?>" size="4" maxlength="4" autocomplete="off"> @
                                        <input type="text" id="hh" name="hh" value="<?php echo $cur_hh; ?>" size="2" maxlength="2" autocomplete="off"> :
                                        <input type="text" id="mn" name="mn" value="<?php echo $cur_mn; ?>" size="2" maxlength="2" autocomplete="off">
                                    </div>
                                    <p>
                                        <a href="#edit_timestamp" class="save-timestamp button"><?php _e( "Set", 'email-newsletter' ); ?></a>
                                        <a href="#edit_timestamp" class="cancel-timestamp"><?php _e( "Cancel/Unset", 'email-newsletter' ); ?></a>
                                    </p>
                                </div>
                        </td>
                    </tr>
				</tbody>
            </table>

        </form>

        <?php
            $sends = $this->get_sends( $_REQUEST['newsletter_id'] );
            $total = array ( 'send' => 0, 'cron' => 0, 'sent' => 0, 'bounced' => 0, 'opened' => 0 );
        ?>

        <h3><?php _e( 'Previous sending:', 'email-newsletter' ) ?></h3>

        <table width="700px" class="widefat post" id="send_list" style="width:95%;">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Start Date', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Waiting send (manually)', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Waiting send (cron)', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Sent To', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Opened', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Bounced', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Actions', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
        <?php
        $i = 0;
        if ( $sends ) {
		?>
			<tbody>
		<?php
            foreach( $sends as $send ) {
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="vertical-align: middle;">
                   <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $send['start_time'])); ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_send_members'];
                        $total['send'] += $send['count_send_members'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_send_cron'];
                        if(is_numeric($send['status']))
                            echo ' <small>('.get_date_from_gmt(date('Y-m-d H:i:s', $send['status'])).')</small>';
                        $total['cron'] += $send['count_send_cron'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_sent'];
                        $total['sent'] += $send['count_sent'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_opened'];
                        $total['opened'] += $send['count_opened'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_bounced'];
                        $total['bounced'] += $send['count_bounced'];
                    ?>
                </td>
                <td style="vertical-align: middle; width: 250px;">
                <?php
                    if ( 0 < $send['count_send_cron'] ) :
                ?>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&cron=remove_from_cron&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>">
                            <input class="button button-secondary" type="button" value="<?php echo _e( "Remove from CRON list", 'email-newsletter' ) ?>" />
                        </a>
                <?php
                    endif;

                    if ( 0 < $send['count_send_members'] ) :
                ?>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&cron=add_to_cron&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>">
                            <input class="button button-secondary" type="button" value="<?php echo _e( "Add to CRON list", 'email-newsletter' ) ?>" />
                        </a>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>&check_key=<?php echo $check_key; ?>">
                            <input class="button button-primary" type="button" value="<?php _e( 'Continue Send', 'email-newsletter' ) ?>" />
                        </a>
                <?php
                    endif;
                ?>
                </td>
            </tr>
        <?php
            }
		?>
			</tbody>
		<?php
		}
        ?>
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Total:', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php echo $total['send']; ?>
                    </th>
                    <th>
                       <?php echo $total['cron']; ?>
                    </th>
                    <th>
                        <?php echo $total['sent']; ?>
                    </th>
                    <th>
                        <?php echo $total['opened']; ?>
                    </th>
                    <th>
                        <?php echo $total['bounced']; ?>
                    </th>
                    <th>
                    </th>
                </tr>
            </thead>
        </table>

        <?php } ?>

    </div><!--/wrap-->