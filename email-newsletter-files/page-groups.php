<?php

    $groups = $this->get_groups();

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2><?php _e( 'Groups', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'This page contains the list of all groups.', 'email-newsletter' ) ?></p>

        <h3><?php _e( 'Create New Group', 'email-newsletter' ) ?></h3>
        <form method="post" action="" name="create_group" id="create_group" >
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <table class="form-table">
                <tr class="top">
                    <th scope="row">

                        <?php _e( 'Group Name:', 'email-newsletter' ) ?><span class="required">*</span>

					</th>
					<td>
						<input type="text" class="input" name="group_name" id="group_name" value="" size="30" />
						<br/>
                        <fieldset>
                            <label for="public"><input type="checkbox" name="public" id="public" value="1" />
                            <?php _e( 'public users can join this group', 'email-newsletter' ) ?></label>
                        </fieldset>
                    </td>
                </tr>
            </table>
			<p class="submit"><input class="button button-primary" type="button" name="save" id="add_group" value="<?php _e( 'Add Group', 'email-newsletter' ) ?>" /></p>
        </form>



        <h3><?php _e( 'List of Groups:', 'email-newsletter' ) ?></h3>
        <form method="post" action="" name="edit_group" id="edit_group" >
            <input type="hidden" name="newsletter_action" id="newsletter_action2" value="" />
            <input type="hidden" name="group_id" id="group_id" value="" />
            <table id="groups_table" class="widefat post table_slim">
                <thead>
                    <tr>
                        <th>
                            <?php _e( 'ID', 'email-newsletter' ) ?>
                        </th>
                        <th>
                            <?php _e( 'Group Name', 'email-newsletter' ) ?>
                        </th>
                        <th>
                            <?php _e( 'Public', 'email-newsletter' ) ?>
                        </th>
                        <th>
                            <?php _e( 'Members', 'email-newsletter' ) ?>
                        </th>
                        <th>
                            <?php _e( 'Actions', 'email-newsletter' ) ?>
                        </th>
                    </tr>
                </thead>
            <?php
            $i = 0;
            if ( $groups ) {
                foreach( $groups as $group ) {
                    if ( $i % 2 == 0 )
                        echo "<tr class='alternate'>";
                    else
                        echo "<tr class='' >";

                    $i++;
            ?>
                    <td style="vertical-align: middle;">
                        <span id="group_id_block_<?php echo $group['group_id'];?>">
                            <?php echo $group['group_id']; ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="group_name_block_<?php echo $group['group_id'];?>">
                            <?php echo $group['group_name']; ?>
                        </span>
                    </td>
                    <td>
                        <span id="public_block_<?php echo $group['group_id'];?>">
                            <?php
                            if ( "1" == $group['public'] )
                                _e( 'Yes', 'email-newsletter' );
                            else
                                _e( 'No', 'email-newsletter' );
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php echo count( $this->get_members_of_group( $group['group_id'], '', 1 ) ); ?>
                    </td>
                    <td>
                        <input class="button button-secondary" type="button" id="edit_button_<?php echo $group['group_id'];?>" value="<?php _e( 'Edit', 'email-newsletter' ) ?>" onclick="jQuery(this).editGroup( <?php echo $group['group_id'];?> );" />
                        <span id="save_block_<?php echo $group['group_id'];?>"></span>
                        <input class="button button-secondary" type="button" value="<?php _e( 'Delete', 'email-newsletter' ) ?>" onclick="jQuery(this).deleteGroup( <?php echo $group['group_id'];?> );" />
                    </td>
                </tr>
            <?php
                }
            }
            else
                echo '<tr><td colspan="5">'.__( 'No groups found.', 'email-newsletter' ).'</td><td>';
            ?>
            </table>
        </form>
    </div><!--/wrap-->