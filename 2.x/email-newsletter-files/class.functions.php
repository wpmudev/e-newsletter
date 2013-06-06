<?php
/**
* Plugin functions class
**/
class Email_Newsletter_functions {
	
	function get_default_builder_var($type='') {
		switch($type) {
			case 'bg_color':
				$return = (defined('BUILDER_DEFAULT_BG_COLOR') ? BUILDER_DEFAULT_BG_COLOR : '' );
				break;
			case 'bg_image':
				$return = (defined('BUILDER_DEFAULT_BG_IMAGE') ? BUILDER_DEFAULT_BG_IMAGE : '' );
				break;
			case 'link_color':
				$return = (defined('BUILDER_DEFAULT_LINK_COLOR') ? BUILDER_DEFAULT_LINK_COLOR : '' );
				break;
			case 'email_title':
				$return = (defined('BUILDER_DEFAULT_EMAIL_TITLE') ? BUILDER_DEFAULT_EMAIL_TITLE : '' );
				break;
			case 'header_image':
				$return = (defined('BUILDER_DEFAULT_HEADER_IMAGE') ? BUILDER_DEFAULT_HEADER_IMAGE : '' );
				break;
			case 'body_color':
				$return = (defined('BUILDER_DEFAULT_BODY_COLOR') ? BUILDER_DEFAULT_BODY_COLOR : '' );
				break;
			default:
				$return = '';
				break;
		}
		return apply_filters('email_newsletter_get_default_builder_var',$return,$type);
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
        if ( $this->is_enewsletter_page( 'unsubscribe_page' ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-unsubscribe.php" );
            exit;
        }
        elseif ( $this->is_enewsletter_page( 'view_newsletter' ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-view-newsletter.php" );
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
    function check_duplicate_send( $newsletter_id, $member_id, $return_results = 0 ) {
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT b.send_id FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.member_id = %d ", $newsletter_id, $member_id ), "ARRAY_A");
        if ( 0 < $result )
            if ( $return_results == 0 )
                return true;
            else
                return $result;
        else
            return false;
    }

    /**
     * get email body of already sent email
     **/
    function get_sent_email( $send_id, $member_id ) {
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT a.email_body FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.send_id = b.send_id AND b.send_id = %d AND b.member_id = %d ", $send_id, $member_id ), "ARRAY_A");
        if ( 0 < $result )
            return $result;
        else
            return false;
    }

    /**
     * get member id by unsubscribe code
     **/
    function get_member_id_by_code( $code ) {
        global $wpdb;
        return  $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code = '%s'", $code ), "ARRAY_A" );
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
        $count = $wpdb->get_row( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code != ''", "ARRAY_A");
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
     * checks that current page is e-newsletter's page
     **/
    function is_enewsletter_page ( $page = '' ) {
        switch ( $page ) {
            case 'newsletters':
            case 'newsletters-dashboard':
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
            case 'view_newsletter':
                if ( 1 == get_query_var( 'view_newsletter' ) )
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

        $schedules['2mins'] = array(
            'interval' => 2*60,
            'display' => __('every 2 min')
        );

        return $schedules;
    }

    /**
     * Send email
     **/
    function send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents,  $options=array() ) {
        if ( !class_exists( 'PHPMailer' ) )
            require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';

        //Set Sending Method
        switch( $this->settings['outbound_type'] ) {
            case 'smtp':
                $mail->IsSMTP();
                $mail->Host = $this->settings['smtp_host'];
                
                if($this->settings['smtp_secure_method'] == 'tls' || $this->settings['smtp_secure_method'] == 'ssl')
					$mail->SMTPSecure = $this->settings['smtp_secure_method'];
				if(!empty($this->settings['smtp_port']))
					$mail->Port = $this->settings['smtp_port'];
                
                $mail->SMTPAuth = ( strlen( $this->settings['smtp_user'] ) > 0 );
                
                if( $mail->SMTPAuth ){
                    $mail->Username = esc_attr($this->settings['smtp_user']);
                    $mail->Password = $this->_decrypt( $this->settings['smtp_pass'] );
                }
                break;
            case 'mail':
                $mail->IsMail();
                break;

            case 'sendmail':
                $mail->IsSendmail();
                break;
        }

        $mail->From = $email_from;
        if( $email_from_name ) {
            $mail->FromName = $email_from_name;
        }
        $mail->Subject = $email_subject;
        $mail->isHTML( true );
        $mail->MsgHTML( $email_contents );
        $mail->AddAddress( $email_to );

        if( isset($options['bounce_email']) ){
            $mail->Sender = $options['bounce_email'];
        }
        if( isset($options['message_id']) ) {
            $mail->XMailer = $options['message_id'];
            $mail->MessageID = $options['message_id'];
        }

        $sent_status = $mail->Send();
        if( !$sent_status ) {
            $this->write_log( 'Send email eroor: '.$mail->ErrorInfo);
            return false;
        }

        return true;
    }

    function pop3_connet($email_host, $email_port = 110, $email_security = '', $email_username = '', $email_password = '' ) {
        $options = $this->settings['bounce_advanced_options'];
        $this->write_log( "Bounce: saved advanced settings: ".$options );
        if(!empty($options)) {
            $connection = imap_open( '{'.$email_host.':'.$email_port.'/pop3'.$options.$email_security.'}INBOX', $email_username, $email_password );
            $this->write_log( "Bounce: 1 connection: ".$connection );
        }    

        if(!$connection) {
            $combinations = array(
                    '/notls',
                    '/norsh/notls',
                    '/novalidate-cert/notls',
                    '/norsh/novalidate-cert/notls',
                    '/norsh',
                    '/novalidate-cert',
                    '/norsh/novalidate-cert',
                    '/tls',
                    '/norsh/tls',
                    '/novalidate-cert/tls',
                    '/norsh/novalidate-cert/tls'
                );
            foreach ($combinations as $combination) {
                $connection = imap_open( '{'.$email_host.':'.$email_port.'/pop3'.$combination.$email_security.'}INBOX', $email_username, $email_password );
                if($connection) {
                    $this->write_log( "Bounce: detected advanced settings: ".$combination );
                    $this->save_settings_array(array('bounce_advanced_options' => $combination));
                    return $connection;
                }
            }
        }
        else
            return $connection;

        $this->write_log( "Bounce: settings incorrect: ".$connection );
        return 0;
    }

    /**
     * Save Settings
     **/
    function save_settings( $settings, $tb_prefix = '', $redirect = 1 ) {
        global $wpdb, $wp_roles;
		
		if(empty($tb_prefix))
			$tb_prefix = $this->tb_prefix;

        if( ! is_array( $settings ) )
            $settings = array();
		

		$caps = $settings['email_caps'];
		unset($settings['email_caps']);
		
		foreach($wp_roles->get_names() as $name => $obj) {
			if($name == 'administrator') continue;
			$role_obj = get_role($name);
			if($role_obj) {
				foreach($this->capabilities as $cap => $label) {
					if(isset($caps[$cap][$name])) {
						$role_obj->add_cap($cap);
					} else {
						$role_obj->remove_cap($cap);
					}
				}
			}
		}
		
        //change time for CRON
        if ( isset($settings['cron_enable']) && 1 == $settings['cron_enable'] ) {
			if ( wp_next_scheduled( $this->cron_send_name ) )
				wp_clear_scheduled_hook( $this->cron_send_name );
				
            wp_schedule_event( time(), '2mins', $this->cron_send_name );
        }
		else {
			if ( wp_next_scheduled( $this->cron_send_name ) )
				wp_clear_scheduled_hook( $this->cron_send_name );
		}

        if ( isset($settings['send_limit']) )
            $settings['send_limit'] = (int) trim( $settings['send_limit'] );

        //Encrypt SMTP password
        if ( isset( $settings['smtp_pass'] ) && '********' == $settings['smtp_pass'] )
            unset( $settings['smtp_pass'] );
        elseif( isset( $settings['smtp_pass'] ) && '' != $settings['smtp_pass'] )
            $settings['smtp_pass'] = $this->_encrypt( $settings['smtp_pass'] );
        else
            $settings['smtp_pass'] = '';
			
        //Encrypt POP3 password
        if ( isset( $settings['bounce_password'] ) && '********' == $settings['bounce_password'] )
            unset( $settings['bounce_password'] );
        elseif( isset( $settings['bounce_password'] ) && '' != $settings['bounce_password'] )
            $settings['bounce_password'] = $this->_encrypt( $settings['bounce_password'] );
        else
            $settings['bounce_password'] = '';
		

        foreach( $settings as $key => $item )
             $result = $wpdb->query( $wpdb->prepare( "REPLACE INTO {$tb_prefix}enewsletter_settings SET `key` = '%s', `value` = '%s'", $key, stripslashes( $item ) ) );

        if ( isset($_REQUEST['mode']) && "install" == $_REQUEST['mode']) {
            // first setup of plugin
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Plugin is installed!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } elseif($redirect == 1) {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-settings', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Settings are saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Simply saves array of settings
     **/
    function save_settings_array( $settings, $tb_prefix = '' ) {
       global $wpdb;
        
        if(empty($tb_prefix))
            $tb_prefix = $this->tb_prefix;

        if( ! is_array( $settings ) )
            $settings = array();

        foreach( $settings as $key => $item )
             $result = $wpdb->query( $wpdb->prepare( "REPLACE INTO {$tb_prefix}enewsletter_settings SET `key` = '%s', `value` = '%s'", $key, stripslashes( $item ) ) );
        
        return $result;
    }

    /**
     * Get Settings
     **/
    function get_settings($tb_prefix = '') {
        global $wpdb;
		
		if(empty($tb_prefix))
			$tb_prefix = $this->tb_prefix;
        
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_settings'" ) == "{$tb_prefix}enewsletter_settings" ) {
            $results = $wpdb->get_results( "SELECT * FROM {$tb_prefix}enewsletter_settings ORDER BY `key`", "ARRAY_A" );

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
        }
        return false;
    }


    /**
     * Get All Sends
     **/
    function get_sends( $newsletter_id ) {
		
        global $wpdb;
        $sends = NULL;
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
        elseif ( 'by_cron' == $status )
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND (status = 'by_cron' OR status >0)", $send_id ), "ARRAY_A");
        else
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(member_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = '%s'", $send_id, $status ), "ARRAY_A");

        return $count['Count(member_id)'];
    }

    /**
     * Get Sent Newsletters
     **/
    function get_sent_newsletters() {
        global $wpdb;
        $newsletters = NULL;
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
     * Get a single email metakey value of a single newsletter
	 * Similar to get_post_meta()
     **/
     function get_newsletter_meta( $id, $meta_key, $default=false ) {
     	global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare( "SELECT meta_value FROM {$this->tb_prefix}enewsletter_meta WHERE email_id = %d AND meta_key = %s", $id, $meta_key ), "ARRAY_A");
                
        if( is_array($result) && $result != false )
        	return $result['meta_value'];
		else if($default !== false)
			return $default;
		else
			return false;       
        
	 }
	 
	 /**
     * Get all email meta data of a single newsletter
	 * Similar to get_post_custom()
     **/
     function get_newsletter_custom( $newsletter_id ) {
     	global $wpdb;
        $newsletter = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM {$this->tb_prefix}enewsletter_meta WHERE email_id = %d", $newsletter_id ), "ARRAY_A");
        
        if(!empty($newsletter) && $newsletter != false)
        	return $newsletter;
		else
			return false;
	 }
	 /**
     * Update email meta data of a single newsletter
	 * Similar to update_post_meta()
     **/
     function update_newsletter_meta( $newsletter_id, $key, $value ) {
     	global $wpdb;
		
		$where = array(
			'email_id' => $newsletter_id,
			'meta_key' => $key,
		);
		$data = array(
			'email_id' => $newsletter_id,
			'meta_value' => $value,
			'meta_key' => $key
		);
		
		if( $this->get_newsletter_meta($newsletter_id,$key) !== false) {
			$wpdb->update( "{$this->tb_prefix}enewsletter_meta", $data, $where);
		} else {
			$wpdb->insert( "{$this->tb_prefix}enewsletter_meta", $data );
		}
	 }
	 /**
     * Delete email meta data of a single newsletter
	 * Similar to update_post_meta()
     **/	 
     function delete_newsletter_meta( $newsletter_id, $key = false, $exclude = 0 ) {
		global $wpdb;
		
     	if($key !== false) {
			
			if($exclude == 1)
				$query = $wpdb->prepare( 
					"
					DELETE FROM {$this->tb_prefix}enewsletter_meta
					WHERE email_id = %d
					AND meta_key != %s
					",
					$newsletter_id, $key 
					);
			else
				$query = $wpdb->prepare( 
					"
					DELETE FROM {$this->tb_prefix}enewsletter_meta
					WHERE email_id = %d
					AND meta_key == %s
					",
					$newsletter_id, $key 
					);
		}
		else
			$query = $wpdb->prepare( 
				"
				DELETE FROM {$this->tb_prefix}enewsletter_meta
				WHERE email_id = %d
				",
				$newsletter_id 
				);
			
		$wpdb->query($query);
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

        $where      = "";
        $orderby    = "";
        $limit      = "";

        if ( isset( $arg['where'] ) ) {
            $where = "WHERE ". $arg['where'];
        }

        if ( isset( $arg['limit'] ) ) {
            $limit = $arg['limit'];
        }

        if ( isset( $arg['orderby'] ) ) {
            $orderby = "ORDER BY ". $arg['orderby'];
            if ( $arg['order'] )
                $orderby .= " ". $arg['order'];
        }

        $results = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_members ". $where . " ".  $orderby . " " . $limit, "ARRAY_A");

        if ( $results )
                foreach( $results as $member ) {
                    $member['count_sent']   = $this->get_count_sent_to_user( $member['member_id'] );
                    $member['count_opened'] = $this->get_count_opened_by_user( $member['member_id'] );
                    $members[] = $member;
                }

        if ( isset( $arg['sortby'] ) )
            $members = $this->sort_array_by_field( $members, $arg['sortby'], $arg['order'] );

        return $members;
    }


    /**
     * Get all members of Group
     **/
    function get_members_of_group( $group_id, $limit = '' ) {
        global $wpdb;
        $members = NULL;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_member_group WHERE group_id = %d" . $limit , $group_id ), "ARRAY_A" );
        foreach( $results as $member ){
            $members[] = $member['member_id'];
        }
        return $members;
    }

    /**
     * Get count members of Group
     **/
    function get_count_members_of_group( $group_id ) {
        global $wpdb;
        $results = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(member_id) FROM {$this->tb_prefix}enewsletter_member_group WHERE group_id = %d", $group_id ) );
        return $results;
    }

    /**
     * Get unsubscribe members
     **/
    function get_unsubscribe_member( $limit = '' ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code = '' OR unsubscribe_code IS NULL" . $limit ), "ARRAY_A" );
        return $results;
    }

    /**
     * Get count unsubscribe members
     **/
    function get_count_unsubscribe_members() {
        global $wpdb;
        $results = $wpdb->get_var( "SELECT COUNT(member_id) FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code = '' OR unsubscribe_code IS NULL" );
        return $results;
    }

    /**
     * Create/Edit new Group
     **/
    function create_group( $group_name, $public, $group_id = "0" ) {
        global $wpdb;

        //checking that group not exist other ID
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_groups WHERE LOWER(group_name) = '%s'",  strtolower( $group_name ) ), "ARRAY_A");
        if ( $result ) {
            if ( "0" != $group_id && $result['group_id'] == $group_id ) {

            } else {
                //if group exist with other ID
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The group already exists!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
        }


        if ( "0" != $group_id ) {
            //update when edit group
            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s' WHERE group_id = %d", trim( $group_name ), $public, $group_id ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The group has been modified.', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            //create new group
            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s'", trim( $group_name), $public ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The group has been created.', 'email-newsletter' ) ) ), 'admin.php' ) );
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
     * Bulk option -  add member to group
     **/
    function add_members_group( $members_id, $group_id ) {
        global $wpdb;

        if ( 0 < $group_id ) {
            foreach( $members_id as $member_id ) {
                $result = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d AND group_id = %d", $member_id, $group_id ) );

                if ( ! $result )
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );
            }
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Members are added to the group!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Bulk option -  delete member from group
     **/
    function delete_members_group( $members_id, $group_id ) {
        global $wpdb;

        if ( 0 < $group_id ) {
            foreach( $members_id as $member_id )
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d AND group_id = %d", $member_id, $group_id ) );

            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Members are deleted from the group!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Get all groups for memeber
     **/
     function get_memeber_groups( $member_id ) {
        global $wpdb;
        $groups = NULL;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT group_id FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ), "ARRAY_A");
        foreach( $results as $group ){
            $groups[] = $group['group_id'];
        }
        return $groups;
    }
	
	function import_wpmu_plugins() {
		if(class_exists('Marketpress')) {
			register_enewsletter_plugin_template('mp_new_order','MarketPress','New Order');
			add_filter('mp_order_notification_body',array(&$this,'process_marketpress_body'), 999, 2);
			add_filter('email_newsletter_marketpress_stop_new_order_email', create_function("", 'return true;') );
		}
	}
	function process_marketpress_body($msg,$order) {
		global $mp;
		$newsletters = $this->get_newsletters();
		foreach($newsletters as $eletter) {
			$meta = $this->get_newsletter_meta($eletter['newsletter_id'], 'plugin_template_id');
			if($meta && $meta == 'mp_new_order')
				return $mp->filter_email($order, $this->make_email_body($eletter['newsletter_id']));
		}
	}

    /**
     * import members
     **/
    function import_members() {
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'] . '/';

        // .csv full file name
        $file = $upload_dir . $_FILES['import_members_file']['name'];

        if ( is_writable( $upload_dir ) ) {
            if ( move_uploaded_file( $_FILES['import_members_file']['tmp_name'], $file ) ) {
                $f = fopen( $file, 'rt' ) or wp_die( 'error!' );

                //Set Separation sign
                if ( isset( $_REQUEST['separ_sign'] ) &&  1 == $_REQUEST['separ_sign'] )
                    $separ_sign = ';';
                elseif ( isset( $_REQUEST['separ_sign'] ) &&  2 == $_REQUEST['separ_sign'] )
                    $separ_sign = ',';
                else
                    $separ_sign = ';';

                // read file and write all to array
                for ( $i = 0; $data = fgetcsv( $f, 1000, $separ_sign ); $i++ ) {
                    $num = count( $data );

                    for ( $c = 0; $c < $num; $c++ )
                        $a[$c] = $data[$c];

                    $import_data[] = $a;
                }
                fclose( $f );
                unlink( $file );

                //write data to member table
                if ( is_array( $import_data ) ) {
                    global $wpdb;
                    $i = 0;
                    foreach( $import_data as $data ) {
                        $unsubscribe_code = $this->gen_unsubscribe_code();
                        $email = $data[0];
                        $fname = ( isset( $data[1] ) ) ? $data[1] : '';
                        $lname = ( isset( $data[2] ) ) ? $data[2] : '';

                        if ( isset( $_REQUEST['import_groups_id'] ) && is_array( $_REQUEST['import_groups_id'] ) )
                            $import_groups_id = $_REQUEST['import_groups_id'];

                        $result = $wpdb->get_var( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE member_email = %s", $email ) );

                        if ( 0 < $result ) {
                            //email of member already exist
                            $exist_members[] = $email;
                        } else {
                            //create new member
                            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_members SET
                                    wp_user_id = 0,
                                    member_email = %s,
                                    member_fname = %s,
                                    member_lname = %s,
                                    join_date = %d,
                                    unsubscribe_code = '%s'
                                 ", $email, $fname, $lname, time(), $unsubscribe_code ) );

                            $member_id = $wpdb->insert_id;

                            //creating new list of groups for user
                            if ( isset( $import_groups_id ) )
                                foreach( $import_groups_id as $group_id )
                                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );

                            $i++;
                        }
                    }
                }

                $dmsg = '';

                if ( 0 < $i )
                    $dmsg .=  __( 'Import is finished successfully,', 'email-newsletter' ) . ' ' . $i . ' ' . __( 'members are added.', 'email-newsletter' );

                if ( isset( $exist_members ) && is_array( $exist_members ) ) {
                    $dmsg .= '<br />' . __( 'These emails already exist in member list:', 'email-newsletter' ) . '<br />';
                    foreach($exist_members as $exist_member )
                        $dmsg .= $exist_member . '<br />';
                }
				
				if(empty($dmsg)) {
					$dmsg .= 'Import ERROR: Nothing to import';
				}

                wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( $dmsg ) ), 'admin.php' ) );
                exit;

            } else {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Import ERROR: Problem with uploading of the file!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
        } else {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Import ERROR: Please change permission for the folder /wp-contant/uploads/', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }


    /**
     * Get pagination data
     **/
    function get_pagination_data( $count, $per_page ) {
            if ( 'all' == $per_page )
                $per_page = 1000000;

            if ( $count > $per_page ) {
                $pagination_data['count'] = $count;

                if ( isset( $_REQUEST['cpage'] ) && 0 < $_REQUEST['cpage'] )
                    $pagination_data['cpage'] = $_REQUEST['cpage'];
                else
                    $pagination_data['cpage'] = 1;

                $pagination_data['cpage_str'] = '&cpage=' . $pagination_data['cpage'];
                $start = ( $pagination_data['cpage'] - 1 ) * $per_page;
                $pagination_data['limit'] = ' LIMIT ' . $start . ',' . $per_page;

                return $pagination_data;
            }

        return NULL;
    }

    /**
     * Prepare inline styles
     **/
	function do_inline_styles($themedata, $contents) {
		if($themedata) {
			
			if(!class_exists('CssToInlineStyles'))
				require_once($this->plugin_dir.'email-newsletter-files/builder/lib/css-inline.php');
				
			$style_path = $themedata->theme_root . '/' . $themedata->stylesheet . '/style.css';
			if(file_exists($style_path)) {
				$handle = fopen( $style_path, "r" );
        		$style_content = fread( $handle, filesize( $style_path ) );
				
        		$css_inline = new CssToInlineStyles("<head><meta http-equiv='Content-type' content='text/html; charset=UTF-8' /></head>".$contents,'<style type="text/css">'.$style_content.'</style>');
				$contents = $css_inline->convert();
				
				$contents = str_replace("<head><meta http-equiv='Content-type' content='text/html; charset=UTF-8' /></head>", '', $contents) ;
				
				return $contents;
			}
		}	
	}

    /**
     * Make email body
     **/
    function make_email_body( $newsletter_id ) {
        global $email_builder;

        //get data of newsletter
        $newsletter_data = $this->get_newsletter_data( $newsletter_id );
        
        if(!$newsletter_data || empty($newsletter_data))
            return false;
    
        //open template file

        $theme = $this->get_selected_theme($newsletter_data['template']);

        $template_path  = $theme['dir'];
        $template_url  = $theme['url'];
        $filename   = $template_path . "template.html";

        if(file_exists($filename)) {
            $handle     = fopen( $filename, "r" );
            $contents   = fread( $handle, filesize( $filename ) );
            fclose( $handle );      
        } else
            return false;

        $newsletter_data['content'] = '{OPENED_TRACKER}' . $newsletter_data['content'];
        
        // Extra Meta Replacements
        // Use the "email_newsletter_make_email_body" filter below to filter your own custom data
        
        // We check for meta-data then look for a defined default coming from
        // the newsletter template then we pick something anyways using the
        // get_default_builder_var() located in class.functions.php

        //Translate template default elements
        $contents = str_replace( "From", __( 'From', 'email-newsletter' ), $contents );
        $contents = str_replace( "Not interested anymore?", __( 'Not interested anymore?', 'email-newsletter' ), $contents );
        $contents = str_replace( "Unsubscribe Instantly.", __( 'Unsubscribe Instantly.', 'email-newsletter' ), $contents );
        
        //Take care of all text, replace body, title, subject... stuff like this:)
        $contents = str_replace( "{EMAIL_BODY}", $newsletter_data['content'], $contents );
        $contents = apply_filters('email_newsletter_make_email_content', $contents);

        //Email Title
        $email_title = $this->get_newsletter_meta($newsletter_id,'email_title', $this->get_default_builder_var('email_title') );
        $email_title = apply_filters('email_newsletter_make_email_title',$email_title,$newsletter_id);
        $contents = str_replace( "{EMAIL_TITLE}", $email_title, $contents);
        
        $contents = str_replace( "{EMAIL_SUBJECT}", $newsletter_data['subject'], $contents );
        $contents = str_replace( "{FROM_NAME}", (isset($newsletter_data['from_name']) ? $newsletter_data['from_name'] : $this->settings['from_name']), $contents );
        $contents = str_replace( "{FROM_EMAIL}", (isset($newsletter_data['from_email']) ? $newsletter_data['from_email'] : $this->settings['from_email']), $contents );
        $contents = str_replace( "{CONTACT_INFO}", (isset($newsletter_data['contact_info']) ? $newsletter_data['contact_info'] : $this->settings['contact_info']), $contents );
        
        //Date
        $date_format = (isset($this->settings['date_format']) ? $this->settings['date_format'] : "F j, Y");
        $contents = str_replace( "{DATE}", date($date_format), $contents );

        //do the inline styling
        $themedata = $email_builder->find_builder_theme();
        $contents = $this->do_inline_styles($themedata, $contents);
        
        // REPLACE THE image LINKS AT THE START
        $contents = str_replace( "images/", $template_url . "/images/", $contents );
        
        // BG COLOR
        $bg_color = $this->get_newsletter_meta($newsletter_id,'bg_color', $this->get_default_builder_var('bg_color'));
        $bg_color = apply_filters('email_newsletter_make_email_bgcolor',$bg_color,$newsletter_id);
        $contents = str_replace( "{BG_COLOR}", $bg_color, $contents);
        
        // BG IMAGE         
        $default_bg = $this->get_default_builder_var('bg_image');
        if(!empty($default_bg))
            $default_bg = $template_url.$default_bg;
        else 
            $default_bg = '';
        $bg_image = $this->get_newsletter_meta($newsletter_id,'bg_image', $default_bg);
        $bg_image = apply_filters('email_newsletter_make_email_bg_image',$bg_image,$newsletter_id);
        $contents = str_replace( "{BG_IMAGE}", $bg_image, $contents);
        
        // LINK COLOR
        $link_color = $this->get_newsletter_meta($newsletter_id,'link_color', $this->get_default_builder_var('link_color'));
        $link_color = apply_filters('email_newsletter_make_email_link_color',$link_color,$newsletter_id);
        $contents = str_replace( "{LINK_COLOR}", $link_color, $contents);
        $contents = str_replace( "#LINK_COLOR", $link_color, $contents);
        
        // BODY COLOR
        $body_color = $this->get_newsletter_meta($newsletter_id,'body_color', $this->get_default_builder_var('body_color'));
        $body_color = apply_filters('email_newsletter_make_email_body_color',$body_color,$newsletter_id);
        $contents = str_replace( "{BODY_COLOR}", $body_color, $contents);
        
        return apply_filters('email_newsletter_make_email_body', $contents, $newsletter_id);
    }

    /**
     * Personalize email
     **/
    function personalise_email_body($contents, $member_id, $code, $send_id, $changes = array()) {
        //adds view in browser message
        if(!isset($changes['disable_view_link'])) {
            $settings = $this->get_settings();
            if(!empty($settings['view_browser']))
                $contents = $settings['view_browser'].$contents;
        }

        if(!empty($changes['user_name']))
            $contents = str_replace( "{USER_NAME}", $changes['user_name'], $contents );
        else
            $contents = str_replace( "{USER_NAME}", '', $contents );

        if(!empty($changes["member_email"]))
            $contents = str_replace( "{TO_EMAIL}", $changes["member_email"], $contents );
        else
            $contents = str_replace( "{TO_EMAIL}", '', $contents );

        $contents = str_replace( "{OPENED_TRACKER}", '<img src="' . admin_url('admin-ajax.php?action=check_email_opened&send_id=' . $send_id . '&member_id=' . $member_id) . '" width="1" height="1" style="display:none;" />', $contents );
        $contents = str_replace( "%7BUNSUBSCRIBE_URL%7D", site_url('/e-newsletter/unsubscribe/' . $code . $member_id . '/'), $contents );
        $view_browser_url = site_url('/e-newsletter/view/' . $code . $send_id . '/');
        $contents = str_replace( "{VIEW_LINK}", $view_browser_url, $contents );
        $contents = str_replace( "%7BVIEW_LINK%7D", $view_browser_url, $contents );
        
        return $contents;
    }

    /**
     * Choose nicename
     **/
    function get_nicename($wp_user_id, $user_nicename) {
        if($wp_user_id == 0 ) {
            $user_name = $user_nicename;
        }
        else {
            $wp_user = ($wp_user_id == 0 ? false : get_user_by('id', $wp_user_id));
            $user_name = is_a($wp_user,'WP_User') ? $wp_user->display_name : '';
        }
        return $user_name;
    }

    /**
     * Install of plugin - creating tables in DB
     **/
    function install( $blog_id = '' ) {
        global $wpdb;

        if ( function_exists( 'is_multisite' ) && is_multisite() && 0 !== $blog_id && isset( $_GET['networkwide'] ) && $_GET['networkwide'] == 1 ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
            if ( 0 !== $blog_id )
                $blogids[] = $wpdb->blogid;
            else
                $blogids[] = $blog_id;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_newsletters'" ) != "{$tb_prefix}enewsletter_newsletters" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_newsletters` (
                    `newsletter_id` int(11) NOT NULL auto_increment,
                    `create_date` int(11) NOT NULL,
                    `template` varchar(100) NOT NULL,
                    `subject` varchar(255) NOT NULL,
                    `from_name` varchar(255) NOT NULL,
                    `from_email` varchar(255) NOT NULL,
                    `content` text NOT NULL,
                    `contact_info` varchar(255) NOT NULL,
                    `bounce_email` varchar(255) NOT NULL,
                    PRIMARY KEY (`newsletter_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send'" ) != "{$tb_prefix}enewsletter_send" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_send` (
                    `send_id` int(11) NOT NULL auto_increment,
                    `newsletter_id` int(11) NOT NULL,
                    `start_time` int(11) DEFAULT '0',
                    `end_time` int(11) DEFAULT '0',
                    `email_body` text,
                    PRIMARY KEY (`send_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send_members'" ) != "{$tb_prefix}enewsletter_send_members" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_send_members` (
                    `send_id` int(11) NOT NULL,
                    `member_id` int(11) NOT NULL,
                    `status` varchar(15),
                    `opened_time` int(11) DEFAULT '0',
                    `bounce_time` int(11) DEFAULT '0',
                    `sent_time` int(11)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_groups'" ) != "{$tb_prefix}enewsletter_groups" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_groups` (
                    `group_id` int(11) NOT NULL auto_increment,
                    `group_name` varchar(255) NOT NULL,
                    `public` varchar(1) NOT NULL,
                    PRIMARY KEY (`group_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_member_group'" ) != "{$tb_prefix}enewsletter_member_group" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_member_group` (
                    `member_id` int(11) NOT NULL,
                    `group_id` int(11) NOT NULL
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_members'" ) != "{$tb_prefix}enewsletter_members" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_members` (
                    `member_id` int(11) NOT NULL auto_increment,
                    `wp_user_id` int(11) DEFAULT '0',
                    `member_fname` varchar(255),
                    `member_lname` varchar(255),
                    `member_email` varchar(255) NOT NULL,
                    `join_date` int(11) NOT NULL,
                    `member_info` text,
                    `unsubscribe_code` varchar(20),
                    PRIMARY KEY (`member_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
                $result = $wpdb->query( $enewsletter_table );

                //Sync exist wp users
                $arg = array (
                    'blog_id' => $blog_id
                );
                $users = get_users( $arg );
                if ( $users )
                    foreach( $users as $user ) {
                        $unsubscribe_code = $this->gen_unsubscribe_code();
                        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$tb_prefix}enewsletter_members SET
                            wp_user_id = %d,
                            member_fname = %s,
                            member_email = %s,
                            join_date = %d,
                            unsubscribe_code = '%s'
                         ", $user->ID, $user->user_nicename, $user->user_email, time(), $unsubscribe_code ) );
                    }

            }

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_settings'" ) != "{$tb_prefix}enewsletter_settings" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_settings` (
                    `key` varchar(255) NOT NULL,
                    `value` varchar(255) NOT NULL,
                    PRIMARY KEY (`key`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }
			
			// Added in v2.0
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_meta'" ) != "{$tb_prefix}enewsletter_meta" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_meta` (
                    `meta_id` int(11) NOT NULL auto_increment,
                    `email_id` int(11) NOT NULL,
                    `meta_key` varchar(255),
                    `meta_value` longtext,
                    PRIMARY KEY (`meta_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }
			
			//create folder for custom themes
			$custom_theme_dir = $this->get_custom_theme_dir();
			
			if (!is_dir($custom_theme_dir)) {
				mkdir($custom_theme_dir);
			}

        }
    }
	
	function upgrade( $blog_id = '' ) {
		global $wpdb;
		
		if ( $this->is_plugin_active_for_network(plugin_basename(__FILE__)) ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
            if ( 0 !== $blog_id )
                $blogids[] = $wpdb->blogid;
            else
                $blogids[] = $blog_id;
        }

        foreach ( $blogids as $blog_id ) {
        	//Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;
                
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_meta'" ) != "{$tb_prefix}enewsletter_meta" ) {

                $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_meta` (
                    `meta_id` int(11) NOT NULL auto_increment,
                    `email_id` int(11) NOT NULL,
                    `meta_key` varchar(255),
                    `meta_value` longtext,
                    PRIMARY KEY (`meta_id`)
                ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

                $result = $wpdb->query( $enewsletter_table );
            }
			
			//Take care of pop3 encryption
			$settings = $this->get_settings($tb_prefix);
			if(isset($settings['bounce_password']) && strlen($settings['bounce_password']) != 44) {
				$new_settings['bounce_password'] = $this->_encrypt($settings['bounce_password']);
				$this->save_settings($new_settings, $tb_prefix, 0);
			}
		}
	}

    /**
     * Deleting tables from DB
     **/
    function uninstall( $blog_id = '' ) {
        global $wpdb;

        if ( $this->is_plugin_active_for_network(plugin_basename(__FILE__)) ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
            if ( 0 !== $blog_id )
                $blogids[] = $wpdb->blogid;
            else
                $blogids[] = $blog_id;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            //Delete all CRON actions
            if ( wp_next_scheduled( $this->cron_send_name ) )
                wp_clear_scheduled_hook( $this->cron_send_name );

            if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );

            if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );

            delete_option( 'enewsletter_cron_send_run' );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_newsletters'" ) == "{$tb_prefix}enewsletter_newsletters" )
                $wpdb->query("DROP TABLE IF EXISTS {$tb_prefix}enewsletter_newsletters");

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send'" ) == "{$tb_prefix}enewsletter_send" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_send" );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send_members'" ) == "{$tb_prefix}enewsletter_send_members" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_send_members" );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_groups'" ) == "{$tb_prefix}enewsletter_groups" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_groups" );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_member_group'" ) == "{$tb_prefix}enewsletter_member_group" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_member_group" );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_members'" ) == "{$tb_prefix}enewsletter_members" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_members" );

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_settings'" ) == "{$tb_prefix}enewsletter_settings" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_settings" );

            //added in 2.0
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_meta'" ) == "{$tb_prefix}enewsletter_meta" )
                $wpdb->query( "DROP TABLE IF EXISTS {$tb_prefix}enewsletter_meta" );

            delete_option('email_newsletter_version');
        }
		
		//remove folder for custom themes
		$custom_theme_dir = $this->get_custom_theme_dir();
		if (is_dir($custom_theme_dir)) {
			$this->delete_dir($custom_theme_dir);
		}

        //remove data about site options
        if($this->is_plugin_active_for_network(plugin_basename(__FILE__)))
            delete_site_option('email_newsletter_version');
        else
            delete_option('email_newsletter_version');
    }

    /**
     * Write log for CRON
     **/
    function write_log( $message ) {
        if(!$this->debug)
            return false;

        $file = $this->plugin_dir . "email-newsletter-files/debug.log";

        $handle = fopen( $file, 'ab' );
        $data = date( "[Y-m-d H:i:s]" ) . $message . "\r\n";
        fwrite($handle, $data);
        fclose($handle);
    }
	
    /**
     * Get path to custom theme directory
     **/
    function get_custom_theme_dir() {
		$enewsletter_themes_dir = wp_upload_dir();
		$enewsletter_themes_dir = $enewsletter_themes_dir['basedir'];
		//$enewsletter_themes_dir = substr($enewsletter_themes_dir, 0, strpos($enewsletter_themes_dir, '/uploads'));
		
		if(!empty($enewsletter_themes_dir)) {
			$enewsletter_themes_dir = $enewsletter_themes_dir.'/enewsletter-custom-themes';
			return $enewsletter_themes_dir;
		}
		
		return false;
    }

    /**
     * Get path to custom theme directory
     **/
    function get_selected_theme($theme_name) {
        register_theme_directory($this->template_custom_directory);
        register_theme_directory($this->template_directory);
        
        //cheating message fix
        wp_clean_themes_cache();

        $themes = wp_get_themes();
        $theme_root_dir = $themes[$theme_name]->theme_root.'/';

        if (strpos($theme_root_dir, 'enewsletter-custom-themes') !== FALSE) {
            $upload_dir = wp_upload_dir();
            $theme_root_url = $upload_dir['baseurl'].'/enewsletter-custom-themes/';
        }
        else
            $theme_root_url = $this->plugin_url . "email-newsletter-files/templates/";

        $template_dir  = $theme_root_dir.$theme_name.'/';
        $template_url  = $theme_root_url.$theme_name.'/';

        return array('url' => $template_url, 'dir' => $template_dir);
    }
	
    /**
     * Deletes whole dir
     **/
    function delete_dir($src) {
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($src . '/' . $file) ) { 
					delete_dir($src . '/' . $file); 
				} 
				else { 
					unlink($src . '/' . $file); 
				} 
			} 
		} 
		rmdir($src);
		closedir($dir); 
    }

    /**
     * Check if network active
     **/
    function is_plugin_active_for_network( $plugin ) {
        if ( !is_multisite() )
            return false;

        $plugins = get_site_option( 'active_sitewide_plugins');
        if ( isset($plugins[$plugin]) )
            return true;

        return false;
    }

    /**
     * Encrypt text (SMTP & POP password)
     **/
    protected function _encrypt( $text ) {
        if  ( function_exists( 'mcrypt_encrypt' ) ) {
            return base64_encode( @mcrypt_encrypt( MCRYPT_RIJNDAEL_256, DB_PASSWORD, $text, MCRYPT_MODE_ECB ) );
        } else {
            return $text;
        }
    }

    /**
     * Decrypt password (SMTP & POP password)
     **/
    protected function _decrypt( $text ) {
        if ( function_exists( 'mcrypt_decrypt' ) ) {
            return trim( @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, DB_PASSWORD, base64_decode( $text ), MCRYPT_MODE_ECB ) );
        } else {
            return $text;
        }
    }


}
?>
