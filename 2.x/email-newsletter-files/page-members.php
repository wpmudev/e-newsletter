<?php
    global $wpdb;
    $arg = NULL;
    $groups = $this->get_groups();

    if(isset( $_REQUEST['order'] ) && $_REQUEST['order'] == 'asc')
        $order = "desc";
    else {
        $order = "asc";
    }
    $url_orginal = add_query_arg( array('order' => $order, 'orderby' => false, 'search_members' => $_REQUEST['search_members']) );

    //Pagination option
    if ( isset( $_REQUEST['per_page'] ) )
        $per_page = $_REQUEST['per_page'];
    else
        $per_page = 15;

    if ( isset( $_REQUEST['orderby'] ) )
        $arg['orderby'] = $_REQUEST['orderby'];

    if ( isset( $_REQUEST['order'] ) )
        $arg['order'] = $_REQUEST['order'];

    if(isset( $_REQUEST['search_members'] )) {
        $sql_search = '%'.$_REQUEST['search_members'].'%';
        $arg['where'] = $wpdb->prepare('member_fname LIKE %s OR member_lname LIKE %s OR member_email LIKE %s', $sql_search, $sql_search, $sql_search);
    }

    if ( isset( $_REQUEST['filter'] ) ) {
        if ( "group" == $_REQUEST['filter'] ) {
            if ( 0 < $_REQUEST['group_id'] ) {
                $arg['inner_join'] = $this->tb_prefix.'enewsletter_member_group C ON (A.member_id = C.member_id)';
                $arg['where'] = $wpdb->prepare('group_id = %d', $_REQUEST['group_id']);
            }
        }
        elseif ( "ungrouped" == $_REQUEST['filter'] ) {
            $arg['left_join'] = $this->tb_prefix.'enewsletter_member_group C ON (A.member_id = C.member_id)';
            $arg['where'] = 'C.group_id IS NULL';
        }
        elseif("unsubscribed" == $_REQUEST['filter']) {
            $arg['where'] = "unsubscribe_code = ''";
        }
        elseif("bounced" == $_REQUEST['filter']) {
            $arg['where'] = "B.status = 'bounced'";
        }

        
    }

    $count = $this->get_members( $arg, 1 );

    $members_pagination = $this->get_pagination_data( $count, $per_page );
    if ( isset( $members_pagination['limit'] ) )
        $arg['limit'] = $members_pagination['limit'];

    $members = $this->get_members( $arg );


    $siteurl = get_option( 'siteurl' );

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>
    <script type="text/javascript">
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
                        jQuery( "#close_block_" + id ).html( '<input class="button button-secondary" type="button" onClick="jQuery(this).closeChangeGroups( ' + id + ' );" value="<?php _e( 'Close', 'email-newsletter' ) ?>" />' );

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

            //Import new members
            jQuery( "#import_members" ).click( function() {
                if ( "" == jQuery( "#import_members_file" ).val() ) {
                    jQuery( "#import_file_line" ).attr('class', 'newsletter_error');
                    return false;
                }

                jQuery( "#newsletter_action2" ).val( 'import_members' );
                jQuery( "#add_new_member" ).submit();

            });


            //Some actions
            jQuery( "#apply" ).click( function() {
                if ( -1 == jQuery( "#some_action" ).val() ) {
                    return false;
                } else if ( ( 'add_members_group' == jQuery( "#some_action" ).val() || 'delete_members_group' == jQuery( "#some_action" ).val() )
                                && -1 == jQuery( "#list_group_id" ).val() ) {
                    return false;
                }

                jQuery( "#newsletter_action" ).val( jQuery( "#some_action" ).val() );
                jQuery( "#form_members" ).submit();
                return false;
            });

            //show/hide select box of groups list
            jQuery( "#some_action" ).change( function() {
                if ( 'add_members_group' == jQuery( "#some_action" ).val() || 'delete_members_group' == jQuery( "#some_action" ).val() ) {
                    jQuery( "#list_group_id" ).show();
                } else {
                    jQuery( "#list_group_id" ).hide();
                }
            });


            //change per page count
            jQuery( "#per_page" ).change( function() {
                jQuery( "#newsletter_action" ).val( '' );
                jQuery( "#form_members" ).submit();
                return false;
            });


            jQuery( "#show_add_form" ).click( function() {
                jQuery( "#panel" ).slideToggle( "slow" );

                if ( "<?php _e( 'Show the New Member / Import forms', 'email-newsletter' ) ?>" == jQuery(this).val() )
                    jQuery(this).val( '<?php _e( 'Hide the New Member / Import forms', 'email-newsletter' ) ?>' );
                else
                    jQuery(this).val( '<?php _e( 'Show the New Member / Import forms', 'email-newsletter' ) ?>' );

                return false;
            });

            jQuery( "#show_add_form2" ).click( function() {
                jQuery( "#panel2" ).slideToggle( "slow" );

                if ( "<?php _e( 'Show the export Members form', 'email-newsletter' ) ?>" == jQuery(this).val() )
                    jQuery(this).val( '<?php _e( 'Show the export Members form', 'email-newsletter' ) ?>' );
                else
                    jQuery(this).val( '<?php _e( 'Hide the export Members form', 'email-newsletter' ) ?>' );

                return false;
            });

           jQuery.fn.editMember = function ( id ) {
                if ( "<?php _e( 'Edit', 'email-newsletter' ) ?>" == jQuery( this ).val() ) {
                    jQuery( "#member_id" ).val( id );

                    member_nicename = jQuery( "#member_nicename_block_" + id ).html();
                    member_nicename = member_nicename.replace(/(^\s+)|(\s+$)/g, "");

                    jQuery( "#member_nicename_block_" + id ).html( '<input type="text" name="edit_member_nicename" id="edit_member_nicename"  value="' + member_nicename + '" />' );

                    member_email = jQuery( "#member_email_block_" + id ).html();
                    member_email = member_email.replace(/(^\s+)|(\s+$)/g, "");

                    jQuery( "#member_email_block_" + id ).html( '<input type="text" size="30" name="edit_member_email" id="edit_member_email"  value="' + member_email + '" />' );

                    jQuery( '#form_members input[type="button"]' ).attr( 'disabled', true );

                    jQuery( this ).val('<?php _e( 'Close', 'email-newsletter' ) ?>');
                    jQuery( this ).attr( 'disabled', false );

                    jQuery( "#save_block_" + id ).html( '<input class="button button-secondary" type="button" id="save_member_button" name="save_button" onClick="jQuery(this).saveMember();" value="<?php _e( 'Save', 'email-newsletter' ) ?>" />' );

                    return;
                }

                if ( "<?php _e( 'Close', 'email-newsletter' ) ?>" == jQuery( this ).val() ) {
                    jQuery( "#member_id" ).val( '' );

                    jQuery( "#member_nicename_block_" + id ).html( member_nicename );
                    jQuery( "#member_email_block_" + id ).html( member_email );

                    jQuery( this ).val('<?php _e( 'Edit', 'email-newsletter' ) ?>');
                    jQuery( '#form_members input[type="button"]' ).attr( 'disabled', false );

                     jQuery( "#save_block_" + id ).html( '' );

                    return;
                }


            };


            jQuery.fn.saveMember = function ( ) {
                filter = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if (filter.test(jQuery( "#edit_member_email" ).val())) {} else {
                    alert( '<?php _e( "Please use proper email", "email-newsletter" ) ?>' );
                    return false;
                }

                jQuery( "#newsletter_action" ).val( "edit_member" );
                jQuery( "#form_members" ).submit();
            };


            jQuery.fn.deleteMember = function ( id ) {
                if (confirm('<?php _e( "Are you sure?", "email-newsletter" ) ?>')) {
                    jQuery( "#newsletter_action" ).val( "delete_member" );
                    jQuery( "#member_id" ).val( id );
                    jQuery( "#form_members" ).submit();
                }
            };

        });
    </script>

    <div class="wrap">
        <h2><?php _e( 'Members', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'At this page you can manage your members.', 'email-newsletter' ) ?></p>
        <p><?php _e( 'Note: edits made to members will not sync to wordpress user but they will the other way around.', 'email-newsletter' ) ?></p>

        <p class="slide">
            <input type="button" class="button-secondary action" id="show_add_form" value="<?php _e( 'Show the New Member / Import forms', 'email-newsletter' ) ?>" />
            <input type="button" class="button-secondary action" id="show_add_form2" value="<?php _e( 'Show the export Members form', 'email-newsletter' ) ?>" />
        </p>

        <div id="panel" class="panel">
            <form action="" method="post" name="add_new_member" id="add_new_member" enctype="multipart/form-data">
                <input type="hidden" name="newsletter_action" id="newsletter_action2" value="" />
                <input type="hidden" name="members_import" id="members_import" value="" />
                <table cellspacing="10">
                    <tr>
                        <td valign="top">
                            <table class="create_member">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Create the new member:', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>
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

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Groups:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="member[groups_id][]" value="<?php echo $group['group_id'];?>" />
                                                <label for="member[groups_id][]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>

                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-secondary" type="button" name="add_member" id="add_member" value="<?php _e( 'Add Member', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                       </td>
                       <td valign="top">
                            <table class="import_members">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Import members:', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                       <span class="description"><?php _e( 'Note: The file should have the next columns: Email (required), First Name (not required), Last Name (not required). Without headers.', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr id="import_file_line">
                                    <td>
                                        <?php _e( 'From .csv file:', 'email-newsletter' ) ?><span class="required">*</span>
                                    </td>
                                    <td>
                                        <input type="file" name="import_members_file" id="import_members_file" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Separated by:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <select name="separ_sign">
                                            <option value="1" <?php echo ( isset( $_GET['separ_sign'] ) && 1 == $_GET['separ_sign'] ) ? 'selected': ''; ?> >
                                                <?php _e( 'Semicolon', 'email-newsletter' ) ?> (;)&nbsp;
                                            </option>
                                            <option value="2" <?php echo ( isset( $_GET['separ_sign'] ) && 2 == $_GET['separ_sign'] ) ? 'selected': ''; ?> >
                                                <?php _e( 'Comma', 'email-newsletter' ) ?> (,)&nbsp;
                                            </option>
                                        </select>
                                    </td>
                                </tr>

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Assign with group:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="import_groups_id[]" value="<?php echo $group['group_id'];?>" />
                                                <label for="import_groups_id[]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>
                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-secondary" type="button" name="import_members" id="import_members" value="<?php _e( 'Import members', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <div id="panel2" class="panel">
            <form action="" method="post" name="export_members" id="export_members" enctype="multipart/form-data">
                <input type="hidden" name="newsletter_action" id="newsletter_action3" value="export_members" />
                <table cellspacing="10">
                    <tr>
                        <td valign="top">
                            <table class="export_members">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Export Members to file', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Groups:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="groups_ungrouped" value="1" checked/>
                                            <label for="groups_id[]">
                                                <?php _e( 'Ungrouped', 'email-newsletter' ) ?>
                                            </label>
                                            <br />
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="groups_id[]" value="<?php echo $group['group_id'];?>" checked/>
                                                <label for="groups_id[]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>

                                <tr>
                                    <td>
                                        <?php _e( 'Separated by:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <select name="separ_sign">
                                            <option value="1" selected>
                                                <?php _e( 'Semicolon', 'email-newsletter' ) ?> (;)&nbsp;
                                            </option>
                                            <option value="2">
                                                <?php _e( 'Comma', 'email-newsletter' ) ?> (,)&nbsp;
                                            </option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-secondary" type="submit" name="add_member" id="add_member" value="<?php _e( 'Exports Members', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                       </td>
                    </tr>
                </table>
            </form>
        </div>

        <form method="post" action="" name="form_members" id="form_members" >
            <p style="float:left;">
                <?php $url = add_query_arg( array('filter' => false), $url_orginal ); ?> 
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Show All', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'ungrouped'), $url_orginal ); ?> 
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Show Ungrouped', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'bounced'), $url_orginal ); ?> 
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Show Bounced', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'unsubscribed'), $url_orginal ); ?> 
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Show Unsubscribed', 'email-newsletter' ); ?></a>
            </p>
            <p style="float:right;">
                <label class="screen-reader-text" for="post-search-input">Search Pages:</label>
                <input type="search" id="post-search-input" name="search_members" value="<?php if(isset( $_REQUEST['search_members'] )) echo $_REQUEST['search_members']; ?>">
                <input type="submit" name="" id="search-submit" class="button" value="<?php _e( 'Search Members', 'email-newsletter' ) ?>">
            </p>

            <input type="hidden" name="member_id" id="member_id" value="" />
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <table id="members_table" class="widefat post">
                <thead>
                    <tr> 
                        <th class="manage-column column-cb check-column" id="cb" scope="col">
                            <input type="checkbox">
                        </th>
                        <th class="members-wp manage-column column-name">
                            <?php _e( 'WP ID', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-email manage-column column-name <?php echo (isset($arg['orderby']) && "member_email" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'member_email'), $url_orginal ); ?> 
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Email Address', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-name manage-column column-name <?php echo (isset($arg['orderby']) && "member_fname" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'member_fname'), $url_orginal ); ?> 
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Name', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-join manage-column column-name <?php echo (isset($arg['orderby']) && "join_date" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'join_date'), $url_orginal ); ?> 
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Join Date', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "count_sent" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'count_sent'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Number Sent', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "count_opened" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'count_opened'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Number Opened', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "count_bounced" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'count_bounced'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Number Bounced', 'email-newsletter' ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-groups manage-column column-name">
                            <?php _e( 'Groups', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-actions manage-column column-name">
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
                        <?php 
                        if(current_user_can('edit_users'))
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
                        <?php echo date( $this->settings['date_format'] . " h:i:s", $member['join_date'] ); ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['count_sent']; ?> <?php _e( 'newsletters', 'email-newsletter' ) ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['count_opened']; ?> <?php _e( 'newsletters', 'email-newsletter' ) ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['count_bounced']; ?> <?php _e( 'newsletters', 'email-newsletter' ) ?>
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
                                        $url = add_query_arg( array('filter' => 'group', 'group_id' => $group['group_id']), $url_orginal );
                                        $memeber_groups .= '<a href="'.$url.'" >' . $group['group_name'] . '</a>, ';
                                    }
                                }
                                echo substr( $memeber_groups, 0, strlen( $memeber_groups )-2 );
                            }
                        } else {
                            $url = add_query_arg( array('filter' => 'unsubscribed' ), $url_orginal );
                            echo '<a href="'.$url.'"><span class="red" >' . __( 'Unsubscribed', 'email-newsletter' ) . '</span></a>';
                        }
                    ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="close_block_<?php echo $member['member_id'];?>"></span>
                        <div id="change_group_block_<?php echo $member['member_id'];?>"></div>
                        <input class="button button-secondary" type="button" id="change_button_<?php echo $member['member_id'];?>" value="<?php _e( 'Change groups', 'email-newsletter' ) ?>" onclick="jQuery(this).changeGroups( <?php echo $member['member_id'];?> );" />
                        
                        <input class="button button-secondary" type="button" id="edit_button_<?php echo $member['member_id'];?>" value="<?php _e( 'Edit', 'email-newsletter' ) ?>" onclick="jQuery(this).editMember( <?php echo $member['member_id'];?> );" />
                        <span id="save_block_<?php echo $member['member_id'];?>"></span>
                        <input class="button button-secondary" type="button" value="<?php _e( 'Delete', 'email-newsletter' ) ?>" onclick="jQuery(this).deleteMember( <?php echo $member['member_id'];?> );" />
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <select name="some_action" id="some_action">
                        <option selected="selected" value="-1"><?php _e( 'Bulk Actions', 'email-newsletter' ) ?></option>

                        <?php if ( $groups ): ?>
                        <option value="add_members_group"><?php _e( 'Add to group', 'email-newsletter' ) ?></option>
                        <option value="delete_members_group"><?php _e( 'Delete from group', 'email-newsletter' ) ?></option>
                        <?php endif; ?>

                        <option value="delete_members"><?php _e( 'Delete', 'email-newsletter' ) ?></option>
                    </select>

                    <?php if ( $groups ): ?>
                    <select name="list_group_id" id="list_group_id" style="display: none;">
                        <option selected="selected" value="-1"> <?php _e( 'Group List', 'email-newsletter' ) ?> </option>
                        <?php foreach( $groups as $group ) : ?>
                            <option value="<?php echo $group['group_id'];?>">
                            <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <input type="button" value="<?php _e( 'Apply', 'email-newsletter' ) ?>" id="apply" class="button-secondary action" name="">
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo ( isset( $members_pagination ) ) ? $members_pagination['count'] : $count; ?> <?php _e( 'member(s)', 'email-newsletter' ) ?>
                        <?php _e( 'by', 'email-newsletter' ) ?>
                        <select name="per_page" id="per_page">
                            <option value="15" <?php echo ( 15 == $per_page ) ? 'selected' : ''; ?> >15</option>
                            <option value="30" <?php echo ( 30 == $per_page ) ? 'selected' : ''; ?> >30</option>
                            <option value="50" <?php echo ( 50 == $per_page ) ? 'selected' : ''; ?> >50</option>
                            <option value="100" <?php echo ( 100 == $per_page ) ? 'selected' : ''; ?> >100</option>
                            <option value="all" <?php echo ( 'all' == $per_page ) ? 'selected' : ''; ?> ><?php _e( 'All', 'email-newsletter' ) ?></option>
                        </select>
                        <?php _e( 'per page.', 'email-newsletter' ) ?>
                    </span>

                    <?php
                    if ( isset( $members_pagination ) && is_array( $members_pagination ) ):

                        //count page count before and after current
                        $pagedisprange = 3;

                        $pagescount = ceil( $members_pagination['count'] / $per_page );

                        //start page number
                        $stpage = $members_pagination['cpage'] - $pagedisprange;
                        if ( $stpage < 1 )
                            $stpage = 1;

                        // end page number
                        $endpage = $members_pagination['cpage'] + $pagedisprange;
                        if ( $endpage > $pagescount )
                            $endpage=$pagescount;
                        ?>

                        <span class="pagination-links">
                        <?php
                            if ( $members_pagination['cpage'] > 1 ) {
                                // first
                                $url = add_query_arg( array('cpage' => 1 ), $url_orginal );
                                echo '<a href="'.$url.'" title="Go to the first page" class="first-page" ><<</a> ';
                                $url = add_query_arg( array('cpage' => ( $members_pagination['cpage'] - 1 ) ), $url_orginal );
                                echo '<a href="'.$url.'" title="Go to the previous page" class="prev-page" ><</a> ';
                            }

                            if ( $stpage > 1)
                                echo '<span>...</span> ';

                            for ( $i = $stpage; $i <= $endpage; $i++ ) {
                                if ( $i == $members_pagination['cpage'] ) {
                                    echo '<span class="current" style="margin: 0px 7px 0px 3px;"><strong>' . $i . '</strong></span>';
                                } else {
                                    $url = add_query_arg( array('cpage' => $i ), $url_orginal );
                                    echo '<a href="'.$url.'">' . $i . '</a> ';
                                }
                            }

                            if ( $endpage < $pagescount )
                                echo '<span>...</span> ';

                            if ( $members_pagination['cpage'] < $pagescount ) {
                                // next
                                $url = add_query_arg( array('cpage' => ( $members_pagination['cpage'] + 1 ) ), $url_orginal );
                                echo '<a href="'.$url.'" title="Go to the next page" class="next-page" >></a> ';
                                // last
                                $url = add_query_arg( array('cpage' => $pagescount ), $url_orginal );
                                echo '<a href="'.$url.'" title="Go to the last page" class="last-page" >>></a> ';
                            }
                        ?>
                        </span>
                    <?php endif;?>
                </div>
            </div>
        </form>

    </div><!--/wrap-->