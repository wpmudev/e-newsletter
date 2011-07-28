<?php
/*
Plugin Name: E-Newsletter
Plugin URI: http://premium.wpmudev.org/project/e-newsletter
Description: E-Newsletter
Version: 1.0.4
Author: Andrey Shipilov (Incsub)
Author URI: http://premium.wpmudev.org
WDP ID: 233

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'email-newsletter') . '</a></p></div>';
  }
}


/**
* Plugin main class
**/

class Email_Newsletter {

    var $plugin_dir;
    var $plugin_url;
    var $settings;
    var $tb_prefix;

    function Email_Newsletter() {
        __construct();
    }

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {
        global $wpdb;

        //checking for MultiSite
        if ( 1 < $wpdb->blogid )
            $this->tb_prefix = $wpdb->base_prefix . $wpdb->blogid . '_';
        else
            $this->tb_prefix = $wpdb->base_prefix;

        add_action( 'admin_init', array( &$this, 'init' ) );

        // filter schedules
        add_filter( 'cron_schedules', array( &$this, 'add_new_cron_time' ) );

        add_action( 'init', array( &$this, 'init_for_all' ) );

        //get all setting of plugin
        $this->settings = $this->get_settings();


        //changing list of members when we create or delete user of the site
        add_action( 'user_register', array( &$this, 'user_create' ) );
        add_action( 'delete_user', array( &$this, 'user_delete' ) );

        //some actions for MultiSite
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            add_action( 'added_existing_user', array( &$this, 'user_create' ) );
            add_action( 'remove_user_from_blog', array( &$this, 'user_remove_from_site' ) );
            add_action( 'wpmu_delete_user', array( &$this, 'user_delete' ) );

            add_action('wpmu_new_blog', array( &$this, 'activation' ) );
            add_action('delete_blog', array( &$this, 'deactivation' ) );

        }

        //creating menu of the plugin
        add_action( 'admin_menu', array( &$this, 'admin_page' ) );

        //send email by WP-CRON
        add_action('e_newsletter_cron_send', array( &$this, 'send_by_wpcron' ) );

        //check bounces email by WP-CRON
        add_action('e_newsletter_cron_check_bounces_1', array( &$this, 'check_bounces' ) );
        add_action('e_newsletter_cron_check_bounces_2', array( &$this, 'check_bounces' ) );


        register_activation_hook ( __FILE__, array( &$this, 'activation' ) );
        register_deactivation_hook ( __FILE__, array( &$this, 'deactivation' ) );

