<?php
/*
Plugin Name: E-Newsletter
Plugin URI: http://premium.wpmudev.org/project/e-newsletter
Description: The ultimate WordPress email newsletter plugin for WordPress
Version: 2.3.5
Text Domain: email-newsletter
Author: Cole / Andrey (Incsub), Maniu (Incsub)
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
include_once( 'email-newsletter-files/class.functions.php' );
require_once( 'email-newsletter-files/builder/class.builder.php' );

/**
* Plugin main class
**/

class Email_Newsletter extends Email_Newsletter_functions {

    var $plugin_ver;
    var $plugin_main_file;
    var $plugin_dir;
    var $plugin_url;
    var $template_directory;
    var $template_custom_directory;
    var $settings;
    var $tb_prefix;
    var $cron_send_name;
    var $cron_bounce_name;
    var $plugin_templates = array();
    var $capabilities = array();

    var $debug;

    function Email_Newsletter() {
        __construct();
    }

    /**
     * PHP 5 constructor
     **/
    function __construct() {
        global $wpdb;

        $this->plugin_ver = 2.35;

        //enable or disable debugging
        $this->debug = 0;

        //checking for MultiSite
        if ( 1 < $wpdb->blogid )
            $this->tb_prefix = $wpdb->base_prefix . $wpdb->blogid . '_';
        else
            $this->tb_prefix = $wpdb->base_prefix;

        //set cron names
        $this->cron_send_name = 'e_newsletter_cron_send_' . $wpdb->blogid;
        $this->cron_bounce_name = 'e_newsletter_cron_check_bounces_' . $wpdb->blogid;

        //setup proper directories
        $this->plugin_main_file = __FILE__;
        $this->plugin_dir = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugins_url( '/', __FILE__ );
        if(!isset($this->plugin_dir) || !isset($this->plugin_url))
            wp_die( __('There was an issue determining plugin path or url', 'email-newsletter' ) );

        //templates directories
        $this->template_directory = $this->plugin_dir . 'email-newsletter-files/templates';
        $this->template_custom_directory = $this->get_custom_theme_dir();
        
        include_once($this->plugin_dir . '/email-newsletter-files/class.wpmudev_dash_notification.php');

        //get all setting of plugin
        $this->settings = $this->get_settings();
        
        // Setup all plugin capabilities
        $this->capabilities['create_newsletter'] = __('Create Newsletters','email-newsletter');
        $this->capabilities['save_newsletter'] = __('Edit Newsletters','email-newsletter');
        $this->capabilities['send_newsletter'] = __('Send Newsletters','email-newsletter');
        $this->capabilities['delete_newsletter'] = __('Delete Newsletters','email-newsletter');
        $this->capabilities['create_newsletter_group'] = __('Create Newsletter Groups','email-newsletter');
        $this->capabilities['edit_newsletter_group'] = __('Edit Newsletter Groups','email-newsletter');
        $this->capabilities['delete_newsletter_group'] = __('Delete Newsletter Groups','email-newsletter');
        $this->capabilities['change_newsletter_group'] = __('Change Newsletter Groups','email-newsletter');
        $this->capabilities['view_newsletter_members'] = __('View Newsletter Members','email-newsletter');
        $this->capabilities['add_newsletter_member'] = __('Add Newsletter Members','email-newsletter');
        $this->capabilities['edit_newsletter_member'] = __('Edit Newsletter Members','email-newsletter');
        $this->capabilities['delete_newsletter_member'] = __('Delete Newsletter Members','email-newsletter');
        $this->capabilities['add_members_group'] = __('Add Members To Group','email-newsletter');
        $this->capabilities['delete_members_group'] = __('Delete Members From Group','email-newsletter');
        $this->capabilities['save_newsletter_settings'] = __('Save Newsletter Settings','email-newsletter');
        $this->capabilities['view_newsletter_dashboard'] = __('View Dashboard Page','email-newsletter');
        $this->capabilities['import_newsletter_members'] = __('Import Members','email-newsletter');
        $this->capabilities['install_newsletter'] = __('First Time Install','email-newsletter');
        $this->capabilities['uninstall_newsletter'] = __('Un-Install Newsletter Data','email-newsletter');
        
        //Activate/deactivate actions
        register_activation_hook( $this->plugin_dir . 'e-newsletter.php', array( &$this, 'do_activation' ) );
        register_deactivation_hook( $this->plugin_dir . 'e-newsletter.php', array( &$this, 'do_deactivation' ) );
        
        //add new rewrite rules
        add_filter( 'rewrite_rules_array', array( &$this, 'insert_rewrite_rules' ) );
        add_filter( 'query_vars', array( &$this, 'insert_query_vars' ) );
        
        add_action('plugins_loaded',array(&$this,'set_current_user'), 1);
        add_action('plugins_loaded',array(&$this,'upgrade_check'));

        add_action( 'admin_init', array( &$this, 'admin_init' ) );
        add_action( 'admin_enqueue_scripts', array(&$this,'admin_enqueue_scripts'));

        // filter schedules
        add_filter( 'cron_schedules', array( &$this, 'add_new_cron_time' ) );

        add_action( 'init', array( &$this, 'init' ), 999 );  

        //some actions for MultiSite
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            add_action( 'wpmu_activate_user', array( &$this, 'user_create' ) );
            add_action( 'wpmu_new_user', array( &$this, 'user_create' ) );
            add_action( 'added_existing_user', array( &$this, 'user_create' ) );
            add_action( 'remove_user_from_blog', array( &$this, 'user_remove_from_site' ) );
            add_action( 'wpmu_delete_user', array( &$this, 'user_delete' ) );
            add_action( 'delete_blog', array( &$this, 'uninstall' ) );
            add_action( 'network_admin_menu', array( &$this, 'admin_page' ) );
        }
        //changing list of members when we create or delete user of the standard site
        add_action( 'user_register', array( &$this, 'user_create' ) );
        add_action( 'delete_user', array( &$this, 'user_delete' ) );            

        //Update member when editing user action
        add_action( 'edit_user_profile_update', array( &$this, 'edit_user_update_member' ) );
        add_action( 'personal_options_update', array( &$this, 'edit_user_update_member' ) );

        add_action( 'edit_user_profile', array( &$this, 'wp_admins_profile' ) );
        add_action( 'show_user_profile', array( &$this, 'wp_admins_profile' ) );

        //creating menu of the plugin
        add_action( 'admin_menu', array( &$this, 'admin_page' ) );

        //send email by WP-CRON
        add_action( $this->cron_send_name, array( &$this, 'send_by_wpcron' ) );

        //check bounces email by WP-CRON
        add_action( $this->cron_bounce_name .'_1', array( &$this, 'check_bounces' ) );
        add_action( $this->cron_bounce_name .'_2', array( &$this, 'check_bounces' ) );

