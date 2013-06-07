<?php
    global $current_user;
    $this->set_current_user();

    $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );

    $member_data = $this->get_member( $member_id );

    if ( "" != $member_data['unsubscribe_code'] ) {
        $groups = $this->get_groups();
        $member_groups = $this->get_memeber_groups( $member_id );
        if ( ! is_array( $member_groups ) )
            $member_groups = array();

    }
    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>
    <script type="text/javascript">
        jQuery( document ).ready( function() {

            //save subscribes
            jQuery( "#save_subscribes" ).click( function() {
                jQuery( "#newsletter_action" ).val( 'save_subscribes' );
                jQuery( "#subscribes_form" ).submit();

            });

            //unsubscribe
            jQuery( "#unsubscribe" ).click( function() {
                jQuery( "#newsletter_action" ).val( 'unsubscribe' );
                jQuery( "#subscribes_form" ).submit();

            });
        });
    </script>

    <div class="wrap">
        <h2><?php _e( 'My Subscriptions', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'At this page you can Subscribe or Unsubcribe to Newsletters', 'email-newsletter' ) ?></p>
        <?php
        if ( "" != $member_data['unsubscribe_code'] ) {
        ?>
        <form action="" method="post" name="subscribes_form" id="subscribes_form" >
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="unsubscribe_code" value="<?php echo $member_data['unsubscribe_code']; ?>" />
            <table id="subscribes_table" class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <?php _e( 'Newsletters:', 'email-newsletter' ) ?>
                    </th>
                    <td>
                        <?php
                            $groups = $this->get_groups();
							$groups_echo = array();
                            if ( $groups )
                                foreach( $groups as $group ){
                                    if ( false === array_search ( $group['group_id'], $member_groups ) )
                                        $checked = '';
                                    else
                                        $checked = 'checked="checked"';
										
                                    $groups_echo[] = '<input type="checkbox" name="e_newsletter_groups_id[]" ' . $checked . ' value="' . $group['group_id'] . '" /><label>' . $group['group_name'] . '</label>';
                                }
							echo implode('<br/>', $groups_echo);
                        ?>
                    </td>
                </tr>
            </table>
			<p class="submit">
				<input class="button button-primary" type="button" id="save_subscribes" value="<?php _e( 'Save Subscribes', 'email-newsletter' ) ?>" />
				<input class="button button-secondary" type="button" id="unsubscribe" value="<?php _e( 'Unsubscribe from all newsletters', 'email-newsletter' ) ?>" />
			</p>
        </form>
        <?php
        } else {
        ?>
        <form action="" method="post" name="" id="" >
            <input type="hidden" name="newsletter_action" id="subscribe" value="subscribe" />
            <input class="button button-primary" type="submit" value="<?php _e( 'Subscribe on Newsletters', 'email-newsletter' ) ?>" />
        </form>
        <?php
        }
        ?>

    </div><!--/wrap-->