        load_plugin_textdomain( 'email-newsletter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


        //ajax action for sent preview (test) email
        add_action( 'wp_ajax_nopriv_send_preview', array( &$this, 'send_preview_ajax' ) );
        add_action( 'wp_ajax_send_preview', array( &$this, 'send_preview_ajax' ) );

        //ajax action for show plreview of newsletter
        add_action( 'wp_ajax_nopriv_show_preview', array( &$this, 'show_preview_ajax' ) );
        add_action( 'wp_ajax_show_preview', array( &$this, 'show_preview_ajax' ) );

        //ajax action for change member's group on members page
        add_action( 'wp_ajax_nopriv_change_groups', array( &$this, 'change_groups_ajax' ) );
        add_action( 'wp_ajax_change_groups', array( &$this, 'change_groups_ajax' ) );

        //ajax action for show transparent image 1x1 for check that email was opened
        add_action( 'wp_ajax_nopriv_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );
        add_action( 'wp_ajax_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );

        //ajax action for upload image file on server
        add_action( 'wp_ajax_nopriv_file_upload', array( &$this, 'file_upload_ajax' ) );
        add_action( 'wp_ajax_file_upload', array( &$this, 'file_upload_ajax' ) );

        //ajax action for unsubscribe from email
        add_action( 'wp_ajax_nopriv_newsletter_unsubscibe', array( &$this, 'unsubscibe_ajax' ) );
        add_action( 'wp_ajax_newsletter_unsubscibe', array( &$this, 'unsubscibe_ajax' ) );

        //ajax action for subscribe
        add_action( 'wp_ajax_nopriv_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );
        add_action( 'wp_ajax_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );

        //ajax action for test connection to bounces email
        add_action( 'wp_ajax_nopriv_test_bounces', array( &$this, 'test_bounces_ajax' ) );
        add_action( 'wp_ajax_test_bounces', array( &$this, 'test_bounces_ajax' ) );

        //ajax action for sand email to member
        add_action( 'wp_ajax_nopriv_send_email_to_member', array( &$this, 'send_email_to_member' ) );
        add_action( 'wp_ajax_send_email_to_member', array( &$this, 'send_email_to_member' ) );



        //setup proper directories
        if ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WPMU_PLUGIN_DIR . '/e-newsletter/';
            $this->plugin_url = WPMU_PLUGIN_URL . '/e-newsletter/';
        } else if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/e-newsletter/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WP_PLUGIN_DIR . '/e-newsletter/';
            $this->plugin_url = WP_PLUGIN_URL . '/e-newsletter/';
        } else if ( defined('WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
            $this->plugin_dir = WP_PLUGIN_DIR;
            $this->plugin_url = WP_PLUGIN_URL;
        } else {
            wp_die( __('There was an issue determining where WPMU DEV Update Notifications is installed. Please reinstall.', 'email-newsletter' ) );
        }

        //including JS scripts
        wp_enqueue_script( 'jquery' );

        //including JS scripts for Newsletter pages
        if ( "newsletters-dashboard"    == $_REQUEST['page'] ||
             "newsletters"              == $_REQUEST['page'] ||
             "newsletters-create"       == $_REQUEST['page'] ||
             "newsletters-groups"       == $_REQUEST['page'] ||
             "newsletters-members"      == $_REQUEST['page'] ||
             "newsletters-subscribes"   == $_REQUEST['page'] ||
             "newsletters-settings"     == $_REQUEST['page'] ) {

            //including JS scripts
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-ui-core' );

            wp_register_script( 'newsletter_tiny_mce', $this->plugin_url . 'email-newsletter-files/js/tiny_mce/tiny_mce.js' );
            wp_enqueue_script( 'newsletter_tiny_mce' );

            wp_register_script( 'newsletter_fileuploader', $this->plugin_url . 'email-newsletter-files/js/fileuploader/fileuploader.js' );
            wp_enqueue_script( 'newsletter_fileuploader' );

            //including JS scripts for tooltips
            wp_register_script( 'jquery_tooltips', $this->plugin_url . 'email-newsletter-files/js/jquery.tools.min.js' );
            wp_enqueue_script( 'jquery_tooltips' );

            //including JS scripts for progressbar
            wp_register_script( 'jquery_ui_widget', $this->plugin_url . 'email-newsletter-files/js/ui.widget.js' );
            wp_enqueue_script( 'jquery_ui_widget' );

            //including JS scripts for progressbar
            wp_register_script( 'jquery_progressbar', $this->plugin_url . 'email-newsletter-files/js/jquery.ui.progressbar.js' );
            wp_enqueue_script( 'jquery_progressbar' );
        }


	}

    /**
     * init for admin
     **/
    function init() {
        // Including CSS file
        wp_register_style( 'emailNewsletterStyle', $this->plugin_url . 'email-newsletter-files/email-newsletter.css' );
        wp_enqueue_style( 'emailNewsletterStyle' );

        //private actions of the plugin
        if ( current_user_can('manage_network_options') || current_user_can('manage_options') ) {
            switch( $_REQUEST[ 'newsletter_action' ] ) {

                //action for save Newsletter
                case "save_newsletter":
                    $this->save_newsletter( $_REQUEST['newsletter_id'], $_REQUEST['page'] );
                break;

                //action for delete Newsletter
                case "delete_newsletter":
                    $this->delete_newsletter( $_REQUEST['newsletter_id'], $_REQUEST['page'] );

                break;

                //action for create new group
                case "create_group":
                    $this->create_group( $_REQUEST['group_name'], $_REQUEST['public'] );

                break;

                //action for edit group
                case "edit_group":
                    $this->create_group( $_REQUEST['edit_group_name'], $_REQUEST['edit_public'], $_REQUEST['group_id'] );
                break;

                //action for dlete group
                case "delete_group":
                    $this->delete_group( $_REQUEST['group_id'] );
                break;

                //action for change group
                case "change_group":
                    $this->change_group( $_REQUEST['member_id'], $_REQUEST['groups_id'] );
                break;

                //action add new member
                case "add_member":
                    $this->add_member( $_REQUEST['member'] );
                break;

                //action delete members
                case "delete_members":
                    $this->delete_members( $_REQUEST['members_id'] );
                break;

                //action save settings
                case "save_settings":
                    if(!isset($_REQUEST['settings']['double_opt_in'])){
                        $_REQUEST['settings']['double_opt_in'] = 0;
                    }
                    $this->save_settings( $_REQUEST['settings'] );
                break;

                //action save settings
                case "send_newsletter":
                    if ( 'add_to_cron' == $_REQUEST['cron'] )
                        $this->add_to_cron( $_REQUEST['newsletter_id'], $_REQUEST['send_id'] );
                    else if ( 'send' == $_REQUEST["action"] )
                        $this->send_newsletter( $_REQUEST['newsletter_id'] );
                break;

            }
        }


    }

    /**
     * init for all users
     **/
    function init_for_all() {

        //public actions of the plugin
        switch( $_REQUEST[ 'newsletter_action' ] ) {

            //action for save selected groups of subscribe
            case "save_subscribes":
                $redirect_to = $_SERVER['HTTP_REFERER'];
                $this->save_subscribes( $_REQUEST['e_newsletter_groups_id'], $redirect_to );
            break;

            //action for subscribe
            case "subscribe":
                $redirect_to = $_SERVER['HTTP_REFERER'];
                $this->subscribe( "", $redirect_to );
            break;

            //action for Unsubscribe
            case "unsubscribe":
                $redirect_to = $_SERVER['HTTP_REFERER'];
                $this->unsubscribe( $_REQUEST['unsubscribe_code'], $redirect_to );
            break;

            //action for Subscribe of public member (not user of site)
            case "new_subscribe":
                $redirect_to = $_SERVER['HTTP_REFERER'];
                if( isset( $this->settings['double_opt_in'] ) && $this->settings['double_opt_in'] ) {
                    $member_data['double_opt_in'] = 1;
                    $member_data['future_groups_id'] = $_REQUEST['e_newsletter_groups_id'];
                }
                $member_data['email']       =  $_REQUEST['e_newsletter_email'];
                $member_data['fname']       =  $_REQUEST['e_newsletter_name'];
                $member_data['lname']       =  '';
                $member_data['groups_id']   =  $_REQUEST['e_newsletter_groups_id'];
                $this->add_member( $member_data, $redirect_to );
            break;

        }


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
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Group already exist!!!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
        }


        if ( "0" != $group_id ) {
            //update when edit group
            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s' WHERE group_id = %d", trim( $group_name ), $public, $group_id ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'Changes of Group were saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            //create new group
            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_groups SET group_name = '%s', public = '%s'", trim( $group_name), $public ) );
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Group was created!', 'email-newsletter' ) ) ), 'admin.php' ) );
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
        wp_redirect( add_query_arg( array( 'page' => 'newsletters-groups', 'updated' => 'true', 'dmsg' => urlencode( __( 'Group was deleted!', 'email-newsletter' ) ) ), 'admin.php' ) );
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

        wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Groups was changed!', 'email-newsletter' ) ) ), 'admin.php' ) );
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
     * Save Subscribes
     **/
    function save_subscribes( $groups_id, $redirect_to = ""  ) {
        global $wpdb, $current_user;

        $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );

        //deleting old list of groups for user
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );

        //creating new list of groups for user
        if ( $groups_id )
            foreach( $groups_id as $group_id )
                $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );

        if ( "" == $redirect_to ) {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'dmsg' => urlencode( __( 'Subscribes were saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            $_SESSION['newsletter_widget_status'] = __( 'Subscribes were saved!', 'email-newsletter' );
            wp_redirect( $redirect_to );
            exit;
        }

    }

    /**
     *  Subscribe on Newsletters
     **/
    function subscribe( $member_id = "", $redirect_to = "" ) {
        global $wpdb, $current_user;

        if ( "" == $member_id )
            $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );

        $unsubscribe_code = $this->gen_unsubscribe_code();

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '%s' WHERE member_id = %d", $unsubscribe_code, $member_id ) );

        if ( "false" != $redirect_to )
            if ( "" == $redirect_to ) {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'dmsg' => urlencode( __( 'You was subscribed!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            } else {
                $_SESSION['newsletter_widget_status'] = __( 'You was subscribed!', 'email-newsletter' );
                wp_redirect( $redirect_to );
                exit;
            }
    }

    /**
     * Unsubscribe on Newsletters
     **/
    function unsubscribe( $unsubscribe_code, $redirect_to = "" ) {
        global $wpdb;
        if ( "" != $unsubscribe_code ) {
            $member =  $wpdb->get_row( $wpdb->prepare( "SELECT member_id FROM {$this->tb_prefix}enewsletter_members WHERE unsubscribe_code = '%s'", $unsubscribe_code ), "ARRAY_A" );
            if ( 0 < $member['member_id'] ) {
                //delete all groups of member
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member['member_id'] ) );

                //delete unsubscribe_code of member
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE unsubscribe_code = '%s'", $unsubscribe_code ) );

                if ( "" == $redirect_to ) {
                    wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'dmsg' => urlencode( __( 'You was unsubscribed!', 'email-newsletter' ) ) ), 'admin.php' ) );
                    exit;
                } else {
                    $_SESSION['newsletter_widget_status'] = __( 'You were unsubscribed!', 'email-newsletter' );
                    wp_redirect( $redirect_to );
                    exit;
                }
            }
        }
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
     * Add new member
     **/
    function add_member( $member_data, $redirect_to = "" ) {
        global $wpdb;

        $dmsg = "";

        if ( 0 < email_exists( $member_data['email'] ) ) {
            //if email of new member == email of site user

            $wp_user_id = email_exists( $member_data['email'] );
            $member_id = $this->get_members_by_wp_user_id( $wp_user_id );

            //check that this site's user there is on list of members
            if ( 0 < $member_id )
                $dmsg =  __( 'User with this email already exist! Please login on site.', 'email-newsletter' );

        } else {
            //check email of new member there isn't on list of members
            $member =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_members WHERE member_email = '%s'", $member_data['email'] ), "ARRAY_A" );
            if ( $member )
                if ( "" != $member['unsubscribe_code'] ) {
                    $dmsg =   __( 'User with this email already subscribed!', 'email-newsletter' );
                } else {
                    $this->subscribe( $member['member_id'], $redirect_to );
                    exit;
                }
        }

        if ( "" == $dmsg ) {
            if ( 1 == $member_data['double_opt_in'] )
                $unsubscribe_code = "";
            else
                $unsubscribe_code = $this->gen_unsubscribe_code();

            if ( $member_data['future_groups_id'] ) {
                $member_info = array(
                    "future_groups_id" => $member_data['future_groups_id']
                );

                $member_info = serialize( $member_info );
            }

            $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_members SET
                member_fname = '%s',
                member_lname = '%s',
                member_email = '%s',
                join_date = '%s',
                member_info = '%s',
                unsubscribe_code = '%s'",
                $member_data['fname'], $member_data['lname'], $member_data['email'], time(), $member_info, $unsubscribe_code ) );

