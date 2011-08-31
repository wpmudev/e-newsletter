<?php
    global $wpdb;

    $settings = $this->get_settings();

    $siteurl = get_option( 'siteurl' );

    $newsletter_id = $_REQUEST['newsletter_id'];

    $templates = $this->get_templates();

    //get data of newsletter
    $newsletter_data = $this->get_newsletter_data( $newsletter_id );

    $page_title =  __( 'Edit Newsletter', 'email-newsletter' );

    //set data by default if we create new newsletter
    if ( ! $newsletter_data ) {
        $newsletter_data['subject']         = "Newsletter";
        $newsletter_data['template']        = $templates['0']['name'];
        $newsletter_data['from_name']       = $settings['from_name'] ? $settings['from_name'] : get_option( 'blogname' );
        $newsletter_data['from_email']      = $settings['from_email'] ? $settings['from_email'] : get_option( 'admin_email' );
        $newsletter_data['contact_info']    = $settings['contact_info'] ? $settings['contact_info'] : "";

//        $newsletter_data['bounce_email']    = get_option( 'admin_email' );

        $page_title =  __( 'Create Newsletter', 'email-newsletter' );

        $mode = "create";
    }

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
    }

    //Creating WYSIWYG editor
    wp_tiny_mce( false ,
        array(
            "editor_selector" => "newsletter_content",
        )
    );