        add_shortcode( 'enewsletter_subscribe', array( &$this, 'subscribe_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'email_newsletter_widgets_scripts' ) );


        //ajax action for sent preview (test) email
        //add_action( 'wp_ajax_nopriv_send_preview', array( &$this, 'send_preview_ajax' ) );
        add_action( 'wp_ajax_send_email_preview', array( &$this, 'send_preview_ajax' ) );

        //ajax action for change member's group on members page
        add_action( 'wp_ajax_nopriv_change_groups', array( &$this, 'change_groups_ajax' ) );
        add_action( 'wp_ajax_change_groups', array( &$this, 'change_groups_ajax' ) );

        //ajax action for show transparent image 1x1 for check that email was opened
        add_action( 'wp_ajax_nopriv_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );
        add_action( 'wp_ajax_check_email_opened', array( &$this, 'check_email_opened_ajax' ) );

        //ajax action for unsubscribe from email
        add_action( 'wp_ajax_nopriv_newsletter_unsubscribe', array( &$this, 'unsubscribe_ajax' ) );
        add_action( 'wp_ajax_newsletter_unsubscribe', array( &$this, 'unsubscribe_ajax' ) );

        //ajax action for subscribe
        add_action( 'wp_ajax_nopriv_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );
        add_action( 'wp_ajax_confirm_subscibe', array( &$this, 'confirm_subscibe_ajax' ) );

        //ajax action for test connection to bounces email
        add_action( 'wp_ajax_nopriv_test_bounces', array( &$this, 'test_bounces_ajax' ) );
        add_action( 'wp_ajax_test_bounces', array( &$this, 'test_bounces_ajax' ) );

        //ajax action for test connection to smtp server
        add_action( 'wp_ajax_nopriv_test_smtp', array( &$this, 'test_smtp_ajax' ) );
        add_action( 'wp_ajax_test_smtp', array( &$this, 'test_smtp_ajax' ) );

        //ajax action for sand email to member
        add_action( 'wp_ajax_nopriv_send_email_to_member', array( &$this, 'send_email_to_member' ) );
        add_action( 'wp_ajax_send_email_to_member', array( &$this, 'send_email_to_member' ) );

        add_action( 'template_redirect', array( &$this, 'template_redirect' ), 12 );

        add_action( 'wp_ajax_manage_subscriptions_ajax', array( &$this, 'manage_subscriptions_ajax' ));
        add_action( 'wp_ajax_nopriv_manage_subscriptions_ajax', array( &$this, 'manage_subscriptions_ajax'));

        // filter does shortcodes
        add_filter('email_newsletter_make_email_content', 'do_shortcode', 11);
    }
    
    /**
     * Sets current user
     */
    function set_current_user() {
        global $current_user;
        
        if(!$current_user)
            get_currentuserinfo();
    }

    /**
     * Do the stuff on activation
     *
     */
    function do_activation() {
        global $email_builder;
        
        //Update rewrite_rules
        flush_rewrite_rules( false );
        
        //create folder for custom themes
        $custom_theme_dir = $this->get_custom_theme_dir();
        
        if (!is_dir($custom_theme_dir)) {
            mkdir($custom_theme_dir);
        }
        
        //sets up cron
        $settings = $this->get_settings();
        if ( 1 == $settings['cron_enable'] ) {
            if ( wp_next_scheduled( $this->cron_send_name ) )
                wp_clear_scheduled_hook( $this->cron_send_name );
                
            wp_schedule_event( time(), '2mins', $this->cron_send_name );
        }
        else {
            if ( wp_next_scheduled( $this->cron_send_name ) )
                wp_clear_scheduled_hook( $this->cron_send_name );
        }
    }
    
    /**
     * Do the stuff on deactivation
     *
     */
    function do_deactivation() {
        if ( wp_next_scheduled( $this->cron_send_name ) )
            wp_clear_scheduled_hook( $this->cron_send_name );
        if ( wp_next_scheduled( $this->cron_bounce_name .'_1' ) )
            wp_clear_scheduled_hook( $this->cron_bounce_name .'_1' );
        if ( wp_next_scheduled( $this->cron_bounce_name .'_2' ) )
            wp_clear_scheduled_hook( $this->cron_bounce_name .'_2' );               
    }

    /**
     * Do the stuff on upgrade
     *
     */
    function upgrade_check() {
        //check if upgrade is necessary
        if($this->is_plugin_active_for_network(plugin_basename(__FILE__)))
            $prev = get_site_option('email_newsletter_version', 2);
        else
            $prev = get_option('email_newsletter_version', 1.25);

        if ($this->plugin_ver > $prev) {
            $this->upgrade('', $prev);

            if($this->is_plugin_active_for_network(plugin_basename(__FILE__)))
                update_site_option('email_newsletter_version', $this->plugin_ver);
            else
                update_option('email_newsletter_version', $this->plugin_ver);
        }               
    }

    /**
     * Adding a new rule
     **/
    function insert_rewrite_rules( $rules ) {
        $newrules = array();
        $newrules['e-newsletter/unsubscribe/([\w\d]{15})(\d*)/?$'] = 'index.php?unsubscribe_page=1&unsubscribe_code=$matches[1]&unsubscribe_member_id=$matches[2]';
        $newrules['e-newsletter/view/([\w\d]{15})(\d*)/?$'] = 'index.php?view_newsletter=1&view_newsletter_code=$matches[1]&view_newsletter_send_id=$matches[2]';
        return $newrules + $rules;
    }
    /**
     * Adding the var for unsubscribe page
     **/
    function insert_query_vars( $vars ) {
        array_push( $vars, 'unsubscribe_page' );
        array_push( $vars, 'unsubscribe_code' );
        array_push( $vars, 'unsubscribe_member_id' );

        array_push( $vars, 'view_newsletter' );
        array_push( $vars, 'view_newsletter_code' );
        array_push( $vars, 'view_newsletter_send_id' );
        return $vars;
    }
    function admin_enqueue_scripts($hook) {
         //including JS scripts for Newsletter pages
        if ( isset( $_REQUEST['page'] ) && 1 == $this->is_enewsletter_page( $_REQUEST['page'] ) ) {
            wp_enqueue_script( 'jquery' );

            //including JS scripts
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-ui-core' );

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
        
        // Including CSS file
        if ( isset( $_REQUEST['page'] ) && 1 == $this->is_enewsletter_page( $_REQUEST['page'] ) ) {
            wp_register_style( 'emailNewsletterStyle', $this->plugin_url . 'email-newsletter-files/email-newsletter.css', array(), 2 );
            wp_enqueue_style( 'emailNewsletterStyle' );
        }
    }

    /**
     * init for admin
     **/
    function admin_init() {
        $mu_cap = (function_exists('is_multisite' && is_multisite()) ? 'manage_network_options' : 'manage_options');

        //adds cap for admin
        $admin_role = get_role('administrator');
        foreach($this->capabilities as $key => $cap) {
            if(!isset($admin_role->capabilities[$key]) || $admin_role->capabilities[$key] == false ) {
                $admin_role->add_cap($key,true);
            }
        }
        
        //private actions of the plugin
        if ( isset( $_REQUEST['newsletter_action'] ) ) {
            switch( $_REQUEST[ 'newsletter_action' ] ) {

                //action for save Newsletter
                case "save_newsletter":
                    if(! (current_user_can('save_newsletter') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                        
                    $this->save_newsletter( $_REQUEST['newsletter_id'], $_REQUEST['page'] );
                break;

                //action for delete Newsletter
                case "delete_newsletter":
                    
                    if(! (current_user_can('delete_newsletter') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->delete_newsletter( $_REQUEST['newsletter_id'], $_REQUEST['page'] );

                break;

                //action for create new group
                case "create_group":
                    
                    if(! (current_user_can('create_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $edit_public = ( isset( $_REQUEST['public'] ) ) ? '1' : '0';
                    $this->create_group( $_REQUEST['group_name'], $edit_public );

                break;

                //action for edit group
                case "edit_group":
                    
                    if(! (current_user_can('edit_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $edit_public = ( isset( $_REQUEST['edit_public'] ) ) ? '1' : '0';
                    $this->create_group( $_REQUEST['edit_group_name'], $edit_public, $_REQUEST['group_id'] );
                break;

                //action for delete group
                case "delete_group":
                    
                    if(! (current_user_can('delete_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->delete_group( $_REQUEST['group_id'] );
                break;

                //action for change group
                case "change_group":
                    
                    if(! (current_user_can('change_newsletter_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $groups_id = ( isset( $_REQUEST['groups_id'] ) ) ? $_REQUEST['groups_id'] : NULL;
                    $this->change_group( $_REQUEST['member_id'], $groups_id );
                break;

                //action add new member
                case "add_member":
                    
                    if(! (current_user_can('add_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->add_member( $_REQUEST['member'] );
                break;

                //action edit member
                case "edit_member":
                    
                    if(! (current_user_can('edit_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->edit_member( $_REQUEST['member_id'], $_REQUEST['edit_member_nicename'], $_REQUEST['edit_member_email'] );
                break;

                //action delete member
                case "delete_member":
                    
                    if(! (current_user_can('delete_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');

                    $member_id = array($_REQUEST['member_id']);
                    
                    $this->delete_members( $member_id );
                break;

                //Bulk action delete members
                case "delete_members":
                    
                    if(! (current_user_can('delete_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->delete_members( $_REQUEST['members_id'] );
                break;

                //Bulk action add members to group
                case "add_members_group":
                    
                    if(! (current_user_can('add_members_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->add_members_group( $_REQUEST['members_id'], $_REQUEST['list_group_id'] );
                break;

                //Bulk action add members to group
                case "delete_members_group":
                    
                    if(! (current_user_can('delete_members_group') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->delete_members_group( $_REQUEST['members_id'], $_REQUEST['list_group_id'] );
                break;

                //Bulk action add members to group
                case "export_members":
                    
                    if(! (current_user_can('edit_newsletter_member') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');

                    if($_REQUEST['separ_sign'] == 1)
                        $separate_by = ';';
                    else
                        $separate_by = ',';
                    
                    $this->export_members($_REQUEST['groups_id'], $_REQUEST['groups_ungrouped'], $separate_by);
                break;

                //action save settings
                case "save_settings":
                    
                    if(! (current_user_can('save_newsletter_settings') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    if( ! isset( $_REQUEST['settings']['double_opt_in'] ) ) {
                        $_REQUEST['settings']['double_opt_in'] = 0;
                    }
                    $this->save_settings( $_REQUEST['settings'] );
                break;

                //action send newsletter
                case "send_newsletter":
                    
                    if(! (current_user_can('send_newsletter') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    //Handles sending ajax sent stopped newsletters
                    if ( isset( $_REQUEST['cron'] ) && 'add_to_cron' == $_REQUEST['cron'] )
                        $this->add_to_cron( $_REQUEST['newsletter_id'], $_REQUEST['send_id'] );
                    //handles main send buttons
                    else if ( isset( $_REQUEST['action'] ) && 'send' == $_REQUEST["action"] )
                        $this->send_newsletter( $_REQUEST['newsletter_id'] );
                break;

                //action import members
                case "import_members":
                    
                    if(! (current_user_can('import_newsletter_members') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->import_members();
                break;

                //action install data in DB
                case "install":
                    
                    if(! (current_user_can('install_newsletter') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->install();
                    if( ! isset( $_REQUEST['settings']['double_opt_in'] ) ) {
                        $_REQUEST['settings']['double_opt_in'] = 0;
                    }
                    $this->save_settings( $_REQUEST['settings'] );
                break;

                //action uninstall data from DB
                case "uninstall":
                    
                    if(! (current_user_can('uninstall_newsletter') || current_user_can($mu_cap)) )
                        wp_die('You do not have permission to do that');
                    
                    $this->uninstall();
                    wp_redirect( add_query_arg( array( 'page' => 'newsletters-settings', 'updated' => 'true', 'message' => urlencode( __( "eNewsletter data are deleted.", 'email-newsletter' ) ) ), 'admin.php' ) );
                    exit;
                break;

            }
        }
    }
    
    /**
     * init for all users
     **/
    function init() {
        load_plugin_textdomain( 'email-newsletter', false, dirname( plugin_basename( __FILE__ ) ) . '/email-newsletter-files/languages/' );

        //public actions of the plugin
        if ( isset( $_REQUEST['newsletter_action'] ) && !defined('DOING_AJAX') ) {
            $redirect_to = $_SERVER['HTTP_REFERER'];
            
            switch( $_REQUEST['newsletter_action'] ) {
                //action for save selected groups of subscribe
                case "save_subscribes":
                    $this->save_subscribes( $_REQUEST['e_newsletter_groups_id'] );
                break;

                //action for subscribe
                case "subscribe":
                    $this->subscribe( "" );
                break;

                //action for Unsubscribe
                case "unsubscribe":
                    $this->unsubscribe( $_REQUEST['unsubscribe_code'] );
                break;
            }
        }
    }

    function manage_subscriptions_ajax() {
        if ( isset( $_REQUEST['newsletter_action'] ) ) {
            switch( $_REQUEST['newsletter_action'] ) {
                //action for save selected groups of subscribe
                case "save_subscribes":
                    $this->save_subscribes( $_REQUEST['e_newsletter_groups_id'], false, 1 );
                break;

                //action for subscribe
                case "subscribe":
                    $this->subscribe( "", false, 1 );
                break;

                //action for Unsubscribe
                case "unsubscribe":
                    $this->unsubscribe( $_REQUEST['unsubscribe_code'], false, 1 );
                break;

                //action for Subscribe of public member (not user of site)
                case "new_subscribe":
                    if(!is_email($_REQUEST['e_newsletter_email'])) {
                        $data['message'] = __( 'Please use correct email!', 'email-newsletter' );
                        echo json_encode($data);
                        die();
                    }

                    $settings = $this->get_settings();
                    $subscribe_groups = explode(',', $settings['subscribe_groups']);
                    if(isset($_REQUEST['e_newsletter_groups_id'])) {
                        $subscribe_groups = array_merge($_REQUEST['e_newsletter_groups_id'], $subscribe_groups);
                        $subscribe_groups = array_unique($subscribe_groups);
                    }

                    if( isset( $this->settings['double_opt_in'] ) && $this->settings['double_opt_in'] ) {
                        $member_data['double_opt_in'] = 1;
                        $member_data['future_groups_id'] = subscribe_groups;
                    }
                    $member_data['email']       =  ( isset( $_REQUEST['e_newsletter_email'] ) ) ? $_REQUEST['e_newsletter_email'] : '';
                    $member_data['fname']       =  ( isset( $_REQUEST['e_newsletter_name'] ) ) ? $_REQUEST['e_newsletter_name'] : '';
                    $member_data['lname']       =  '';
                    $member_data['groups_id']   =  ( isset( $subscribe_groups ) ) ? $subscribe_groups : '';
                    $this->add_member( $member_data, false, 1 );
                break;
            }
        }

        die();
    }

    /**
     * Save Subscribes
     **/
    function save_subscribes( $groups_id, $redirect_to = true, $ajax = 0  ) {
        global $wpdb, $current_user;

        $member_id = $this->get_members_by_wp_user_id( $current_user->data->ID );

        do_action( 'enewsletter_user_save_subscribe', $member_id, $groups_id );
        
        if(!empty($member_id)) {
            //deleting old list of groups for user
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
    
            //creating new list of groups for user
            if ( $groups_id )
                foreach( $groups_id as $group_id )
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );
        }   
    
        if ( $redirect_to )
            if ( true === $redirect_to ) {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( __( 'Subscriptions are saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            } else {
                wp_redirect( $redirect_to );
                exit;
            }

        if ( 1 == $ajax ) {
            $data['message'] = __( 'Subscribes were saved!', 'email-newsletter' );
            $data['view'] = 'manage_subscriptions';
            $data['hide'] = '';
            echo json_encode($data);
        }
    }

    /**
     *  Subscribe on Newsletters
     **/
    function subscribe( $member_id = "", $redirect_to = true, $ajax = 0 ) {
        global $wpdb, $current_user;

        $user_id = $current_user->data->ID;

        do_action( 'enewsletter_user_subscribe', $member_id );

        $result = $this->create_update_member_user($user_id, array(), 1);

        if ( $redirect_to )
            if ( true === $redirect_to ) {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( __( 'You are subscribed successfully!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            } else {
                wp_redirect( $redirect_to );
                exit;
            }

        if ( 1 == $ajax ) {
            if($result) {
                $data['message'] = __( 'You are subscribed successfully!', 'email-newsletter' );
                $data['view'] = 'manage_subscriptions';
                $data['hide'] = 'subscribe';
                $data['unsubscribe_code'] = $result['unsubscribe_code'];
            }
            else {
                $data['message'] = __( 'Error occured while subscribing!', 'email-newsletter' );
                $data['view'] = 'subscribe';
                $data['hide'] = 'subscribe';
            }

            echo json_encode($data);
        }
    }

    /**
     * Unsubscribe on Newsletters
     **/
    function unsubscribe( $unsubscribe_code, $redirect_to = true, $ajax = 0 ) {
        global $wpdb;
        if ($unsubscribe_code) {
            $member =  $this->get_member_id_by_code($unsubscribe_code);
            if ( 0 < $member['member_id'] ) {
                //delete all groups of member
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member['member_id'] ) );

                //delete unsubscribe_code of member
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET unsubscribe_code = '' WHERE unsubscribe_code = '%s'", $unsubscribe_code ) );

                if ( $redirect_to )
                    if ( true === $redirect_to ) {
                        wp_redirect( add_query_arg( array( 'page' => 'newsletters-subscribes', 'updated' => 'true', 'message' => urlencode( __( 'You are unsubscribed!', 'email-newsletter' ) ) ), 'admin.php' ) );
                        exit;
                    } else {
                        wp_redirect( $redirect_to );
                        exit;
                    }

                if ( 1 == $ajax ) {
                    $data['message'] = __( 'You are unsubscribed!', 'email-newsletter' );
                    $data['view'] = 'subscribe';
                    $data['hide'] = 'manage_subscriptions';
                    echo json_encode($data);
                }

                return true;
            }
            elseif( 0 < $member['wp_only_user_id'] ) {
                update_user_meta( $member['wp_only_user_id'], 'email_newsletter_unsubscribe_code', 'unsubscribed' );
                return true;
            }
            return false;
        }
    }

    /**
     * Add new member
     **/
    function add_member( $member_data, $redirect_to = true, $ajax = 0 ) {
        global $wpdb;

        do_action( 'enewsletter_before_user_add', $member_data );

        $message = "";

        if ( email_exists( $member_data['email'] ) !== false ) {
            //if email of new member == email of site user

            $wp_user_id = email_exists( $member_data['email'] );
            $member_id = $this->get_members_by_wp_user_id( $wp_user_id );

            //check that this site's user there is on list of members
            if ( 0 < $member_id )
                $message =  __( 'This email is already used!', 'email-newsletter' );

        } else {
            //check email of new member that isn't on list of members
            $member =  $this->get_member_by_email($member_data['email']);
            if ( $member )
                if ( "" != $member['unsubscribe_code'] )
                    $message =   __( 'This email is already subscribed!', 'email-newsletter' );
        }

        if ( !$message ) {
            if ( isset( $member_data['double_opt_in'] ) && 1 == $member_data['double_opt_in'] )
                $subscribe = "";
            else
                $subscribe = 1;

            $member_info = '';
            if ( isset( $member_data['future_groups_id'] ) && $member_data['future_groups_id'] ) {
                $member_info = array(
                    "future_groups_id" => $member_data['future_groups_id']
                );

                $member_info = serialize( $member_info );
            }

            $member_data_ready = array(
                    'member_fname' => $member_data['fname'],
                    'member_lname' => $member_data['lname'],
                    'member_email' => $member_data['email'],
                    'member_info' => $member_info            
                );
            $result = $this->create_update_member_user('', $member_data_ready, $subscribe);

            $member_id = $result['member_id'];

            if ( isset( $member_data['double_opt_in'] ) && 1 == $member_data['double_opt_in'] ) {
                $this->do_double_opt_in( $member_id );
                $message = __( 'Confirmation email has been sent! Please confirm subscription.', 'email-newsletter' );
            } else {
                //creating new list of groups for user
                if ( isset( $member_data['groups_id'] ) && is_array( $member_data['groups_id'] ) )
                    foreach( $member_data['groups_id'] as $group_id )
                        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );

                $settings = $this->get_settings();
                if($settings['subscribe_newsletter'])
                    $this->add_send_email_info( $settings['subscribe_newsletter'], array($member_id), 0, 'by_cron' );
            }

            if(!$message)
                if ( "" == $redirect_to )
                    $message =  __( 'The new member is added!', 'email-newsletter' );
                else
                    $message =  __( 'You have been successfully subscribed!', 'email-newsletter' );
        }

        if ( $redirect_to )
            if ( true === $redirect_to ) {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $message ) ), 'admin.php' ) );
                exit;
            } else {
                wp_redirect( $redirect_to );
                exit;
            }

        if ( 1 == $ajax ) {
            $data['message'] = $message;
            echo json_encode($data);
        }

        do_action( 'enewsletter_after_user_add', $member_id );
    }

    /**
     * Edit member
     **/
    function edit_member( $member_id, $member_nicename, $member_email ) {
        global $wpdb;

        if ( $member_id && is_email( $member_email ) ) {
            $member_name = explode(' ', $member_nicename);
            if(!isset($member_name[1]))
                $member_name[1] = '';

            $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET
            member_fname = %s,
            member_lname = %s,
            member_email = %s
            WHERE member_id = %d
            ", $member_name[0], $member_name[1], $member_email, $member_id ) );
        }

        if($result)
            $message = 'User updated!';
        else
            $message = 'Updating user failed!';

        do_action( 'enewsletter_user_edit', $member_id, $member_nicename, $member_email );

        wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $message ) ), 'admin.php' ) );
        exit;
    }

    /**
     * Delete members
     **/
    function delete_members( $members_id ) {
        global $wpdb;

        do_action( 'enewsletter_user_delete', $members_id );

        if ( $members_id ) {
            foreach( ( array ) $members_id as $member_id ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
            }

            if(count($members_id) == 1)
                $message = __( 'Member deleted!', 'email-newsletter' );
            elseif(count($members_id) == 0)
                $message = __( 'No members to delete!', 'email-newsletter' );
            else
                $message = __( 'Members deleted!', 'email-newsletter' );

            wp_redirect( add_query_arg( array( 'page' => 'newsletters-members', 'updated' => 'true', 'message' => urlencode( $message ) ), 'admin.php' ) );
            exit;
        }
    }

    /**
     * Adding new member when create new user
     **/
    function user_create( $userID ) {
        $result = $this->create_update_member_user($userID, array(), 1);

        $member_id = $result['member_id'];

        if($member_id) {
            global $wpdb;
            $settings = $this->get_settings();

            //creating new list of groups for user
            $subscribe_groups = explode(',', $settings['subscribe_groups']);
            if ( isset( $subscribe_groups ) && is_array( $subscribe_groups ) )
                foreach( $subscribe_groups as $group_id )
                    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );

            if($settings['subscribe_newsletter'])
                $this->add_send_email_info( $settings['subscribe_newsletter'], array($member_id), 0, 'by_cron' );
        }
    }

    /**
     * Updates newsletters member details when updating any wp profile
     **/
    function edit_user_update_member( $user_id ) {
        if ( current_user_can('edit_user',$user_id) ) {
            if(is_email( $_POST['email'] )) {
                $blogs = get_blogs_of_user( $user_id );
                foreach ($blogs as $blog) {
                    if(is_multisite()) {
                        if(!$current_blog)
                            $current_blog = get_current_blog_id();
                        switch_to_blog( $blog->userblog_id  );
                    }

                    $member_data_ready = array(
                            'wp_user_id' => $user_id,
                            'member_email' => $_POST['email']         
                        );
                    if(!empty($_POST['first_name'])) {
                        $member_data_ready['member_fname'] = $_POST['first_name'];
                        if(!empty($_POST['last_name']))
                            $member_data_ready['member_lname'] = $_POST['last_name'];
                    }
                    elseif(!empty($_POST['nickname'])) {
                        $member_data_ready['member_fname'] = $_POST['nickname'];
                        $member_data_ready['member_lname'] = '';
                    }
                    else {
                        $member_data_ready['member_fname'] = '';
                        $member_data_ready['member_lname'] = '';               
                    }

                    $result = $this->create_update_member_user($user_id, $member_data_ready, '', 1);
                }

                if(is_multisite())
                    switch_to_blog( $current_blog  );
            }

            if(isset($_POST['email_newsletter_unsubscribe_code'])) {
                if($_POST['email_newsletter_unsubscribe_code'] == 'unsubscribed')
                    update_user_meta( $user_id, 'email_newsletter_unsubscribe_code', 'unsubscribed' );
            }
        }          
    }

    /**
     * Allows admins to control if they want to recieve mass newsletters for admins
     **/
    function wp_admins_profile() {
        global $user_ID, $wpdb;

        if ( !empty( $_GET['user_id'] ) ) {
            $user_id = $_GET['user_id'];
        } else {
            $user_id = $user_ID;
        }
        if($this->is_plugin_active_for_network(plugin_basename($this->plugin_main_file))) {
            $results = $wpdb->get_results( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key LIKE %s AND meta_value LIKE %s AND user_id = %d", '%capabilities', '%administrator%', $user_id) );
            if($results) {
                $unsubscribe_code = get_user_meta( $user_id, 'email_newsletter_unsubscribe_code', true );
                ?>
                <h3><?php _e('Multisite users newsletters', 'email-newsletter'); ?></h3>

                <table class="form-table">
                <tr>
                    <th><label for="email_newsletter_unsubscribe_code"><?php _e('Recieve newsletters for site admins', 'email-newsletter'); ?></label></th>
                    <td>
                        <select name="email_newsletter_unsubscribe_code" id="email_newsletter_unsubscribe_code">
                                <option value="yes"<?php if ( $unsubscribe_code != 'unsubscribed' ) { echo ' selected="selected" '; } ?>><?php _e('Yes', 'email-newsletter'); ?></option>
                                <option value="unsubscribed"<?php if ( $unsubscribe_code == 'unsubscribed' ) { echo ' selected="selected" '; } ?>><?php _e('No', 'email-newsletter'); ?></option>
                        </select>
                    </td>

                </tr>
                </table>
            <?php
            }
        }
    }

    /**
     * Deleting member's groups and member when delete site user
     **/
    function user_delete( $userID ) {
        global $wpdb;

        if ( function_exists('is_multisite' ) && is_multisite() ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        } else {
                $blogids[] = 1;
        }

        foreach ( $blogids as $blog_id ) {
            //Checking DB prefix TODO - maybe function?
            if ( 1 < $blog_id )
                $tb_prefix = $wpdb->base_prefix . $blog_id . '_';
            else
                $tb_prefix = $wpdb->base_prefix;

            if($this->get_settings($tb_prefix)) {
                $member_id = $this->get_members_by_wp_user_id( $userID, $blog_id );

                if ( 0 < $member_id ) {
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_member_group WHERE member_id = %d", $member_id ) );
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$tb_prefix}enewsletter_members WHERE member_id = %d", $member_id ) );
                }
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
     * Delete Newsletter
     **/
    function delete_newsletter( $newsletter_id, $page_redirect ) {
        global $wpdb;

        do_action( 'enewsletter_delete_newsletter', $newsletter_id );

        if ( ! $page_redirect )
            $page_redirect = "newsletters-dashboard";

        if ( "newsletters-create" == $page_redirect )
            $page_redirect = "newsletters";

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_newsletters WHERE newsletter_id = %d", $newsletter_id ) );
        //$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tb_prefix}enewsletter_send WHERE newsletter_id = %d", $newsletter_id ) );
        
        $this->delete_newsletter_meta($newsletter_id);

        wp_redirect( add_query_arg( array( 'page' => $page_redirect, 'updated' => 'true', 'message' => urlencode( __( 'The Newsletter is deleted!', 'email-newsletter' ) ) ), 'admin.php' ) );
        exit;
    }

    /**
     * Save Newsletter
     **/
    function save_newsletter( $newsletter_id = NULL, $page_redirect, $data = NULL ) {
        global $wpdb, $email_builder;

        do_action( 'enewsletter_delete_newsletter', $newsletter_id );
        
        if(empty($data))
            $data = $_REQUEST;
        
        if(isset($data['newsletter_id']) && empty($newsletter_id)) {
            $newsletter_id = $data['newsletter_id'];
        }
        
        $current_theme = $email_builder->get_builder_theme($newsletter_id);

        $content        = base64_decode( str_replace( "-", "+", (isset($data['content_encoded']) ? $data['content_encoded'] : '' ) ) );
        $contact_info   = base64_decode( str_replace( "-", "+", (isset($data['contact_info']) ? $data['contact_info'] : '' ) ) );

        $fields = array(
            "template"      => $data['newsletter_template'],
            "subject"       => $data['subject'],
            "from_name"     => $data['from_name'],
            "from_email"    => $data['from_email'],
            "bounce_email"  => ( isset( $data['bounce_email'] ) ) ? $data['bounce_email'] : '',
            "content"       => $content,
            "contact_info"  => $contact_info,
        );
        
        $meta = $data['meta'];
        
        if($data['newsletter_template'] != $current_theme) {
            if($meta['email_title'] != BUILDER_DEFAULT_EMAIL_TITLE)
                $this->delete_newsletter_meta($newsletter_id, 'email_title', 1 );
            else
                $this->delete_newsletter_meta($newsletter_id);
        }
        else        
            foreach($meta as $meta_key => $meta_value) {
                $this->update_newsletter_meta($newsletter_id, $meta_key, $meta_value);
            }

        if( ! $newsletter_id ) {
            $sql    = "INSERT INTO {$this->tb_prefix}enewsletter_newsletters SET create_date = " . time() . " ";
            $where  = '';
        }else{
            $sql    = "UPDATE {$this->tb_prefix}enewsletter_newsletters SET newsletter_id = '".mysql_real_escape_string( $newsletter_id )."' ";
            $where  = " WHERE newsletter_id = '".mysql_real_escape_string( $newsletter_id )."' LIMIT 1";
        }

        foreach( $fields as $key=>$val ) {
            $val = trim( $val );

            $sql .= ", `".$key."` = '".mysql_real_escape_string( $val )."'";
        }
        $sql .= $where;

        $result = $wpdb->query( $sql );

        if( ! $newsletter_id )
            $newsletter_id = $wpdb->insert_id;
            
        if($page_redirect != false) {
            //Save nad redirect on Send page
            if ( "send" == $_REQUEST['send'] ) {
                wp_redirect( add_query_arg( array( 'page' => 'newsletters-dashboard', 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'The Newsletter is saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
                exit;
            }
    
            wp_redirect( add_query_arg( array( 'page' => 'newsletters-create', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'The Newsletter is saved!', 'email-newsletter' ) ) ), 'admin.php' ) );
            exit;
        } else {
            return $newsletter_id;
        }
    }

    /**
     * Check that email was opened
     **/
    function check_email_opened_ajax() {
        global $wpdb;
        //write opened time to table
        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET opened_time = %d WHERE send_id = %d AND member_id = %d AND wp_only_user_id = %d AND opened_time = 0" , time(), $_REQUEST['send_id'], $_REQUEST['member_id'], $_REQUEST['wp_only_user_id'] ) );

        //show blank image 1x1
        header('Content-Type: image/jpeg');
        $filename = $this->plugin_dir . "email-newsletter-files/images/spacer.gif";
        $handle = fopen( $filename, "r" );
        $content = fread( $handle, filesize( $filename ) );
        fclose( $handle );
        echo $content;
        die();
    }

    /**
     * Confirm subscibe from Email
     **/
    function confirm_subscibe_ajax() {
        global $wpdb;

        $member_id = $_REQUEST['member_id'];

        if ( $_REQUEST['hash'] != md5( "sometext123" . $member_id ) )
            die( __( 'Error: Wrong subscription data!', 'email-newsletter' ) );

        $member_data = $this->get_member( $member_id );

        $unsubscribe_code = $this->gen_unsubscribe_code();

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_members SET member_info = '', unsubscribe_code = '%s' WHERE member_id = %d", $unsubscribe_code, $member_id ) );

        //creating new list of groups for user
        if ( is_array( $member_data['future_groups_id'] ) )
            foreach( ( array ) $member_data['future_groups_id'] as $group_id )
                $result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->tb_prefix}enewsletter_member_group SET member_id = %d, group_id =  %d", $member_id, $group_id ) );
        
        $settings = $this->get_settings();
        if($settings['subscribe_newsletter'])
            $this->add_send_email_info( $settings['subscribe_newsletter'], array($member_id), 0, 'by_cron' );


        die( __( 'Successful subscription!', 'email-newsletter' ) );
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
            $content = '<p>'.__( 'Select groups for this user:', 'email-newsletter' ) . "</p>";

            $content .= "<p>";
            foreach( $groups as $group ){
                if ( false === array_search ( $group['group_id'], $users_group ) )
                    $checked = '';
                else
                    $checked = 'checked="checked"';
                $content .= '<label><input type="checkbox" name="groups_id[]" value="' . $group['group_id'] . '" ' . $checked . ' /> ' . $group['group_name'] . '</label><br />';
            }
            $content .= "</p>";

        } else {
            $content = '<p>'.__( 'Please create some groups.', 'email-newsletter' ) . "</p>";
        }

        die($content);
    }

    /**
     * unsubscribe from email
     **/
    function unsubscribe_ajax() {
        $this->unsubscribe( $_REQUEST['unsubscribe_code'] );
        die('');
    }

    /**
     * Write inforamtion of Send newsletter to DB
     **/
    function send_newsletter( $newsletter_id ) {
        global $wpdb;

        do_action( 'enewsletter_before_send', $newsletter_id );

        $members_id = array();
        if ( isset( $_REQUEST["all_members"] ) && "1" == $_REQUEST["all_members"] ) {
            $args = array (
                'where' => "unsubscribe_code != ''"
            );

            $members = $this->get_members( $args );
            foreach ( $members as $member ) {
                $members_id[] = $member['member_id'];
            }
        } else {
            //Get ids for eNewsletter group members
            if ( isset( $_REQUEST["target"]["groups"] ) && is_array($_REQUEST["target"]["groups"]) )
                foreach ( $_REQUEST["target"]["groups"] as $group_id ) {
                    $members_id = array_merge ( $members_id,  $this->get_members_of_group( $group_id ) );
                }

            //Get ids for Membership levels being eNewsletter members
            if ( isset( $_REQUEST["target"]["membership_levels"] ) && is_array($_REQUEST["target"]["membership_levels"]) )
                foreach ( $_REQUEST["target"]["membership_levels"] as $membership_level ) {
                    $members = $this->get_members_of_membership($membership_level);
                    foreach ( $members as $member ) {
                        $members_id[] = $member['member_id'];
                    }
                }

            //Get ids for Roles being eNewsletter members
            if ( isset( $_REQUEST["target"]["roles"] ) && is_array($_REQUEST["target"]["roles"]) )
                foreach ( $_REQUEST["target"]["roles"] as $role_name ) {
                    $users_id = get_users( array( 'role' => $role_name ) );
                    foreach ( $users_id as $user_id ) {
                        $member_id = $this->get_members_by_wp_user_id( $user_id->ID, '', 1 );
                        if ( 0 < $member_id )
                            $members_id[] = $member_id;
                    }
                }

            $members_id = array_unique( $members_id );
        }
        //Get ids for admins of other sites
        if ( isset( $_REQUEST["target"]["site_admins"] ) && $_REQUEST["target"]["site_admins"] == 'yes' )
            $wp_only_users_id = $this->get_global_wp_user_ids();
        else
            $wp_only_users_id = 0;

        if ( 'cron_time' == $_REQUEST['cron_time'] ) {
            $time_str = $_REQUEST['aa'].'-'.$_REQUEST['mm'].'-'.$_REQUEST['jj'].' '.$_REQUEST['hh'].':'.$_REQUEST['mn'].':00 GMT';
            $status = $start_time = strtotime($time_str);
        }
        elseif ( 'cron' == $_REQUEST["cron"] )
            $status = 'by_cron';
        else
            $status = 'waiting_send';

        $dont_send_duplicate = (isset( $_REQUEST['dont_send_duplicate'] )) ? $_REQUEST['dont_send_duplicate'] : 0;
        $send_to_bounced = (isset( $_REQUEST['send_to_bounced'] )) ? $_REQUEST['send_to_bounced'] : 0;

        $result = $this->add_send_email_info( $newsletter_id, $members_id, $wp_only_users_id, $status, $dont_send_duplicate, $send_to_bounced );
        if ( !$result['count'] )
            wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( __( 'All members have already received it or no user is subscribed!', 'email-newsletter' ) ) ), 'admin.php' ) );
        else
            if ( 'cron' == $_REQUEST["cron"] )
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( $count_send_members . ' ' . __( 'Members are added to CRON list', 'email-newsletter' ) ) ), 'admin.php' ) );
            else
                wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'send_id' => $result['send_id'], 'check_key' => $_REQUEST['check_key'] ), 'admin.php' ) );

        exit;
    }

    /**
     * Add email or send to CRON list
     **/
    function add_to_cron( $newsletter_id, $send_id ) {
        global $wpdb;

        $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'by_cron' WHERE send_id = %d AND status = 'waiting_send'", $send_id ) );

        $count_send_members = $this->get_count_send_members( $send_id, 'by_cron' );

        wp_redirect( add_query_arg( array( 'page' => $_REQUEST['page'], 'newsletter_action' => 'send_newsletter', 'newsletter_id' => $newsletter_id, 'updated' => 'true', 'message' => urlencode( $count_send_members . ' ' . __( 'Members are added to CRON list', 'email-newsletter' ) ) ), 'admin.php' ) );

        exit;
    }

    /**
     * Send email to member
     **/
    function send_email_to_member() {
        global $wpdb;

        if ( ! wp_verify_nonce( $_REQUEST['check_key'], 'newsletter_send' ) )
             die( 'Security check' ); 


        $send_id = $_REQUEST['send_id'];

        do_action( 'enewsletter_before_send_newsletter', $send_id );

        //get data of newsletter
        $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_id ), "ARRAY_A");

        $send_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE send_id = %d AND status = 'waiting_send' LIMIT 0, 1",  $send_id ), "ARRAY_A");

        if ( ! $send_member ) {
            if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' ) )
                wp_schedule_single_event( time() + 60*2, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
            else {
                wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
                wp_schedule_single_event( time() + 60*2, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_1' );
            }

            die('end');
        }

        if($send_member['member_id']) {
            $member_data = $this->get_member( $send_member['member_id'] );
            $bounce_id = $send_member['member_id'];
            $bounce_hash = md5( 'Hash of bounce member_id='. $bounce_id . ', send_id='. $send_id );
        }
        elseif($send_member['wp_only_user_id']) {
            $member_data = $this->get_wp_user_only( $send_member['wp_only_user_id'] );
            $bounce_id = $send_member['wp_only_user_id'];
            $bounce_hash = md5( 'Hash of bounce wp_only_user_id='. $bounce_id . ', send_id='. $send_id );
        }

        if( !empty($member_data["member_email"]) && is_email($member_data["member_email"]) ) {

            $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

            $contents = $send_data['email_body'];

            //Replace some content inside the email body
            $user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);
            $contents = $this->personalise_email_body($contents, $send_member['member_id'], $send_member['wp_only_user_id'], $member_data['unsubscribe_code'], $send_id, array('user_name' => $user_name, 'member_email' => $member_data["member_email"]));
            
            if( $this->settings['bounce_email'] ) {
                $options['bounce_email'] = $this->settings['bounce_email'];
            }

            $options['message_id'] = 'Newsletters-' . $bounce_id . '-' . $send_id . '-'. $bounce_hash;

            $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $member_data["member_email"], $newsletter_data["subject"], $contents, $options );
            $this->write_log( 'Send status: '.$sent_status);
            if( $sent_status == true ) {
                //write info of Sent in DB
                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'sent' WHERE send_id = %d AND member_id = %d AND wp_only_user_id = %d", $send_id, $send_member['member_id'], $send_member['wp_only_user_id'] ) );
                if ( $result )
                    die('ok');
                else
                    die( __( 'Error when updating DB.', 'email-newsletter' ) );
            } else {
                if( $sent_status == 'recipients_failed' || $sent_status == 'invalid_address' ) {
                    $result = $this->set_send_email_status('bounced', $send_id, $send_member['member_id'], $send_member['wp_only_user_id']);
                    if ( $result )
                        die('ok');
                    else
                        die( __( 'Error when updating DB.', 'email-newsletter' ) );   
                }
                else {
                    die(__( 'Error sending email. Please check outgoing email settings.', 'email-newsletter' ) );
                }
            }
        }
        else {
            $result = $this->set_send_email_status('bounced', $send_id, $send_member['member_id'], $send_member['wp_only_user_id']);
            if ( $result )
                die('ok');
            else
                die( __( 'Error when updating DB.', 'email-newsletter' ) );         
        }

        do_action( 'enewsletter_after_send_newsletter', $send_id );
    }

    /**
     * Send email to member
     **/
    function send_by_wpcron() {
        global $wpdb;

        @set_time_limit( 0 );

        if ( 1 > $wpdb->get_var( "SELECT Count(send_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE status = 'by_cron' OR status < UNIX_TIMESTAMP()") )
            return false;

        $process_id = time();
        //writing some information in the plugin log file
        $this->write_log( $process_id . " 01 - start" );

        if ( ! get_option( 'enewsletter_cron_send_run' ) ) {

            //writing some information in the plugin log file
            $this->write_log( $process_id . " 02 - before enewsletter_cron_send_run 1" );

            //add new column for check limit
            if ( 1 != $wpdb->query( "DESCRIBE {$this->tb_prefix}enewsletter_send_members sent_time" ) ) {
                $wpdb->query( "ALTER TABLE {$this->tb_prefix}enewsletter_send_members ADD sent_time INT" );
            }

            update_option( 'enewsletter_cron_send_run', time() );

            //writing some information in the plugin log file
            $this->write_log( $process_id . " 03 - set enewsletter_cron_send_run 1" );


            if ( 0 < $this->settings['send_limit'] ) {

                $month  = date( 'n', time() );
                $year   = date( 'Y', time() );
                $day    = date( 'j', time() );
                $hour   = date( 'H', time() );
                $min    = date( 'i', time() );

                switch ( $this->settings['cron_time'] ) {
                case '1':
                    $limit_time_start   = mktime( $hour , 0, 0, $month, $day, $year ) ;
                    $limit_time_end     = mktime( $hour + 1, 0, -1, $month, $day, $year );
                    break;
                case '2':
                    $limit_time_start   = mktime( 0, 0, 0, $month, $day, $year );
                    $limit_time_end     = mktime( 0, 0, -1, $month, $day + 1, $year );
                    break;
                case '3':
                    $limit_time_start   =  mktime( 0, 0, 0, $month, 1, $year);
                    $limit_time_end     =  mktime( 0, 0, -1, $month + 1, 1, $year);
                    break;
                }

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 04 - cron_time: " . $this->settings['cron_time'] . "  limit_time_start:" . $limit_time_start . "  limit_time_end:" . $limit_time_end );

                $current_count_sent = $wpdb->get_var( $wpdb->prepare( "SELECT Count(send_id) FROM {$this->tb_prefix}enewsletter_send_members WHERE sent_time BETWEEN %d AND %d", $limit_time_start, $limit_time_end ) );
                
                //writing some information in the plugin log file
                $this->write_log( $process_id . " 05 - current_count_sent: " . $current_count_sent  . "  send_limit:" . $this->settings['send_limit'] );            
            }


            if ( ! isset( $current_count_sent ) || $current_count_sent < $this->settings['send_limit'] ) {
                $send_limit = 'LIMIT 0, 500';
                if(!isset($current_count_sent))
                    $current_count_sent = 0;

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 06 - NOT LIMIT YET" );

                //Remember not to use numbers as status other then unixtimestamp (status > 0)
                $send_members = $wpdb->get_results( "SELECT * FROM {$this->tb_prefix}enewsletter_send_members WHERE status = 'by_cron' OR (status > 0 and status < UNIX_TIMESTAMP()) " . $send_limit , "ARRAY_A");

                //writing some information in the plugin log file
                $this->write_log( $process_id . " 07 - send_members count:" . count($send_members) );

                if ( ! $send_members ) {
                    delete_option( 'enewsletter_cron_send_run' );
                    die(1);
                }

                foreach ( $send_members as $send_member ) {
                    do_action( 'enewsletter_before_cron_send_newsletter', $send_member );

                    update_option( 'enewsletter_cron_send_run', time() );

                    if($send_member['member_id']) {
                        $member_data = $this->get_member( $send_member['member_id'] );
                        $bounce_id = $send_member['member_id'];
                    }
                    elseif($send_member['wp_only_user_id']) {
                        $member_data = $this->get_wp_user_only( $send_member['wp_only_user_id'] );
                        $bounce_id = 'wp'.$send_member['wp_only_user_id'];
                    }

                    if( !empty($member_data["member_email"]) && is_email($member_data["member_email"]) ) {
                        $send_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tb_prefix}enewsletter_send WHERE send_id = %d",  $send_member['send_id'] ), "ARRAY_A");
                        $newsletter_data = $this->get_newsletter_data( $send_data['newsletter_id'] );

                        if( !empty($newsletter_data) ) {

                            $this->write_log( $process_id . " 07-2 - send_member_id:" . $send_member['member_id'] . "/" . $send_member['wp_only_user_id'] . "/" . $send_data['newsletter_id'] . "/" . $newsletter_data['from_name'] . "/" . $send_member['send_id'] );

                            $contents = $send_data['email_body'];
        
                            //Replace some content inside the email body
                            $user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);
                            $contents = $this->personalise_email_body($contents, $send_member['member_id'], $send_member['wp_only_user_id'], $member_data['unsubscribe_code'], $send_member['send_id'], array('user_name' => $user_name, 'member_email' => $member_data["member_email"]));

                            $options['message_id'] = 'Newsletters-' . $bounce_id . '-' . $send_member['send_id'] . '-'. md5( 'Hash of bounce member_id='. $bounce_id . ', send_id='. $send_member['send_id'] );
                            
                            $options['bounce_email'] = $newsletter_data['bounce_email'];

                            $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $member_data["member_email"], $newsletter_data["subject"], $contents, $options );

                            if( $sent_status == true ) {
                                //write info of Sent in DB
                                $result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->tb_prefix}enewsletter_send_members SET status = 'sent', sent_time = %d WHERE send_id = %d AND member_id = %d AND wp_only_user_id = %d", time(), $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id'] ) );

                                //writing some information in the plugin log file
                                $this->write_log( $process_id . " 09 - send OK" );

                                if ( ++$current_count_sent == $this->settings['send_limit'] ) {
                                    //writing some information in the plugin log file
                                    $this->write_log( $process_id . " 10 - STOP - LIMIT" );

                                    delete_option( 'enewsletter_cron_send_run' );
                                    die(2);
                                }
                            } else {
                                //writing some information in the plugin log file
                                $this->write_log( $process_id . " 08 - send_errors" );
                            }
                        }
                        else {
                            $result = $this->set_send_email_status('bounced', $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id']);
                            
                            $this->write_log( $process_id . " 08 - send_errors:" . " newsletter data empty" );
                        }
                    }
                    else {
                        $result = $this->set_send_email_status('bounced', $send_member['send_id'], $send_member['member_id'], $send_member['wp_only_user_id']);
                        
                        $this->write_log( $process_id . " 08 - send_errors:" . " no_email" );
                    }

                    do_action( 'enewsletter_after_cron_send_newsletter', $send_member );
                }

                if ( ! wp_next_scheduled( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' ) )
                    wp_schedule_single_event( time() + 60*5, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                else {
                    wp_clear_scheduled_hook( 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                    wp_schedule_single_event( time() + 60*5, 'e_newsletter_cron_check_bounces_' . $wpdb->blogid .'_2' );
                }

            } else {
                delete_option( 'enewsletter_cron_send_run' );
            }
        } elseif ( get_option( 'enewsletter_cron_send_run' ) < time() - 3*60 ) {
            //writing some information in the plugin log file
            $this->write_log( $process_id . " 11 - CRON works more 3 min - restart CRON" );

            delete_option( 'enewsletter_cron_send_run' );
            die(3);
        }
        //writing some information in the plugin log file
        $this->write_log( $process_id . " 12 - END" );

        die(4);
    }

    /**
     * Check bounces email
     **/
    function check_bounces() {
        if(!function_exists('imap_open'))
            return false;
            
        global $wpdb;

        @set_time_limit( 0 );
        $email_address  = $this->settings['bounce_email'];
        $email_username = $this->settings['bounce_username'];
        $email_password = $this->settings['bounce_password'];
        $email_host     = trim( $this->settings['bounce_host'] );
        $email_port     = ( $this->settings['bounce_port'] ) ? $this->settings['bounce_port'] : 110;
        
        $email_password = $this->_decrypt($email_password);

        if( ! $email_host )
            return true;
            
        $email_security = ( $this->settings['bounce_security'] ) ? $this->settings['bounce_security'] : '';

        $mbox = $this->pop3_connet($email_host, $email_port, $email_security, $email_username, $email_password );
        
        if( ! $mbox ) {
            $this->write_log( 'bounce: error cant connect. Error details: '.strip_tags(imap_last_error()) );
            return 'Error: Failed to connect when checking bounces!';
        } else {
            $this->write_log('bounce: connected to check bounce');

            $MC     = imap_check( $mbox );
            $mails  = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

            foreach ( $mails as $mail ) {
                $body = imap_body ( $mbox, $mail->msgno );

                $this->write_log('bounce: checked email');

                if( preg_match( '/X-Mailer:\s*<?Newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches) || preg_match( '/Message-ID:\s*<?Newsletters-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $body, $matches) ) {

                    $member_id      = ( int ) $matches[1];
                    $send_id        = ( int ) $matches[2];
                    $email_hash     = trim( $matches[3] );
                    $hash           = md5( 'Hash of bounce member_id='. $member_id . ', send_id='. $send_id );
                    $hash_wp        = md5( 'Hash of bounce wp_only_user_id='. $member_id . ', send_id='. $send_id );

                    $this->write_log('bounce: data: '.$member_id.'/'.$send_id.'/'.$email_hash.'/'.$hash.'/'.$hash_wp);

                    if( $email_hash == $hash || $email_hash == $hash_wp ){
                        if($email_hash == $hash_wp) {
                            $wp_only_user_id = $member_id;
                            $member_id = 0;
                        }
                        else {
                            $wp_only_user_id = 0;
                        }

                        $result = $this->set_send_email_status('bounced', $send_id, $member_id, $wp_only_user_id);
                        imap_delete( $mbox, $mail->msgno );
                        echo 'ok';
                    } else {
                        echo 'Error: hash';
                    }

                    $this->write_log('bounce: found bounce:'.$member_id.'/'.$wp_only_user_id);
                }
            }
            imap_expunge( $mbox );
            imap_close( $mbox );
        }
        die();
    }

    /**
     * Send Preview (Test) newsletter email
     **/
    function send_preview_ajax() {
        $newsletter_id = (isset($_REQUEST['newsletter_id']) ? $_REQUEST['newsletter_id'] : false);
        
        if(!$newsletter_id)
            die( __( 'No valid newsletter ID supplied.', 'email-newsletter' ) );
                        
        $newsletter_data = $this->get_newsletter_data( $newsletter_id );
        $content = $this->make_email_body($newsletter_id);
        $content = str_replace( "{UNSUBSCRIBE_URL}", '#', $content );
        $content = str_replace( "{OPENED_TRACKER}", '<div style="display:none; font-size: 0px; line-height:0px;"><img src="' . admin_url('admin-ajax.php') . '" width="1" height="1"/></div>', $content );
        if($newsletter_data && $content) {
            $subject = '(PREVIEW) '.$newsletter_data['subject'];
            if( $this->settings['bounce_email'] ) {
                $options = array('bounce_email' => $this->settings['bounce_email']);
            }
    
            $sent_status = $this->send_email( $newsletter_data['from_name'], $newsletter_data['from_email'], $_REQUEST['preview_email'], $subject, $content, $options );
            if( $sent_status == true )
                die( __( 'Your test email has been sent.', 'email-newsletter' ) );
            else
                die( __( 'Failed to send test email! Make sure that entered email is correct and also check outgoing email settings.', 'email-newsletter' ) );
        } else {
            die( __( 'Failed to generate email body.', 'email-newsletter' ) );
        }
    }

    /**
     * Test smtp settings
     **/
    function test_smtp_ajax(){ 
        global $current_user;

        @set_time_limit( 0 );

        //Send test email on bounces address
        $email_id           = time();
        $email_to           = $_REQUEST['smtp_from'];
        $email_from         = $_REQUEST['smtp_from'];
        $email_subject      = "Test-Connection-Send-". $email_id;
        $email_contents     = 'Test';

        $server_host = $_REQUEST['smtp_host'];
        $server_username = $_REQUEST['smtp_username'];
        $server_password = $_REQUEST['smtp_password'];
        if($server_password == '********') {
            $settings = $this->get_settings();
            $server_password = $this->_decrypt($settings['smtp_pass']);
        }

        $server_port = $_REQUEST['smtp_port'];
        $server_security = $_REQUEST['smtp_security'];

        if ( !class_exists( 'PHPMailer' ) )
            require_once( $this->plugin_dir . "email-newsletter-files/phpmailer/class.phpmailer.php" );

        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';

        $mail->IsSMTP();
        $mail->Host = $server_host;
        
        if($server_security == 'tls' || $server_security == 'ssl')
            $mail->SMTPSecure = $server_security;
        if(!empty($server_port))
            $mail->Port = $server_port;
        
        $mail->SMTPAuth = ( strlen( $server_username ) > 0 );
        
        if( $mail->SMTPAuth ){
            $mail->Username = esc_attr($server_username);
            $mail->Password = $server_password;
        }

        $mail->From = $email_from;
        $mail->Subject = $email_subject;
        $mail->isHTML( true );
        $mail->MsgHTML( $email_contents );
        $mail->AddAddress( $email_to );

        $send_status = $mail->Send();
        if( $send_status != true ) {
            die( __( 'Failed to send test email! - Please check your outgoing email settings and server config to see if selected ports are open. Error details: ', 'email-newsletter' ).strip_tags($mail->ErrorInfo) );
        }
        else
            die( __( 'Test message successfully sent! Feel free to save your settings. Please keep in mind that your server may limit the number of allowed messages to be sent per hour/day/week.', 'email-newsletter' ) );
    }

    /**
     * Test bounces settings
     **/
    function test_bounces_ajax(){
        if(!function_exists('imap_open'))
            die( __( 'PHP Imap not supported', 'email-newsletter' ) );
            
        @set_time_limit( 0 );

        //Send test email on bounces address
        $email_id           = time();
        $email_to           = $_REQUEST['bounce_email'];
        $email_from         = ( $this->settings['from_email'] ) ? $this->settings['from_email'] : $_REQUEST['bounce_email'];
        $email_from_name    = ( $this->settings['from_name'] ) ? $this->settings['from_name'] : $_REQUEST['bounce_email'];
        $email_subject      = "Test-Connection-Bounce-". $email_id;
        $email_contents     = 'Test';
        $options            = array ();

        $send_status = $this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents, $options );
        if( $send_status != true )
            die( __( 'Failed to send test email! Please check your outgoing email settings.', 'email-newsletter' )  );

        sleep(10);

        //Set value for connect to email server
        $email_address  = $_REQUEST['bounce_email'];
        $email_username = $_REQUEST['bounce_username'];
        $email_password = $_REQUEST['bounce_password'];
        $email_host     = trim( $_REQUEST['bounce_host'] );
        $email_port     = ( $_REQUEST['bounce_port'] ) ? $_REQUEST['bounce_port'] : 110;
        
        if($email_password == '********') {
            $settings = $this->get_settings();
            $email_password = $this->_decrypt($settings['bounce_password']);
        }
        
        if( ! $email_host )
            return true;

        sleep( 5 );
        
        $email_security = isset($_REQUEST['bounce_security']) ? $_REQUEST['bounce_security'] : '';

        $mbox = $this->pop3_connet($email_host, $email_port, $email_security, $email_username, $email_password );
        
        if( ! $mbox ) {
            die( __( 'Failed to connect while checking bounces! Please check your bounce settings and server config to see if selected ports are open. Error details: ', 'email-newsletter' ).strip_tags(imap_last_error()) );
        } else {
            $i = 1;
            while ($i <= 5) {
                $i++;
                $MC = imap_check( $mbox );

                $this->write_log('bounce_test: find bounce attempt: '.$i);
                
                //get all emails
                $mails = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );

                foreach ( $mails as $mail ) {
                    $this->write_log('bounce_test: subject: '.$mail->subject);
                    //Search test email on server
                    if( $mail->subject == 'Test-Connection-Bounce-'. $email_id ) {
                        imap_delete( $mbox, $mail->uid, FT_UID );
                        imap_expunge( $mbox );
                        imap_close( $mbox );

                        $this->write_log('bounce_test: subject found!');
                        die( __( 'Successfully connected! Feel free to save your settings.', 'email-newsletter' ) );
                    }
                }
                imap_expunge( $mbox );
                imap_close( $mbox );

                sleep( 5 );
            }
            die(  __( 'Bounce test message not found!', 'email-newsletter' ) );
        }
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
            $email_subject      = ( isset($this->settings['double_opt_in_subject']) && !empty($this->settings['double_opt_in_subject']) ) ? $this->settings['double_opt_in_subject'] : __('Confirm newsletter subscription','email-newsletter');
            
            // Determine our locale to check for a specific template
            $locale = get_locale();
            if( file_exists($this->plugin_dir . 'email-newsletter-files/emails/double_optin-'.$locale.'.html') ) {
                $email_contents     = file_get_contents( $this->plugin_dir . 'email-newsletter-files/emails/double_optin-'.$locale.'.html' );
            } else {
                $email_contents     = file_get_contents( $this->plugin_dir . "email-newsletter-files/emails/double_optin.html" );
            }

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

            $sent_status = $this->send_email( $email_from_name, $email_from, $email_to, $email_subject, $email_contents );
            $this->write_log('double opt in send status:'.$sent_status);
            if( $sent_status != true )
                $message .= "Failed to send opt-in email, please contact us to inform us of this error.";
        }

    }
    
    /**
     * Creating admin menu
     **/
    function admin_page() {
            
        $mu_cap = ( $this->is_plugin_active_for_network(plugin_basename(__FILE__)) ) ? 'manage_network_options' : 'view_newsletter_dashboard';
        
        if ( $this->settings ) {
            global $email_builder, $submenu;
                add_menu_page( __( 'eNewsletter', 'email-newsletter' ), __( 'eNewsletter', 'email-newsletter' ), 'view_newsletter_dashboard', 'newsletters-dashboard', null, $this->plugin_url . 'email-newsletter-files/images/icon.png');
                add_submenu_page( 'newsletters-dashboard', __( 'Reports', 'email-newsletter' ), __( 'Reports', 'email-newsletter' ), 'view_newsletter_dashboard', 'newsletters-dashboard', array( &$this, 'newsletters_dashboard_page' ) );
                add_submenu_page( 'newsletters-dashboard', __( 'Newsletters', 'email-newsletter' ), __( 'Newsletters', 'email-newsletter' ), 'save_newsletter', 'newsletters', array( &$this, 'newsletters_page' ) );
                add_submenu_page( 'newsletters-dashboard', __( 'Create Newsletter', 'email-newsletter' ), __( 'Create Newsletter', 'email-newsletter' ), 'create_newsletter', 'newsletters-create', array( &$this, 'create_newsletter_page' ) );
                add_submenu_page( 'newsletters-dashboard', __( 'Member Groups', 'email-newsletter' ), __( 'Member Groups', 'email-newsletter' ), 'edit_newsletter_group', 'newsletters-groups', array( &$this, 'member_groups_page' ) );
                add_submenu_page( 'newsletters-dashboard', __( 'Members', 'email-newsletter' ), __( 'Members', 'email-newsletter' ), 'view_newsletter_members', 'newsletters-members',  array( &$this, 'members_page' ) );
                add_submenu_page( 'newsletters-dashboard', __( 'Settings', 'email-newsletter' ), __( 'Settings', 'email-newsletter' ), 'save_newsletter_settings', 'newsletters-settings', array( &$this, 'settings_page' ) );

                //menu for lowest level users
                add_submenu_page( 'newsletters-dashboard', __( 'My Subscriptions', 'email-newsletter' ), __( 'My Subscriptions', 'email-newsletter' ), 'read', 'newsletters-subscribes', array( &$this, 'newsletters_subscribe_page' ) );

                if(isset($submenu['newsletters-dashboard'])) {
                    foreach($submenu['newsletters-dashboard'] as $k => $v) {
                        if(isset($v[2]) && $v[2] == 'newsletters-create') {
                            $submenu['newsletters-dashboard'][$k][2] = $email_builder->generate_builder_link('new');
                        }
                    }
                }
                
                
        } else {
            //first start of plugin
            add_menu_page( __( 'eNewsletter', 'email-newsletter' ), __( 'eNewsletter', 'email-newsletter' ), $mu_cap, 'newsletters-settings' );
            add_submenu_page( 'newsletters-settings', __( 'Install Settings', 'email-newsletter' ), __( 'Install Settings', 'email-newsletter' ), $mu_cap, 'newsletters-settings', array( &$this, 'settings_page' ) );
        }
    }

    /**
     *  Tempalate of the Newsletters Dashboard page
     **/
    function newsletters_dashboard_page() {
        //including file for send newsletter
        if ( isset( $_REQUEST['newsletter_action'] ) && "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }
        require_once( $this->plugin_dir . "email-newsletter-files/page-newsletters-dashboard.php" );
    }

    /**
     *  Tempalate of the Newsletters page
     **/
    function newsletters_page() {
        //including file for send newsletter
        if ( isset( $_REQUEST['newsletter_action'] ) && "send_newsletter" == $_REQUEST['newsletter_action'] && ( $_REQUEST['newsletter_id'] ||  $_REQUEST['send_id'] ) ) {
            require_once( $this->plugin_dir . "email-newsletter-files/page-send-newsletter.php" );
            return;
        }

        require_once( $this->plugin_dir . "email-newsletter-files/page-newsletters.php" );
    }

    /**
     *  Tempalate of the Create/Edit Newsletter page
     **/
    function create_newsletter_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-create-newsletter.php" );
    }

    /**
     *  Tempalate of the Groups list
     **/
    function member_groups_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-groups.php" );
    }

    /**
     *  Tempalate of the Memebers page
     **/
    function members_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-members.php" );
    }

    /**
     *  Tempalate of the Settings page
     **/
    function settings_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-settings.php" );
    }

    /**
     *  Tempalate of the Settings page
     **/
    function newsletters_subscribe_page() {
        require_once( $this->plugin_dir . "email-newsletter-files/page-subscribe.php" );
    }

    function subscribe_widget($show_name = false, $show_groups = true) {
        global $email_newsletter, $current_user;

        $groups = $this->get_groups(1);

        if ( $current_user->data && 0 < $current_user->data->ID ) {
            $member_id      = $this->get_members_by_wp_user_id( $current_user->data->ID );
            $member_data    = $this->get_member( $member_id );

            if ( "" != $member_data['unsubscribe_code'] ) {
                $member_groups = $this->get_memeber_groups( $member_id );
                if ( ! is_array( $member_groups ) )
                    $member_groups = array();
            }

            $show_groups = true;
        }

        if ( ! $current_user->data || 0 == $current_user->data->ID ) {
            $view = "add_member";
        } else if ( isset( $member_data['unsubscribe_code'] ) && "" != $member_data['unsubscribe_code'] && 0 < $current_user->data->ID ) {
            $view = "manage_subscriptions";
        } else if ( $current_user->data && 0 < $current_user->data->ID ) {
            $view = "subscribe";
        } else {
            $view = "";
        }

        $return = '
        <div class="e-newsletter-widget">
            <div id="message" style="display:none; background-color: #FFFFE0;border-color: #E6DB55;margin: 5px 0 15px;-moz-border-radius: 3px 3px 3px 3px;border-style: solid;border-width: 1px;padding: 5px;"></div>

            <form action="" method="post" name="subscribes_form" id="subscribes_form">
                <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />';
        if($view != 'add_member')
            $return .= '
                <div id="add_member" style="display:none;">';
        else
            $return .= 
                '<div id="add_member">';
        $return .= '
                    <p>
                        <label for="e_newsletter_email">'.__( 'Your Email:', 'email-newsletter' ).'</label>
                        <input type="text" name="e_newsletter_email" id="e_newsletter_email" value="" />';
        if( isset($show_name) && $show_name )
            $return .= '
                        <br/>

                        <label for="e_newsletter_name">'.__( 'Your Name:', 'email-newsletter' ).'</label>
                        <input type="text" name="e_newsletter_name" id="e_newsletter_name" />';
        $return .= '
                    </p>';
        if( $show_groups && count($groups) > 0 ) {
            $return .='
                        <h3>'.__( 'Subscribe to:', 'email-newsletter' ).'</h3>
                        <p>
                            <ul class="subscribe_groups" style="list-style: none outside none;">';
            foreach( ( array ) $groups as $group ) {
                if( ! $group['public'] ) continue;
                    $return .= '
                                    <li>

                                        <input type="checkbox" name="e_newsletter_groups_id[]" value="'.$group['group_id'].'" id="e_newsletter_groups_id_'.$group['group_id'].'" />
                                        <label for="e_newsletter_groups_id_'.$group['group_id'].'">'.$group['group_name'].'</label>

                                    </li>';
            }
            $return .= '
                            </ul>
                        </p>';

        }
        $return .='
                    <p>
                        <input type="button" id="new_subscribe" class="enewletter_widget_submit" value="'.__( 'Subscribe', 'email-newsletter' ).'" />
                    </p>

                </div>';
        if($view != 'manage_subscriptions')
            $return .= '
                <div id="manage_subscriptions" style="display:none;">';
        else
            $return .='
                <div id="manage_subscriptions">';
        $unsubscribe_code = isset( $member_data['unsubscribe_code'] ) ? $member_data['unsubscribe_code'] : '';
        $return .='
                    <input type="hidden" name="unsubscribe_code" id="unsubscribe_code" value="'.$unsubscribe_code.'" />';
        if( $groups ) {
            $return .= '
                        <h3>'.__( 'Subscribe to:', 'email-newsletter' ).'</h3>
                        <p>
                            <ul class="subscribe_groups" style="list-style: none outside none;">';
            foreach( (array) $groups as $group ){
                if ( isset($member_groups) && in_array($group['group_id'], $member_groups) )
                    $checked = 'checked="checked"';
                else
                    $checked = '';
            $return .= '
                                    <li>
                                        <input type="checkbox" name="e_newsletter_groups_id[]" value="'.$group['group_id'].'" '.$checked.' id="e_newsletter_groups_id_'.$group['group_id'].'" />
                                        <label for="e_newsletter_groups_id_'.$group['group_id'].'">'.$group['group_name'].'</label>
                                    </li>';
            }
            $return .= '
                            </ul>
                        </p>';
        }
        $return .= '
                    <p>
                        <input type="button" id="save_subscribes" class="enewletter_widget_submit" value="'.__( 'Save Subscriptions', 'email-newsletter' ).'" />
                    </p>

                    <p>
                        <a href="#" id="unsubscribe" class="enewletter_widget_submit" >'.__( 'Unsubscribe', 'email-newsletter' ).'</a>
                    </p>
                </div>';
        if($view != 'subscribe')
            $return .= '
                <div id="subscribe" style="display:none;">';
        else
            $return .= '
                <div id="subscribe">';
        $return .= '
                    <input type="submit" id="subscribe" class="enewletter_widget_submit" value="'.__( 'Subscribe to Newsletters', 'email-newsletter' ).'" />
                </div>
            </form>
        </div><!--//e-newsletter-widget  -->';

        return $return;
    }

    function subscribe_shortcode( $atts ) {
        extract( shortcode_atts( array(
            'show_name' => false,
            'show_groups' => true,
        ), $atts ) );

        $subscribe = $this->subscribe_widget($show_name, $show_groups);

        return $subscribe;
    }

    function email_newsletter_widgets_scripts() {
        wp_register_script( 'email-newsletter-widget-scripts', plugins_url( '/email-newsletter-files/js/widget_script.js', __FILE__ ), array( 'jquery' ) );
        wp_enqueue_script( 'email-newsletter-widget-scripts' );

        $protocol = isset( $_SERVER["HTTPS"] ) ? 'https://' : 'http://'; //This is used to set correct adress if secure protocol is used so ajax calls are working
        $params = array(
            'ajax_url' => admin_url( 'admin-ajax.php', $protocol ),
            'empty_email' => __( 'Please write your Email!', 'email-newsletter' ),
            'saving' => __( 'Saving...', 'email-newsletter' )
        );
        wp_localize_script( 'email-newsletter-widget-scripts', 'email_newsletter_widget_scripts', $params );
    }
}
global $email_newsletter, $email_builder;
$email_newsletter =& new Email_Newsletter();
$email_builder =& new Email_Newsletter_Builder();

// Widget for Subscribe
class e_newsletter_subscribe extends WP_Widget {
    //constructor
    function e_newsletter_subscribe() {
        $widget_ops = array( 'description' => __( 'Allow people to subscribe to your newsletter database.') );
        parent::WP_Widget( false, __( 'eNewsletter: Subscribe' ), $widget_ops );
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
        global $email_newsletter, $current_user;

        extract( $args );

        $show_name      = apply_filters( 'widget_title', $instance['name'] );
        $show_groups    = apply_filters( 'widget_title', $instance['groups'] );
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $before_widget;

        if ( $title )
            echo $before_title . $title . $after_title;
        
        echo $email_newsletter->subscribe_widget($show_name, $show_groups);

        echo $after_widget;
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
        if ( isset( $instance['title'] ) )
            $title = esc_attr( $instance['title'] );
        else
            $title = __( 'Subscribe to our Newsletters', 'email-newsletter' );

        if ( isset( $instance['name'] ) )
            $name = esc_attr( $instance['name'] );
        else
            $name = 0;

        if ( isset( $instance['groups'] ) )
            $groups = esc_attr( $instance['groups'] );
        else
            $groups = 0;

        ?>
            <p>
                <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title', 'email-newsletter' ) ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />

            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'name' ); ?>"><?php _e( 'Ask the name?', 'email-newsletter' ) ?></label>
                <input id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" type="checkbox" value="1" <?php echo $name ? ' checked' : '';?> />

            </p>
            <p>
                <label for="<?php echo $this->get_field_name( 'groups' ); ?>"><?php _e( 'Show Groups?', 'email-newsletter' ) ?></label>
                <input id="<?php echo $this->get_field_id( 'groups' ); ?>" name="<?php echo $this->get_field_name( 'groups' ); ?>" type="checkbox" value="1" <?php echo $groups ? ' checked' : '';?> />

            </p>
        <?php
    }
} // class e_newsletter_subscribe


add_action( 'widgets_init', create_function( '', 'return register_widget("e_newsletter_subscribe");' ) );

function register_enewsletter_plugin_template( $template_id, $plugin_name, $email_type) {
    global $email_newsletter;
    $return = array(
        'name' => $plugin_name,
        'type' => $email_type,
    );
    $email_newsletter->plugin_templates[$template_id] = $return;
}
?>