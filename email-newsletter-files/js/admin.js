jQuery( document ).ready( function() {


    //Groups page


    jQuery( "#add_group" ).click( function() {
        if ( "" == jQuery( "#group_name" ).val() ) {
            alert( 'Please write Group name' );
            return false;
        }

        jQuery( "#newsletter_action" ).val( "create_group" );
        jQuery( "#create_group" ).submit();
    });

    var group_name      = "";
    var group_public    = "";

    jQuery.fn.editGroup = function ( id ) {
        if ( enewsletter.edit == jQuery( this ).val() ) {
            group_name = jQuery( "#group_name_block_" + id ).html();
            group_name = group_name.replace(/(^\s+)|(\s+$)/g, "");

            jQuery( "#group_name_block_" + id ).html( '<input type="text" name="edit_group_name" size="30" id="edit_group_name"  value="' + group_name + '" /><input type="hidden" name="group_id" value="' + id + '" />' );

            group_public = jQuery( "#public_block_" + id ).html();
            group_public = group_public.replace(/(^\s+)|(\s+$)/g, "");

            if ( "Yes" == group_public )
                jQuery( "#public_block_" + id ).html( '<input type="checkbox" name="edit_public" id="public" value="1" checked="checked" />' );
            else
                jQuery( "#public_block_" + id ).html( '<input type="checkbox" name="edit_public" id="public" value="1" />' );


            jQuery( '#edit_group input[type="button"]' ).attr( 'disabled', true );

            jQuery( this ).val(enewsletter.close);
            jQuery( this ).attr( 'disabled', false );

            jQuery( "#save_block_" + id ).html( '<input class="button button-secondary" type="button" name="save_button" onClick="jQuery(this).saveGroup();" value="'+enewsletter.save+'" />' );

            return;
        }

        if ( enewsletter.close == jQuery( this ).val() ) {
            jQuery( "#group_name_block_" + id ).html( group_name );
            jQuery( "#public_block_" + id ).html( group_public );

            jQuery( this ).val(enewsletter.edit);
            jQuery( '#edit_group input[type="button"]' ).attr( 'disabled', false );

             jQuery( "#save_block_" + id ).html( '' );

            return;
        }
    };


    jQuery.fn.saveGroup = function ( ) {
        if ( "" == jQuery( "#edit_group_name" ).val() ) {
            alert( 'Please write Group name' );
            return false;
        }

        jQuery( "#newsletter_action2" ).val( "edit_group" );
        jQuery( "#edit_group" ).submit();
    };


    jQuery.fn.deleteGroup = function ( id ) {
        jQuery( "#newsletter_action2" ).val( "delete_group" );
        jQuery( "#group_id" ).val( id );
        jQuery( "#edit_group" ).submit();
    };


    //members page


    //Add new member
    jQuery( "#add_member" ).click( function() {
        if ( "" == jQuery( "#member_email" ).val() ) {
            alert(enewsletter.write_email);
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
        jQuery( "#members_per_page" ).val(jQuery(this).val());
        jQuery( "#form_members" ).submit();
        return false;
    });


    jQuery( "#show_add_form" ).click( function() {
        jQuery( "#panel" ).slideToggle( "slow" );

        if ( enewsletter.show_add_member == jQuery(this).val() )
            jQuery(this).val( enewsletter.hide_add_member );
        else
            jQuery(this).val( enewsletter.show_add_member );

        return false;
    });

    jQuery( "#show_add_form2" ).click( function() {
        jQuery( "#panel2" ).slideToggle( "slow" );

        if ( enewsletter.show_export_member == jQuery(this).val() )
            jQuery(this).val( enewsletter.show_export_member );
        else
            jQuery(this).val( enewsletter.hide_export_member );

        return false;
    });

   jQuery.fn.editMember = function ( id ) {
        if ( enewsletter.edit == jQuery( this ).val() ) {
            jQuery( "#member_id" ).val( id );

            member_nicename = jQuery( "#member_nicename_block_" + id ).html();
            member_nicename = member_nicename.replace(/(^\s+)|(\s+$)/g, "");

            jQuery( "#member_nicename_block_" + id ).html( '<input type="text" name="edit_member_nicename" id="edit_member_nicename"  value="' + member_nicename + '" />' );

            member_email = jQuery( "#member_email_block_" + id ).html();
            member_email = member_email.replace(/(^\s+)|(\s+$)/g, "");

            jQuery( "#member_email_block_" + id ).html( '<input type="text" size="30" name="edit_member_email" id="edit_member_email"  value="' + member_email + '" />' );

            jQuery( '#form_members input[type="button"]' ).attr( 'disabled', true );

            jQuery( this ).val(enewsletter.close);
            jQuery( this ).attr( 'disabled', false );

            jQuery( "#save_block_" + id ).html( '<input class="button button-secondary" type="button" id="save_member_button" name="save_button" onClick="jQuery(this).saveMember();" value="'+enewsletter.save+'" />' );

            return;
        }

        if ( enewsletter.close == jQuery( this ).val() ) {
            jQuery( "#member_id" ).val( '' );

            jQuery( "#member_nicename_block_" + id ).html( member_nicename );
            jQuery( "#member_email_block_" + id ).html( member_email );

            jQuery( this ).val(enewsletter.edit);
            jQuery( '#form_members input[type="button"]' ).attr( 'disabled', false );

             jQuery( "#save_block_" + id ).html( '' );

            return;
        }
    };

    jQuery.fn.saveMember = function ( ) {
        filter = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (filter.test(jQuery( "#edit_member_email" ).val())) {} else {
            alert( enewsletter.proper_email );
            return false;
        }

        jQuery( "#newsletter_action" ).val( "edit_member" );
        jQuery( "#form_members" ).submit();
    };

    jQuery.fn.deleteMember = function ( id ) {
        if (confirm(enewsletter.confirm)) {
            jQuery( "#newsletter_action" ).val( "delete_member" );
            jQuery( "#member_id" ).val( id );
            jQuery( "#form_members" ).submit();
        }
    };

    jQuery.fn.changeGroups = function ( id ) {
        if ( enewsletter.save_groups == jQuery( "#change_button_" + id ).val() ) {
            jQuery( "#newsletter_action" ).val( "change_group" );
            jQuery( "#member_id" ).val( id );
            jQuery( "#form_members" ).submit();
            return;
        }
        jQuery( "body" ).css( "cursor", "wait" );
        jQuery( "#form_members input[type=button]" ).attr( 'disabled', true );
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=change_groups&member_id=" + id,
            success: function(html){
                jQuery( "#change_group_block_" + id ).html( html );
                jQuery( "#close_block_" + id ).html( '<input class="button button-secondary" type="button" onClick="jQuery(this).closeChangeGroups( ' + id + ' );" value="'+enewsletter.close+'" />' );

                jQuery( "#change_button_" + id ).val(enewsletter.save_groups);

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
        jQuery( "#change_button_" + id ).val(enewsletter.change_groups);
    };


    //newsletter page


    jQuery('.deleteNewsletter').on('click', function() {
        var choice = confirm(enewsletter.confirm);
        if(choice)
            return true;
        else
            return false;
    });


    //send neswsletter page


    var cron = 0;
    var assoontext = jQuery('#timestamp b').html();

    jQuery( '#add_cron' ).click( function() {
        cron = 1;
        jQuery( '#send_form' ).submit();
    });


    jQuery( '#send_form' ).submit( function() {
        error = '1';

        if ( true == jQuery( "input[name='all_members']" ).prop( 'checked' ) )
            error = '0'

        jQuery( "input[name='target[groups][]']" ).each( function() {
            if ( true == jQuery(this).prop( 'checked' ) )
                error = '0'
        });

        jQuery( "input[name='target[roles][]']" ).each( function() {
            if ( true == jQuery(this).prop( 'checked' ) )
                error = '0'
        });

        jQuery( "input[name='target[m2][]']" ).each( function() {
            if ( true == jQuery(this).prop( 'checked' ) )
                error = '0'
        });

        jQuery( "input[name='target[membership_levels][]']" ).each( function() {
            if ( true == jQuery(this).prop( 'checked' ) )
                error = '0'
        });

        if ( true == jQuery( "input[name='target[site_admins]']" ).prop( 'checked' ) )
            error = '0'


        if ( '1' == error ) {
            alert( enewsletter.select_members );
            cron = 0;
            return false;
        } else {
            if ( 1 == cron ) {
                jQuery( '#cron' ).val( 'cron' );
            }
            return true;
        }
    });

    jQuery('a.edit-timestamp').click(function() {
        if (jQuery('#timestampdiv').is(":hidden")) {
            jQuery('#timestampdiv').slideDown('fast');
            jQuery('#mm').focus();
            jQuery(this).hide();
        }
        return false;
    });

    jQuery('.save-timestamp', '#timestampdiv').click(function () {
        aa = jQuery('#aa').val(), mm = jQuery('#mm').val(), jj = jQuery('#jj').val(), hh = jQuery('#hh').val(), mn = jQuery('#mn').val();

        jQuery('#timestamp b').html(
            jQuery('option[value="' + jQuery('#mm').val() + '"]', '#mm').text() + ' ' +
            jj + ', ' +
            aa + ' @ ' +
            hh + ':' +
            mn
        );
        jQuery('#timestampdiv').slideUp('fast');
        jQuery('a.edit-timestamp').show();
        jQuery( '#cron_time' ).val( 'cron_time' );

        return false;
    });
    jQuery('.cancel-timestamp', '#timestampdiv').click(function() {
        jQuery('#timestampdiv').slideUp('fast');
        jQuery('a.edit-timestamp').show();
        jQuery('#timestamp b').html(assoontext);
        jQuery( '#cron_time' ).val( '' );
        return false;
    });


    //settings page


    current_tab = '#'+enewsletter.settings_tab;
    jQuery( "#newsletter_setting_page" ).val(current_tab.substring(1));
    current_menu_link = jQuery('#newsletter-tabs a[href^="'+current_tab+'"]');

    jQuery(current_menu_link).addClass('nav-tab-active').siblings('a').removeClass('nav-tab-active');
    jQuery(current_tab).show().siblings('div').hide();
    jQuery(current_tab).addClass('active');

    jQuery('#newsletter-tabs a').click(function(e) {
        var tab = jQuery(this).attr('href');
        jQuery( "#newsletter_setting_page" ).val(tab.substring(1));
        jQuery(this).addClass('nav-tab-active').siblings('a').removeClass('nav-tab-active');
        jQuery(tab).show().siblings('div').hide();
        jQuery(tab).addClass('nav-tab-active');
        return false;
    });

    jQuery('.newsletter-settings-tabs > div').not('.active').hide();

    jQuery('.newsletter-settings-tabs').show();

    jQuery( "input[type=button][name='save']" ).click( function() {
        if ( "" == jQuery( "#smtp_host" ).val() && jQuery( "#smtp_method" ).attr( 'checked' ) ) {
            alert(enewsletter.smtp_warning);
            return false;
        }

        jQuery( "#newsletter_action" ).val( "save_settings" );
        jQuery( "#settings_form" ).submit();
    });

    //install plugin data
    jQuery( "#install" ).click( function() {
        if ( "" == jQuery( "#smtp_host" ).val() && jQuery( "#smtp_method" ).attr( 'checked' ) ) {
            alert(enewsletter.smtp_warning);
            return false;
        }

        jQuery( "#newsletter_action" ).val( "install" );
        jQuery( "#settings_form" ).submit();
        return false;
    });



    //uninstall plugin data
    jQuery( "#uninstall_yes" ).click( function() {
        jQuery( "#newsletter_action" ).val( "uninstall" );
        jQuery( "#settings_form" ).submit();
        return false;

    });

    jQuery( "#uninstall" ).click( function() {
        jQuery( "#uninstall_confirm" ).show( );
        return false;
    });

    jQuery( "#uninstall_no" ).click( function() {
        jQuery( "#uninstall_confirm" ).hide( );
        return false;
    });


    //Test connection to bounces email
    jQuery( "#test_bounce_conn" ).click( function() {
        var bounce_email    = encodeURIComponent(jQuery( "#bounce_email" ).val());
        var bounce_host     = encodeURIComponent(jQuery( "#bounce_host" ).val());
        var bounce_port     = encodeURIComponent(jQuery( "#bounce_port" ).val());
        var bounce_username = encodeURIComponent(jQuery( "#bounce_username" ).val());
        var bounce_password = encodeURIComponent(jQuery( "#bounce_password" ).val());
        var bounce_security = encodeURIComponent(jQuery( "#bounce_security" ).val());

        jQuery( "#test_bounce_loading" ).show();
        jQuery( "#test_bounce_conn" ).attr( 'disabled', true );

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=test_bounces&bounce_email=" + bounce_email + "&bounce_host=" + bounce_host + "&bounce_port=" + bounce_port + "&bounce_username=" + bounce_username + "&bounce_password=" + bounce_password + "&bounce_security=" + bounce_security,
            success: function( html ){
                jQuery( "#test_bounce_conn" ).attr( 'disabled', false );
                jQuery( "#test_bounce_loading" ).hide();
                alert( html );
            }
         });
    });

    //Test connection to bounces email
    jQuery( "#test_smtp_conn" ).click( function() {
        var smtp_host     = encodeURIComponent(jQuery( "#smtp_host" ).val());
        var smtp_port     = encodeURIComponent(jQuery( "#smtp_port" ).val());
        var smtp_from = encodeURIComponent(jQuery( "#smtp_from" ).val());
        var smtp_username = encodeURIComponent(jQuery( "#smtp_username" ).val());
        var smtp_password = encodeURIComponent(jQuery( "#smtp_password" ).val());
        var smtp_security = encodeURIComponent(jQuery( "#smtp_security" ).val());

        jQuery( "#test_smtp_loading" ).show();
        jQuery( "#test_smtp_conn" ).attr( 'disabled', true );

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=test_smtp&smtp_from=" + smtp_from + "&smtp_host=" + smtp_host + "&smtp_port=" + smtp_port + "&smtp_username=" + smtp_username + "&smtp_password=" + smtp_password + "&smtp_security=" + smtp_security,
            success: function( html ){
                jQuery( "#test_smtp_conn" ).attr( 'disabled', false );
                jQuery( "#test_smtp_loading" ).hide();
                alert( html );
            }
         });
    });

    function set_out_option() {
        jQuery('.email_out_type' ).each( function() {
            if( jQuery( this )[0].checked ){
                jQuery( '.email_out' ).hide();
                jQuery( '.email_out_' + jQuery( this ).val() ).show();
            }
        });
    }

    set_out_option();
    jQuery( '.email_out_type' ).change( function() {
        set_out_option();
        if( jQuery( this )[0].checked ){
            jQuery( '.email_out' ).hide();
            jQuery( '.email_out_' + jQuery( this ).val() ).show();
        }
    });

    jQuery('table.permissionTable thead .check-column input:checkbox').change(function() {
        if(jQuery(this).is(':checked')) {
            jQuery(this).parents('table').find('.check-column input:checkbox').not(jQuery(this)).attr('checked','checked');
        } else {
            jQuery(this).parents('table').find('.check-column input:checkbox').not(jQuery(this)).prop("checked", false);
        }
    });


    //subscribe page


    //save subscribes
    jQuery( "#save_subscribes" ).click( function() {
        jQuery( "#newsletter_action" ).val( 'save_subscribes' );
        jQuery( "#subscribes_form" ).submit();

    });

    //unsubscribe
    jQuery( "#unsubscribe" ).click( function() {
        jQuery( "#newsletter_action" ).val( 'unsubscribe' );
        jQuery( "#subscribes_form" ).submit();

    });
});