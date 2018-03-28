<?php
    $current_user = wp_get_current_user();

    $settings = $this->settings;

    $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );

    $member_data = $this->get_member( $member_id );

    if ( "" != $member_data['unsubscribe_code'] ) {
        $groups = $this->get_groups();
        $member_groups = $this->get_memeber_groups( $member_id );
        if ( ! is_array( $member_groups ) )
            $member_groups = array();

    }

    $only_public = (isset($settings['non_public_group_access']) && $settings['non_public_group_access'] == 'nobody') ? 1 : 0;
    $groups = $this->get_groups($only_public);
    $groups_echo = array();

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2><?php _e( 'My Subscriptions', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'At this page you can Subscribe or Unsubscribe to Newsletters', 'email-newsletter' ) ?></p>
        <?php
        if ( "" != $member_data['unsubscribe_code']) {
        ?>
        <form action="" method="post" name="subscribes_form" id="subscribes_form" >
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="unsubscribe_code" value="<?php echo $member_data['unsubscribe_code']; ?>" />
            <?php
            if($groups) {
            ?>
                <table id="subscribes_table" class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php _e( 'Newsletters:', 'email-newsletter' ) ?>
                        </th>
                        <td>
                            <?php
                                    foreach( $groups as $group ){
                                        if ( false === array_search ( $group['group_id'], $member_groups ) )
                                            $checked = '';
                                        else
                                            $checked = 'checked="checked"';

                                        $groups_echo[] = '<label><input type="checkbox" name="e_newsletter_groups_id[]" ' . $checked . ' value="' . $group['group_id'] . '" />' . $group['group_name'] . '</label>';
                                    }
    							echo implode('<br/>', $groups_echo);
                            ?>
                        </td>
                    </tr>
                </table>
            <?php
            }
            ?>
			<p class="submit">
				<?php if ( $groups ) { ?><input class="button button-primary" type="button" id="save_subscribes" value="<?php _e( 'Save Subscribes', 'email-newsletter' ) ?>" /><?php } ?>
				<input class="button button-secondary" type="button" id="unsubscribe" value="<?php _e( 'Unsubscribe from all newsletters', 'email-newsletter' ) ?>" />
			</p>
        </form>
        <?php
        } else {
        ?>
        <form action="" method="post" name="" id="" >
            <p class="submit">
                <input type="hidden" name="newsletter_action" id="subscribe" value="subscribe" />
                <input class="button button-primary" type="submit" value="<?php _e( 'Subscribe on Newsletters', 'email-newsletter' ) ?>" />
            </p>
        </form>
        <?php
        }
        ?>

    </div><!--/wrap-->