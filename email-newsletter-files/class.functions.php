<?php
/**
* Plugin functions class
**/
class Email_Newsletter_functions {

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
            case 'subscribe_page':
                if ( 1 == get_query_var( 'subscribe_page' ) )
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
            case 'title_color':
                $return = (defined('BUILDER_DEFAULT_TITLE_COLOR') ? BUILDER_DEFAULT_TITLE_COLOR : '' );
                break;
            case 'alternative_color':
                $return = (defined('BUILDER_DEFAULT_ALTERNATIVE_COLOR') ? BUILDER_DEFAULT_ALTERNATIVE_COLOR : '' );
                break;
			default:
				$return = '';
				break;
		}
		return apply_filters('email_newsletter_get_default_builder_var',$return,$type);
	}

    /**
     * Generate Unsubscribe code
     **/
    function gen_unsubscribe_code($wp_only_user = 0) {
        $now = time();
        $unsubscribe_code = substr( $now, strlen( $now ) - 3, 3 ) . substr( md5( uniqid( rand(), true ) ), 0, 8 ) . substr( md5( $now . rand() ), 0, 4);

        if($wp_only_user)
            $unsubscribe_code = 'wp'.substr($unsubscribe_code, 2);

        return $unsubscribe_code;
    }

    /**
     * Checking of duplicate send
     **/
    function check_duplicate_send( $newsletter_id, $member_id = 0, $wp_only_user_id = 0, $return_results = 0 ) {
        global $wpdb;

        if(empty($member_id) && is_numeric($wp_only_user_id) && $wp_only_user_id > 0) {
            $type = 'wp_only_user_id';
            $id = $wp_only_user_id;
        }
        else {
            $type = 'member_id';
            $id = $member_id;
        }

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT b.send_id FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.{$type} = %d ", $newsletter_id, $id ), "ARRAY_A");
        if ( 0 < $result )
            if ( $return_results == 0 )
                return true;
            else
                return $result;
        else
            return false;
    }

    /**
     * Checking for x send status
     **/
    function check_bounced_send( $newsletter_id, $member_id ) {
        global $wpdb;
        $result_bounced = $wpdb->get_row( $wpdb->prepare( "SELECT b.send_id FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.member_id = %d AND b.status = 'bounced'", $newsletter_id, $member_id ), "ARRAY_A");
        if ( $result_bounced ){
            $result_sent = $wpdb->get_row( $wpdb->prepare( "SELECT b.send_id FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.newsletter_id = %d AND a.send_id = b.send_id AND b.member_id = %d AND b.status = 'sent'", $newsletter_id, $member_id ), "ARRAY_A");
            if(!$result_sent)
                return true;
        }
        else
            return false;
    }

    /**
     * Get count of sent email by newsletter_id or for all newsletters
     **/
     function get_count_stats( $newsletter_id = '' ) {
        global $wpdb;
        $stats = $wpdb->get_row( "SELECT SUM(sent) AS sent, SUM(bounced) AS bounced, SUM(opened) AS opened FROM {$this->tb_prefix}enewsletter_members", "ARRAY_A");
        return $stats;
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
     * Get count send member
     **/
    function get_count_send_members( $send_id = '', $status = 'waiting_send' ) {
        global $wpdb;
        if ( '' === $send_id )
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(*) AS count FROM {$this->tb_prefix}enewsletter_send_members WHERE status = %s", $status ), "ARRAY_A");
        elseif ( 'by_cron' == $status )
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(*) AS count FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND (status = 'by_cron' OR status >0)", $send_id ), "ARRAY_A");
        else
            $count = $wpdb->get_row( $wpdb->prepare( "SELECT Count(*) AS count FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = %s", $send_id, $status ), "ARRAY_A");

        return $count['count'];
    }

    /**
     * Get All Sends
     **/
    function get_sends( $newsletter_id ) {
        global $wpdb;
        $query = $wpdb->prepare(
                    "SELECT
                    A.*,
                    MIN(B.status) AS 'status',
                    SUM(if(B.status = 'bounced', 1, 0)) AS 'count_bounced',
                    SUM(if(B.status = 'sent', 1, 0)) AS 'count_sent',
                    SUM(if(B.status = 'waiting_send', 1, 0)) AS 'count_send_members',
                    SUM(if(B.status = 'by_cron'  OR concat('',B.status * 1) = B.status, 1, 0)) AS 'count_send_cron',
                    SUM(if(B.opened_time > 0, 1, 0)) AS 'count_opened'
                    FROM {$this->tb_prefix}enewsletter_send A
                    LEFT JOIN {$this->tb_prefix}enewsletter_send_members B
                    ON (A.send_id = B.send_id)
                    WHERE A.newsletter_id = %d
                    GROUP BY A.send_id
                    ORDER BY start_time DESC
                    ", $newsletter_id );
        $results = $wpdb->get_results($query, "ARRAY_A");

        foreach ($results as $key => $result)
            if($result['send_id'] === NULL)
                unset($results[$key]);

        return $results;
    }

    /**
     * get member id by unsubscribe code
     **/
    function get_member_id_by_code( $code ) {
        global $wpdb;
        $member_id = $wp_only_user_id = 0;

        $is_wp = substr($code, 0, 2);
        if($is_wp == 'wp') {
            $is_wp = true;
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'email_newsletter_unsubscribe_code' AND meta_value = %s", $code ), "ARRAY_A" );
            $wp_only_user_id = $result['user_id'];
        }
        else {
            $is_wp = false;
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code = %s", $code ), "ARRAY_A" );
            $member_id = $result['member_id'];
        }

        return array('member_id' => $member_id, 'wp_only_user_id' => $wp_only_user_id);
    }

    /**
     * get member id by unsubscribe code
     **/
    function get_member_by_join_date( $time ) {
        global $wpdb;
        $member_id = $wp_only_user_id = 0;

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE join_date = '%d'", $time ), "ARRAY_A" );
        if(isset($result['member_id']) && $result['member_id'] > 0)
            $member_id = $result['member_id'];

        if($member_id == 0) {
            $time = date("Y-m-d H:i:s", $time);
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_registered = %s", $time ), "ARRAY_A" );
            if(isset($result['ID']) && $result['ID'] > 0)
            $wp_only_user_id = $result['ID'];
        }

        return array('member_id' => $member_id, 'wp_only_user_id' => $wp_only_user_id);
    }

    /**
     * get member id by unsubscribe code
     **/
    function get_member_by_email( $email ) {
        global $wpdb;
        return  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members WHERE member_email = '%s'", $email ), "ARRAY_A" );
    }

    /**
     * Get member id of wp user
     **/
    function get_members_by_wp_user_id( $wp_user_id, $blog_id = '', $subscribed = 0 ) {
        global $wpdb;

        if ( 1 < $blog_id )
            $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
        else
            $tb_prefix = $wpdb->base_prefix;

        if($subscribed)
            $subscribed = " AND unsubscribe_code != ''";
        else
            $subscribed = "";

        $member = $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE wp_user_id = %d".$subscribed, $wp_user_id ), "ARRAY_A" );
        return $member['member_id'];
    }

    /**
     * Get all members of Group
     **/
    function get_members_of_group( $group_id, $limit = '', $unsubscribed = '' ) {
        global $wpdb;
        $members = NULL;
        if($unsubscribed)
            $unsubscribed = " AND B.unsubscribe_code != ''";
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT A.member_id FROM {$this->tb_prefix}enewsletter_member_group A INNER JOIN {$this->tb_prefix}enewsletter_members B ON A.member_id = B.member_id WHERE A.group_id = %d" . $unsubscribed . $limit , $group_id ), "ARRAY_A" );
        foreach( $results as $member ){
            $members[] = $member['member_id'];
        }
        return $members;
    }

    /**
     * Get all subscribers of a M2 Membership.
     *
     * M2 stores subscriptions as custom post type using post-meta values.
     * So we need to have a join to the posts/postmeta tables to fetch details
     * on active subscriptions.
     **/
    function get_members_of_membership2( $membership_id, $count = 0 ) {
        if(!$count) {
            $membership_users = MS_Model_Relationship::get_subscriptions(
                array(
                    'membership_id' => $membership_id,
                    'status' 	=> 'valid',
                )
            );

            $wp_users = array();
            foreach($membership_users as $membership_user) {
                $wp_users[] = $membership_user->user_id;
            }

            return $wp_users;
        }
        else {
            $count = MS_Model_Relationship::get_subscription_count(
                array(
                    'membership_id' => $membership_id,
                    'status' 	=> 'valid',
                )
            );

            return $count;
        }
    }

    /**
     * Get all members of membership
     * @deprecated The Membership plugin was replaced by M2 (above)
     **/
    function get_members_of_membership( $level_id, $count = 0 ) {
        global $wpdb;

        if(!function_exists('membership_db_prefix'))
            return false;

        $table = membership_db_prefix($wpdb, 'membership_relationships');
        $arg['inner_join'] = "{$table} MR ON A.wp_user_id = MR.user_id";
        $arg['where'] = $wpdb->prepare('MR.level_id = %d AND A.unsubscribe_code != ""', $level_id);
        $members = $this->get_members( $arg, $count, 0);

        return $members;
    }

   /**
     * Get member by ID
     **/
    function get_member( $member_id ) {
        global $wpdb;
        $member =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ), "ARRAY_A" );

        if($member) {
            $member['member_email'] = str_replace(' ', '', $member['member_email']);
            $member['member_email'] = str_replace("\t", '', $member['member_email']);

            $member['member_nicename'] = $member['member_fname'];
            $member['member_nicename'] .= $member['member_lname'] ? ' ' . $member['member_lname'] : '';

            if ( $member['member_info'] ) {
                $member =  array_merge ( $member, unserialize( $member['member_info'] ) );
                unset( $member['member_info'] );
            }
            return $member;
        }

        return 0;
    }

    /**
     * Get all members
     **/
    function get_members( $arg = "", $count = 0, $details = 0, $tb_prefix = '') {
        global $wpdb;
        if(empty($tb_prefix))
            $tb_prefix = $this->tb_prefix;

        $where      = "";
        $orderby    = "";
        $limit      = "";
        $left_join  = "";
        $inner_join = "";

        if ( isset( $arg['where'] ) && !empty( $arg['where'] ) ) {
            $where = "WHERE ". $arg['where'];
        }

        if ( isset( $arg['inner_join'] ) && !empty( $arg['inner_join'] ) ) {
            $inner_join= "INNER JOIN ". $arg['inner_join'];
        }

        if ( isset( $arg['left_join'] ) && !empty( $arg['left_join'] ) ) {
            $inner_join= "LEFT JOIN ". $arg['left_join'];
        }

        if ( isset( $arg['limit'] ) && !empty( $arg['limit'] ) ) {
            $limit = $arg['limit'];
        }

        $allowed_order_by = array('member_email', 'member_fname', 'join_date', 'count_sent', 'count_bounced', 'count_opened', 'sent', 'bounced', 'opened');
        if ( isset( $arg['orderby'] ) && in_array($arg['orderby'], $allowed_order_by) ) {
            $orderby = "ORDER BY ". $arg['orderby'];
            if ( $arg['order'] == 'asc' || $arg['order'] == 'desc' )
                $orderby .= " ". $arg['order'];
        }
        if($count == 1)
            $select =
            "SELECT COUNT(*)";
        else
            $select =
            "SELECT A.*";

        //this seems to only be used to migrate
        if($details == 1) {
            $details = ",
            SUM(if(B.status = 'bounced', 1, 0)) AS 'count_bounced',
            SUM(if(B.status = 'sent', 1, 0)) AS 'count_sent',
            SUM(if(B.opened_time > 0, 1, 0)) AS 'count_opened'
            ";
            $left_join = "LEFT JOIN {$tb_prefix}enewsletter_send_members B ON (A.member_id = B.member_id)";
        }
        else
            $details = "";

        $query = $select." ".$details." FROM {$tb_prefix}enewsletter_members A ".$left_join." ".$inner_join." " .$where." GROUP BY A.member_id ".$orderby." " .$limit;
        $results = $wpdb->get_results($query, "ARRAY_A");

        if($count == 1)
            return count($results);
        else
            return $results;
    }

    /**
     * Creates or merges users
     **/
    function create_update_member_user($wp_user_id = '', $member_data = array(), $subscribe = '', $force_data = 0) {
        global $wpdb;
        $member_results = array();

        //remove spaces and tabs from email
        $member_data['member_email'] = isset($member_data['member_email']) ? str_replace(array("\n", "\r", "\t", " "), "", $member_data['member_email']) : '';

        if( is_email($member_data['member_email']) || is_numeric($wp_user_id) || ( isset($member_data['member_id']) && is_numeric($member_data['member_id']) ) ) {
            $member_possible_data = array('member_id', 'wp_user_id', 'member_fname', 'member_lname', 'member_email', 'unsubscribe_code', 'member_info');
            foreach ($member_possible_data as $data)
                if(!isset($member_data[$data]))
                    $member_data[$data] = '';

            if(is_numeric($wp_user_id)) {
                $user = get_userdata( $wp_user_id );
                $member_id = $this->get_members_by_wp_user_id( $wp_user_id );
                $member_email = $user->user_email;
                if($member_id)
                    $member_data['member_id'] = $member_id;
                if($member_email && empty($member_data['member_email']))
                    $member_data['member_email'] = $member_email;
            }
            if(is_numeric($member_data['member_id'])) {
                $member = $this->get_member($member_data['member_id']);
                if(empty($user))
                    $user = get_userdata( $member['wp_user_id'] );
            }

            if(is_email($member_data['member_email'])) {
                if(empty($user))
                    $user = get_user_by( 'email', $member_data['member_email'] );
                if(empty($member))
                    $member = $this->get_member_by_email($member_data['member_email']);
            }

            if ( !empty($member) ) {
                $member_results[] = 'member_exists';

                if(empty($member_data['wp_user_id']) && !empty($member['wp_user_id']))
                    $member_data['wp_user_id'] = $member['wp_user_id'];
                if(empty($member_data['member_fname']) && !empty($member['member_fname']))
                    $member_data['member_fname'] = $member['member_fname'];
                if(empty($member_data['member_lname']) && !empty($member['member_lname']))
                    $member_data['member_lname'] = $member['member_lname'];
                if(empty($member_data['member_email']) && !empty($member['member_email']))
                    $member_data['member_email'] = $member['member_email'];
                if(empty($member_data['member_info']) && !empty($member['member_info']))
                    $member_data['member_info'] = $member['member_info'];

                if(!empty($member['unsubscribe_code']))
                    $member_data['unsubscribe_code'] = $member['unsubscribe_code'];

                if( is_numeric($member['member_id']) )
                    $member_data['member_id'] = $member['member_id'];
            }

            if ( !empty($user) && $force_data == 0 ) {
                $member_results[] = 'user_exists';

                $member_data['wp_user_id'] = $user->ID;

                if(!empty($user->user_firstname)) {
                    $member_data['member_fname'] = $user->user_firstname;
                    if(!empty($user->user_lastname))
                        $member_data['member_lname'] = $user->user_lastname;
                }
                elseif(!empty($user->nickname)) {
                    $member_data['member_fname'] = $user->nickname;
                    $member_data['member_lname'] = '';
                }

                if(!empty($user->user_email))
                    $member_data['member_email'] = $user->user_email;

                if(isset($member['member_id']))
                    $member_data['member_id'] = $member['member_id'];
            }

            if($subscribe == 1 && $member_data['unsubscribe_code'] == '')
                $member_data['unsubscribe_code'] = $this->gen_unsubscribe_code();
            elseif($subscribe === 0)
                $member_data['unsubscribe_code'] = '';

            //if email - do the magic!
            if(is_email($member_data['member_email'])) {
                if ( is_numeric($member_data['member_id']) ) {
                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET
                    wp_user_id = %d,
                    member_fname = %s,
                    member_lname = %s,
                    member_email = %s,
                    member_info = '%s',
                    unsubscribe_code = %s
                    WHERE member_id = %d
                    ", $member_data['wp_user_id'], $member_data['member_fname'], $member_data['member_lname'], $member_data['member_email'], $member_data['member_info'], $member_data['unsubscribe_code'], $member_data['member_id'] ) );

                    if($result)
                        $member_results[] = 'member_updated';
                    else
                        $member_results[] = 'problem_updating_member';
                }
                else {
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_members SET
                        wp_user_id = %d,
                        member_fname = %s,
                        member_lname = %s,
                        member_email = %s,
                        join_date = %d,
                        sent = 0,
                        opened = 0,
                        bounced = 0,
                        member_info = '%s',
                        unsubscribe_code = %s
                     ", $member_data['wp_user_id'], $member_data['member_fname'], $member_data['member_lname'], $member_data['member_email'], time(), $member_data['member_info'], $member_data['unsubscribe_code'] ) );

                    if($result) {
                        $member_results[] = 'member_inserted';
                        $member_data['member_id'] = $wpdb->insert_id;
                    }
                    else
                        $member_results[] = 'problem_inserting_member';
                }
            }
        }

        if($result) {
            $member_data['results'] = $member_results;
            do_action("enewsletter_create_update_member_user", $member_data);
            return $member_data;
        }
        else
            return 0;
    }

    function plus_one_member_stats($member_id, $type) {
        global $wpdb;

        $allowed_types = array('sent', 'bounced', 'opened');
        if(in_array($type, $allowed_types)){
            $query = $wpdb->prepare(
                "UPDATE {$this->tb_prefix}enewsletter_members SET
                {$type} = {$type} + 1
                WHERE member_id = %d",
                $member_id
            );
            $result = $wpdb->query($query);
        }
    }

    function get_global_wp_user_ids() {
        global $wpdb;

        $results = $wpdb->get_results( "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE meta_key LIKE '%capabilities' AND meta_value LIKE '%administrator%' GROUP BY user_id", ARRAY_A );
        foreach ($results as $result) {
            $user_id = $result['user_id'];
            $is_unsubscribed = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'email_newsletter_unsubscribe_code' AND meta_value = 'unsubscribed' AND user_id = $user_id" );
            if(!$is_unsubscribed)
                $return[] = $user_id;
        }

        return $return;
    }

    function get_wp_user_only($wp_user_id) {
        $unsubscribe = get_user_meta($wp_user_id, 'email_newsletter_unsubscribe_code', true);
        if(empty($unsubscribe)) {
            $unsubscribe = $this->gen_unsubscribe_code(1);
            update_user_meta( $wp_user_id, 'email_newsletter_unsubscribe_code', $unsubscribe );
        }
        $return['unsubscribe_code'] = $unsubscribe;

        if($unsubscribe != 'unsubscribed') {
            $user_info = get_userdata($wp_user_id);

            $return['member_id'] = 0;
            $return['member_email'] = $user_info->user_email;
            $return['wp_user_id'] = 0;
            $return['wp_only_user_id'] = $user_info->ID;
            $return['join_date'] = strtotime($user_info->user_registered);

            if(!empty($user_info->user_firstname)) {
                $return['member_nicename'] = $user_info->user_firstname;
                if(!empty($user_info->user_lastname))
                    $return['member_nicename'] .= ' '.$user_info->user_lastname;
            }
            elseif(!empty($user_info->nickname)) {
                $return['member_nicename'] = $user_info->nickname;
            }

            return $return;
        }

        return 0;
    }
    /**
     * Get target groups
     **/
    function the_targets($single_list_echo = 1, $groups = 1, $roles = 1, $membership = 1) {
        global $wpdb;
        $targets = array();

        if($groups) {
            $groups = $this->get_groups();

            foreach ($groups as $group) {
                $count = count( $this->get_members_of_group( $group['group_id'], '', 1 ) );
                if($count) {
                    $targets['groups']['name'] = __( 'eNewsletter Groups', 'email-newsletter' );
                    $targets['groups'][] = '<label><input type="checkbox" name="target[groups][]" value="'.$group['group_id'].'"> '.$group['group_name'].' ('.$count.')</input></label>';
                }
            }
        }

        if($membership){
            if (class_exists('MS_Plugin')) {
                // Support for the Membership 2 plugin.
                $api = MS_Plugin::$api;
                $memberships = $api->list_memberships();
                if (count($memberships)) {
                    $targets['m2'] = array();
                    $targets['m2']['name'] = __( 'Membership 2 Subscribers', 'email-newsletter' );
                    foreach ($memberships as $membership) {
                        $count = $this->get_members_of_membership2($membership->id, 1);
                        $targets['m2'][] = sprintf(
                            '<label><input type="checkbox" name="target[m2][]" value="%2$s" /> %1$s (%3$s)</label>',
                            $membership->name,
                            $membership->id,
                            $count
                        );
                    }
                }
            } elseif (function_exists('membership_db_prefix')) {
                // Support for old Membership1 plugin (deprecated).
                $prefix = membership_db_prefix($wpdb, 'membership_levels');
                $membership_levels = $wpdb->get_results("SELECT * FROM {$prefix} WHERE level_active = 1", "ARRAY_A");
                foreach ($membership_levels as $membership_level) {
                    $count = $this->get_members_of_membership($membership_level, 1);
                    if($count) {
                        $targets['membership_levels']['name'] = __( 'Membership Plugin Levels', 'email-newsletter' );
                        $targets['membership_levels'][] = '<label><input type="checkbox" name="target[membership_levels][]" value="'.$membership_level['id'].'"> '.$membership_level['level_title'].' ('.$count.')</input></label>';
                    }
                }
            }
        }

        if(1 == $wpdb->blogid && class_exists('ProSites') && 0 == 1) {
            $psts_levels = get_site_option( 'psts_levels' );
            foreach ($psts_levels as $psts_level_id => $psts_level) {
                $targets['psts_levels'][] = '<label><input type="checkbox" name="target[prosite_levels][]" value="'.$psts_level_id.'"> '.$psts_level['name'].'</input></label>';
            }
        }

        if($roles) {
            $roles = $this->get_roles();
            foreach ($roles as $role_id => $role) {
                $targets['roles']['name'] = __( 'WordPress User Roles', 'email-newsletter' );
                $targets['roles'][] = '<label><input type="checkbox" name="target[roles][]" value="'.$role_id.'"> '.$role['name'].'</input></label>';
            }
        }

        if(1 == $wpdb->blogid && function_exists('is_multisite') && is_multisite()) {
            $count = $this->get_global_wp_user_ids();
            $count = count($count);
            if($count) {
                $targets['site_admins'][] = '<label><input type="checkbox" name="target[site_admins]" value="yes"> <strong>'.__( 'Admins of all sites', 'email-newsletter' ).'</strong> ('.$count.')</input></label>';
            }
        }

        if($single_list_echo) {
            foreach ($targets as $type) {
                if(isset($type['name']))
                    $type['name'] = '<strong>'.$type['name'].':</strong>';
                $targets_echo[] = implode('<br/>', $type);
            }

            echo implode('<br/><br/>', $targets_echo);
        }
        else
            return $targets;
    }

    /**
     * Get user roles
     **/
    function get_roles() {
        global $wp_roles;

        $roles = $wp_roles->roles;
        foreach($roles as $role_id => $role)
            if( count(get_users(array('role' => $role_id))) == 0 )
                unset($roles[$role_id]);

        $email_newsletter_wp_roles = apply_filters('email_newsletter_wp_roles', $roles);

        return $email_newsletter_wp_roles;
    }

    /**
     * Get all data of all groups
     **/
     function get_groups($only_public = 0) {
        global $wpdb;

        $public = ($only_public) ? ' WHERE public = 1' : '';

        $groups = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_groups".$public, "ARRAY_A");
        $groups = apply_filters( 'email_newsletter_get_groups', $groups );

        return $groups;
    }

    /**
     * Get all data of one group
     **/
     function get_group_by_id( $group_id ) {
        global $wpdb;
        $result =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_groups WHERE group_id = %d", $group_id ), "ARRAY_A" );
        $result = apply_filters( 'get_group_by_id', $result );
        return $result;
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
     function delete_newsletter_meta( $newsletter_id, $keys = array(), $exclude = 0 ) {
		global $wpdb;

     	if(!empty($keys)) {

			if($exclude == 1) {
				$query = $wpdb->prepare(
					"
					DELETE FROM {$this->tb_prefix}enewsletter_meta
					WHERE email_id = %d
					",
					$newsletter_id
					);
                foreach ($keys as $key) {
                    $query .= $wpdb->prepare(
                        "
                        AND meta_key != %s
                        ",
                        $key
                        );
                }
            }
			else {
				$query = $wpdb->prepare(
					"
					DELETE FROM {$this->tb_prefix}enewsletter_meta
					WHERE email_id = %d
                    ",
                    $newsletter_id
                    );
                foreach ($keys as $key) {
                    $query .= $wpdb->prepare(
                        "
                        AND meta_key == %s
                        ",
                        $key
                        );
                }
            }
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
    function get_newsletters( $arg = "", $count = 0, $details = 1) {
        global $wpdb;

        $where      = "";
        $orderby    = "";
        $limit      = "";
        $left_join  = "";
        $inner_join = "";

        if ( isset( $arg['where'] ) ) {
            $where = "WHERE ". $arg['where'];
        }

        if ( isset( $arg['inner_join'] ) ) {
            $inner_join = "INNER JOIN ". $arg['inner_join'];
        }

        if ( isset( $arg['left_join'] ) ) {
            $left_join = "LEFT JOIN ". $arg['left_join'];
        }

        if ( isset( $arg['limit'] ) ) {
            $limit = $arg['limit'];
        }

        $allowed_order_by = array('create_date', 'subject', 'template', 'start_time', 'newsletter_id');
        if ( isset( $arg['orderby'] ) && in_array($arg['orderby'], $allowed_order_by) ) {
            $orderby = "ORDER BY ". $arg['orderby'];
            if ( isset($arg['order']) && ($arg['order'] == 'asc' || $arg['order'] == 'desc') )
                $orderby .= " ". $arg['order'];
        }
        if($count == 1)
            $select =
            "SELECT COUNT(*)";
        else
            $select =
            "SELECT A.*, A.sent AS 'count_sent', A.opened AS 'count_opened', A.bounced AS 'count_bounced'";

        $query = $select." FROM {$this->tb_prefix}enewsletter_newsletters A ".$left_join." ".$inner_join." " .$where." GROUP BY A.newsletter_id ".$orderby." " .$limit;
        $results = $wpdb->get_results($query, "ARRAY_A");

        //lets check if we need to transfer details for newsletter
        if(!$count && $details == 1)
            $results = $this->migrate_newsletters_stats( $results );

        if($count == 1)
            return count($results);
        else
            return $results;
    }

    function migrate_newsletters_stats( $results ) {
        global $wpdb;

        foreach ($results as $key => $result)
            if($result['sent'] === NULL) {
                $query = $wpdb->prepare(
                "SELECT SUM(if(SM.status = 'bounced', 1, 0)) AS 'count_bounced',
                SUM(if(SM.status = 'sent', 1, 0)) AS 'count_sent',
                SUM(if(SM.opened_time > 0, 1, 0)) AS 'count_opened'
                FROM {$this->tb_prefix}enewsletter_send S
                LEFT JOIN {$this->tb_prefix}enewsletter_send_members SM ON (S.send_id = SM.send_id)
                WHERE S.newsletter_id = %d", $result['newsletter_id']);
                $details = $wpdb->get_row($query, "ARRAY_A");

                $query = $wpdb->prepare(
                    "UPDATE {$this->tb_prefix}enewsletter_newsletters 
                    SET sent = %d, opened = %d, bounced = %d
                    WHERE newsletter_id = %d",
                    $details['count_sent'], $details['count_opened'], $details['count_bounced'], $result['newsletter_id']
                );
                $wpdb->query($query);

                $results[$key] = array_merge($result, $details);
            }
        
        return $results;
    }

    function plus_one_newsletter_stats($newsletter_id, $type) {
        global $wpdb;

        $allowed_types = array('sent', 'bounced', 'opened');
        if(in_array($type, $allowed_types)){

            $query = $wpdb->prepare(
                "UPDATE {$this->tb_prefix}enewsletter_newsletters SET
                {$type} = {$type} + 1
                WHERE newsletter_id = %d",
                $newsletter_id
            );
            $result = $wpdb->query($query);
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
     * Add information about emails to send into db
     **/
    function add_send_email_info( $newsletter_id, $members_id, $wp_only_users_id, $status, $dont_send_duplicate = 1, $send_to_bounced = 0 ) {
        global $wpdb;

        $count = 0;
        $start_time = time();

        $email_body = $this->make_email_body( $newsletter_id );

        $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_send SET newsletter_id = %d, start_time = %d, end_time = 0, email_body = '%s'", $newsletter_id, $start_time, $email_body ) );
        $send_id = $wpdb->insert_id;

        if(!is_array($members_id) && is_numeric($members_id))
            $members_id = array($members_id);
        if ( 0 < count( $members_id ) )
            foreach ( $members_id as $member_id ) {
                if ( !( "1" == $dont_send_duplicate && $this->check_duplicate_send($newsletter_id, $member_id) ) || ( "1" == $send_to_bounced && $this->check_bounced_send($newsletter_id, $member_id) ) ) {
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_send_members SET send_id = %d, member_id = %d, status = '%s' ", $send_id, $member_id, $status ) );
                    if($result)
                        $count ++;
                }
            }
        if($wp_only_users_id && !is_array($wp_only_users_id) && is_numeric($wp_only_users_id))
            $wp_only_users_id = array($wp_only_users_id);
        if ( $wp_only_users_id && 0 < count( $wp_only_users_id ) )
            foreach ( $wp_only_users_id as $wp_only_user_id ) {
                if ( !( "1" == $dont_send_duplicate && $this->check_duplicate_send($newsletter_id, '', $wp_only_user_id) ) || ( "1" == $send_to_bounced && $this->check_bounced_send($newsletter_id, '', $wp_only_user_id) ) ) {
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_send_members SET send_id = %d, member_id = 0, wp_only_user_id = %d, status = '%s' ", $send_id, $wp_only_user_id, $status ) );
                    if($result)
                        $count ++;
                }
            }

        return array('count' => $count, 'send_id' => $send_id);
    }

    function set_send_email_status($status, $send_id, $member_id = 0, $wp_only_user_id = 0, $newsletter_id = 0) {
        global $wpdb;
        $extra = '';

        if($status == 'sent')
            $extra = $wpdb->prepare(", sent_time = %d", time());
        elseif($status == 'bounced')
            $extra = $wpdb->prepare(", bounce_time = %d", time());

        if($member_id) {
            $this->plus_one_member_stats($member_id, $status);
            if($newsletter_id)
                $this->plus_one_newsletter_stats($newsletter_id, $status);
        }

        return $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = %s".$extra." WHERE send_id = %d AND member_id = %d AND wp_only_user_id = %d", $status, $send_id, $member_id, $wp_only_user_id ) );
    }

    /**
     * get email body of already sent email
     **/
    function get_sent_email( $send_id, $member_id = 0, $wp_only_user_id = 0 ) {
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT email_body FROM {$this->tb_prefix}enewsletter_send WHERE start_time = %d", $send_id ), "ARRAY_A");
        if ( 0 < $result )
            return $result;
        else {
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT a.email_body FROM {$this->tb_prefix}enewsletter_send a, {$this->tb_prefix}enewsletter_send_members b WHERE a.send_id = b.send_id AND b.send_id = %d AND b.member_id = %d AND b.wp_only_user_id = %d ", $send_id, $member_id, $wp_only_user_id ), "ARRAY_A");
            if ( 0 < $result )
                return $result;
            else
                return false;
        }
    }

    /**
     * Send email
     **/
    function send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents, $options=array() ) {
    	global $enewsletter_send_options;

    	$enewsletter_send_options = $options;

        $email_contents = wordwrap($email_contents, 50);

		if($this->settings['outbound_type'] == 'wpmail') {
			add_filter('wp_mail_content_type', function() {
				return "text/html";
			});
			add_filter( 'wp_mail_from', function() use ( $email_from ) {
				return  $email_from;
			});
    		if( $email_from_name )
				add_filter( 'wp_mail_from_name', function() use ( $email_from_name ) {
					return $email_from_name;
				});
    		add_action( 'phpmailer_init', array($this, 'wp_mail_phpmailer_init'));

    		$this->send_options = $enewsletter_send_options;


    		$headers = array();
    		$headers[] = $email_from_name ? 'From: '.$email_from_name.' <'.$email_from.'>' : 'From: <'.$email_from.'>';
    		if( isset($options['bounce_email']) )
    			$headers[] = 'Return-Path: <'.$options['bounce_email'].'>';
    		if( isset($options['message_id']) ) {
    			$headers[] = 'X-Mailer: '.$options['message_id'];
    			$headers[] = 'Message-ID: '.$options['message_id'];
			}

    		$sent_status = wp_mail( $email_to, $email_subject, $email_contents, $headers );

	        if( !$sent_status ) {
	            $this->write_log('WP Mail send email error');
	            //return 'WP Mail send email error';
	        }
    	}
    	else {
	        if ( !class_exists( 'ePHPMailer' ) )
	            require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

	        $mail = new ePHPMailer();
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

	        if( isset($options['bounce_email']) )
	            $mail->Sender = $options['bounce_email'];
	        else
	            $mail->Sender = $email_from;

	        if( isset($options['message_id']) ) {
	            $mail->XMailer = $options['message_id'];
	            $mail->MessageID = $options['message_id'];
	        }

			/**
			 * Fires after ePHPMailer is initialized.
			 *
			 * @param ePHPMailer $mail The ePHPMailer instance (passed by reference).
			*/
			do_action_ref_array( 'ephpmailer_init', array( &$mail ) );
			
	        $sent_status = $mail->Send();
	        if( !$sent_status ) {
	            $this->write_log( 'Send email error: '.$mail->ErrorInfo.'['.json_encode($mail->ErrorInfoRaw).']');
	            return !empty( $mail->ErrorInfoRaw ) ? json_encode( $mail->ErrorInfoRaw ) : $mail->ErrorInfo;
	        }
	    }

        $wait_time = isset($options['cron_wait']) ? $options['cron_wait'] : 1;
        sleep( $wait_time );
        return true;
    }

    function wp_mail_phpmailer_init($phpmailer) {
    	$options = $this->send_options;

        if( isset($options['bounce_email']) )
            $phpmailer->Sender = $options['bounce_email'];
        else
            $phpmailer->Sender = $email_from;

        if( isset($options['message_id']) ) {
            $phpmailer->XMailer = $options['message_id'];
            $phpmailer->MessageID = $options['message_id'];
        }
    }

    /**
     * Make email body
     **/
    function make_email_body( $newsletter_id, $customizer = 0 ) {
        global $email_builder;
        $settings = $this->get_settings();
        $newsletter_data = $this->get_newsletter_data( $newsletter_id );

        if(!$newsletter_data || empty($newsletter_data))
            return false;

        //open template file
        $theme = $this->get_selected_theme($newsletter_data['template']);

        $template_path  = $theme['dir'];
        $template_url  = $theme['url'];

        $contents_parts = $this->get_contents_elements($template_path);
        if($contents_parts['content'])
            $contents = $contents_parts['header'].$contents_parts['content'].$contents_parts['footer'];
        else
            return false;

        //Translate template default elements
        $default_texts = array(
            'From' => __( 'From', 'email-newsletter' ),
            'Not interested anymore?' => __( 'Not interested anymore?', 'email-newsletter' ),
            'Unsubscribe Instantly.' => __( 'Unsubscribe Instantly.', 'email-newsletter' )
        );
        foreach ($default_texts as $text => $translation) {
            $contents = str_replace( $text, $translation, $contents );
        }

        if(strpos($contents,'{VIEW_LINK_TEXT}') === false)
			add_filter( 'email_newsletter_make_email_content_header', function( $a, $b ) {
				return "{VIEW_LINK_TEXT}" . $a;
			}, 10, 2 );

        $date_format = (isset($settings['date_format']) ? $settings['date_format'] : "F j, Y");

        //Prepare newsletter body
        $body_prepare =
        array(
            'standard' => array(
                'header' => $contents_parts['default_style_header'].$contents_parts['style_header'],
                'content_header' => '',
                'footer' => '',
                'content_footer' => '',
                'content=email_body' => $newsletter_data['content'],
                'title=email_title' => $this->get_newsletter_meta($newsletter_id,'email_title', $this->get_default_builder_var('email_title') ),
                'subject=email_subject' => $newsletter_data['subject'],
                'from_name' => (isset($newsletter_data['from_name']) ? $newsletter_data['from_name'] : $this->settings['from_name']),
                'from_email' => (isset($newsletter_data['from_email']) ? $newsletter_data['from_email'] : $this->settings['from_email']),
                'branding_html' => $this->get_newsletter_meta($newsletter_id,'branding_html', $this->get_default_builder_var('branding_html') ),
                'contact_info' => (isset($newsletter_data['contact_info']) ? $newsletter_data['contact_info'] : $this->settings['contact_info']),
                'date' => date_i18n( $date_format ),
                'view_link_text' => $settings['view_browser']
            )
        );
        if($customizer)
            $body_prepare['standard']['header'] .= '<style type="text/css">'.$contents_parts['default_style'].$contents_parts['style'].'</style>';

        $contents = $this->make_email_values($body_prepare, $contents, $newsletter_id);

        //Open tracker code
        if(strpos($contents,'</body>') !== false)
            $contents = str_replace( "</body>", "{OPENED_TRACKER}</body>", $contents );
        else
            $contents = $contents.'{OPENED_TRACKER}';

        $default_header = $this->get_default_builder_var('header_image');
        $default_header = (!empty($default_header)) ? $template_url.$default_header : '';
        
        $visuals_prepare =
        array(
            'images' => array(
                'header_image' => $this->get_newsletter_meta($newsletter_id,'header_image', $default_header)
            )
        );
        $contents = $this->make_email_values($visuals_prepare, $contents, $newsletter_id);

        //do the inline styling
        $contents = $this->do_inline_styles($contents, $contents_parts['default_style'].$contents_parts['style']);

        //Add url to elements
        $contents = str_replace( "{TEMPLATE_URL}", $template_url, $contents );
        $contents = str_replace( "%7BTEMPLATE_URL%7D", $template_url, $contents );

        //replace image links
        $contents = str_replace( "'images/", "'".$template_url . "images/", $contents );
        $contents = str_replace( '"images/', '"'.$template_url . 'images/', $contents );
        $contents = str_replace( "'/images/", "'".$template_url . "images/", $contents );
        $contents = str_replace( '"/images/', '"'.$template_url . 'images/', $contents );

        //set up visual stuff
        $default_bg = $this->get_default_builder_var('bg_image');
        $default_bg = (!empty($default_bg)) ? $template_url.$default_bg : '';

        $visuals_prepare =
        array(
            'standard' => array(
                'bg_image' => $this->get_newsletter_meta($newsletter_id,'bg_image', $default_bg)
            ),
            'colors' => array(
                'link_color' => $this->get_newsletter_meta($newsletter_id, 'link_color', $this->get_default_builder_var('link_color')),
                'body_color' => $this->get_newsletter_meta($newsletter_id, 'body_color', $this->get_default_builder_var('body_color')),
                'title_color' => $this->get_newsletter_meta($newsletter_id, 'title_color', $this->get_default_builder_var('title_color')),
                'alternative_color' => $this->get_newsletter_meta($newsletter_id, 'alternative_color', $this->get_default_builder_var('alternative_color')),
                'bg_color' => $this->get_newsletter_meta($newsletter_id, 'bg_color', $this->get_default_builder_var('bg_color'))
            )
        );
        $contents = $this->make_email_values($visuals_prepare, $contents, $newsletter_id);

        //dom walker to add classes to ensure compability
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($contents);
        $imgs = $dom->getElementsByTagName('img');
        $ps = $dom->getElementsByTagName('p');
        foreach ($ps as $p) {
            $p_style = $p->getAttribute('style');
            if(!empty($p_style))
                break;
        }
        foreach ($imgs as $img) {
            $classes_to_aligns = array('left', 'right');
            foreach ($classes_to_aligns as $class_to_align)
                if ($img->hasAttribute('class') && strstr($img->getAttribute('class'), 'align'.$class_to_align))
                    $img->setAttribute('align', $class_to_align);

            /*
            if ($img->hasAttribute('width') )
                $img->removeAttribute('width');
            */
            if ($img->hasAttribute('height'))
                $img->removeAttribute('height');

            if ($img->hasAttribute('class') && strstr($img->getAttribute('class'), 'aligncenter')) {
                $img_style = $img->getAttribute('style');
                $img_style = preg_replace('#display:(.*?);#', '', $img_style);
                $img->setAttribute('style',$img_style);

                $parent = $img->parentNode;
                if($parent->nodeName == 'a')
                    $parent = $parent->parentNode;

                if($parent->nodeName != 'div')
                    $parent->setAttribute('style','text-align:center;'.$parent->getAttribute('style'));
                else {
                    $element = $dom->createElement('p');
                    $element->setAttribute('style','text-align:center;'.$p_style);

                    $img->parentNode->replaceChild($element, $img);
                    $element->appendChild($img);
                }
            }

            $style = $img->getAttribute('style');
            preg_match('#margin:(.*?);#', $style, $matches);
            if($matches) {
                $space_px = explode('px',$matches[1]);
                $space_procent = explode('%',$matches[1]);
                $space = ($space_procent > $space_px) ? $space_procent : $space_px;
                $space_unit = ($space_procent > $space_px) ? '%' : '';
                if($space) {
                    $hspace = trim($space[0]);
                    $vspace = (isset($space[1])) ? $hspace : trim($space[0]);

                    $img->setAttribute('hspace', $hspace.$space_unit);
                    $img->setAttribute('vspace', $vspace.$space_unit);
                }
                $style = preg_replace('#margin:(.*?);#', '', $style);
                if($style)
                    $img->setAttribute('style', $style);
                else
                    $img->removeAttribute('style');
            }
        }
        $contents = $dom->saveHTML();

        return apply_filters('email_newsletter_make_email_body', $contents, $newsletter_id);
    }

    /**
     * Personalize email
     **/
    function personalise_email_body( $contents, $member_id, $wp_only_user_id, $join_date, $unsubscribe_code, $send_id, $changes = array() ) {
        if($member_id)
            $id = $member_id;
        elseif($wp_only_user_id)
            $id = $wp_only_user_id;
        else
            $id = 0;

        //Set up permalinks
        $changes['OPENED_TRACKER'] = '<div style="font-size: 0px; line-height:0px; visibility: hidden;"><img src="' . admin_url('admin-ajax.php?action=check_email_opened&send_id=' . $send_id . '&member_id=' . $member_id . '&wp_only_user_id=' . $wp_only_user_id) . '" width="1" height="1"/></div>';
        $unsubscribe_url = add_query_arg( array('unsubscribe_page' => '1', 'unsubscribe_code' => $unsubscribe_code, 'unsubscribe_member_id' => $id), home_url() );
        $changes['UNSUBSCRIBE_URL'] = $unsubscribe_url;
        $view_browser_url = add_query_arg( array('view_newsletter' => '1', 'view_newsletter_code' => $join_date, 'view_newsletter_send_id' => $send_id), home_url() );
        $changes['VIEW_LINK'] = $view_browser_url;

        $changes = apply_filters( 'email_newsletter/personalise_email_body', $changes, $member_id, $send_id );

        //apply all dynamic replcements to content
        foreach ($changes as $key => $value) {
            if(!empty($value)) {
                $contents = str_replace( "{".strtoupper($key)."}", $value, $contents );
                $contents = str_replace( "%7B".strtoupper($key)."%7D", $value, $contents );
            }
        }

        return $contents;
    }

    function register_enewsletter_themes() {
        global $wp_theme_directories;

        $added = 0;
        if(!in_array($this->template_custom_directory, $wp_theme_directories)) {
            $added = 1;
            register_theme_directory($this->template_custom_directory);
        }
        if(!in_array($this->template_directory, $wp_theme_directories)) {
            $added = 1;
            register_theme_directory($this->template_directory);
        }

        //cheating message fix
        if($added)
            wp_clean_themes_cache();
    }

    /**
     * Get theme details
     **/
    function get_selected_theme($theme_name, $newsletter_id = false) {
        global $wp_theme_directories;

        $this->register_enewsletter_themes();

        $theme = wp_get_theme($theme_name);
        if($theme->exists()) {
            $template = $this->get_theme_dir_url($theme, $theme_name);

            //load theme options
            if($this->loaded_theme_options != $template['dir']) {
                $this->loaded_theme_options = $template['dir'];
                if(file_exists($template['dir'] . 'functions.php'))
                    include($template['dir'] . 'functions.php');
                elseif(file_exists($template['dir'] . 'index.php'))
                    include($template['dir'] . 'index.php');
            }

            $styles = $this->get_contents_elements($template['dir'], 0);

            $return = array('url' => $template['url'], 'dir' => $template['dir'], 'Stylesheet' => $theme['Stylesheet'], 'Template' => $theme['Template'], 'Status' => $theme['Status'], 'Style' => $styles['default_style'].$styles['style']);
            return $return;
        }
        else if($theme_name != 'iletter') {
            if($newsletter_id) {
                global $wpdb;
                $query = $wpdb->prepare(
                    "UPDATE {$this->tb_prefix}enewsletter_newsletters 
                    SET template = %s
                    WHERE newsletter_id = %d",
                    'iletter', $newsletter_id
                );
                $wpdb->query($query);
            }

            return $this->get_selected_theme('iletter');
        }

        return false;
    }

    function get_theme_dir_url($theme, $theme_name) {
        $theme_root_dir = $theme->theme_root.'/';

        if(strpos($theme_root_dir, 'enewsletter-custom-themes') !== FALSE) {
            $upload_dir = wp_upload_dir();
            $theme_root_url = $upload_dir['baseurl'].'/enewsletter-custom-themes/';
        }
        else
            $theme_root_url = $this->plugin_url . "email-newsletter-files/templates/";

        return array('dir' => $theme_root_dir.$theme_name.'/', 'url' => $theme_root_url.$theme_name.'/');
    }

    /**
     * Prepare inline styles
     **/
    function do_inline_styles($contents, $styles) {
        if($contents && $styles) {
            if(!class_exists('CssToInlineStyles'))
                require_once($this->plugin_dir.'email-newsletter-files/builder/lib/css-inline.php');

            $css_inline = new CssToInlineStyles($contents,$styles);
            $contents = $css_inline->convert();
        }
        return $contents;
    }

    /**
     * Converts themes pseudo variables
     **/
    function make_email_values($prepare, $contents, $newsletter_id) {
        foreach ($prepare as $type => $values) {
            foreach ($values as $name => $value) {

                $name = explode('=', $name);
                $name_big = (isset($name[1])) ? strtoupper($name[1]) : strtoupper($name[0]);
                $name = $name[0];
                $value = apply_filters('email_newsletter_make_email_'.$name, $value, $newsletter_id);

                if($type == 'images' && !empty($value))
                    $value = '<img src="'.$value.'"/>';

                $contents = str_replace( "{".$name_big."}", $value, $contents );
                if($type == 'colors')
                    $contents = str_replace( "#".$name_big, $value, $contents);
            }
        }

        return $contents;
    }

    /**
     * Gets correct parts of newsletter
     **/
    function get_contents_elements($template_path, $get_html = 1, $get_styles = 1) {
        if($template_path) {
            $contents_parts = $build_htmls = $build_styles = array();

            if($get_html) {
                $build_htmls['header'][] = $template_path . "header.html";
                $build_htmls['content'][] = $template_path . "template.html";
                $build_htmls['footer'][] = $template_path . "footer.html";

                if(defined('BUILDER_SETTING_USE_DEFAULT_HEADER_FOOTER')) {
                    $build_htmls['header'][] = $this->template_custom_directory . "/default_header.html";
                    $build_htmls['header'][] = $this->template_directory . "/default_header.html";

                    $build_htmls['footer'][] = $this->template_custom_directory . "/default_footer.html";
                    $build_htmls['footer'][] = $this->template_directory . "/default_footer.html";
                }
            }
            if($get_styles) {
                $build_styles['style'][] = $template_path . "style.css";
                $build_styles['style_header'][] = $template_path . "style_header.css";

                if(defined('BUILDER_SETTING_USE_DEFAULT_STYLES')) {
                    $build_styles['default_style'][] = $this->template_custom_directory . "/default_style.css";
                    $build_styles['default_style'][] = $this->template_directory . "/default_style.css";

                    $build_styles['default_style_header'][] = $this->template_custom_directory . "/default_style_header.css";
                    $build_styles['default_style_header'][] = $this->template_directory . "/default_style_header.css";
                }
                else {
                    $build_styles['default_style'][] = '';
                    $build_styles['default_style'][] = '';

                    $build_styles['default_style_header'][] = '';
                    $build_styles['default_style_header'][] = '';
                }
            }
            $build_theme = array_merge($build_htmls, $build_styles);

            foreach ($build_theme as $type => $possible_files)
                foreach ($possible_files as $possible_file) {
                    if(isset($contents_parts[$type]) && !empty($contents_parts[$type]))
                        continue;
                    if(file_exists($possible_file)) {
                        $handle = fopen( $possible_file, "r" );
                        $contents_parts[$type] = fread( $handle, filesize( $possible_file ) );
                        fclose( $handle );

                        if (strpos($type, 'style') !== FALSE)
                            $contents_parts[$type] = preg_replace("/^\s*\/\*[^(\*\/)]*\*\//m","",$contents_parts[$type]);
                    }
                    if(!isset($contents_parts[$type]))
                        $contents_parts[$type] = '';
                }

            //if head missing - fix it!
            if($get_html) {
                if(strpos($contents_parts['header'].$contents_parts['content'],'<html') === false && strpos($contents_parts['content'].$contents_parts['footer'],'</html>') === false) {
                    if(strpos($contents_parts['header'].$contents_parts['content'],'<body') === false && strpos($contents_parts['content'].$contents_parts['footer'],'</body>') === false) {
                        $body_header = '<body>';
                        $body_footer = '</body>';
                    }
                    else
                        $body_header = $body_footer = '';

                    $contents_parts['header'] = '
                        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                        <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                            <title>{EMAIL_TITLE}</title>
                            <style type="text/css">
                                {DEFAULT_STYLE_HEADER}
                                {STYLE_HEADER}
                            </style>

                            {HEADER}
                        </head>'.$body_header.$contents_parts['header'];

                    $contents_parts['footer'] = $contents_parts['footer'].$body_footer.'
                        </html>';
                }

                //adds content header tag if missing on top
                if(strpos($contents_parts['header'].$contents_parts['content'],'{CONTENT_HEADER}') === false) {
                    if(strpos($contents_parts['content'],'<body') !== false)
                        $has_body = 'content';
                    elseif(strpos($contents_parts['header'],'<body') !== false)
                        $has_body = 'header';
                    else
                        $has_body = '';

                    if($has_body) {
                        $start = stripos($contents_parts[$has_body], '<body');
                        $end = stripos($contents_parts[$has_body], '>', $start);
                        $contents_parts[$has_body] = substr_replace($contents_parts[$has_body], "{CONTENT_HEADER}", $end+1, 0);
                    }
                }
                //adds content footer tag if missing on bottom
                if(strpos($contents_parts['content'].$contents_parts['footer'],'{CONTENT_FOOTER}') === false) {
                    if(strpos($contents_parts['content'],'</body>') !== false)
                        $has_body = 'content';
                    elseif(strpos($contents_parts['footer'],'</body>') !== false)
                        $has_body = 'footer';
                    else
                        $has_body = '';

                    if($has_body)
                        $contents_parts[$has_body] = str_replace( "</body>", "{CONTENT_FOOTER}{FOOTER}</body>", $contents_parts[$has_body]);
                }
            }

            return $contents_parts;
        }

        return array();
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
     * Choose firstname
     **/
    function get_firstname( $wp_user_id, $user_nicename ) {
        if($wp_user_id == 0 ) {
            $user_name = explode( ' ', $user_nicename );
            $user_name = $user_name[0];
        }
        else {
            $wp_user = ( $wp_user_id == 0 ? false : get_user_by('id', $wp_user_id ) );

            if( is_a( $wp_user,'WP_User' ) )
                $user_name = !empty( $wp_user->first_name ) ? $wp_user->first_name : $wp_user->display_name;
            else
                $user_name = '';
        }
        return $user_name;
    }

    /**
     * Choose lasttname
     **/
    function get_lastname($wp_user_id, $user_nicename) {
        $last_name = '';

        if( $wp_user_id == 0 ) {
            $last_name = explode( ' ', $user_nicename );
            $last_name = $last_name[1];
        }
        else {
            $wp_user = ( $wp_user_id == 0 ? false : get_user_by( 'id', $wp_user_id ) );

            if(is_a($wp_user,'WP_User'))
                $last_name = !empty( $wp_user->last_name ) ? $wp_user->last_name : $wp_user->display_name;
            else
                $last_name = '';
        }
        return $last_name;
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
                $connection = @imap_open( '{'.$email_host.':'.$email_port.'/pop3'.$combination.$email_security.'}INBOX', $email_username, $email_password );
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

                        $member_id = $wpdb->get_var( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE member_email = %s", $email ) );

                        if ( $member_id ) {
                            if(isset($import_groups_id)) {
                                $memeber_groups = $this->get_memeber_groups( $member_id );
                                if(!$memeber_groups)
                                    $memeber_groups = array();
                                $import_and_memeber_groups_id = array_unique(array_merge($memeber_groups, $import_groups_id));
                                if($import_and_memeber_groups_id > $memeber_groups) {
                                    $this->add_members_to_groups( $member_id, $import_and_memeber_groups_id );
                                    $i++;
                                }
                                else
                                    $exist_members[] = $email;
                            }
                            else
                                $exist_members[] = $email;
                        } else {
                            if ( is_email($email) ) {
                                $member_data_ready = array(
                                        'member_fname' => $fname,
                                        'member_lname' => $lname,
                                        'member_email' => $email
                                    );
                                $result = $this->create_update_member_user('', $member_data_ready, 1);

                                $member_id = $result['member_id'];

                                if($result) {
                                    //creating new list of groups for user
                                    if ( isset( $import_groups_id ) )
                                        $this->add_members_to_groups( $member_id, $import_groups_id );

                                    $i++;
                                }
                                else
                                    $incorrect_members[] = $email;
                            }
                            else {
                                $incorrect_members[] = $email;
                            }
                        }
                    }
                }

                $message = '';

                if ( 0 < $i )
                    $message .=  __( 'Import is finished successfully,', 'email-newsletter' ) . ' ' . $i . ' ' . __( 'members are added or subscribed to group(s).', 'email-newsletter' );

                if ( isset( $exist_members ) && is_array( $exist_members ) ) {
                    $message .= '<br />' . __( 'These emails already exist in member list:', 'email-newsletter' ) . '<br />';
                    $exist_members_count = count($exist_members);
                    $exist_members = array_slice($exist_members, 0, 40);
                    foreach($exist_members as $exist_member )
                        $message .= $exist_member . '<br />';

                    if($exist_members_count > 40)  {
                        $exist_members_count_left = $exist_members_count-40;
                        $message .= __( '...and '.$exist_members_count_left.' more!', 'email-newsletter' ) . '<br />';
                    }
                }
                if ( isset( $incorrect_members ) && is_array( $incorrect_members ) ) {
                    $message .= '<br />' . __( 'These emails are incorrect:', 'email-newsletter' ) . '<br />';
                    $incorrect_members_count = count($incorrect_members);
                    $incorrect_members = array_slice($incorrect_members, 0, 40);
                    foreach($incorrect_members as $incorrect_member )
                        $message .= $incorrect_member . '<br />';

                    if($incorrect_members_count > 40)  {
                        $incorrect_members_count_left = $incorrect_members_count-40;
                        $message .= __( '...and '.$incorrect_members_count_left.' more!', 'email-newsletter' ) . '<br />';
                    }
                }

                if(empty($message)) {
                    $message .= 'Import ERROR: Nothing to import';
                }

                wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $message ) ), 'admin.php' ) );
                exit;

            } else {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( __( 'Import ERROR: Problem with uploading of the file!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
        } else {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( __( 'Import ERROR: Please change permission for the folder /wp-content/uploads/', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Exporting members of selected groups to csv
     **/
    function export_members( $groups = array(), $ungrouped = 0, $separate_by = ';' ) {
        global $wpdb;
        $sitename = sanitize_key( get_bloginfo( 'name' ) );
        if ( ! empty($sitename) ) $sitename .= '.';
        $groups_filename = $groups;
        if($ungrouped == 1)
            $groups_filename[] = 'ug';
        elseif(empty($groups))
            $groups_filename[] = 'all';
        $groups_filename = implode('-', $groups_filename);

        $filename = $sitename . 'wp.enewsletter.members.'.$groups_filename.'.'.date( 'Y-m-d' ) . '.csv';

        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

        $arg = array();
        if(count($groups) > 0) {
            foreach ($groups as $key => $group)
                if(!is_numeric($group))
                    unset($groups[$key]);

            $groups_string = implode(',', $groups);

            $arg['where'] = 'group_id IN('.$groups_string.')';

            if($ungrouped == 1)
                $arg['where'] .= ' OR group_id IS NULL';
        }
        elseif($ungrouped == 1)
                $arg['where'] = 'group_id IS NULL';

        $arg['left_join'] = $this->tb_prefix.'enewsletter_member_group C ON (A.member_id = C.member_id)';

        $results = $this->get_members( $arg, 0, 0 );
        if ( $results )
            foreach( $results as $member ) {
                echo $member['member_email'].$separate_by.$member['member_fname'].$separate_by.$member['member_lname']."\r\n";
            }

        die();
    }

    /**
     * Get pagination data
     **/
    function get_pagination_data( $count, $per_page ) {
            if ( 'all' == $per_page )
                $per_page = 1000000;

            if ( is_numeric($count) && is_numeric($per_page) && $count > $per_page ) {
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
     * Save Settings
     **/
    function save_settings( $settings, $tb_prefix = '', $redirect = 1 ) {
        global $wpdb, $wp_roles;

        if(empty($tb_prefix))
            $tb_prefix = $this->tb_prefix;

        if( ! is_array( $settings ) )
            $settings = array();

        if( ! isset( $settings['double_opt_in'] ) ) {
            $settings['double_opt_in'] = 0;
        }

        if(isset($settings['email_caps'])) {
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

        if (isset( $settings['subscribe_groups']))
            $settings['subscribe_groups'] = implode(',', $settings['subscribe_groups']);
        else {
            $settings['subscribe_groups'] = '';
        }


        foreach( $settings as $key => $item )
             $result = $wpdb->query( $wpdb->prepare( "REPLACE INTO {$tb_prefix}enewsletter_settings SET `key` = '%s', `value` = '%s'", $key, stripslashes( $item ) ) );

        if ( isset($_REQUEST['mode']) && "install" == $_REQUEST['mode']) {
            // first setup of plugin
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'updated' => 'true', 'message' => urlencode( __( 'The Plugin is installed!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } elseif($redirect == 1) {
            $newsletter_setting_page = (isset($_REQUEST['newsletter_setting_page'])) ? $_REQUEST['newsletter_setting_page'] : '';
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-settings', 'tab' => $newsletter_setting_page, 'updated' => 'true', 'message' => urlencode( __( 'The Settings are saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
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
                    `contact_info` text NOT NULL,
                    `bounce_email` varchar(255) NOT NULL,
                    `sent` int(11) DEFAULT '0',
                    `opened` int(11) DEFAULT '0',
                    `bounced` int(11) DEFAULT '0',
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
                    `wp_only_user_id` int(11) NOT NULL DEFAULT  '0',
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
                    `sent` int(11) DEFAULT '0',
                    `opened` int(11) DEFAULT '0',
                    `bounced` int(11) DEFAULT '0',
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
                    `value` text NOT NULL,
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

	function upgrade( $blog_id = '', $prev ) {
		global $wpdb;

		if ( $this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file)) ) {
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

            if($prev < 2.01) {
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_meta'" ) != $tb_prefix.'enewsletter_meta' ) {

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

            if($prev < 2.3) {
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send_members'" ) == $tb_prefix.'enewsletter_send_members' ) {
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_send_members` LIKE 'wp_only_user_id'" )) {
                        $enewsletter_table = "ALTER TABLE  `{$tb_prefix}enewsletter_send_members` ADD  `wp_only_user_id` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `member_id`";
                        $result = $wpdb->query( $enewsletter_table );
                    }
                }
            }
            if($prev < 2.5) {
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_settings'" ) == $tb_prefix.'enewsletter_settings') {
                    //allow for longer texts
                    $result = $wpdb->query("ALTER TABLE {$tb_prefix}enewsletter_settings MODIFY value text");
                }
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_meta'" ) == $tb_prefix.'enewsletter_meta') {
                    $result = $wpdb->query("DELETE a FROM {$tb_prefix}enewsletter_meta a LEFT JOIN {$tb_prefix}enewsletter_newsletters b ON a.email_id = b.newsletter_id WHERE b.newsletter_id IS NULL");
                }
            }
            if($prev < 2.51) {
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_members'" ) == $tb_prefix.'enewsletter_members') {
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_members` LIKE 'bounced'" ))
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_members` ADD  `bounced` INT( 11 ) NULL AFTER `join_date`" );
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_members` LIKE 'opened'" ))
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_members` ADD  `opened` INT( 11 ) NULL AFTER `join_date`" );
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_members` LIKE 'sent'" ))
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_members` ADD  `sent` INT( 11 ) NULL AFTER `join_date`" );
                }
            }
            if($prev < 2.67) {
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_newsletters'" ) == $tb_prefix.'enewsletter_newsletters') {
                    //allow for longer texts
                    $result = $wpdb->query("ALTER TABLE {$tb_prefix}enewsletter_newsletters MODIFY contact_info text");
                }
            }
            if($prev < 2.703) {
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_newsletters'" ) == $tb_prefix.'enewsletter_newsletters') {
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_newsletters` LIKE 'bounced'" )) 
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_newsletters` ADD  `bounced` INT( 11 ) NULL AFTER `bounce_email`" );
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_newsletters` LIKE 'opened'" ))
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_newsletters` ADD  `opened` INT( 11 ) NULL AFTER `bounce_email`" );
                    if ( !$wpdb->get_var( "SHOW COLUMNS FROM `{$tb_prefix}enewsletter_newsletters` LIKE 'sent'" ))
                        $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_newsletters` ADD  `sent` INT( 11 ) NULL AFTER `bounce_email`" );
                }
            }
            if($prev < 2.704) {
                if($wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send_members'" ) == $tb_prefix.'enewsletter_send_members') {
                    $result = $wpdb->query( "ALTER TABLE  `{$tb_prefix}enewsletter_send_members` DROP PRIMARY KEY" );
                }
            }
            if($prev < 2.705) {
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_members'" ) != "{$tb_prefix}enewsletter_members" ) {

                    $enewsletter_table = "CREATE TABLE `{$tb_prefix}enewsletter_members` (
                        `member_id` int(11) NOT NULL auto_increment,
                        `wp_user_id` int(11) DEFAULT '0',
                        `member_fname` varchar(255),
                        `member_lname` varchar(255),
                        `member_email` varchar(255) NOT NULL,
                        `join_date` int(11) NOT NULL,
                        `member_info` text,
                        `sent` int(11) DEFAULT '0',
                        `opened` int(11) DEFAULT '0',
                        `bounced` int(11) DEFAULT '0',
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
            }
            if($prev < 2.729) {
                if(function_exists( 'mcrypt_decrypt' ) ) {
                    $settings = $this->get_settings($tb_prefix);
                    $new_settings = array();
                    
                    if(isset($settings['bounce_password'])) {
                        $settings['bounce_password'] = trim( @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, DB_PASSWORD, base64_decode( $settings['bounce_password'] ), MCRYPT_MODE_ECB ) );
                    }
                    if(isset($settings['smtp_pass'])) {
                        $new_settings['smtp_pass'] = trim( @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, DB_PASSWORD, base64_decode( $settings['smtp_pass'] ), MCRYPT_MODE_ECB ) );
                    }

                    if($new_settings)
                        $this->save_settings($new_settings, $tb_prefix, 0);
                }
            }
		}

        if($prev < 2.51) {
            //turns on stats migration
            if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file))) {
                update_site_option('email_newsletter_upgraded_cron', 0);
                update_site_option('email_newsletter_upgraded_cron_migrate_stats', 0);
            }
            else {
                update_option('email_newsletter_upgraded_cron', 0);
                update_option('email_newsletter_upgraded_cron_migrate_stats', 0);
            }
        }
	}

    function upgrade_cron() {
        global $wpdb;
        @set_time_limit( 0 );

        if ( $this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file)) ) {
            $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else
            $blogids[] = $wpdb->blogid;

        if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file)))
            $upgraded_cron_migrate_stats = get_site_option('email_newsletter_upgraded_cron_migrate_stats', 1);
        else
            $upgraded_cron_migrate_stats = get_option('email_newsletter_upgraded_cron_migrate_stats', 1);

        $total = $count = $count_all = $count_blogs = 0;
        foreach ( $blogids as $blog_id ) {
            $count_blogs ++;
            //Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            if(!$upgraded_cron_migrate_stats && $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_members'" ) == $tb_prefix.'enewsletter_members' && $wpdb->get_var( "SHOW TABLES LIKE '{$tb_prefix}enewsletter_send_members'" ) == $tb_prefix.'enewsletter_send_members') {
                $arg['where'] = 'sent IS NULL';
                $members = $this->get_members( $arg, 0, 1, $tb_prefix );

                $total = $total + count($members);
                $count = 0;
                foreach ($members as $member) {
                    $count_all ++;
                    $count ++;
                    if(empty($member['count_sent']))
                        $member['count_sent'] = 0;
                    if(empty($member['count_opened']))
                        $member['count_opened'] = 0;
                    if(empty($member['count_bounced']))
                        $member['count_bounced'] = 0;

                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$tb_prefix}enewsletter_members SET sent = %d WHERE member_id = %d", $member['count_sent'], $member['member_id'] ) );
                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$tb_prefix}enewsletter_members SET opened = %d WHERE member_id = %d", $member['count_opened'], $member['member_id'] ) );
                    $result = $wpdb->query( $wpdb->prepare( "UPDATE {$tb_prefix}enewsletter_members SET bounced = %d WHERE member_id = %d", $member['count_bounced'], $member['member_id'] ) );
                }
                if($count == count($members)) {
                    $result = $wpdb->query("DELETE a FROM {$tb_prefix}enewsletter_send_members a LEFT JOIN {$tb_prefix}enewsletter_send b ON a.send_id = b.send_id WHERE b.send_id IS NULL");
                }
            }
        }

        if(!$upgraded_cron_migrate_stats && $total == $count_all && count($blogids) == $count_blogs) {
            if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file))) {
                update_site_option('email_newsletter_upgraded_cron', 1);
                update_site_option('email_newsletter_upgraded_cron_migrate_stats', 1);
            }
            else {
                update_option('email_newsletter_upgraded_cron', 1);
                update_option('email_newsletter_upgraded_cron_migrate_stats', 1);
            }
        }

        die();
    }

    /**
     * Deleting tables from DB
     **/
    function uninstall( $blog_id = '' ) {
        global $wpdb, $wp_roles;

        $remove_from_network = false;
        $deleting_specific_site = $blog_id ? true : false;

        if ( $remove_from_network ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
            $blogids = array();
            if ( $deleting_specific_site )
                $blogids[] = $blog_id;
            else
                $blogids[] = $wpdb->blogid;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;          

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

            //if we are deleting entire site, we dont need to deal with it.
            if(!$deleting_specific_site) {
                foreach($wp_roles->get_names() as $name => $obj) {
                    if($name == 'administrator') continue;
                    $role_obj = get_role($name);
                    if($role_obj) {
                        foreach($this->capabilities as $cap => $label) {
                            $role_obj->remove_cap($cap);
                        }
                    }
                }

                //Delete all CRON actions
                if ( wp_next_scheduled( $this->cron_send_name ) )
                    wp_clear_scheduled_hook( $this->cron_send_name );

                if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' ) )
                    wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );

                if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' ) )
                    wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );

                delete_option( 'enewsletter_cron_send_run' );
                delete_option('email_newsletter_install_dismissed');
                delete_option('email_newsletter_version');
            }
        }

		//remove folder for custom themes
        if($remove_from_network) {
    		$custom_theme_dir = $this->get_custom_theme_dir();
    		if (is_dir($custom_theme_dir)) {
    			$this->delete_dir($custom_theme_dir);
    		}
        }

        //remove data about site options
        if($remove_from_network)
            delete_site_option('email_newsletter_version');
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
     * Deletes whole dir
     **/
    function delete_dir($src) {
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					$this->delete_dir($src . '/' . $file);
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
            return base64_encode( @mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5(DB_PASSWORD), $text, MCRYPT_MODE_ECB ) );
        } else {
            return $text;
        }
    }

    /**
     * Decrypt password (SMTP & POP password)
     **/
    protected function _decrypt( $text ) {
        if ( function_exists( 'mcrypt_decrypt' ) ) {
            return trim( @mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5(DB_PASSWORD), base64_decode( $text ), MCRYPT_MODE_ECB ) );
        } else {
            return $text;
        }
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
}