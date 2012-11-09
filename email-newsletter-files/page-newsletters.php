<?php

    $newsletters = $this->get_newsletters();

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

?>

    <div class="wrap">
        <h2><?php _e( 'Newsletters', 'email-newsletter' ) ?><a href="<?php echo admin_url('admin.php?page=newsletters-create'); ?>" class="add-new-h2"><?php _e('Create New','email-newsletter'); ?></a></h2>
        <p><?php _e( 'This page contains the list of all Newsletters.', 'email-newsletter' ) ?></p>
        <table class="widefat post">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Date of creation', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Email Subject', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Template', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Bounced', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Sent To', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Opened', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Actions', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
        <?php
		global $email_builder;
        $i = 0;
		$template_query = array();
        if ( $newsletters )
            foreach( $newsletters as $key => $newsletter ) {
            	$template_id = $this->get_newsletter_meta($newsletter['newsletter_id'],'plugin_template_id');
				
				if($template_id != false) {
					$template_query[$template_id] = $newsletter;
					unset($newsletters[$key]);
					continue;
				}
				
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="vertical-align: middle;">
                   <?php echo date( $this->settings['date_format'] . " h:i:s", $newsletter['create_date'] ); ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php echo $newsletter['subject']; ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php echo $newsletter['template']; ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php echo $this->get_count_bounced( $newsletter['newsletter_id'] ); ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php echo $this->get_count_sent( $newsletter['newsletter_id'] ); ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php echo $this->get_count_opened( $newsletter['newsletter_id'] ); ?> <?php _e( 'members', 'email-newsletter' ) ?>
                </td>
                <td style="vertical-align: middle; width: 140px;">
                    <a href="<?php echo $email_builder->generate_builder_link($newsletter['newsletter_id'],'admin.php?page=newsletters') ?>">
                        <input type="button" value="<?php _e( 'Edit', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="?page=newsletters&newsletter_action=delete_newsletter&newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input type="button" value="<?php _e( 'Delete', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="?page=newsletters&newsletter_action=send_newsletter&newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input type="button" value="<?php _e( 'Send', 'email-newsletter' ) ?>" />
                    </a>
                </td>
            </tr>
        <?php
            }
        ?>
        </table>
        <h2><?php _e('WPMU Plugin Templates','email-newsletter'); ?></h2>
        <p><?php _e('Here you can create custom email templates for other WPMU plugins you have installed.','email-newsletter'); ?></p>
        <table id="wpmuTemplates" class="widefat post">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Plugin', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Email Type', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Template', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Active', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Actions', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
           	<tbody>
           		<?php foreach($this->plugin_templates as $k_id => $data) : ?>
           			<tr>
           				<td>
           					<?php echo $data['name']; ?> 
           				</td>
           				<td>
           					<?php echo $data['type']; ?>
           				</td>
           				<td></td>
           				<td></td>
           				<td>
           					<?php if(isset($template_query[$k_id])) : ?>
           					<a href="<?php echo $email_builder->generate_builder_link($template_query[$k_id]['newsletter_id'],'admin.php?page=newsletters') ?>">
		                        <input type="button" value="<?php _e( 'Edit', 'email-newsletter' ) ?>" />
		                    </a>
		                    <a href="?page=newsletters&newsletter_action=delete_newsletter&newsletter_id=<?php echo $template_query[$k_id]['newsletter_id'];?>">
		                        <input type="button" value="<?php _e( 'Delete', 'email-newsletter' ) ?>" />
		                    </a>
		                    <?php else: ?>
		                    	<a href="#">Create New</a>
		                    <?php endif; ?>
           				</td>
           			</tr>

           		<?php endforeach; ?>
           	</tbody>
       </table>
    </div><!--/wrap-->