<?php

class Email_Newsletter_Builder  {
	
	var $theme = '';
	var $ID = '';
	var $settings = array();
	
	function Email_Newsletter_Builder() {
		add_action( 'plugins_loaded', array( &$this, 'plugins_loaded'), 999 );
		add_action( 'wp_ajax_builder_do_shortcodes', array( &$this, 'ajax_do_shortcodes' ) );
		
		//shorcodes
		add_shortcode('recent-posts', array( &$this, 'recent_posts_shortcode'));
		add_shortcode( 'n-gallery' , array( &$this,'n_gallery_shortcode') );
	}
	function parse_theme_settings() {
		global $email_newsletter;
		$filename = $email_newsletter->plugin_dir . 'email-newsletter-files/templates/' . $this->get_builder_theme() . '/template.html';

		if(file_exists($filename)) {
			$handle = fopen($filename,'r');
			$contents = fread($handle,filesize($filename));
			fclose($handle);
		
			$pattern = '/\{(?P<name>\w*)\}/';
			$matches = array();
			$match_count = preg_match_all($pattern,$contents,$matches);
			$this->settings = $matches['name'];
		} else {
			$this->settings = array();
		}
		
		
	}
	function generate_builder_link($id=false, $return_url=NULL, $url=false) {
		if(!$id)
			return;
		$final = 'customize.php?wp_customize=on&theme='.$this->get_builder_theme($id).'&newsletter_id='.$id;
		if(empty($return_url))
			$final .= '&return='.urlencode('admin.php?page=newsletters');
		else if($return_url != false)
			$final .= '&return='.urlencode($return_url);
		
		if($url)
			$final .= '&url='.$url;
		return admin_url($final);
	}
	function plugins_loaded() {
		global $current_user, $pagenow, $builder_id, $email_newsletter;
		
		$this->template_directory = $email_newsletter->plugin_dir . 'email-newsletter-files/templates';
		$this->template_custom_directory = $email_newsletter->get_custom_theme_dir();
		register_theme_directory($this->template_custom_directory);
		register_theme_directory($this->template_directory);
		
		//cheating message fix
		wp_clean_themes_cache();
		
		// Start the id at false for checking
		$builder_id = false;
		
		if(isset($_REQUEST['newsletter_id'])) {
			if(is_numeric($_REQUEST['newsletter_id'])){
				$builder_id = $_REQUEST['newsletter_id'];
				delete_transient('builder_email_id_'.$current_user->ID);
				set_transient('builder_email_id_'.$current_user->ID, $builder_id);
			} elseif($_REQUEST['newsletter_id'] == 'new') {
				// We pass an empty array to create a new newsletter and get our ID
				$builder_id = $this->save_builder(array('template' => $_REQUEST['theme']));
				delete_transient('builder_email_id_'.$current_user->ID);
				set_transient('builder_email_id_'.$current_user->ID, $builder_id);
				
				// Now redirect to our new newsletter builder
				wp_redirect($this->generate_builder_link($builder_id));
				exit;
			} else {
				die(__('Something is wrong, we can not determine what your trying to do.','email-newsletter'));
			}
		}
		
		$builder_id = $this->get_builder_email_id();
		$this->ID = $this->get_builder_email_id();
		
		if( isset( $_REQUEST['wp_customize'] ) && 'on' == $_REQUEST['wp_customize'] && $_REQUEST['theme'] === $this->get_builder_theme()  ) {
			add_filter( 'template', array( &$this, 'inject_builder_template'), 999 );
			add_filter( 'stylesheet', array( &$this, 'inject_builder_stylesheet' ), 999 );
			add_action( 'customize_register', array( &$this, 'init_newsletter_builder'),9999 );
			add_action( 'setup_theme' , array( &$this, 'setup_builder_header_footer' ), 999 );
		}
		$this->parse_theme_settings();
	}
	function setup_builder_header_footer() {
		global $wp_customize;
		
		add_action( 'builder_head', array( $wp_customize, 'customize_preview_base' ) );
		add_action( 'builder_head', array( $wp_customize, 'customize_preview_html5' ) );
		add_action( 'builder_head', array( &$this, 'construct_builder_head' ) );
		add_action( 'builder_footer', array( $wp_customize, 'customize_preview_settings' ), 20 );
		add_action( 'customize_controls_print_scripts', array( $this, 'customize_controls_print_scripts') );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_controls_print_footer_scripts'), 20 );
	}
	function construct_builder_head() {
		do_action('admin_head');
	}
	function construct_builder_footer() {
		do_action('admin_footer');
		do_action('admin_print_footer_scripts');
		do_action('wp_print_footer_scripts');
		do_action('wp_footer');		
	}
	function customize_controls_print_scripts() {
		do_action('admin_enqueue_scripts');
		do_action('email_newsletter_template_builder_print_scripts');
	}
	function customize_controls_print_footer_scripts() {
		global $email_newsletter;
		
		// Collect other theme info so we can allow changes
		$themes = wp_get_themes();
		
		foreach($themes as $key => $theme) {
			if($theme->theme_root != $this->template_directory && $theme->theme_root != $this->template_custom_directory )
				unset($themes[$key]);
		}
		?><script type="text/javascript">
			_wpCustomizeControlsL10n.save = "<?php _e('Save Newsletter','email-newsletter'); ?>";
			

			ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
			var current_theme = "<?php echo $_GET['theme']; ?>";
			email_templates = [
				<?php foreach($themes as $theme): ?>
				{	"name": <?php echo json_encode($theme->get('Name')); ?>, 
					"description": <?php echo json_encode($theme->get('Description')); ?>,
					"screenshot": <?php echo json_encode($theme->get_screenshot()); ?>,
					"stylesheet": <?php echo json_encode($theme->stylesheet); ?>,
				},
				<?php endforeach; ?>
			];
			var _info = jQuery('#customize-info .customize-section-content');
			_info
				.prepend('<a href="#" class="arrow left" />')
				.prepend('<a href="#" class="arrow right" />')
			
			jQuery.each(email_templates, function(i,e) {
				var clone = _info.clone().addClass('hidden');
				
				if( e.stylesheet != current_theme ) {
					clone.find('img.theme-screenshot').attr('src',e.screenshot);
					clone.find('.theme-description').text(e.description);
					clone.data('theme',e);
					jQuery('#customize-info').append(clone);
				} else {
					// Use this opportunity to change the theme preview area
					var current_name = jQuery('#customize-info .preview-notice .theme-name');
					jQuery('#customize-info .preview-notice').html("<?php _e('Manage template (Save to see changes)','email-newsletter'); ?>").prepend(current_name);
					_info.data('theme',e);
				}
				
			});
				
			jQuery('#customize-info').on('click', function() {
											
				jQuery('.arrow').on('click', function() {
					var _this = jQuery(this);
					var data = jQuery(this).parent().data('theme');
					
					var next = _this.parent().next('.customize-section-content');
					var prev = _this.parent().prev('.customize-section-content');
					
					if(_this.hasClass('left')) {
						if(prev.length > 0) {
							_this.parent().slideUp("slow");
							prev.slideDown("slow");
							var data = prev.data('theme');
						}
					} else {
						if(next.length > 0) {
							_this.parent().slideUp("slow");
							next.slideDown("slow");
							var data = next.data('theme');
						}
					}
					
					if( typeof data != 'undefined') {
						// Use string replace to redirect the url
						jQuery('[data-customize-setting-link="template"]').val(data.stylesheet).trigger('change');
						
						//changes name in sidebar
						jQuery('.theme-name').text(data.name);
					}
					return false;
				});
				wp.customize.bind( 'saved', function() {
					var new_theme = jQuery('[data-customize-setting-link="template"]').val();
					if(current_theme != new_theme)
						window.location.href = window.location.href.replace('theme='+current_theme,'theme='+new_theme)
				});
			});
			jQuery(document).ready(function() {
				jQuery("#save").click();
			});

		</script>
		
		<style type="text/css">
			.theme-screenshot {
				min-height:258px;	
				display:block;
			}
			.wp-full-overlay {
				z-index: 15000;
			}
			#TB_overlay, #TB_window {
				z-index: 16000!important;
			}
			.customize-section.open .customize-section-content.hidden {
				display:none;
			}
			.arrow {
				width: 22px;
				height: 22px;
				display: block;
				position: absolute;
				background-color: #fff;
				border-radius:5px;
				border:1px solid #174C63;
				opacity:0.7;
				top: 200px;
			}
			.arrow:hover {
				opacity:1;
			}
			.arrow.right {
				right: 35px;
				background-image:url('<?php echo admin_url().'/images/arrows-dark-vs-2x.png'; ?>');
				background-position: center 27px;
			}
			.arrow.left {
				left: 35px;
				background-image:url('<?php echo admin_url().'/images/arrows-dark-vs-2x.png'; ?>');
				background-position: center 98px;
			}
		</style>
		<?php
		// We need to call this action for the tinyMCE editor to work properly
		do_action('admin_print_footer_scripts');
		do_action('admin_footer');
	}
	
	function get_builder_email_id() {
		global $current_user;
		return get_transient('builder_email_id_'.$current_user->ID);
	}
	function get_builder_theme($id=false) {
		global $builder_id, $email_newsletter;
		$email_id = ($id !== false ? $id : $builder_id);
		if( isset($_GET['theme']) ) {
			return $_GET['theme'];
		} else {
			$data = $email_newsletter->get_newsletter_data($email_id);
			return (isset($data['template']) ? $data['template'] : 'iletter');
		}

	}
	function find_builder_theme() {
		$cap = 'manage_options';
		
    	if (!current_user_can($cap)) {
        	// not admin
        	return false;
        }

		  $theme = $this->get_builder_theme();
	      
	      $theme_data = wp_get_theme($theme);
	      
	      if (empty($theme_data) || !$theme_data) {
	      	return false;
		  } else {
	          // Don't let people peek at unpublished themes
	          if (isset($theme_data['Status']) && $theme_data['Status'] != 'publish') {
	              return false;
	          }
	          return $theme_data;
	      }
	      
	      // perhaps they are using the theme directory instead of title
	      $themes = wp_get_themes();
	      
	      foreach ($themes as $theme_data) {
	          // use Stylesheet as it's unique to the theme - Template could point to another theme's templates
	          if ($theme_data['Stylesheet'] == $theme) {
	              // Don't let people peek at unpublished themes
	              if (isset($theme_data['Status']) && $theme_data['Status'] != 'publish') {
	                  return false;
				  }
	            	return $theme_data;
	        	}
	    	}
    	return false;
	}
	function inject_builder_stylesheet($stylesheet) {
		$theme = $this->find_builder_theme();
		if ($theme === false)
			return $stylesheet;
		else
			return $theme['Stylesheet'];
	}
	function inject_builder_template($template) {
		$theme = $this->find_builder_theme();
		if ($theme === false)
			return $template;
		else		  
			return $theme['Template'];
	}
	function init_newsletter_builder( $instance ) {
		global $builder_id, $email_newsletter;
		$email_data = $email_newsletter->get_newsletter_data($builder_id);
		
		$template_url  = $email_newsletter->plugin_url . "email-newsletter-files/templates/" . $email_data['template'].'/';
		
		
		// Load our extra control classes
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.tinymce-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.textarea-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.multiadd-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.hidden-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.preview-control.php');
		
		if( in_array('BG_IMAGE', $this->settings)) {

			$bg_image = $email_newsletter->get_default_builder_var('bg_image');
			if(!empty($bg_image))
				$bg_image = $template_url.$bg_image;
			else 
				$bg_image = '';
				
			$instance->add_section( 'bg_image', array(
				'title'          => __('Background Image','email-newsletter'),
				'priority'       => 37,
			) );
			$instance->add_setting( 'bg_image', array(
				//'subject'        => '',
				//'capability' => NULL,
				'default' => $bg_image,
				'type' => 'newsletter_save'
			) );
			$instance->add_control( new WP_Customize_Image_Control( $instance, 'bg_image', array(
				'label'   => __('Background Image','email-newsletter'),
				'section' => 'bg_image',
			)) );
		}
		
		if( in_array('SIDEBAR', $this->settings) ) {
			$instance->add_section( 'builder_sidebar', array(
				'title'          => __('Sidebar','email-newsletter'),
				'priority'       => 38,
			) );
			$instance->add_setting( 'sidebar_active', array(
				//'subject'        => '',
				//'capability' => NULL,
				'default' => '1',
				'type' => 'newsletter_save'
			) );
			$instance->add_setting( 'sidebar_items', array(
				//'subject'        => '',
				//'capability' => NULL,
				'default' => serialize(array()),
				'type' => 'newsletter_save'
			) );
			$instance->add_control( new Builder_MultiAdd_Control( $instance, 'sidebar_items', array(
				'label'   => 'Use Sidebar?',
				'section' => 'builder_sidebar',
				'settings'    => 'sidebar_items',
			) ));
		}
		
		if( in_array('BG_COLOR', $this->settings) || in_array('LINK_COLOR', $this->settings) || in_array('BODY_COLOR', $this->settings) ) {
			
			$instance->add_section( 'builder_colors', array(
				'title' => __('Colors','email-newsletter'),
				'priority' => 39
			) );
			
			if( in_array('BG_COLOR', $this->settings) ) {
				$instance->add_setting( 'bg_color', array(
					//'subject'        => '',
					//'capability' => NULL,
					'default' => $email_newsletter->get_default_builder_var('bg_color'),
					'type' => 'newsletter_save'
				) );
				$instance->add_control( new WP_Customize_Color_Control( $instance, 'bg_color', array(
					'label'        => __('Background Color', 'email-newsletter' ),
					'section'    => 'builder_colors',
					'settings'   => 'bg_color',
				) ) );
			}
			
			if( in_array('BODY_COLOR', $this->settings) ) {
				$instance->add_setting( 'body_color', array(
					//'subject'        => '',
					//'capability' => NULL,
					'default' => $email_newsletter->get_default_builder_var('body_color'),
					'type' => 'newsletter_save'
				) );
				$instance->add_control( new WP_Customize_Color_Control( $instance, 'body_color', array(
					'label'        => __( 'Body Text Color', 'email-newsletter' ),
					'section'    => 'builder_colors',
					'settings'   => 'body_color',
				) ) );
			}
			
			if( in_array('LINK_COLOR', $this->settings) ) {
				$instance->add_setting( 'link_color', array(
					//'subject'        => '',
					//'capability' => NULL,
					'default' => $email_newsletter->get_default_builder_var('link_color'),
					'type' => 'newsletter_save'
				) );
				$instance->add_control( new WP_Customize_Color_Control( $instance, 'link_color', array(
					'label'        => __( 'Link Color', 'email-newsletter' ),
					'section'    => 'builder_colors',
					'settings'   => 'link_color',
				) ) );
			}
		}
		
		if( in_array('EMAIL_TITLE',$this->settings) ) {
			$instance->add_setting( 'email_title', array(
				'default' => $email_newsletter->get_default_builder_var('email_title'),
				'type' => 'newsletter_save'
			) );
			$instance->add_control( 'email_title', array(
				'label'   => __('Email Title','email-newsletter'),
				'section' => 'builder_email_content',
				'type'    => 'text',
			) );
		}
		
		// Setup Sections
		$instance->remove_section( 'title_tagline' );
		$instance->remove_section( 'static_front_page' );
		$instance->add_section( 'builder_email_settings', array(
			'title'          => 'Settings',
			'priority'       => 35,
		) );
		$instance->add_section( 'builder_email_content', array(
			'title'          => 'Content',
			'priority'       => 36,
		) );
		$instance->add_section( 'builder_preview', array(
			'title'          => __('Send Preview','email-newsletter'),
			'priority'       => 40,
		) );
		
		
		// Setup Settings
		$instance->add_setting( 'template', array(
			'default' => $_REQUEST['theme'],
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'subject', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => $email_newsletter->get_default_builder_var('email_title'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'from_name', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => $email_newsletter->settings['from_name'],
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'from_email', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => $email_newsletter->settings['from_email'],
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'bounce_email', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => $email_newsletter->settings['bounce_email'],
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'email_content', array(
			//'subject'        => '',
			//'capability' => NULL,
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'email_preview', array(
			//'subject'        => '',
			//'capability' => NULL,
			'type' => 'newsletter_save'
		) );
		
		$instance->add_setting( 'contact_info', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => '',
			'type' => 'newsletter_save',
		) );
		$instance->add_control( new Builder_TextArea_Control( $instance, 'contact_info', array(
			'label'   => __('Contact Info','email-newsletter'),
			'section' => 'builder_email_content',
			'settings'   => 'contact_info',
		) ) );
		
		
		// Setup Controls
		$instance->add_control( new Builder_Hidden_Control( $instance, 'template', array(
			'label'   => __('Template','email-newsletter'),
			'section' => 'builder_email_settings',
			'settings'   => 'template',
		) ) );
		$instance->add_control( 'subject', array(
			'label'   => __('Email Subject','email-newsletter'),
			'section' => 'builder_email_settings',
			'type'    => 'text',
		) );
		$instance->add_control( 'from_name', array(
			'label'   => __('From Name','email-newsletter'),
			'section' => 'builder_email_settings',
			'type'    => 'text',
		) );
		$instance->add_control( 'from_email', array(
			'label'   => __('From Email','email-newsletter'),
			'section' => 'builder_email_settings',
			'type'    => 'text',
		) );
		$instance->add_control( 'bounce_email', array(
			'label'   => __('Bounce Email','email-newsletter'),
			'section' => 'builder_email_settings',
			'type'    => 'text',
		) );
		$instance->add_control( new Builder_TinyMCE_Control( $instance, 'email_content', array(
			'label'   => __('Email Content','email-newsletter'),
			'section' => 'builder_email_content',
			'settings'   => 'email_content',
		) ) );
		$instance->add_control( new Builder_Preview_Control($instance, 'email_preview', array(
			'label'   => __('Send Preview To Email (Save First)','email-newsletter'),
			'section' => 'builder_preview',
		) ) );
		
		
		
		
		if ( $instance->is_preview() && !is_admin() )
			add_action( 'wp_footer', array( &$this, 'email_builder_customize_preview'), 21);

		$instance->get_setting('from_name')->transport='postMessage';
		$instance->get_setting('from_email')->transport='postMessage';
		$instance->get_setting('bounce_email')->transport='postMessage';
		$instance->get_setting('email_subject')->transport='postMessage';
		$instance->get_setting('contact_info')->transport='postMessage';
		$instance->get_setting('email_content')->transport='postMessage';
		$instance->get_setting('bg_color')->transport='postMessage';
		$instance->get_setting('bg_image')->transport='postMessage';
		$instance->get_setting('link_color')->transport='postMessage';
		$instance->get_setting('body_color')->transport='postMessage';
		
		// Add all the filters we need for all the settings to save and be retreived
		add_action( 'customize_save_subject', array( &$this, 'save_builder') );
		add_filter( 'customize_value_subject', array( &$this, 'get_builder_subject') );
		add_filter( 'customize_value_from_name', array( &$this, 'get_builder_from_name') );
		add_filter( 'customize_value_from_email', array( &$this, 'get_builder_from_email') );
		add_filter( 'customize_value_bounce_email', array( &$this, 'get_builder_bounce_email') );
		add_filter( 'customize_value_email_title', array( &$this, 'get_builder_email_title') );
		add_filter( 'customize_value_contact_info', array( &$this, 'get_builder_contact_info') );
		add_filter( 'customize_value_email_content', array( &$this, 'get_builder_email_content') );
		add_filter( 'customize_value_bg_color', array( &$this, 'get_builder_bg_color') );
		add_filter( 'customize_value_link_color', array( &$this, 'get_builder_link_color') );
		add_filter( 'customize_value_body_color', array( &$this, 'get_builder_body_color') );
		add_filter( 'customize_value_bg_image', array( &$this, 'get_builder_bg_image') );
		
	}

	function save_builder($new_values = false) {
		global $email_newsletter;
		
		$data = array();
		$default = array(
			'subject' => '',
			'content_ecoded' => '',
			'contact_info' => base64_encode($email_newsletter->settings['contact_info']),
			'from_name' => $email_newsletter->settings['from_name'],
			'from_email' => $email_newsletter->settings['from_email'],
			'bounce_email' => $email_newsletter->settings['bounce_email'],
			'meta' => array(),
		);
		
		if(!$new_values && isset($_POST['customized']))
			$new_values = json_decode(stripslashes($_POST['customized']), true);
			
		if(isset($new_values['template']) ) {
			$data['newsletter_template'] = $new_values['template'];
		}
			
		if(isset($new_values['subject']))
			$data['subject'] = $new_values['subject'];
		
		if(isset($new_values['email_title']))
			$data['meta']['email_title'] = $new_values['email_title'];
		
		if(isset($new_values['email_content']))
			$data['content_encoded'] = base64_encode($new_values['email_content']);
		
		if(isset($new_values['contact_info']))
			$data['contact_info'] = base64_encode($new_values['contact_info']);
		
		if(isset($new_values['from_name']))
			$data['from_name'] = $new_values['from_name'];
		
		if(isset($new_values['from_email']))
			$data['from_email'] = $new_values['from_email'];
			
		if(isset($new_values['bounce_email']))
			$data['bounce_email'] = $new_values['bounce_email'];
		
		if(isset($new_values['bg_color']))
			$data['meta']['bg_color'] = $new_values['bg_color'];
					
		if(isset($new_values['link_color']))
			$data['meta']['link_color'] = $new_values['link_color'];
			
		if(isset($new_values['body_color']))
			$data['meta']['body_color'] = $new_values['body_color'];

		if(isset($new_values['bg_image']))
			$data['meta']['bg_image'] = $new_values['bg_image'];

		
		$data = array_merge($default,$data);
		
		return $email_newsletter->save_newsletter($this->ID, false, $data);
	}
	
	// Anything that isnt a text input has to have its own function because 
	// WordPress only gives us the $default value to match in the filter
	function get_builder_bg_color($default) {

		global $builder_id, $email_newsletter;
		
		$bg_color = $email_newsletter->get_newsletter_meta($builder_id,'bg_color');
		if(!empty($bg_color))
			return $bg_color;
		else
			return $default;
	}
	function get_builder_link_color($default) {
		global $builder_id, $email_newsletter;
		
		$link_color = $email_newsletter->get_newsletter_meta($builder_id,'link_color');
		if(!empty($link_color))
			return $link_color;
		else
			return $default;
	}
	function get_builder_body_color($default) {
		global $builder_id, $email_newsletter;
		
		$body_color = $email_newsletter->get_newsletter_meta($builder_id,'body_color');
		if(!empty($body_color))
			return $body_color;
		else
			return $default;
	}
	function get_builder_email_title($default) {
		global $builder_id, $email_newsletter;
		
		$email_title = $email_newsletter->get_newsletter_meta($builder_id,'email_title');
		if(!empty($email_title))
			return $email_title;
		else
			return $default;
	}
	function get_builder_email_content($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		
		if(!empty($data['content']))
			return $data['content'];
		else
			return $default;
	}
	function get_builder_bg_image($default) {
		global $builder_id, $email_newsletter;
		
		$bg_image = $email_newsletter->get_newsletter_meta($builder_id,'bg_image');
		if(!empty($bg_image))
			return $bg_image;
		else
			return $default;
	}
	
	
	
	function get_builder_contact_info($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		if(isset($data['contact_info']))
			return $data['contact_info'];
		else
			return $email_newsletter->settings['contact_info'];
	}
	function get_builder_subject($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		if(isset($data['subject']))
			return $data['subject'];
		else
			return $email_newsletter->settings['subject'];
	}
	function get_builder_bounce_email($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		if(isset($data['bounce_email']) && is_email($data['bounce_email']))
			return $data['bounce_email'];
		else
			return $email_newsletter->settings['bounce_email'];
	}
	function get_builder_from_name($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		if(!empty($data['from_name']))
			return $data['from_name'];
		else
			return $email_newsletter->settings['from_name'];
	}
	function get_builder_from_email($default) {
		global $builder_id, $email_newsletter;
		
		$data = $email_newsletter->get_newsletter_data($builder_id);
		if(isset($data['from_email']) && is_email($data['from_email']))
			return $data['from_email'];
		else
			return $email_newsletter->settings['from_email'];
	}



	function email_builder_customize_preview() {
		$admin_url = admin_url('admin-ajax.php');
		?><script type="text/javascript">
			
			( function( $ ){
				<?php if( in_array('EMAIL_TITLE',$this->settings)) : ?>
					wp.customize('email_title',function( value ) {
						value.bind(function(to) {
							$('[data-builder="email_title"]').text( to ? to : '' );
						});
					});
				<?php endif; ?>
				wp.customize('email_content',function( value ) {
					value.bind(function(to) {
						console.log($('#customize-control-link_color').find('.wp-color-result').css("background-color"));
						var data = {
							action: 'builder_do_shortcodes',
							content: to
						}
						$.post('<?php echo $admin_url; ?>', data, function(response) {
							if(response != '0') {
								$('[data-builder="email_content"]').html( response );
							}
						});
						
					});
				});
				wp.customize('from_name',function( value ) {
					value.bind(function(to) {
						$('[data-builder="from_name"]').text( to ? to : '' );
					});
				});
				wp.customize('from_email',function( value ) {
					value.bind(function(to) {
						$('[data-builder="from_email"]').text( to ? to : '' );
					});
				});
				wp.customize('contact_info',function( value ) {
					value.bind(function(to) {
						$('[data-builder="contact_info"]').text( to ? to : '' );
					});
				});
				<?php if( in_array('BG_COLOR',$this->settings)) : ?>
					wp.customize('bg_color',function( value ) {
						value.bind(function(to) {
							$('[data-builder="bg"]').css( 'background-color', to ? to : '' );
						});
					});
				<?php endif; ?>
				<?php if( in_array('LINK_COLOR',$this->settings)) : ?>
					wp.customize('link_color',function( value ) {
						value.bind(function(to) {
							$('a[href]').css( 'color', to ? to : '' );
						});
					});
				<?php endif; ?>
				<?php if( in_array('BODY_COLOR',$this->settings)) : ?>
					wp.customize('body_color',function( value ) {
						value.bind(function(to) {
							$('[data-builder="body_color"]').css( 'color', to ? to : '' );
						});
					});
				<?php endif; ?>
				<?php if( in_array('BG_IMAGE',$this->settings)) : ?>				
					wp.customize('bg_image',function( value ) {
						
						value.bind(function(to) {
							$('[data-builder="bg"]').css( 'background-image', 'url(' + to + ')');
						});
					});
				<?php endif; ?>
			} )( jQuery )
		</script>
	<?php 
	}
	public function ajax_do_shortcodes() {
		echo stripslashes($this->prepare_preview($_POST['content'], true));
		die();
	}
	public function prepare_preview($content = '', $ajax = false) {
		global $email_newsletter;
		
		// Fix the tracker image from showing up in the preview
		$content = str_replace('{OPENED_TRACKER}','',$content);
		$content = str_replace('{UNSUBSCRIBE_URL}','#',$content);
		
		$date_format = (isset($this->settings['date_format']) ? $this->settings['date_format'] : "F j, Y");
		$content = str_replace( "{DATE}", date($date_format), $content );
		
		if($ajax == true) {
			$content = apply_filters('the_content',$content);
			
			$themedata = $this->find_builder_theme();
			$content = $email_newsletter->do_inline_styles($themedata, $content);
			
			// LINK COLOR
			$link_color = $email_newsletter->get_newsletter_meta($this->ID,'link_color', $email_newsletter->get_default_builder_var('link_color'));
			$link_color = apply_filters('email_newsletter_make_email_link_color',$link_color,$this->ID);
			$content = str_replace( "{LINK_COLOR}", $link_color, $content);
			$content = str_replace( "#linkcolor", $link_color, $content);
		}
			
		return $content;
	}
	public function print_preview_head() {
		do_action('builder_head');
	}
	function print_preview_footer() {
		do_action('wp_footer');
	}
	
	//builders shortcodes
	public function recent_posts_shortcode($atts){
	   extract(shortcode_atts(array(
	      'posts' => 1,
	   ), $atts));
	
	   $return_string = '<ul>';
	   query_posts(array('orderby' => 'date', 'order' => 'DESC' , 'showposts' => $posts));
	   if (have_posts()) :
	      while (have_posts()) : the_post();
	         $return_string .= '<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
	      endwhile;
	   endif;
	   $return_string .= '</ul>';
	
	   wp_reset_query();
	   return $return_string;
	}
	//wrapper for default wp gallery so it does not show up image that is destroying everything
	public function n_gallery_shortcode($attr) {
		return gallery_shortcode($attr);
	}
}
?>