            $member_id = $wpdb->insert_id;

            if ( 1 == $member_data['double_opt_in'] ) {
                $this->do_double_opt_in( $member_id );
            } else {
                //creating new list of groups for user
                if ( is_array( $member_data['groups_id'] ) )
                    foreach( $member_data['groups_id'] as $group_id )
                        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );
            }
            $dmsg =  __( 'The new Member was added!', 'email-newsletter' );
        }

        if ( "" == $redirect_to ) {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => $dmsg ), 'admin.php' ) );
            exit;
        } else {
            $_SESSION['newsletter_widget_status'] = $dmsg;
            wp_redirect( $redirect_to );
            exit;
        }
    }

    /**
     * Delete members
     **/
    function delete_members( $members_id ) {
        global $wpdb;

        if ( $members_id ) {
            foreach( ( array ) $members_id as $member_id ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
            }

            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'dmsg' => urlencode( __( 'Members were deleted!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }
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
     * Adding new member when create new user
     **/
    function user_create( $userID ) {
        global $wpdb;
        $unsubscribe_code = $this->gen_unsubscribe_code();

        $user = get_userdata( $userID );

        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_members SET
            wp_user_id = %d,
            member_fname = %s,
            member_email = %s,
            join_date = %d,
            unsubscribe_code = '%s'
         ", $user->ID, $user->user_nicename, $user->user_email, time(), $unsubscribe_code ) );
    }

    /**
     * Deleting member's groups and member when delete site user
     **/
    function user_delete( $userID ) {
        global $wpdb;

        if ( function_exists('is_multisite' ) && is_multisite() ) {
                $blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
        } else {
                $blogids[] = 1;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            $member_id = $this->get_members_by_wp_user_id( $userID, $blog_id );

            if ( 0 < $member_id ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
            }
        }
    }

    /**
     * Deleting member's groups and member when remove user fron site
     **/
    function user_remove_from_site( $userID ) {
        global $wpdb;

        $member_id = $this->get_members_by_wp_user_id( $userID );

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
    }

    /**
     * Get users by Role
     **/
    function get_users_by_role( $role ) {
        $wp_user_search = new WP_User_Search( "", "", $role );
        return $wp_user_search->get_results();
    }





    /**
     * Delete Newsletter
     **/
    function delete_newsletter( $newsletter_id, $page_redirect ) {
        global $wpdb;
        if ( ! $page_redirect )
            $page_redirect = "newsletters-dashboard";

        if ( "newsletters-create" == $page_redirect )
            $page_redirect = "newsletters";

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_newsletters WHERE newsletter_id = %d", $newsletter_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_send WHERE newsletter_id = %d", $newsletter_id ) );

        wp_redirect( add_query_arg( array( 'page' => $page_redirect, 'updated' => 'true', 'dmsg' => urlencode( __( 'The Newsletter was deleted!', 'email-newsletter' ) ) ), 'admin.php' ) );
        exit;
    }

    /**
     * Save Newsletter
     **/
    function save_newsletter( $newsletter_id, $page_redirect ) {
        global $wpdb;

        $newsletter_id = $_REQUEST['newsletter_id'];

        $content        = base64_decode( str_replace( "-", "+", $_REQUEST['content_ecoded'] ) );
        $contact_info   = base64_decode( str_replace( "-", "+", $_REQUEST['contact_info'] ) );

        $fields = array(
            "template"      => $_REQUEST['newsletter_template'],
            "subject"       => $_REQUEST['subject'],
            "from_name"     => $_REQUEST['from_name'],
            "from_email"    => $_REQUEST['from_email'],
            "bounce_email"  => $_REQUEST['bounce_email'],
            "content"       => $content,
            "contact_info"  => $contact_info,
        );

        if( ! $newsletter_id ) {
            $sql    = "INSERT INTO {$this->tb_prefix}enewsletter_newsletters SET create_date = " . time() . " ";
            $where  = '';
        }else{
            $sql    = "UPDATE {$this->tb_prefix}enewsletter_newsletters SET newsletter_id = '".mysql_real_escape_string( $newsletter_id )."' ";
            $where  = " WHERE newsletter_id = '".mysql_real_escape_string( $newsletter_id )."' LIMIT 1";
        }

        foreach( $fields as $key=>$val ) {
            $val = trim( $val );
            if( $val == '' )continue;

            $sql .= ", `".$key."` = '".mysql_real_escape_string( $val )."'";
        }
        $sql .= $where;

        $result = $wpdb->query( $sql );

        if( ! $newsletter_id )
            $newsletter_id = $wpdb->insert_id;

        //Save nad redirect on Send page
        if ( "send" == $_REQUEST['send'] ) {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'dmsg' => urlencode( __( 'The Newsletter was Saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        }

        wp_redirect( add_query_arg( array( 'page' => 'newsletters-create', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'dmsg' => urlencode( __( 'The Newsletter was Saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
        exit;
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
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Plugin was installed!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-settings', 'updated' => 'true', 'dmsg' => urlencode( __( 'The Settings was saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
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
     * Generate Unsubscribe code
     **/
    function gen_unsubscribe_code() {
        $now = time();
        $unsubscribe_code = substr( $now, strlen( $now ) - 3, 3 ) . substr( md5( uniqid( rand(), true ) ), 0, 8 ) . substr( md5( $now . rand() ), 0, 4);
        return $unsubscribe_code;
    }


    /**
     * file_upload_ajax
     **/
    function file_upload_ajax() {
        global $wpdb;
        require_once( $plugin_dir . "email-newsletter-files/file-uploader.php" );

        die("");
    }


    /**
     * Check that email was opened
     **/
    function check_email_opened_ajax() {
        global $wpdb;
        //write opened time to table
        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET opened_time = %d WHERE send_id = %d AND member_id = %d AND opened_time = 0" , time(), $_REQUEST['send_id'], $_REQUEST['member_id'] ) );

        //show blank image 1x1
        $filename = $this->plugin_dir . "email-newsletter-files/images/spacer.gif";
        $handle = fopen( $filename, "r" );
        $content = fread( $handle, filesize( $filename ) );
        fclose( $handle );
        die($content);
    }


    /**
     * Confirm subscibe from Email
     **/
    function confirm_subscibe_ajax() {
        global $wpdb;

        $member_id = $_REQUEST['member_id'];

        if ( $_REQUEST['hash'] != md5( "sometext123" . $member_id ) )
            die( __( 'Wrong Subscribe data!', 'email-newsletter' ) );

        $member_data = $this->get_member( $member_id );

        $unsubscribe_code = $this->gen_unsubscribe_code();

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET member_info = '', unsubscribe_code = '%s' WHERE member_id = %d", $unsubscribe_code, $member_id ) );

        //creating new list of groups for user
        if ( is_array( $member_data['future_groups_id'] ) )
            foreach( ( array ) $member_data['future_groups_id'] as $group_id )
                $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );


        die( __( 'Subscribe Successful!', 'email-newsletter' ) );
    }


    /**
     * Change group
     **/
    function change_groups_ajax() {
        $users_group = $this->get_memeber_groups( $_REQUEST['member_id'] );
        if ( ! is_array( $users_group ) )
            $users_group = array();

        $groups = $this->get_groups();
         if ( 0 < count( $groups ) ) {
            $content = __( 'Select Groups for this user:', 'email-newsletter' ) . "<br />";

            foreach( $groups as $group ){
                if ( false === array_search ( $group['group_id'], $users_group ) )
                    $checked = '';
                else
                    $checked = 'checked="checked"';
                $content .= '<label><input type="checkbox" name="groups_id[]" value="' . $group['group_id'] . '" ' . $checked . ' />' . $group['group_name'] . '</label><br />';
            }
            $content .= "<br />";

        } else {
            $content = __( 'Please create any groups.', 'email-newsletter' ) . "<br />";
        }

        die($content);
    }


    /**
     * Unsubscibe from email
     **/
    function unsubscibe_ajax() {
        $this->unsubscribe( $_REQUEST['unsubscribe_code'] );
        die('');
    }


    /**
     * Show Preview
     **/
    function show_preview_ajax() {

        //open template file
        $filename   = $this->plugin_dir . "email-newsletter-files/templates/" . $_REQUEST['template'] . "/template.html";
        $handle     = fopen( $filename, "r" );
        $contents   = fread( $handle, filesize( $filename ) );
        fclose( $handle );

        //Replace content of template
        $content        = base64_decode( str_replace( "-", "+", $_REQUEST['content'] ) );
        $contact_info   = base64_decode( str_replace( "-", "+", $_REQUEST['contact_info'] ) );

        $contents = str_replace( "{EMAIL_BODY}", $content, $contents );
        $contents = str_replace( "{USER_NAME}", "UserName", $contents );
        $contents = str_replace( "{TO_EMAIL}", "", $contents );
        $contents = str_replace( "{EMAIL_SUBJECT}", stripslashes ( $_REQUEST['subject'] ), $contents );
        $contents = str_replace( "{FROM_NAME}", stripslashes ( $_REQUEST['from_name'] ), $contents );
        $contents = str_replace( "{FROM_EMAIL}", stripslashes ( $_REQUEST['from_email'] ), $contents );
        $contents = str_replace( "{CONTACT_INFO}", $contact_info, $contents );
        $contents = str_replace( "images/", $this->plugin_url . "email-newsletter-files/templates/" . $_REQUEST['template'] . "/images/", $contents );


       die( $contents );

    }


    /**
     * Write inforamtion of Send newsletter to DB
     **/
    function send_newsletter( $newsletter_id ) {
        global $wpdb;

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

        $email_body = $this->make_email_body( $newsletter_id );

        $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_send SET newsletter_id = %d, start_time = %d, end_time = '', email_body = '%s'", $newsletter_id, time(), $email_body ) );
        $send_id = $wpdb->insert_id;

        if ( 'cron' == $_REQUEST["cron"] )
            $status = 'by_cron';
        else
            $status = 'waiting_send';


        foreach ( $members_id as $member_id ) {

            if ( ! ( "1" == $_REQUEST['dont_send_duplicate'] && $this->check_duplicate_send( $newsletter_id, $member_id ) ) )
                $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_send_members SET send_id = %d, member_id = %d, status = '%s' ", $send_id, $member_id, $status ) );
        }

        $count_send_members = $this->get_count_send_members( $send_id, $status );

        if ( 0 == $count_send_members )
            wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'dmsg' => urlencode( __( 'All members already received it!', 'email-newsletter' ) ) ), 'admin.php' ) );
        else
            if ( 'cron' == $_REQUEST["cron"] )
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'dmsg' => urlencode( $count_send_members . ' ' . __( 'members were added to CRON list', 'email-newsletter' ) ) ), 'admin.php' ) );
            else
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'send_id' => $send_id, 'check_key' => $_SESSION['check_key'] ), 'admin.php' ) );

        exit;

    }

    /**
     * Add email or send to CRON list
     **/
    function add_to_cron( $newsletter_id, $send_id ) {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'by_cron' WHERE send_id = %d AND status = 'waiting_send'", $send_id ) );

        $count_send_members = $this->get_count_send_members( $send_id, 'by_cron' );

        wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'dmsg' => urlencode( $count_send_members . ' ' . __( 'members were added to CRON list', 'email-newsletter' ) ) ), 'admin.php' ) );

        exit;

    }



    /**
     * Send email to member
     **/
    function send_email_to_member() {
        global $wpdb;

        if ( $_REQUEST['check_key'] != $_SESSION['check_key'] )
            die('error1');

        $send_id = $_REQUEST['send_id'];
        //get data of newsletter
        $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_id ), "ARRAY_A");

        $send_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = 'waiting_send' LIMIT 0, 1",  $send_id ), "ARRAY_A");

        if ( ! $send_member ) {
            if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_1' . $wpdb->blogid ) )
                wp_schedule_single_event( time() + 60, 'e_newsletter_cron_check_bounces_1' . $wpdb->blogid );

            die('end');
        }

        $member_data = $this->get_member( $send_member['member_id'] );

        require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

        $unsubscribe_code = $member_data['unsubscribe_code'];

        $siteurl = get_option( 'siteurl' );

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

        $contents = $send_data['email_body'];
        //Replace content of template
        $contents = str_replace( "{OPENED_TRACKER}", '<img src="' . $siteurl . '/wp-admin/admin-ajax.php?action=check_email_opened&send_id=' . $send_id . '&member_id=' . $member_data['member_id'] . '" width="1" height="1" style="display:none;" />', $contents );
        $contents = str_replace( "{UNSUBSCRIBE_URL}", $siteurl . '/wp-admin/admin-ajax.php?action=newsletter_unsubscibe&unsubscribe_code=' . $unsubscribe_code, $contents );

        $mail->From         = $newsletter_data['from_email'];
        $mail->FromName     = $newsletter_data['from_name'];
        $mail->Subject      = $newsletter_data["subject"];

        $mail->MsgHTML( $contents );

        $mail->AddAddress( $member_data["member_email"] );

        $mail->MessageID = 'Newsletters-' . $send_member['member_id'] . '-' . $send_id . '-'. md5( 'Hash of bounce member_id='. $send_member['member_id'] . ', send_id='. $send_id );

        if( ! $mail->Send() ) {
//            return "Mailer Error: " . $mail->ErrorInfo;
            die('error');
        } else {
            //write info of Sent in DB
            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'sent' WHERE send_id = %d AND member_id = %d", $send_id, $send_member['member_id'] ) );
            if ( $result )
                die('ok');
            else
                die('error');
        }
    }



    /**
     * Send email to member
     **/
    function send_by_wpcron() {
        global $wpdb;

        @set_time_limit( 0 );

        if ( 0 < $this->settings['send_limit'] )
            $send_limit = 'LIMIT 0, ' . $this->settings['send_limit'];
        else
            $send_limit = '';

        $send_members = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE status = 'by_cron' " . $send_limit , "ARRAY_A");

        if ( ! $send_members )
            return 'end';

        require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        foreach ( $send_members as $send_member ) {

            $member_data = $this->get_member( $send_member['member_id'] );

            $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_member['send_id'] ), "ARRAY_A");

            $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

            $unsubscribe_code = $member_data['unsubscribe_code'];

            $siteurl = get_option( 'siteurl' );

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

            $contents = $send_data['email_body'];
            //Replace content of template
            $contents = str_replace( "{OPENED_TRACKER}", '<img src="' . $siteurl . '/wp-admin/admin-ajax.php?action=check_email_opened&send_id=' . $send_member['send_id'] . '&member_id=' . $member_data['member_id'] . '" width="1" height="1" style="display:none;" />', $contents );
            $contents = str_replace( "{UNSUBSCRIBE_URL}", $siteurl . '/wp-admin/admin-ajax.php?action=newsletter_unsubscibe&unsubscribe_code=' . $unsubscribe_code, $contents );

            $mail->From         = $newsletter_data['from_email'];
            $mail->FromName     = $newsletter_data['from_name'];
            $mail->Subject      = $newsletter_data["subject"];

            $mail->MsgHTML( $contents );

            $mail->AddAddress( $member_data["member_email"] );

            $mail->MessageID = 'Newsletters-' . $send_member['member_id'] . '-' . $send_member['send_id'] . '-'. md5( 'Hash of bounce member_id='. $send_member['member_id'] . ', send_id='. $send_member['send_id'] );

            if( ! $mail->Send() ) {
    //            return "Mailer Error: " . $mail->ErrorInfo;
//                return 'error';
            } else {
                //write info of Sent in DB
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'sent' WHERE send_id = %d AND member_id = %d", $send_member['send_id'], $send_member['member_id'] ) );
//                if ( ! $result )
//                    return 'error';

            }
        }

        if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_2' . $wpdb->blogid ) )
            wp_schedule_single_event( time() + 60, 'e_newsletter_cron_check_bounces_2' . $wpdb->blogid );
    }


    /**
     * Send Preview (Test) newsletter email
     **/
    function send_preview_ajax() {

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

        //open template file
        $filename   = $this->plugin_dir . "email-newsletter-files/templates/" . $_REQUEST['template'] . "/template.html";
        $handle     = fopen( $filename, "r" );
        $contents   = fread( $handle, filesize( $filename ) );
        fclose( $handle );

        //Replace content of template
        $content        = base64_decode( str_replace( "-", "+", $_REQUEST['content'] ) );
        $contact_info   = base64_decode( str_replace( "-", "+", $_REQUEST['contact_info'] ) );

        $contents = str_replace( "{EMAIL_BODY}", $content, $contents );
        $contents = str_replace( "{EMAIL_SUBJECT}", stripslashes ( $_REQUEST['subject'] ), $contents );
        $contents = str_replace( "{FROM_NAME}", stripslashes ( $_REQUEST['from_name'] ), $contents );
        $contents = str_replace( "{FROM_EMAIL}", $_REQUEST['from_email'], $contents );
        $contents = str_replace( "{CONTACT_INFO}", $contact_info, $contents );
        $contents = str_replace( "images/", $this->plugin_url . "email-newsletter-files/templates/" . $_REQUEST['template'] . "/images/", $contents );

        $mail->From     = $_REQUEST['from_email'];
        $mail->FromName = stripslashes ( $_REQUEST['from_name'] );
        $mail->Subject  = stripslashes ( $_REQUEST["subject"] );

        $mail->MsgHTML( $contents );

        $mail->AddAddress( $_REQUEST["preview_email"] );

        if( $this->settings['bounce_email'] ) {
            $mail->Sender = $this->settings['bounce_email'];
        }

        if( ! $mail->Send() )
            die( "Mailer Error: " . $mail->ErrorInfo );
        else
            die( "Test Email was sent" );
    }


    /**
     * Send newsletter email
     **/
    function send_email_newsletter( $newsletter_id, $member_id ) {
        global $wpdb;

        require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        //get data of newsletter
        $newsletter_data = $this->get_newsletter_data( $newsletter_id );

        $member_data = $this->get_member( $member_id );

        $unsubscribe_code = $member_data['unsubscribe_code'];

        $siteurl = get_option( 'siteurl' );

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

        //open template file
        $filename   = $this->plugin_dir . "email-newsletter-files/templates/" . $newsletter_data['template'] . "/template.html";
        $handle     = fopen( $filename, "r" );
        $contents   = fread( $handle, filesize( $filename ) );
        fclose( $handle );

        $newsletter_data['content'] = '<img src="' . $siteurl . '/wp-admin/admin-ajax.php?action=check_email_opened&newsletter_id=' . $newsletter_id . '&member_id=' . $member_id . '" width="1" height="1" style="display:none;" />' . $newsletter_data['content'];

        //Replace content of template
        $contents = str_replace( "{EMAIL_BODY}", $newsletter_data['content'], $contents );
        $contents = str_replace( "{UNSUBSCRIBE_URL}", $siteurl . '/wp-admin/admin-ajax.php?action=newsletter_unsubscibe&unsubscribe_code=' . $unsubscribe_code, $contents );
        $contents = str_replace( "{EMAIL_SUBJECT}", $newsletter_data['subject'], $contents );
        $contents = str_replace( "{FROM_NAME}", $newsletter_data['from_name'], $contents );
        $contents = str_replace( "{FROM_EMAIL}", $newsletter_data['from_email'], $contents );
        $contents = str_replace( "{CONTACT_INFO}", $newsletter_data['contact_info'], $contents );
        $contents = str_replace( "images/", $this->plugin_url . "email-newsletter-files/templates/" . $newsletter_data['template'] . "/images/", $contents );

        $mail->From         = $newsletter_data['from_email'];
        $mail->FromName     = $newsletter_data['from_name'];
        $mail->Subject      = $newsletter_data["subject"];

        $mail->MsgHTML( $contents );

        $mail->AddAddress( $member_data["member_email"] );

        if( ! $mail->Send() ) {
//            return "Mailer Error: " . $mail->ErrorInfo;
        } else {
            //write info of Sent in DB
            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send SET sent_time = %d, status = 2 WHERE newsletter_id = %d, member_id = %d", time(), $newsletter_id, $member_id ) );
            return true;
        }


    }


    /**
     * Make email body
     **/
    function make_email_body( $newsletter_id ) {
        //get data of newsletter
        $newsletter_data = $this->get_newsletter_data( $newsletter_id );

        //open template file
        $filename   = $this->plugin_dir . "email-newsletter-files/templates/" . $newsletter_data['template'] . "/template.html";
        $handle     = fopen( $filename, "r" );
        $contents   = fread( $handle, filesize( $filename ) );
        fclose( $handle );

        $newsletter_data['content'] = '{OPENED_TRACKER}' . $newsletter_data['content'];

        //Replace content of template
        $contents = str_replace( "{EMAIL_BODY}", $newsletter_data['content'], $contents );
        $contents = str_replace( "{EMAIL_SUBJECT}", $newsletter_data['subject'], $contents );
        $contents = str_replace( "{FROM_NAME}", $newsletter_data['from_name'], $contents );
        $contents = str_replace( "{FROM_EMAIL}", $newsletter_data['from_email'], $contents );
        $contents = str_replace( "{CONTACT_INFO}", $newsletter_data['contact_info'], $contents );
        $contents = str_replace( "images/", $this->plugin_url . "email-newsletter-files/templates/" . $newsletter_data['template'] . "/images/", $contents );

        return $contents;
    }

    /**
     * Test bounces settings
     **/
    function test_bounces_ajax(){
        @set_time_limit( 0 );

        //Send test email on bounces address
        $email_id           = time();
        $email_to           = $_REQUEST['bounce_email'];
        $email_from         = ( $this->settings['from_email'] ) ? $this->settings['from_email'] : $_REQUEST['bounce_email'];
        $email_from_name    = ( $this->settings['from_name'] ) ? $this->settings['from_name'] : $_REQUEST['bounce_email'];
        $email_subject      = 'Test Connection Bounce';
        $email_contents     = 'Test';
        $options            = array (
            "bounce_email" => $_REQUEST['bounce_email'],
            "message_id" => "Test-Connection-Bounce-". $email_id,
        );

        if( !$this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents, $options ) ) {
            die( "Failed to send test email!" );
        }

        //Set value for connect to email server
        $email_address  = $_REQUEST['bounce_email'];
        $email_username = $_REQUEST['bounce_username'];
        $email_password = $_REQUEST['bounce_password'];
        $email_host     = trim( $_REQUEST['bounce_host'] );
        $email_port     = ( $_REQUEST['bounce_port'] ) ? $_REQUEST['bounce_port'] : 110;

        if( ! $email_host )
            return true;

        $mbox = imap_open ( '{'.$email_host.':'.$email_port.'/pop3/notls}INBOX', $email_username, $email_password ) or die( imap_last_error() );

        if( ! $mbox ) {
            echo 'Failed to connect when checking bounces.';
        } else {
            $MC     = imap_check( $mbox );

            //get all emails
            $mails = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

            foreach ( $mails as $mail ) {
                //Search test email on server
                if( preg_match( '/Test-Connection-Bounce-(\d+)/i', $mail->message_id, $matches) )
                    if( ( int ) $matches[1] == $email_id ) {
                        imap_delete( $mbox, $mail->msgno );
                        imap_expunge( $mbox );
                        imap_close( $mbox );
                        die(  __( 'Connecting successful!', 'email-newsletter' ) );
                    }
            }
            imap_expunge( $mbox );
            imap_close( $mbox );
            die(  __( 'Connection failed!', 'email-newsletter' ) );
        }
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
     * Install of plugin
     **/
    function activation( $blog_id = '' ) {
        global $wpdb;

        if ( function_exists('is_multisite' ) && is_multisite() && 0 !== $blog_id && $_GET['networkwide'] == 1 ) {
                $blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
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
                    `bounce_time` int(11) DEFAULT '0'
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

        }
    }

    /**
     * Deleting DB tables and other setting when deactivation plugin
     **/
    function deactivation( $blog_id = '' ) {
        global $wpdb;

        if ( function_exists('is_multisite' ) && is_multisite() && 0 !== $blog_id  && $_GET['networkwide'] == 1 ) {
                $blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
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
            if ( wp_next_scheduled( 'e_newsletter_cron_send' . $wpdb->blogid ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_send' . $wpdb->blogid );

            if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_1' . $wpdb->blogid ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_1' . $wpdb->blogid );

            if ( wp_next_scheduled( 'e_newsletter_cron_check_bounces_2' . $wpdb->blogid ) )
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_2' . $wpdb->blogid );


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

        }
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
     * Send Confirm email for subscribe
     **/
     function do_double_opt_in( $member_id ){
        $message = '';
        if( isset( $this->settings['double_opt_in'] ) && $this->settings['double_opt_in'] ) {

            $siteurl = get_option( 'siteurl' );

            $member_data = $this->get_member( $member_id );

            $email_to           = $member_data['member_email'];
            $email_from         = $this->settings['from_email'];
            $email_from_name    = $this->settings['from_name'];
            $email_subject      = ( isset( $this->settings['double_opt_in_subject'] ) ) ? $this->settings['double_opt_in_subject'] : 'Confirm newsletter subscription';
            $email_contents     = file_get_contents( $this->plugin_dir . "email-newsletter-files/emails/double_optin.html" );

            $replace = array(
                "from_name"=>$email_from_name,
                "CONFIRM_SUBSCRIPTION"=> $siteurl . '/wp-admin/admin-ajax.php?action=confirm_subscibe&member_id=' . $member_id .'&hash='.md5( "sometext123" . $member_id ) . '',
                "first_name"=>$member_data['member_fname'],
                "last_name"=>$member_data['member_lname'],
                "email"=>$member_data['member_email'],
            );

            foreach( $replace as $key=>$val ) {
                if( is_array( $val ) )continue;
                $email_contents = preg_replace( '/\{'.strtoupper( preg_quote( $key,'/' ) ).'\}/', $val, $email_contents );
            }
            if( !$this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents ) ) {
                $message .= "Failed to send opt-in email, please contact us to inform us of this error. ";
            }else{
            }
        }

    }


    /**
     * Creating admin menu
     **/
    function admin_page() {

        if ( $this->settings ) {
            if ( current_user_can('manage_network_options') || current_user_can('manage_options') ) {
                //menu for admin
                if ( current_user_can('manage_network_options') )
                    $cap = "manage_network_options";
                else
                    $cap = "manage_options";

                add_menu_page( __( 'eNewsletter', 'email-newsletter' ), __( 'eNewsletter', 'email-newsletter' ), $cap, 'newsletters-dashboard' );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Dashboard', 'email-newsletter' ), __( 'Dashboard', 'email-newsletter' ), $cap, 'newsletters-dashboard', array( &$this, 'newsletters_dashboard_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Newsletters', 'email-newsletter' ), __( 'Newsletters', 'email-newsletter' ), $cap, 'newsletters', array( &$this, 'newsletters_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Create Newsletter', 'email-newsletter' ), __( 'Create Newsletter', 'email-newsletter' ), $cap, 'newsletters-create', array( &$this, 'create_newsletter_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Member Groups', 'email-newsletter' ), __( 'Member Groups', 'email-newsletter' ), $cap, 'newsletters-groups', array( &$this, 'member_groups_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Members', 'email-newsletter' ), __( 'Members', 'email-newsletter' ), $cap, 'newsletters-members',  array( &$this, 'members_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'My Subscriptions', 'email-newsletter' ), __( 'My Subscriptions', 'email-newsletter' ), $cap, 'newsletters-subscribes', array( &$this, 'newsletters_subscribe_page' ) );
                $page = add_submenu_page( 'newsletters-dashboard', __( 'Settings', 'email-newsletter' ), __( 'Settings', 'email-newsletter' ), $cap, 'newsletters-settings', array( &$this, 'settings_page' ) );

            } else {
                //menu for other users
                add_menu_page( __( 'eNewsletter', 'email-newsletter' ), __( 'eNewsletter', 'email-newsletter' ), 'read', 'newsletters-subscribes' );
                $page = add_submenu_page( 'newsletters-subscribes', __( 'My Subscriptions', 'email-newsletter' ), __( 'My Subscriptions', 'email-newsletter' ), 'read', 'newsletters-subscribes', array( &$this, 'newsletters_subscribe_page' ) );
            }
        } else {
            //firsr start of plugin
            add_menu_page( __( 'eNewsletter', 'email-newsletter' ), __( 'eNewsletter', 'email-newsletter' ), 'manage_options', 'newsletters-settings' );
            $page = add_submenu_page( 'newsletters-settings', __( 'Install Settings', 'email-newsletter' ), __( 'Install Settings', 'email-newsletter' ), 'manage_options', 'newsletters-settings', array( &$this, 'settings_page' ) );
        }
    }


    /**
     *  Tempalate of the Newsletters Dashboard page
     **/
    function newsletters_dashboard_page() {
        //including file for send newsletter
        if ( "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }

        require_once( $plugin_dir . "email-newsletter-files/page-newsletters-dashboard.php" );
    }

    /**
     *  Tempalate of the Newsletters page
     **/
    function newsletters_page() {
        //including file for send newsletter
        if ( "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }

        require_once( $plugin_dir . "email-newsletter-files/page-newsletters.php" );
    }

    /**
     *  Tempalate of the Create/Edit Newsletter page
     **/
    function create_newsletter_page() {
        require_once( $plugin_dir . "email-newsletter-files/page-create-newsletter.php" );
    }

    /**
     *  Tempalate of the Groups list
     **/
    function member_groups_page() {
        require_once( $plugin_dir . "email-newsletter-files/page-groups.php" );
    }

    /**
     *  Tempalate of the Memebers page
     **/
    function members_page() {
        require_once( $plugin_dir . "email-newsletter-files/page-members.php" );
    }

    /**
     *  Tempalate of the Settings page
     **/
    function settings_page() {
        require_once( $plugin_dir . "email-newsletter-files/page-settings.php" );
    }

    /**
     *  Tempalate of the Settings page
     **/
    function newsletters_subscribe_page() {
        require_once( $plugin_dir . "email-newsletter-files/page-subscribe.php" );
    }

}

$email_newsletter =& new Email_Newsletter();






// Widget for Subscribe
class e_newsletter_subscribe extends WP_Widget {
    //constructor
    function e_newsletter_subscribe() {
        if( isset( $_REQUEST['wp3_newsletter_subscribe'] ) ) {
        }
        session_start();

        $widget_ops = array( 'description' => __( 'Allow people to subscribe to your newsletter database. (creates a wordpress user account for them)') );
        parent::WP_Widget( false, __( 'Newsletters: Subscribe' ), $widget_ops );
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $email_newsletter, $current_user;


        $groups = $email_newsletter->get_groups();

        extract( $args );

        if ( 0 < $current_user->data->ID ) {
            $member_id      = $email_newsletter->get_members_by_wp_user_id( $current_user->data->ID );
            $member_data    = $email_newsletter->get_member( $member_id );

            if ( "" != $member_data['unsubscribe_code'] ) {
                $member_groups = $email_newsletter->get_memeber_groups( $member_id );
                if ( ! is_array( $member_groups ) )
                    $member_groups = array();

            }

            $show_groups = true;
        } else {

            $show_name      = apply_filters( 'widget_title', $instance['name'] );
            $show_groups    = apply_filters( 'widget_title', $instance['groups'] );
        }

        $title = apply_filters( 'widget_title', $instance['title'] );

        ?>
        <?php
        echo $before_widget;
        if ( $title )
            echo $before_title . $title . $after_title;
        ?>

    <script language="JavaScript">
        jQuery( document ).ready( function() {

            //New Subscibes
            jQuery( "#new_subscribe" ).click( function() {
                if ( "" == jQuery( "#e_newsletter_email" ).val() ) {
                    alert('<?php _e( 'Please write your Email!', 'email-newsletter' ) ?>');
                    return false;
                }

                jQuery( "#newsletter_action" ).val( 'new_subscribe' );
                jQuery( "#subscribes_form" ).submit();

            });

            //Save Subscibes
            jQuery( "#save_subscribes" ).click( function() {
                jQuery( "#newsletter_action" ).val( 'save_subscribes' );
                jQuery( "#subscribes_form" ).submit();

            });

            //Unsubscribes
            jQuery( "#unsubscribe" ).click( function() {
                jQuery( "#newsletter_action" ).val( 'unsubscribe' );
                jQuery( "#subscribes_form" ).submit();

            });
        });
    </script>

    <div class="e-newsletter-widget">
        <?php
        if ( $_SESSION['newsletter_widget_status'] ) {
        ?>
            <div id="message" style="background-color: #FFFFE0;border-color: #E6DB55;margin: 5px 0 15px;-moz-border-radius: 3px 3px 3px 3px;border-style: solid;border-width: 1px;padding: 5px;"><?php echo $_SESSION['newsletter_widget_status'] ; ?></div>
        <?php
            session_unregister( 'newsletter_widget_status' );
        }
        ?>

        <form action="" method="post" name="subscribes_form" id="subscribes_form">
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <?php
            if ( 0 == $current_user->data->ID ) {
            ?>
                <label><?php _e( 'Your Email:', 'email-newsletter' ) ?></label>
                <input type="text" name="e_newsletter_email" id="e_newsletter_email" />


                <?php
                if( $show_name ) {
                ?>
                    <label><?php _e( 'Your Name:', 'email-newsletter' ) ?></label>
                    <input type="text" name="e_newsletter_name" id="e_newsletter_name" />
                <?php
                }

                if( $show_groups && $groups ) {
                ?>
                    <label><?php _e( 'Subscribe to:', 'email-newsletter' ) ?></label>
                    <ul style="list-style: none outside none;">
                        <?php
                        foreach( ( array ) $groups as $group ) {
                            if( ! $group['public'] ) continue;
                        ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="e_newsletter_groups_id[]" value="<?php echo $group['group_id'];?>" />
                                    <?php echo $group['group_name'];?>
                                </label>
                            </li>
                        <?php
                        }
                        ?>
                    </ul>
                <?php
                }
                ?>

                <input type="button" id="new_subscribe" value="<?php _e( 'Subscribe', 'email-newsletter' ) ?>" />


            <?php
            } else if ( "" != $member_data['unsubscribe_code'] && 0 < $current_user->data->ID ) {
            ?>
                <input type="hidden" name="unsubscribe_code" value="<?php echo $member_data['unsubscribe_code']; ?>" />

                <?php
                if( $groups ) {
                ?>
                    <label><?php _e( 'Subscribe to:', 'email-newsletter' ) ?></label>
                    <ul style="list-style: none outside none;">
                        <?php
                        foreach( (array) $groups as $group ){
                            if ( false === array_search ( $group['group_id'], ( array ) $member_groups ) )
                                $checked = '';
                            else
                                $checked = 'checked="checked"';
                        ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="e_newsletter_groups_id[]" value="<?php echo $group['group_id'];?>" <?php echo $checked;?> />
                                    <?php echo $group['group_name'];?>
                                </label>
                            </li>
                        <?php
                        }
                        ?>
                    </ul>
                <?php
                }
                ?>
                <input type="button" id="save_subscribes" value="<?php _e( 'Save Subscribes', 'email-newsletter' ) ?>" />
                <br />
                <a href="javascript:;" id="unsubscribe" ><?php _e( 'Unsubscribe', 'email-newsletter' ) ?></a>

            <?php
            } else if ( 0 < $current_user->data->ID ) {
            ?>
                <input type="hidden" name="newsletter_action" value="subscribe" />
                <input type="submit" id="subscribe"  value="<?php _e( 'Subscribe on Newsletters', 'email-newsletter' ) ?>" />
            <?php
            }
            ?>
        </form>
    </div><!--//e-newsletter-widget  -->


        <?php echo $after_widget; ?>

    <?php

    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
        $instance           = $old_instance;
        $instance['title']  = strip_tags($new_instance['title']);
        $instance['name']   = strip_tags($new_instance['name']);
        $instance['groups'] = strip_tags($new_instance['groups']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {
        $title = esc_attr( $instance['title'] );
        if( ! $title ) $title = __( 'Subscribe to our newsletters', 'email-newsletter' );

        $name       = esc_attr( $instance['name'] );
        $groups     = esc_attr( $instance['groups'] );
        $campaigns  = esc_attr( $instance['campaigns'] );
        ?>
            <p>
                <label>
                    <?php _e( 'Title', 'email-newsletter' ) ?>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>
            <p>
                <label>
                    <?php _e( 'Ask for name?', 'email-newsletter' ) ?>
                    <input id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" type="checkbox" value="1" <?php echo $name ? ' checked' : '';?> />
                </label>
            </p>
            <p>
                <label>
                    <?php _e( 'Show Groups?', 'email-newsletter' ) ?>
                    <input id="<?php echo $this->get_field_id( 'groups' ); ?>" name="<?php echo $this->get_field_name( 'groups' ); ?>" type="checkbox" value="1" <?php echo $groups ? ' checked' : '';?> />
                </label>
            </p>
        <?php
    }

} // class e_newsletter_subscribe


add_action('widgets_init', create_function('', 'return register_widget("e_newsletter_subscribe");'));







?>