?>
    <script language="JavaScript">

        jQuery( document ).ready( function() {

            //Creating tabs
            jQuery(function() {
                jQuery( "#tabs" ).tabs();
            });


            //Send  Preview Email by AJAX
            jQuery( "#send_preview" ).click( function() {
                var template        = jQuery( "#newsletter_template" ).val();
                var subject         = jQuery( "#subject" ).val();
                var from_name       = jQuery( "#from_name" ).val();
                var from_email      = jQuery( "#from_email" ).val();
                var preview_email   = jQuery( "#preview_email" ).val();
                var contact_info    = jQuery.base64Encode( jQuery( "#contact_info" ).val() );
                contact_info        = contact_info.replace(/\+/g, "-");

                var content         = jQuery.base64Encode( tinyMCE.get( "newsletter_content" ).getContent() );
                content = content.replace(/\+/g, "-");

                if ( "" == preview_email ) {
                    alert( 'Please write Preview email address' );
                    return false;
                }

                if ( "" != content ) {
                    jQuery( "#send_preview" ).attr( 'disabled', true );
                    jQuery( "#send_preview" ).attr( 'value', 'Sending...' );
                    jQuery( "body" ).css( "cursor", "wait" );
                    jQuery.ajax({
                        type: "POST",
                        url: "<?php echo $siteurl;?>/wp-admin/admin-ajax.php",
                        data: "action=send_preview&template=" + template + "&subject=" + subject + "&from_name=" + from_name + "&from_email=" + from_email + "&contact_info=" + contact_info + "&preview_email=" + preview_email + "&content=" + content,
                        success: function(html){
                            jQuery( "body" ).css( "cursor", "default" );
                             jQuery( "#send_preview" ).attr( 'value', 'Send Preview' );
                            alert( html );
                            jQuery( "#send_preview" ).attr( 'disabled', false );
                        }
                     });
                } else {
                    alert( '<?php _e( 'Please write some content!', 'email-newsletter' ) ?>' );
                }
            });

            //Activate template
            jQuery( ".newsletter-templates a.template" ).click( function() {
                jQuery( ".newsletter-templates a.template" ).attr( 'class', 'template' );
                jQuery( this ).attr( 'class', 'template selected' );
                jQuery( "#newsletter_template" ).val( jQuery( this ).attr( 'rel' ) );
                jQuery( "#selected_temp" ).html( jQuery( this ).attr( 'rel' ) );
            });

            //Show Newsletter Preview
            jQuery( "#newsletter_preview" ).click( function() {
                var template        = jQuery( "#newsletter_template" ).val();
                var subject         = jQuery( "#subject" ).val();
                var from_name       = jQuery( "#from_name" ).val();
                var from_email      = jQuery( "#from_email" ).val();
                var contact_info    = jQuery.base64Encode( jQuery( "#contact_info" ).val() );
                contact_info        = contact_info.replace(/\+/g, "-");

                var content         = jQuery.base64Encode( tinyMCE.get( "newsletter_content" ).getContent() );
                content             = content.replace(/\+/g, "-");


//             iframe for show Newsletter Preview
               jQuery( "#preview_block" ).html( "<iframe frameborder='0' name='preview_iframe' style='border:1px solid #CCCCCC; width: 100%; height: 450px;' src=''  ></iframe>" );

//             create for hidden form for send POST request for iframe for show Newsletter Preview
               preview_form = '';
               preview_form = preview_form + '<form method="POST" id="newsletter_preview_send_post" target="preview_iframe" action="<?php echo $siteurl;?>/wp-admin/admin-ajax.php" style="display: none;" >';
               preview_form = preview_form + '<input type="hidden" name="action" value="show_preview" />';
               preview_form = preview_form + '<input type="hidden" name="template" value="' + template + '" />';
               preview_form = preview_form + '<input type="hidden" name="subject" value="' + subject + '" />';
               preview_form = preview_form + '<input type="hidden" name="from_name" value="' + from_name + '" />';
               preview_form = preview_form + '<input type="hidden" name="from_email" value="' + from_email + '" />';
               preview_form = preview_form + '<input type="hidden" name="contact_info" value="' + contact_info + '" />';
               preview_form = preview_form + '<input type="hidden" name="content" value="' + content + '" />';
               preview_form = preview_form + '</form>';

               jQuery( "#for_newsletter_preview_form" ).html( preview_form );
               jQuery( "#newsletter_preview_send_post" ).submit( );
               jQuery( "#for_newsletter_preview_form" ).html( '' );

            });

            //Save Newsletter action
            jQuery( "#newsletter_save" ).click( function() {
                var content = jQuery.base64Encode( tinyMCE.get( "newsletter_content" ).getContent() );
                content     = content.replace(/\+/g, "-");

                var contact_info    = jQuery.base64Encode( jQuery( "#contact_info" ).val() );
                contact_info        = contact_info.replace(/\+/g, "-");

                jQuery( "#contact_info" ).val( contact_info );

                jQuery( "#newsletter_action" ).val( "save_newsletter" );
                jQuery( "#content_ecoded" ).val( content );
                jQuery( "#create_newsletter" ).submit();
            });

            //Save Newsletter and then go on send page
            jQuery( "#newsletter_save_send" ).click( function() {
                var content = jQuery.base64Encode( tinyMCE.get( "newsletter_content" ).getContent() );
                content = content.replace(/\+/g, "-");

                var contact_info = jQuery.base64Encode( jQuery( "#contact_info" ).val() );
                contact_info = contact_info.replace(/\+/g, "-");

                jQuery( "#contact_info" ).val( contact_info );
                jQuery( "#newsletter_action" ).val( "save_newsletter" );
                jQuery( "#content_ecoded" ).val( content );
                jQuery( "#send" ).val( "send" );
                jQuery( "#create_newsletter" ).submit();
            });

            //Delete Newsletter action
            jQuery( "#newsletter_delete" ).click( function() {
               jQuery( "#newsletter_action" ).val( "delete_newsletter" );
               jQuery( "#create_newsletter" ).submit();
            });


            //Creating WYSIWYG editor
            tinyMCE.init({
                    // General options
                    mode: "exact",
                    elements : "newsletter_content",
                    theme : "advanced",
                    height : '300px',
                    width : '95%',
                    relative_urls : false,
//                    remove_script_host : true,
                    convert_urls : false,

                    plugins : "autolink,lists,spellchecker,table,advhr,wpeditimage,advlink,iespell,inlinepopups,contextmenu,paste,fullscreen,noneditable,visualchars,nonbreaking",

                    // Theme options
                    theme_advanced_buttons1 : "undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
                    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor,|,fullscreen,nonbreaking,spellchecker,visualchars",
                    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_statusbar_location : "bottom",
                    theme_advanced_resizing : true,
                    valid_elements : "*[*]",

                    // Skin options
                    skin : "o2k7",
                    skin_variant : "silver",

            });


            //function base64 encode
            (function(jQuery){

                var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

                var uTF8Encode = function(string) {
                    string = string.replace(/\x0d\x0a/g, "\x0a");
                    var output = "";
                    for (var n = 0; n < string.length; n++) {
                        var c = string.charCodeAt(n);
                        if (c < 128) {
                            output += String.fromCharCode(c);
                        } else if ((c > 127) && (c < 2048)) {
                            output += String.fromCharCode((c >> 6) | 192);
                            output += String.fromCharCode((c & 63) | 128);
                        } else {
                            output += String.fromCharCode((c >> 12) | 224);
                            output += String.fromCharCode(((c >> 6) & 63) | 128);
                            output += String.fromCharCode((c & 63) | 128);
                        }
                    }
                    return output;
                };

                var uTF8Decode = function(input) {
                    var string = "";
                    var i = 0;
                    var c = c1 = c2 = 0;
                    while ( i < input.length ) {
                        c = input.charCodeAt(i);
                        if (c < 128) {
                            string += String.fromCharCode(c);
                            i++;
                        } else if ((c > 191) && (c < 224)) {
                            c2 = input.charCodeAt(i+1);
                            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                            i += 2;
                        } else {
                            c2 = input.charCodeAt(i+1);
                            c3 = input.charCodeAt(i+2);
                            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                            i += 3;
                        }
                    }
                    return string;
                }

                jQuery.extend({
                        base64Encode: function(input) {
                            var output = "";
                            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                            var i = 0;
                            input = uTF8Encode(input);
                            while (i < input.length) {
                                chr1 = input.charCodeAt(i++);
                                chr2 = input.charCodeAt(i++);
                                chr3 = input.charCodeAt(i++);
                                enc1 = chr1 >> 2;
                                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                                enc4 = chr3 & 63;
                                if (isNaN(chr2)) {
                                    enc3 = enc4 = 64;
                                } else if (isNaN(chr3)) {
                                    enc4 = 64;
                                }
                                output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
                            }
                            return output;
                        },
                        base64Decode: function(input) {
                            var output = "";
                            var chr1, chr2, chr3;
                            var enc1, enc2, enc3, enc4;
                            var i = 0;
                            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
                            while (i < input.length) {
                                enc1 = keyString.indexOf(input.charAt(i++));
                                enc2 = keyString.indexOf(input.charAt(i++));
                                enc3 = keyString.indexOf(input.charAt(i++));
                                enc4 = keyString.indexOf(input.charAt(i++));
                                chr1 = (enc1 << 2) | (enc2 >> 4);
                                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                                chr3 = ((enc3 & 3) << 6) | enc4;
                                output = output + String.fromCharCode(chr1);
                                if (enc3 != 64) {
                                    output = output + String.fromCharCode(chr2);
                                }
                                if (enc4 != 64) {
                                    output = output + String.fromCharCode(chr3);
                                }
                            }
                            output = uTF8Decode(output);
                            return output;
                        }
                    });
            })(jQuery);



           //upload  image on server
            var uploader = new qq.FileUploader({
                element: document.getElementById('file-uploader'),
                action: '<?php echo $siteurl;?>/wp-admin/admin-ajax.php',
                params: {
                    action: 'file_upload'
                },
                allowedExtensions: ['jpg', 'jpeg', 'png', 'gif'],
                sizeLimit: 2097152, // max size
                minSizeLimit: 0 // min size


            });

            //insert  image to body of email
            jQuery.fn.insertImage = function ( ) {
                var src = jQuery( '#uploads_images' ).val();
                jQuery( '#uploads_images' ).val( '' );
                var alt = jQuery( '#image_alt' ).val();
                if ( alt == 'Image Description' ) alt = '';
                jQuery( '#image_alt' ).val( 'Image Description' );
                if( !src || src == '' )return;

                var imghtml = '<img src="' + src + '" alt="'+alt+'"';
                imghtml += ' />';
                tinyMCE.execCommand( 'mceInsertRawHTML', false, imghtml );
            };

            //show big preview image of template
            jQuery( ".newsletter-templates a" ).click( function() {

                var largePath = jQuery( this ).attr( "href" );
                var largeAlt = jQuery( this ).attr( "title" );

                jQuery( "#largeImg" ).attr( { src: largePath, alt: largeAlt } );

                return false;
            });





        });
    </script>


    <div class="wrap">
        <h2><?php echo $page_title; ?></h2>
        <p><?php _e( 'On this page you can create/edit Newsletters.', 'email-newsletter' ) ?></p>
        <form method="post" action="" id="create_newsletter">
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="send" id="send" value="" />
            <input type="hidden" name="content_ecoded" id="content_ecoded" value="" />
            <input type="hidden" name="newsletter_id" id="newsletter_id" value="<?php echo $newsletter_data['newsletter_id']; ?>" />
            <input type="hidden" name="newsletter_template" id="newsletter_template" value="<?php echo $newsletter_data['template']; ?>" />
            <div id="newsletter-tabs">
                <div class="ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs">
                    <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                        <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tabs-1"><?php _e( 'Template', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-2"><?php _e( 'Settings', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-3"><?php _e( 'Content', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-4" id="newsletter_preview"><?php _e( 'Preview', 'email-newsletter' ) ?></a></li>
                        <li class="ui-state-default ui-corner-top"><a href="#tabs-5"><?php _e( 'Actions', 'email-newsletter' ) ?></a></li>
                    </ul>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="tabs-1">
                        <h2><?php _e( 'Template of Newsletter:', 'email-newsletter' ) ?><span id="selected_temp"><?php echo $newsletter_data['template'] ;?></span></h2>

                        <div class="newsletter-templates">
                            <?php
                            foreach( $templates as $template ){
                            ?>
                            <a href="<?php echo $this->plugin_url . 'email-newsletter-files/templates/'.basename($template['dir']);?>/preview_big.jpg"  class="template<?php echo ( $newsletter_data['template'] == $template['name'] ) ? ' selected':'';?>" rel="<?php echo $template['name'];?>" title="<?php echo $template['name'];?>">
                                <div style="height:94px;">
                                    <img  src="<?php echo $this->plugin_url . 'email-newsletter-files/templates/'.basename($template['dir']);?>/preview.jpg" border="0" alt="template preview">
                                </div>
                                <div class="template-name"><?php echo $template['name'];?></div>
                            </a>
                            <?php } ?>
                        </div>
                        <div class="preview-template">
                            <p>
                                <img id="largeImg" src="<?php echo $this->plugin_url . 'email-newsletter-files/templates/'.basename( $newsletter_data['template'] );?>/preview_big.jpg" alt="<?php echo $template['name'];?>">
                            </p>
                        </div>

                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-2">
                        <h2><?php _e( 'Settings of Newsletter:', 'email-newsletter' ) ?></h2>
                        <table class="form-table" style="width: auto;">
                            <tr>
                                <td>
                                    <?php _e( 'Email Subject:', 'email-newsletter' ) ?><span class="required">*</span>
                                </td>
                                <td>
                                    <input type="text" class="input" name="subject" id="subject" value="<?php echo htmlspecialchars( $newsletter_data['subject'] );?>" size="30" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'From Name:', 'email-newsletter' ) ?><span class="required">*</span>
                                </td>
                                <td>
                                    <input type="text" class="input" name="from_name" id="from_name" value="<?php echo htmlspecialchars( $newsletter_data['from_name'] );?>" size="30" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'From Email:', 'email-newsletter' ) ?><span class="required">*</span>
                                </td>
                                <td>
                                    <input type="text" class="input" name="from_email" id="from_email" value="<?php echo htmlspecialchars( $newsletter_data['from_email'] );?>" size="30" />
                                    <?php
                                    if ( "smtp" == $this->settings['outbound_type'] )
                                        echo '<span class="red">' . __( 'Attention! You use SMTP method for sending email - please use only emails related with your SMTP server!', 'email-newsletter' ) . '</span>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php _e( 'Contact information:', 'email-newsletter' ) ?>
                                </td>
                                <td>
                                    <textarea name="contact_info" id="contact_info" class="contact-information" ><?php echo $newsletter_data['contact_info'];?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Will be added to the bottom of emails', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-3">
                        <h2><?php _e( 'Content of Newsletter:', 'email-newsletter' ) ?></h2>
                        <table width="100%">
                            <tr>
                                <td>
                                    <textarea name="newsletter_content" id="newsletter_content" style="width:100%"><?php echo htmlspecialchars( $newsletter_data['content'] );?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h4><?php _e( 'Insert images from server:', 'email-newsletter' ) ?> </h4>
                                    <select name="uploads_images" id="uploads_images">
                                    <?php echo $this->get_uploads(); ?>
                                    </select>
                                    <input type="text" name="image_alt" id="image_alt" value="Image Description" onfocus="if(this.value=='Image Description')this.value='';">
                                    <input type="button" name="image_insert" onclick="jQuery(this).insertImage();" value="Insert Image" />
                                    <h4><?php _e( 'Upload images to server:', 'email-newsletter' ) ?> </h4>
                                    <div id="file-uploader">
                                        <noscript>
                                            <p>Please enable JavaScript to use file uploader.</p>
                                        </noscript>
                                    </div>
                                </td>
                            </tr>
                        </table>

                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-4">
                        <h2><?php _e( 'Preview of Newsletter:', 'email-newsletter' ) ?></h2>
                        <?php _e( 'Preview in Email:', 'email-newsletter' ) ?>
                        <input type="text" name="preview_email" id="preview_email" value="" />
                        <input type="button" id="send_preview" value="<?php _e( 'Send Preview', 'email-newsletter' ) ?>" />
                        <div id="preview_block">
                        </div>
                    </div>

                    <div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" id="tabs-5">
                        <h2><?php _e( 'Save the Newsletter:', 'email-newsletter' ) ?></h2>
                        <input type="button" id="newsletter_save" value="<?php _e( 'Save', 'email-newsletter' ) ?>" />
                        <br />
                        <?php
                        _e( 'or', 'email-newsletter' ) ;
                         ?>
                        <br />
                        <input type="button" id="newsletter_save_send" value="<?php _e( 'Save, and go to Send page', 'email-newsletter' ) ?>" />
                        <?php
                        if ( "create" != $mode) {
                        ?>
                        <br />
                        <br />
                        <input type="button" id="newsletter_delete" value="<?php _e( 'Delete Newsletter', 'email-newsletter' ) ?>" />
                        <?php
                        }
                        ?>
                    </div>

                </div><!--/#tabs-->

            </div><!--/#newsletter-tabs-->

        </form>

        <div id="for_newsletter_preview_form"></div>

    </div><!--/wrap-->