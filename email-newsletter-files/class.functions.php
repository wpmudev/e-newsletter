<?php
/**
* Plugin functions class
**/
class Email_Newsletter_functions {


    /**
     * load template for page
     **/
    function load_template( $name ) {
        $path = locate_template( $name );

        if ( !$path ) {
            $path = $this->plugin_dir . "email-newsletter-files/$name";
        }

        load_template( $path );
        die;
    }

    function add_rewrite_rule( $regex, $args, $position = 'top' ) {
        global $wp, $wp_rewrite;

        $result = add_query_arg( $args, 'index.php' );
        add_rewrite_rule( $regex, $result, $position );
    }



    /**
     * Show not menu page
     **/
    function template_redirect() {
//        global $wp_query;
        if ( $this->is_enewsletter_page( 'unsubscribe_page' ) ) {
//            $this->load_template( 'page-unsubscribe.php' );
            require_once( $this->plugin_dir . "email-newsletter-files/page-unsubscribe.php" );
            exit;
        }
    }



    /**
     * Generate Unsubscribe code
     **/
    function gen_unsubscribe_code() {
        $now = time();
        $unsubscribe_code = substr( $now, strlen( $now ) - 3, 3 ) . substr( md5( uniqid( rand(), true ) ), 0, 8 ) . substr( md5( $now . rand() ), 0, 4);
        return $unsubscribe_code;
    }

