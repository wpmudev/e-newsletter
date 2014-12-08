<?php
	global $email_builder;

    $arg['limit'] = 'LIMIT 0,5';
    $arg['orderby'] = 'newsletter_id';
    $arg['order'] = 'desc';
    $newsletters = $this->get_newsletters($arg);

    $arg = array();
    $arg['limit'] = 'LIMIT 0,5';
    $arg['orderby'] = 'join_date';
    $arg['order'] = 'desc';
    $members = $this->get_members( $arg );


    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }
?>

    <div class="wrap">
        <h2><?php _e( 'Newsletters Dashboard', 'email-newsletter' ) ?></h2>
		<p><?php _e( 'Take a look at newsletter reports.', 'email-newsletter' ) ?></p>

		<h3><?php _e( 'Statistics for current members:', 'email-newsletter' ) ?></h3>
        <table class="widefat post table_slim table_centered">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Newsletters', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Members', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Groups', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Sent', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Opened', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Bounced', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Waiting', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'CRON', 'email-newsletter' ) ?>
                        (<?php echo wp_next_scheduled( $this->cron_send_name ) ? __( 'enabled', 'email-newsletter') : __( 'disabled', 'email-newsletter'); ?>)

                    </th>
                </tr>
            </thead>

            <tr class="alternate">
                <?php $stats = $this->get_count_stats(); ?>
                <td>
                    <?php echo $this->get_newsletters("", 1); ?>
                </td>
                <td>
                    <?php echo $this->get_count_members(); ?>
                </td>
                <td>
                    <?php echo $this->get_count_groups(); ?>
                </td>
                <td>
                    <?php echo $stats['sent']; ?>
                </td>
                <td>
                    <?php echo $stats['opened']; ?>
                </td>
                <td>
                    <?php echo $stats['bounced']; ?>
                </td>
                <td>
                    <?php echo $this->get_count_send_members( '', 'waiting_send' ); ?>
                </td>
                <td>
                    <?php echo $this->get_count_send_members( '', 'by_cron' ); ?>
                </td>
            </tr>
        </table>

        <?php
        if ( $newsletters ) {
        ?>
        <h3><?php _e( '5 Latest Newsletters:', 'email-newsletter' ) ?></h3>
        <table class="widefat post newsletter_table_center">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Create Date', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Email Subject', 'email-newsletter' ) ?>
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
                    <th class="newsletters_actions">
                        <?php _e( 'Actions', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
        <?php
            $i = 0;

            foreach( $newsletters as $newsletter ) {
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="text-align: left;">
                    <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $newsletter['create_date'])); ?>
                </td>        
                <td style="text-align: left;">
                    <?php echo $newsletter['subject']; ?>
                </td>

                <td>
                     <?php echo $newsletter['count_sent']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td>
                     <?php echo $newsletter['count_opened']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td>
                     <?php echo $newsletter['count_bounced']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td style="width: 180px;">
                    <a href="?page=newsletters-dashboard&amp;newsletter_action=delete_newsletter&amp;newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input class="button button-secondary" type="button" value="<?php _e( 'Delete', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="<?php echo $email_builder->generate_builder_link($newsletter['newsletter_id']); ?>">
                        <input class="button button-secondary" type="button" value="<?php _e( 'Edit', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="?page=newsletters-dashboard&amp;newsletter_action=send_newsletter&amp;newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input class="button button-primary" type="button" value="<?php _e( 'Send', 'email-newsletter' ) ?>" />
                    </a>
                </td>
            </tr>
        <?php
            }
        ?>

        </table>
        <?php
        }

        if(current_user_can('create_newsletter')) { ?>
		<p class="submit">
            <a class="button button-primary" href="<?php echo admin_url( 'admin.php?newsletter_builder_action=create_newsletter' ); ?>"><?php _e( 'Create New Newsletter', 'email-newsletter' ) ?></a>
        </p>
        <?php } ?>

        <h3><?php _e( '5 Latest Members:', 'email-newsletter' ) ?></h3>
           <table id="members_table" class="widefat post">
                <thead>
                    <tr>
                        <th class="members-wp manage-column column-name">
                            <?php _e( 'WP ID', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-email manage-column column-name">

                                <span><?php _e( 'Email Address', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-name manage-column column-name">

                                <span><?php _e( 'Name', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-join manage-column column-name">

                                <span><?php _e( 'Join Date', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Sent', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Opened', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Bounced', 'email-newsletter' ) ?></span>

                        </th>
                        <th class="members-groups manage-column column-name">
                            <?php _e( 'Groups', 'email-newsletter' ) ?>
                        </th>
                    </tr>
                </thead>
            <?php
            $i = 0;
            if ( $members )
                foreach( $members as $member ) {
                    if ( $i % 2 == 0 )
                        echo "<tr class='alternate'>";
                    else
                        echo "<tr class='' >";

                    $i++;

                    $member['member_nicename'] = $member['member_fname'];
                    $member['member_nicename'] .= $member['member_lname'] ? ' ' . $member['member_lname'] : '';

            ?>
                    <td style="vertical-align: middle;">
                        <?php
                        if(current_user_can('edit_users') && $member['wp_user_id'])
                            echo '<a href="'.admin_url( 'user-edit.php?user_id='.$member['wp_user_id'] ).'">'.$member['wp_user_id'].'</a>';
                        else
                            echo $member['wp_user_id']
                        ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="member_email_block_<?php echo $member['member_id'];?>">
                            <?php echo $member['member_email']; ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="member_nicename_block_<?php echo $member['member_id'];?>">
                            <?php echo $member['member_nicename']; ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $member['join_date'])); ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['sent']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['opened']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['bounced']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                    <?php
                        if ( "" != $member['unsubscribe_code'] ) {
                            $groups_id = $this->get_memeber_groups( $member['member_id'] );
                            if ( $groups_id ) {
                                $memeber_groups = "";
                                foreach ( $groups_id as $group_id) {
                                    $group  = $this->get_group_by_id( $group_id );
                                    if ( isset( $_REQUEST['group_id'] ) && $group_id == $_REQUEST['group_id'] )
                                        $memeber_groups .= '<span style="color: green;" >' . $group['group_name'] . '</span>, ';
                                    else {
                                        $memeber_groups .= $group['group_name'];
                                    }
                                }
                                echo substr( $memeber_groups, 0, strlen( $memeber_groups )-2 );
                            }
                        } else {
                            echo __( 'Unsubscribed', 'email-newsletter' );
                        }
                    ?>
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>

    </div><!--/wrap-->