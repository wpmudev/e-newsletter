<?php

    $newsletter_data = $this->get_newsletter_data( $_REQUEST['newsletter_id'] );

    $groups = $this->get_groups();

    //send newsletter
    if ( "send" == $_REQUEST["action"] ) {
        $members_id = array();
        if ( "1" == $_REQUEST["all_members"] ) {
            $members = $this->get_members();
            foreach ( $members as $member ) {
                $members_id[] = $member['member_id'];
            }
        } else {
            if ( $_REQUEST["group_name"] )
                foreach ( $_REQUEST["group_name"] as $group_name ) {
                    $users_id = $this->get_users_by_role( $group_name );
                    foreach ( $users_id as $user_id ) {
                        $members_id[] = $this->get_members_by_wp_user_id( $user_id );
                    }
                }
             if ( $_REQUEST["group_id"] )
                foreach ( $_REQUEST["group_id"] as $group_id ) {
                    $members_id = array_merge ( $members_id,  $this->get_members_of_group( $group_id ) );
                }

            $members_id = array_unique( $members_id );
        }
    }

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>

     <script language="JavaScript">
        jQuery( document ).ready( function() {
            jQuery( "#send" ).click( function() {
                error = "1";

                if ( true == jQuery( "input[name='all_members']" ).attr( 'checked' ) )
                    error = "0"

                jQuery( "input[name='group_name[]']" ).each( function() {
                    if ( true == jQuery(this).attr( 'checked' ) )
                        error = "0"
                });

                jQuery( "input[name='group_id[]']" ).each( function() {
                    if ( true == jQuery(this).attr( 'checked' ) )
                        error = "0"
                });

                if ( "1" == error ) {
                    alert( "<?php _e( 'Please select members.', 'email-newsletter' ) ?>" );
                } else {
                    jQuery( "#send_form" ).submit();
                }
            });

        });

     </script>


    <div class="wrap">
        <h2><?php _e( 'Send Newsletter:', 'email-newsletter' ) ?> "<?php echo htmlspecialchars($newsletter_data['subject']);?>"</h2>
        <p><?php _e( 'On this page you can send newsletter to selected groups.', 'email-newsletter' ) ?></p>

        <?php
        if ( "send" == $_REQUEST["action"] ) {
        ?>
            <div id="message" class="updated fade">
                <p>The Newsletter was sent to <span id="count_sent">0</span> out of <?php echo count( $members_id ); ?> members</p>



            <script language="javascript">
                var i = 0;

            </script>

            <?php
            $count_duplicate    = 0;
            $count_errors       = 0;
            $errors_text        = "";

            foreach ( $members_id as $member_id ) {

                if ( ! ( "1" == $_REQUEST['dont_send_duplicate'] && $this->check_duplicate_send( $_REQUEST['newsletter_id'], $member_id ) ) ) {
                    $send_email = $this->send_email_newsletter( $_REQUEST['newsletter_id'], $member_id );
                    if ( true === $send_email ) {

            ?>
                    <script language="javascript">
                        i = i + 1;
                        //alert(i);
                        jQuery( "#count_sent" ).html( i );

                    </script>

        <?php
                    } else {
                        $count_errors++;
                        $errors_text = $send_email . "<br />";
                    }
                } else {
                    $count_duplicate++;
                }
            }
            if ( 0 < $count_duplicate )
                echo __( 'Not sent (People already received):', 'email-newsletter' ) . $count_duplicate . "<br />";

            if ( 0 < $count_errors ) {
                echo "Errors: " . $count_errors . "<br />";
                echo $errors_text;
            }

        }
        ?>
        </div>
        <form action="" method="post" id="send_form">
            <input type="hidden" name="newsletter_id" value="<?php echo $newsletter_data["newsletter_id"];?>">
            <input type="hidden" name="action" value="send">
            <table cellpadding="10" cellspacing="10" class="widefat post">
                <thead><tr>

                        <th>
                            <?php _e( 'Tick which groups you would like to send to:', 'email-newsletter' ) ?>
                        </th>

                </tr></thead>
                <tr>
                    <td>
                    <p>
                        <label><input type="checkbox" name="all_members" value="1" /> <strong><?php _e( 'All Members', 'email-newsletter' ) ?></strong> (<?php echo count( $this->get_members() );?>)</label><br/>
                        &nbsp;&nbsp;-or-<br/>
                        <?php
                            foreach ( array('administrator', 'editor', 'author', 'contributor', 'subscriber') as $rol ) {
                                $col = count ($this->get_users_by_role( $rol ));
                                if ( 0 < $col )
                                    echo "<label><input type='checkbox' name='group_name[]' value='{$rol}' /> All site {$rol}s ({$col})</label><br>";
                            }
                        ?>
                        <?php
                            if ( $groups )
                                foreach ( $groups as $group ) {
                                    $col = count( $this->get_members_of_group( $group['group_id'] ) );
                                    if ( 0 < $col )
                                        echo "<label><input type='checkbox' name='group_id[]' value='{$group['group_id']}' /> {$group['group_name']} ({$col})</label><br>";
                                }
                        ?>
                        <br />
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                         <label>
                             <input type="checkbox" name="dont_send_duplicate" value="1" checked="checked" />
                             <?php echo _e( "Don't send to people who've already received this:", 'email-newsletter' ) ?>
                         </label>
                    </td>
                </tr>
            </table>
            <input type="button" name="send" id="send" value="Send Newsletter <?php echo (count($sends))?' Again':'';?>!" />
        </form>
    </div><!--/wrap-->