    /**
     * function for sorting an array of arrays by volue of field
     **/
    function sort_array_by_field ( $array, $field, $sort = "asc" ) {
        $fn = create_function( '$a, $b', '
            if( $a["' . $field . '"] == $b["' . $field . '"] ) return 0;
            if ( "asc" == "' . $sort . '")
                return ( $a["' . $field . '"] < $b["' . $field . '"] ) ? -1 : 1;
            else
                return ( $a["' . $field . '"] > $b["' . $field . '"] ) ? -1 : 1;
        ');

        usort($array, $fn);
        return $array;
    }

    /**
     * Checking of duplicate send
     **/
    function check_duplicate_send( $newsletter_id, $member_id ) {
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT b.member_id FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.member_id = %d ", $newsletter_id, $member_id ), "ARRAY_A");
        if ( 0 < $result )
            return true;
        else
            return false;
    }

    /**
     * Get count of sent email by newsletter_id or for all newsletters
     **/
     function get_count_sent( $newsletter_id = '' ) {
        global $wpdb;
        if ( '' === $newsletter_id )
            $count = $wpdb->get_row( "SELECT Count(b.member_id) FROM {$this->tb_prefix}enewsletter_send_members b  WHERE b.status = 'sent'", "ARRAY_A");
        else
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(b.member_id) FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b  WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.status = 'sent'", $newsletter_id ), "ARRAY_A");
        return $count['Count(b.member_id)'];
    }

    /**
     * Get count of bounced email by newsletter_id or for all newsletters
     **/
     function get_count_bounced( $newsletter_id = '' ) {
        global $wpdb;
        if ( '' === $newsletter_id )
            $count = $wpdb->get_row( "SELECT Count(b.member_id) FROM {$this->tb_prefix}enewsletter_send_members b  WHERE b.status = 'bounced'", "ARRAY_A");
        else
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(b.member_id) FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b  WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.status = 'bounced'", $newsletter_id ), "ARRAY_A");
        return $count['Count(b.member_id)'];
    }

    /**
     * Get count of all newsletters
     **/
     function get_count_newsletters() {
        global $wpdb;
        $count = $wpdb->get_row( "SELECT Count(newsletter_id) FROM {$this->tb_prefix}enewsletter_newsletters", "ARRAY_A");
        return $count['Count(newsletter_id)'];
    }
    /**
     * Get count of all groups
     **/
     function get_count_groups() {
        global $wpdb;
        $count = $wpdb->get_row( "SELECT Count(group_id) FROM {$this->tb_prefix}enewsletter_groups", "ARRAY_A");
        return $count['Count(group_id)'];
    }

    /**
     * Get count of all members
     **/
     function get_count_members() {
        global $wpdb;
        $count = $wpdb->get_row( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_members", "ARRAY_A");
        return $count['Count(member_id)'];
    }

    /**
     * Get count of opened email
     **/
     function get_count_opened( $newsletter_id ) {
        global $wpdb;
        $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(b.member_id) FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b  WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.opened_time > 0", $newsletter_id ), "ARRAY_A");
        return $count['Count(b.member_id)'];
    }

    /**
     * Get count of sent email to user
     **/
     function get_count_sent_to_user( $member_id ) {
        global $wpdb;
        $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE member_id = %d  AND status = 'sent'", $member_id ), "ARRAY_A");
        return $count['Count(member_id)'];
    }

    /**
     * Get count of opened email by user
     **/
     function get_count_opened_by_user( $member_id ) {
        global $wpdb;
        $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE member_id = %d AND opened_time > 0", $member_id ), "ARRAY_A");
        return $count['Count(member_id)'];
    }

    /**
     * Get all templates
     **/
    function get_templates(){
        $template_dirs = glob( $this->plugin_dir . "email-newsletter-files/templates/*" );
        $templates = array();
        foreach( $template_dirs as $template_dir ){
            $templates[] = array(
                "dir" => $template_dir,
                "name" => basename( $template_dir ),
            );
        }
        return $templates;
    }

    /**
     * Get all uploads images
     **/
    function get_uploads(){
        $upload_files = glob( $this->plugin_dir . "email-newsletter-files/uploads/*" );
        $uploads = '<option value="">' . __( 'Select an image', 'email-newsletter' ) . '</option>';
        foreach( $upload_files as $upload_file ) {
            $uploads .='<option value="' . $this->plugin_url . 'email-newsletter-files/uploads/' . basename( $upload_file ) . '"> ' . basename( $upload_file ) . ' </option>';
        }
        return $uploads;
    }

    /**
     * checks that current page is e-newsletter's page
     **/
    function is_enewsletter_page ( $page = '' ) {
        switch ( $page ) {
            case 'newsletters':
            case 'newsletters-dashboard':
            case 'newsletters-create':
            case 'newsletters-groups':
            case 'newsletters-members':
            case 'newsletters-subscribes':
            case 'newsletters-settings':
                return 1;
                break;
            case 'unsubscribe_page':
                if ( 1 == get_query_var( 'unsubscribe_page' ) )
                    return 1;
                else
                    return 0;
                break;
            default:
                return 0;
        }
    }

    /**
     * Add some periods for CRON
     **/
    function add_new_cron_time( $schedules ) {

        $schedules['enewsletter_min_5'] = array(
            'interval' => 5*60,
            'display' => __('every 5 min')
        );

        $schedules['enewsletter_min_10'] = array(
            'interval' => 10*60,
            'display' => __('every 10 min')
        );

        $schedules['enewsletter_min_15'] = array(
            'interval' => 15*60,
            'display' => __('every 15 min')
        );

        $schedules['enewsletter_min_30'] = array(
            'interval' => 30*60,
            'display' => __('every 30 min')
        );

        $schedules['enewsletter_hour_3'] = array(
            'interval' => 3*60*60,
            'display' => __('every 3 hour')
        );

        $schedules['enewsletter_hour_6'] = array(
            'interval' => 6*60*60,
            'display' => __('every 6 hour')
        );
        return $schedules;
    }

    /**
     * Send email
     **/
    function send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents,  $options=array() ) {

        $options['to']          = $email_to;
        $options['subject']     = $email_subject;
        $options['from']        = $email_from;
        $options['from_name']   = $email_from_name;

        foreach( array( "to", "cc", "bcc", "reply_to" ) as $type ) {
            if( ! $options[$type] ) {
                $options[$type]=array();
            } else if( ! is_array( $options[$type] ) ) {
                $emails = explode( ",", $options[$type] );
                $options[$type] = array();
                foreach( $emails as $e ) {
                    if ( $e=trim( $e ) ) {
                        $options[$type][]=$e;
                    }
                }
            }
        }

        require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';

        //Set Sending Method
        switch( $this->settings['outbound_type'] ) {
            case 'smtp':
                $mail->IsSMTP();
                $mail->Host     = $this->settings['smtp_host'];
                $mail->SMTPAuth = ( strlen( $this->settings['smtp_user'] ) > 0 );
                if( $mail->SMTPAuth ){
                    $mail->Username = $this->settings['smtp_user'];
                    $mail->Password = $this->settings['smtp_pass'];
                }
                break;

            case 'mail':
                $mail->IsMail();
                break;

            case 'sendmail':
                $mail->IsSendmail();
                break;
        }


        $mail->From = $options['from'];
        if( $options['from_name'] ) {
            $mail->FromName = $options['from_name'];
        }
        $mail->Subject = $options['subject'];

        $mail->isHTML( true );


        $mail->MsgHTML( $email_contents );


        foreach( $options['to'] as $email ) {
            $mail->AddAddress( $email );
        }
        foreach( $options['cc'] as $email ) {
            $mail->AddCC( $email );
        }
        foreach( $options['bcc'] as $email ) {
            $mail->AddBCC( $email );
        }
        foreach( $options['reply_to'] as $email ) {
            $mail->AddReplyTo( $email );
        }

        if( $options['bounce_email']  ){
            $mail->Sender = $options['bounce_email'];
        }
        if( $options['message_id'] ) {
            $mail->MessageID = $options['message_id'];
        }

        if( ! $mail->Send() ) {
            echo $mail->ErrorInfo;
            return false;
        }
        return true;
    }

    /**
     * Check bounces email
     **/
    function check_bounces() {
        global $wpdb;

        @set_time_limit( 0 );
        $email_address  = $this->settings['bounce_email'];
        $email_username = $this->settings['bounce_username'];
        $email_password = $this->settings['bounce_password'];
        $email_host     = trim( $this->settings['bounce_host'] );
        $email_port     = ( $this->settings['bounce_port'] ) ? $this->settings['bounce_port'] : 110;

        if( ! $email_host )
            return true;

        $mbox = imap_open ( '{'.$email_host.':'.$email_port.'/pop3/notls}INBOX', $email_username, $email_password ) or die( imap_last_error() );

        if( ! $mbox ) {
            return 'Error: Failed to connect when checking bounces!';
        } else {
            $MC     = imap_check( $mbox );
            $mails  = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

            foreach ( $mails as $mail ) {
                $body = imap_body ( $mbox, $mail->msgno );

                if( preg_match( '/Message-ID:\s*<?Newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches) ) {

                    $member_id      = ( int ) $matches[1];
                    $send_id        = ( int ) $matches[2];
                    $email_hash     = trim( $matches[3] );
                    $hash           = md5( 'Hash of bounce member_id='. $member_id . ', send_id='. $send_id );

                    if( $email_hash == $hash ){
                        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'bounced' WHERE send_id = %d AND member_id = %d AND status = 'sent'", $send_id, $member_id ) );
                        imap_delete( $mbox, $mail->msgno );
                        echo 'ok';
                    } else {
                        echo 'Error: hash';
                    }
                }
            }
            imap_expunge( $mbox );
            imap_close( $mbox );
        }
    }

    /**
     * Save Settings
     **/
     function save_settings( $settings ) {
        global $wpdb;

        if( ! is_array( $settings ) )
            $settings = array();


        //change time for CRON
        if ( $this->settings['cron_time'] != $settings['cron_time'] ) {
            if ( wp_next_scheduled( 'e_newsletter_cron_send' . $wpdb->blogid ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_send' . $wpdb->blogid );

            if ( 1 < $settings['cron_time'] ) {
                switch ( $settings['cron_time'] ) {
                case 2:    $cron_time = 'enewsletter_min_5';
                           break;
                case 3:    $cron_time = 'enewsletter_min_10';
                           break;
                case 4:    $cron_time = 'enewsletter_min_15';
                           break;
                case 5:    $cron_time = 'enewsletter_min_30';
                           break;
                case 6:    $cron_time = 'hourly';
                           break;
                case 7:    $cron_time = 'enewsletter_hour_3';
                           break;
                case 8:    $cron_time = 'enewsletter_hour_6';
                           break;
                case 9:    $cron_time = 'twicedaily';
                           break;
                case 10:   $cron_time = 'daily';
                           break;
                }
                wp_schedule_event( time(), $cron_time, 'e_newsletter_cron_send' . $wpdb->blogid );
            }
        }

        if ( $settings['send_limit'] )
            $settings['send_limit'] = (int) trim( $settings['send_limit'] );


        foreach( $settings as $key=>$item )
             $result = $wpdb->query( $wpdb->prepare( "REPLACE INTO {$this->tb_prefix}enewsletter_settings SET `key` = '%s', `value` = '%s'", $key, stripslashes( $item ) ) );

        $this->get_settings();

        if ( "install" == $_REQUEST['mode']) {
            // first setup of plugin
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Plugin is installed!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-settings', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Settings are saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Get Settings
     **/
    function get_settings() {
        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_settings ORDER BY `key`", "ARRAY_A");

        if ( $results ) {
            foreach( $results as $setting )
                $this->settings[$setting['key']] = $setting['value'];

            //Set date format
            $date_format = get_option( 'date_format' );
            if ( $date_format )
                $this->settings['date_format'] = $date_format;
            else
                $this->settings['date_format'] = "Y-m-d";

            return $this->settings;
        }
        return false;
    }


    /**
     * Get All Sends
     **/
    function get_sends( $newsletter_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE newsletter_id = %d ORDER BY start_time DESC", $newsletter_id ), "ARRAY_A");

        foreach( $results as $result ){
            $result['count_send_members']   = $this->get_count_send_members( $result['send_id'], 'waiting_send' );
            $result['count_send_cron']      = $this->get_count_send_members( $result['send_id'], 'by_cron' );
            $result['count_sent']           = $this->get_count_send_members( $result['send_id'], 'sent' );
            $result['count_bounced']        = $this->get_count_send_members( $result['send_id'], 'bounced' );

            $sends[] = $result;
        }
        return $sends;
    }

    /**
     * Get count send member
     **/
    function get_count_send_members( $send_id = '', $status = 'waiting_send' ) {
        global $wpdb;
        if ( '' === $send_id )
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE status = '%s'", $status ), "ARRAY_A");
        else
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = '%s'", $send_id, $status ), "ARRAY_A");

        return $count['Count(member_id)'];
    }

    /**
     * Get Sent Newsletters
     **/
    function get_sent_newsletters() {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_newsletters WHERE newsletter_id IN (SELECT newsletter_id FROM {$this->tb_prefix}enewsletter_send GROUP BY newsletter_id)", "ARRAY_A");
        foreach( $results as $result ){

            //count of sent email
            $result["count_sent"] = $this->get_count_sent( $result['newsletter_id'] );

            //count of bounced email
            $result["count_bounced"] = $this->get_count_bounced( $result['newsletter_id'] );

            //count of opened email
            $result["count_opened"] = $this->get_count_opened( $result['newsletter_id'] );

            $last_sent_time = $wpdb->get_row( $wpdb->prepare( "SELECT start_time FROM {$this->tb_prefix}enewsletter_send WHERE newsletter_id = %d ORDER BY start_time DESC", $result['newsletter_id'] ), "ARRAY_A");
            $result["last_sent_time"] = $last_sent_time['start_time'];

            $newsletters[] = $result;
        }
        return $newsletters;
    }

    /**
     * Get all data of newsletter
     **/
     function get_newsletter_data( $newsletter_id ) {
        global $wpdb;
        $newsletter = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_newsletters WHERE newsletter_id = %d", $newsletter_id ), "ARRAY_A");
        return $newsletter;
    }

    /**
     * Get all data of all newsletters
     **/
     function get_newsletters() {
        global $wpdb;
        $newsletters = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_newsletters", "ARRAY_A");
        return $newsletters;
    }

    /**
     * Get users by Role
     **/
    function get_users_by_role( $role ) {
        $wp_user_search = new WP_User_Search( "", "", $role );
        return $wp_user_search->get_results();
    }

    /**
     * Get member by ID
     **/
    function get_member( $member_id ) {
        global $wpdb;
        $member =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ), "ARRAY_A" );

        $member['member_nicename'] = $member['member_fname'];
        $member['member_nicename'] .= $member['member_lname'] ? ' ' . $member['member_lname'] : '';

        if ( $member['member_info'] ) {
            $member =  array_merge ( $member, unserialize( $member['member_info'] ) );
            unset( $member['member_info'] );
        }

        $member['count_sent']   = $this->get_count_sent_to_user( $member_id );
        $member['count_opened'] = $this->get_count_opened_by_user( $member_id );

        return $member;
    }

    /**
     * Get member id of wp user
     **/
    function get_members_by_wp_user_id( $wp_user_id, $blog_id = '' ) {
        global $wpdb;

        //Checking DB prefix
        if ( 0 !== $blog_id && 1 < $blog_id )
            $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
        else
            if ( 1 < $wpdb->blogid )
                $tb_prefix = $wpdb->base_prefix . $wpdb->blogid . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

        $member = $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$tb_prefix}enewsletter_members WHERE wp_user_id = %d", $wp_user_id ), "ARRAY_A" );
        return $member['member_id'];
    }

    /**
     * Get all members
     **/
    function get_members( $arg = "") {
        global $wpdb;

        $orderby = "";

        if ( $arg['orderby'] ) {
            $orderby = "ORDER BY ". $arg['orderby'];
            if ( $arg['order'] )
                $orderby .= " ". $arg['order'];
        }

        $results = $wpdb->get_results(  $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members ". $orderby  ), "ARRAY_A" );

        if ( $results )
                foreach( $results as $member ) {
                    $member['count_sent']   = $this->get_count_sent_to_user( $member['member_id'] );
                    $member['count_opened'] = $this->get_count_opened_by_user( $member['member_id'] );
                    $members[] = $member;
                }

        if ( $arg['sortby'] )
            $members = $this->sort_array_by_field( $members, $arg['sortby'], $arg['order'] );

        return $members;
    }

    /**
     * Get all members of Group
     **/
    function get_members_of_group( $group_id ) {
        global $wpdb;
        $results =  $wpdb->get_results( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_member_group WHERE group_id = %d", $group_id ), "ARRAY_A" );
        foreach( $results as $member ){
            $members[] = $member['member_id'];
        }
        return $members;
    }

    /**
     * Create/Edit new Group
     **/
    function create_group( $group_name, $public, $group_id = "0" ) {
        global $wpdb;

        if ( "1" != $public )
            $public = '0';

        //checking that group not exist other ID
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_groups WHERE LOWER(group_name) = '%s'",  strtolower( $group_name ) ), "ARRAY_A");
        if ( $result ) {
            if ( "0" != $group_id && $result['group_id'] == $group_id ) {

            } else {
                //if group exist with other ID
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Group already exists!!!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
        }


        if ( "0" != $group_id ) {
            //update when edit group
            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s' WHERE group_id = %d", trim( $group_name ), $public, $group_id ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The changes of the group are saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            //create new group
            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s'", trim( $group_name), $public ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'Group is created!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Get all data of all groups
     **/
     function get_groups() {
        global $wpdb;
        $groups = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_groups", "ARRAY_A");
        return $groups;
    }

    /**
     * Get all data of one group
     **/
     function get_group_by_id( $group_id ) {
        global $wpdb;
        $result =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_groups WHERE group_id = %d", $group_id ), "ARRAY_A" );
        return $result;
    }

    /**
     * Delete Group
     **/
    function delete_group( $group_id ) {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_groups WHERE group_id = %d", $group_id ) );
        wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'Group is deleted!', 'email-newsletter' ) ) ), 'admin.php' ) );
        exit;
    }

    /**
     * Change Group
     **/
    function change_group( $member_id, $groups_id ) {
        global $wpdb;

        //deleting old list of groups for user
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );

        $member_data = $this->get_member( $member_id );
        if ( "" == $member_data['unsubscribe_code'] ) {
            $this->subscribe( $member_id, "false" );
        }

        //creating new list of groups for user
        if ( $groups_id )
            foreach( ( array ) $groups_id as $group_id )
                $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );

        wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Groups are changed!', 'email-newsletter' ) ) ), 'admin.php' ) );
        exit;
    }

    /**
     * Get all groups for memeber
     **/
     function get_memeber_groups( $member_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ), "ARRAY_A");
        foreach( $results as $group ){
            $groups[] = $group['group_id'];
        }
        return $groups;
    }

    /**
     * Get all groups for memeber
     **/
    function my_tinymce_plugins( $plugin_array ) {
        $plugin_array['autolink']           = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/autolink/editor_plugin.js';
        $plugin_array['lists']              = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/lists/editor_plugin.js';
        $plugin_array['table']              = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/table/editor_plugin.js';
        $plugin_array['advhr']              = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/advhr/editor_plugin.js';
        $plugin_array['advlink']            = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/advlink/editor_plugin.js';
        $plugin_array['iespell']            = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/iespell/editor_plugin.js';
        $plugin_array['inlinepopups']       = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/inlinepopups/editor_plugin.js';
        $plugin_array['contextmenu']        = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/contextmenu/editor_plugin.js';
        $plugin_array['paste']              = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/paste/editor_plugin.js';
        $plugin_array['fullscreen']         = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/fullscreen/editor_plugin.js';
        $plugin_array['noneditable']        = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/noneditable/editor_plugin.js';
        $plugin_array['visualchars']        = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/visualchars/editor_plugin.js';
        $plugin_array['nonbreaking']        = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/nonbreaking/editor_plugin.js';
        $plugin_array['wordcount']          = $this->plugin_url . 'email-newsletter-files/js/tiny_mce/plugins/wordcount/editor_plugin.js';

        return $plugin_array;
    }

    /**
     * change plugin's icon
     **/
    function change_icon( $plugin_array ) {
       ?>
        <style type="text/css">
            #toplevel_page_newsletters-dashboard .wp-menu-image a img {
                display: none;
            }

            #toplevel_page_newsletters-dashboard div.wp-menu-image {
                background: url("<?php echo $this->plugin_url; ?>email-newsletter-files/images/icon.png") no-repeat scroll 0px 0px transparent;
            }

            #toplevel_page_newsletters-dashboard:hover div.wp-menu-image {
                background: url("<?php echo $this->plugin_url; ?>email-newsletter-files/images/icon.png") no-repeat scroll 0px -32px transparent;
            }

            #toplevel_page_newsletters-dashboard.wp-has-current-submenu div.wp-menu-image {
                background: url("<?php echo $this->plugin_url; ?>email-newsletter-files/images/icon.png") no-repeat scroll 0px -32px transparent;
            }
        </style>
    <?php
    }


}
?>