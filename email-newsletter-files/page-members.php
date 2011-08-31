<?php

    if ( isset( $_REQUEST['orderby'] ) )
        $arg['orderby'] = $_REQUEST['orderby'];

    if ( isset( $_REQUEST['sortby'] ) )
        $arg['sortby'] = $_REQUEST['sortby'];

    if ( isset( $_REQUEST['order'] ) )
        $arg['order'] = $_REQUEST['order'];

    if ( "desc" == $_REQUEST['order'] )
        $order = "asc";
    else
        $order = "desc";


    if ( "group" == $_REQUEST['filter'] ) {
        if ( 0 < $_REQUEST['group_id'] ) {
            $members_id = $this->get_members_of_group( $_REQUEST['group_id'] );
            foreach( $members_id as $member_id )
                $members[] = $this->get_member( $member_id );

            $filter = "&filter=group&group_id=" . $_REQUEST['group_id'];

            if ( $arg['orderby'] )
                $members = $this->sort_array_by_field( $members, $arg['orderby'], $arg['order'] );
            else if ( $arg['sortby'] )
                $members = $this->sort_array_by_field( $members, $arg['sortby'], $arg['order'] );
        }

    } else if ( "unsubscribed" == $_REQUEST['filter'] ) {
        $all_members = $this->get_members();
            if ( $all_members ) {
                foreach( $all_members as $member )
                    if ( ! $member['unsubscribe_code'] )
                        $members[] = $member;
                $filter = "&filter=unsubscribed";
            }

    } else {
        $members = $this->get_members( $arg );
    }


    $siteurl = get_option( 'siteurl' );

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>
    <script language="JavaScript">
        jQuery( document ).ready( function() {

            jQuery.fn.changeGroups = function ( id ) {

                if ( '<?php _e( 'Save Groups', 'email-newsletter' ) ?>' == jQuery( "#change_button_" + id ).val() ) {
                    jQuery( "#newsletter_action" ).val( "change_group" );
                    jQuery( "#member_id" ).val( id );
                    jQuery( "#form_members" ).submit();
                    return;
                }
                jQuery( "body" ).css( "cursor", "wait" );
                jQuery( "#form_members input[type=button]" ).attr( 'disabled', true );
                jQuery.ajax({
                    type: "POST",
                    url: "<?php echo $siteurl;?>/wp-admin/admin-ajax.php",
                    data: "action=change_groups&member_id=" + id,
                    success: function(html){
                        jQuery( "#change_group_block_" + id ).html( html );
                        jQuery( "#close_block_" + id ).html( '<input type="button" onClick="jQuery(this).closeChangeGroups( ' + id + ' );" value="<?php _e( 'Close', 'email-newsletter' ) ?>" />' );

                        jQuery( "#change_button_" + id ).val('<?php _e( 'Save Groups', 'email-newsletter' ) ?>');

                        if ( jQuery( "#change_group_block_" + id + " input[type=checkbox]" ).length )
                            jQuery( "#change_button_" + id ).attr( 'disabled', false );

                        jQuery( "body" ).css( "cursor", "default" );
                    }
                });




            };

            jQuery.fn.closeChangeGroups = function ( id ) {
                jQuery( "#form_members input[type=button]" ).attr( 'disabled', false );
                jQuery( "#change_group_block_" + id ).html( '' );
                jQuery( "#close_block_" + id ).html( '' );
                jQuery( "#change_button_" + id ).val('<?php _e( 'Change groups', 'email-newsletter' ) ?>');
            };



            //Add new member
            jQuery( "#add_member" ).click( function() {
                if ( "" == jQuery( "#member_email" ).val() ) {
                    alert('<?php _e( 'Please write Email of the member', 'email-newsletter' ) ?>');
                    return false;
                }
                jQuery( "#newsletter_action2" ).val( 'add_member' );
                jQuery( "#add_new_member" ).submit();

            });


            //Some actions
            jQuery( "#apply" ).click( function() {
                if ( -1 == jQuery( "#some_action" ).val() ) {
                    return false;
                }

                jQuery( "#newsletter_action" ).val( jQuery( "#some_action" ).val() );
                jQuery( "#form_members" ).submit();

            });

            jQuery(".btn-slide").click(function(){
                jQuery("#panel").slideToggle("slow");

                if ( "<?php _e( 'Show the New Member form', 'email-newsletter' ) ?>" == jQuery(this).val() )
                    jQuery(this).val( '<?php _e( 'Close the New Member form', 'email-newsletter' ) ?>' );
                else
                    jQuery(this).val( '<?php _e( 'Show the New Member form', 'email-newsletter' ) ?>' );

                return false;
            });


        });
    </script>

    <div class="wrap">
        <h2><?php _e( 'Members', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'At this page you can add or remove members from groups.', 'email-newsletter' ) ?></p>


        <p class="slide">
            <input type="button" class="btn-slide" id="show_add_form" value="<?php _e( 'Show the New Member form', 'email-newsletter' ) ?>" />
        </p>
        <div id="panel">
            <form action="" method="post" name="add_new_member" id="add_new_member" >
                <input type="hidden" name="newsletter_action" id="newsletter_action2" value="" />
                <table>
                    <tr>
                        <td>
                            <?php _e( 'Member Email:', 'email-newsletter' ) ?><span class="required">*</span>
                        </td>
                        <td>
                            <input type="text" name="member[email]" id="member_email" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php _e( 'First Name:', 'email-newsletter' ) ?>
                        </td>
                        <td>
                            <input type="text" name="member[fname]" id="member_fname" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php _e( 'Last Name:', 'email-newsletter' ) ?>
                        </td>
                        <td>
                            <input type="text" name="member[lname]" id="member_lname" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php _e( 'Groups:', 'email-newsletter' ) ?>
                        </td>
                        <td>
                            <?php
                                $groups = $this->get_groups();
                                if ( $groups )
                                    foreach( $groups as $group ){
                                        echo '<label><input type="checkbox" name="member[groups_id][]" value="' . $group['group_id'] . '" />' . $group['group_name'] . '</label>';
                                        echo $group['public'] ? " (public)" : '';
                                        echo '<br />';
                                    }
                            ?>
                        </td>
                    </tr>
                </table>
                <input type="button" name="add_member" id="add_member" value="<?php _e( 'Add Member', 'email-newsletter' ) ?>" />
            </form>
        </div>


        <form method="post" action="" name="form_members" id="form_members" >
            <input type="hidden" name="member_id" id="member_id" value="" />
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <table width="700px" class="widefat post fixed" style="width:95%;">
                <thead>
                    <tr>
                        <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                            <input type="checkbox">
                        </th>
                        <th class="manage-column column-name <?php echo "member_email" == $_REQUEST['orderby'] ? 'sorted ' . $_REQUEST['order'] : 'sortable desc';?>">
                            <a href="admin.php?page=newsletters-members&orderby=member_email&order=<?php echo $order;?><?php echo $filter;?>">
                                <span><?php _e( 'Email Address', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="manage-column column-name <?php echo "member_fname" == $_REQUEST['orderby'] ? 'sorted ' . $_REQUEST['order'] : 'sortable desc';?>">
                            <a href="admin.php?page=newsletters-members&orderby=member_fname&order=<?php echo $order;?><?php echo $filter;?>">
                                <span><?php _e( 'Name', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="manage-column column-name <?php echo "join_date" == $_REQUEST['orderby'] ? 'sorted ' . $_REQUEST['order'] : 'sortable desc';?>">
                            <a href="admin.php?page=newsletters-members&orderby=join_date&order=<?php echo $order;?><?php echo $filter;?>">
                                <span><?php _e( 'Join Date', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="manage-column column-name <?php echo "count_sent" == $_REQUEST['sortby'] ? 'sorted ' . $_REQUEST['order'] : 'sortable desc';?>">
                            <a href="admin.php?page=newsletters-members&sortby=count_sent&order=<?php echo $order;?><?php echo $filter;?>">
                                <span><?php _e( 'Number Sent', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="manage-column column-name <?php echo "count_opened" == $_REQUEST['sortby'] ? 'sorted ' . $_REQUEST['order'] : 'sortable desc';?>">
                            <a href="admin.php?page=newsletters-members&sortby=count_opened&order=<?php echo $order;?><?php echo $filter;?>">
                                <span><?php _e( 'Number Opened', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th>
                            <?php _e( 'Groups', 'email-newsletter' ) ?><?php echo $filter ? ' <a href="admin.php?page=newsletters-members">(all)</a>' : ''; ?>
                        </th>
                        <th>
                            <?php _e( 'Actions', 'email-newsletter' ) ?>
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
                    <th class="check-column" scope="row">
                        <input type="checkbox" value="<?php echo $member['member_id'];?>" class="administrator" id="user_<?php echo $member['member_id'];?>" name="members_id[]">
                    </th>
                    <td style="vertical-align: middle;">
                       <?php echo $member['member_email']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['member_nicename']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo date( $this->settings['date_format'] . " h:i:s", $member['join_date'] ); ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['count_sent']; ?> <?php _e( 'newsletters', 'email-newsletter' ) ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['count_opened']; ?> <?php _e( 'newsletters', 'email-newsletter' ) ?>
                    </td>
                    <td style="vertical-align: middle;">
                    <?php
                        if ( "" != $member['unsubscribe_code'] ) {
                            $groups_id = $this->get_memeber_groups( $member['member_id'] );
                            if ( $groups_id ) {
                                $groups = "";
                                foreach ( $groups_id as $group_id) {
                                    $group  = $this->get_group_by_id( $group_id );
                                    $groups .= '<a href="admin.php?page=newsletters-members&filter=group&group_id=' . $group['group_id'] . '" >' . $group['group_name'] . '</a>, ';

                                }
                                echo substr( $groups, 0, strlen( $groups )-2 );
                            }
                        } else {
                            echo '<a href="admin.php?page=newsletters-members&filter=unsubscribed"><span class="red" >' . __( 'Unsubscribed', 'email-newsletter' ) . '</span></a>';
                        }
                    ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="close_block_<?php echo $member['member_id'];?>"></span>
                        <div id="change_group_block_<?php echo $member['member_id'];?>"></div>
                        <input type="button" id="change_button_<?php echo $member['member_id'];?>" value="<?php _e( 'Change groups', 'email-newsletter' ) ?>" onclick="jQuery(this).changeGroups( <?php echo $member['member_id'];?> );" />
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>
            <div class="alignleft actions">
                <select name="some_action" id="some_action">
                <option selected="selected" value="-1"><?php _e( 'Bulk Actions', 'email-newsletter' ) ?></option>
                    <option value="delete_members"><?php _e( 'Delete', 'email-newsletter' ) ?></option>
                </select>
                <input type="button" value="<?php _e( 'Apply', 'email-newsletter' ) ?>" id="apply" class="button-secondary action" name="">
            </div>
        </form>

    </div><!--/wrap-->