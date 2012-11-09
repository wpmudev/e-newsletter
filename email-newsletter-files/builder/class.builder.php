<?php

class Email_Newsletter_Builder  {
	
	var $theme = '';
	var $ID = '';
	var $settings = array();
	
	function Email_Newsletter_Builder() {
		add_action( 'plugins_loaded', array( &$this, 'plugins_loaded'), 999 );
		add_action( 'wp_ajax_builder_do_shortcodes', array( &$this, 'ajax_do_shortcodes' ) );
		add_shortcode('recent-posts', array( &$this, 'recent_posts'));
	}
	function parse_theme_settings() {
		global $email_newsletter;
		$filename = $email_newsletter->plugin_dir . 'email-newsletter-files/templates/' . $this->get_builder_theme() . '/template.html';
		$handle = fopen($filename,'r');
		$contents = fread($handle,filesize($filename));
		fclose($handle);
		
		$pattern = '/\{(?P<name>\w*)\}/';
		$matches = array();
		$match_count = preg_match_all($pattern,$contents,$matches);
		
		$this->settings = $matches['name'];
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
		register_theme_directory($this->template_directory);
		
		// Start the id at false for checking
		$builder_id = false;
		
		if(isset($_REQUEST['newsletter_id'])) {
			if(is_numeric($_REQUEST['newsletter_id'])){
				$builder_id = $_REQUEST['newsletter_id'];
				delete_transient('builder_email_id_'.$current_user->ID);
				set_transient('builder_email_id_'.$current_user->ID, $builder_id);
			} else {
				// We pass an empty array to create a new newsletter and get our ID
				$builder_id = $this->save_builder(array());
				delete_transient('builder_email_id_'.$current_user->ID);
				set_transient('builder_email_id_'.$current_user->ID, $builder_id);
				wp_redirect($this->generate_builder_link($builder_id));
				exit;
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
		
		
		/* add_action( 'admin_print_footer_scripts', array( __CLASS__, 'editor_js'), 50 );
				add_action( 'admin_footer', array( __CLASS__, 'enqueue_scripts'), 1 );
			} else {
				add_action( 'wp_print_footer_scripts', array( __CLASS__, 'editor_js'), 50 );
				add_action( 'wp_footer', array( __CLASS__, 'enqueue_scripts'), 1 ); */
		
		
		
	}
	function customize_controls_print_scripts() {
		do_action('admin_enqueue_scripts');
		do_action('email_newsletter_template_builder_print_scripts');
	}
	function customize_controls_print_footer_scripts() {
		
		do_action('admin_print_footer_scripts');
		do_action('admin_footer');
		
		// Collect other theme info so we can allow changes
		$themes = wp_get_themes();
		
		foreach($themes as $key => $theme) {
			if($theme->theme_root != $this->template_directory)
				unset($themes[$key]);
		}
		?><script type="text/javascript">
			_wpCustomizeControlsL10n.save = "<?php _e('Save Newsletter','email-newsletter'); ?>";
			ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
			var current_theme = "<?php echo $_GET['theme']; ?>";
			email_templates = [
				<?php foreach($themes as $theme): ?>
				{	"name": "<?php echo $theme->get('Name'); ?>", 
					"description": "<?php echo $theme->get('Description'); ?>",
					"screenshot": "<?php echo $theme->get_screenshot(); ?>",
					"stylesheet": "<?php echo $theme->stylesheet; ?>",
				},
				<?php endforeach; ?>
			];
			var _info = jQuery('#customize-info .customize-section-content');
			_info
				.prepend('<a href="#" class="arrow left" />')
				.prepend('<a href="#" class="arrow right" />')
				.prepend('<a href="#" class="change_theme">Change Theme</a>');
			jQuery.each(email_templates, function(i,e) {
				var clone = _info.clone().addClass('hidden');
				if(e.screenshot != _info.find('img.theme-screenshot').attr('src')) {
					clone.find('img.theme-screenshot').attr('src',e.screenshot);
					clone.find('.theme-description').text(e.description);
					clone.data('theme',e);
					jQuery('#customize-info').append(clone);
				} else {
					// Use this opportunity to change the theme preview area
					var current_name = jQuery('#customize-info .preview-notice .theme-name');
					jQuery('#customize-info .preview-notice').html('Click to change theme').prepend(current_name);
					_info.data('theme',e);
				}
				
			});
			
			jQuery('.arrow').on('click', function() {
				var _this = jQuery(this);
				var next = _this.parent().next('.customize-section-content');
				var prev = _this.parent().prev('.customize-section-content');
				
				if(_this.hasClass('left')) {
					if(prev.length > 0) {
						_this.parent().addClass('hidden');
						prev.removeClass('hidden');
						var data = prev.data('theme');
						console.log(data);
					}
				} else {
					if(next.length > 0) {
						_this.parent().addClass('hidden');
						next.removeClass('hidden');
						var data = next.data('theme');
						console.log(data);
					}
				}
				return false;
			});
			jQuery('.change_theme').on('click', function() {
				var data = jQuery(this).parent().data('theme');
				
				if( typeof data != 'undefined') {
					// Use string replace to redirect the url
					jQuery('[data-customize-setting-link="template"]').val(data.stylesheet).trigger('change');
				}
				return false;
			});
			wp.customize.bind( 'saved', function() {
				var new_theme = jQuery('[data-customize-setting-link="template"]').val();
				if(current_theme != new_theme)
					window.location.href = window.location.href.replace('theme='+current_theme,'theme='+new_theme)
			});
			
		</script>
		
		<style type="text/css">
			.wp-full-overlay {
				z-index: 15000;
			}
			#TB_overlay, #TB_window {
				z-index: 16000!important;
			}
			.customize-section.open .customize-section-content.hidden {
				display: none;
			}
			.arrow {
				width: 20px;
				height: 20px;
				display: block;
				position: absolute;
				background: #007CBD; 
			}
			.arrow.right {
				right: 35px;
				top: 200px;
			}
			.arrow.left {
				left: 35px;
				top: 200px;
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
			return $data['template'];
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
		
		
		// Load our extra control classes
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.tinymce-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.textarea-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.multiadd-control.php');
		require_once($email_newsletter->plugin_dir . 'email-newsletter-files/builder/class.hidden-control.php');
		
		if( in_array('HEADER_IMAGE', $this->settings)) {
			$instance->add_section( 'header_image', array(
				'title'          => __('Header Image','email-newsletter'),
				'priority'       => 37,
			) );
			$instance->add_setting( 'header_image', array(
				//'subject'        => '',
				//'capability' => NULL,
				'default' => (defined('BUILDER_DEFAULT_HEADER_IMAGE') ? BUILDER_DEFAULT_HEADER_IMAGE : ''),
				'type' => 'newsletter_save'
			) );
			$instance->add_control( new WP_Customize_Image_Control( $instance, 'header_image', array(
				'label'   => __('Header Image','email-newsletter'),
				'section' => 'header_image',
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
		
		if( in_array('BG_COLOR', $this->settings) || in_array('LINK_COLOR', $this->settings) ) {
			
			$instance->add_section( 'builder_colors', array(
				'title' => __('Colors','email-newsletter'),
				'priority' => 39
			) );
			
			if( in_array('BG_COLOR', $this->settings) ) {
				$instance->add_setting( 'bg_color', array(
					//'subject'        => '',
					//'capability' => NULL,
					'default' => (defined('BUILDER_DEFAULT_BG_COLOR') ? BUILDER_DEFAULT_BG_COLOR : '#FFF'),
					'type' => 'newsletter_save'
				) );
				$instance->add_control( new WP_Customize_Color_Control( $instance, 'bg_color', array(
					'label'        => __('Background Color', 'email-newsletter' ),
					'section'    => 'builder_colors',
					'settings'   => 'bg_color',
				) ) );
			}
			
			if( in_array('LINK_COLOR', $this->settings) ) {
				$instance->add_setting( 'link_color', array(
					//'subject'        => '',
					//'capability' => NULL,
					'default' => (defined('BUILDER_DEFAULT_LINK_COLOR') ? BUILDER_DEFAULT_LINK_COLOR : '#4CA6C'),
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
				'default' => '',
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
		
		// Setup Settings
		$instance->add_setting( 'template', array(
			'default' => 'iletter',
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'subject', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('Email Subject','email-newsletter'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'from_name', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('From Name','email-newsletter'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'from_email', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('From Email','email-newsletter'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'bounce_email', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('bounce@email.com','email-newsletter'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'email_content', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('Insert Content Here','email-newsletter'),
			'type' => 'newsletter_save'
		) );
		$instance->add_setting( 'contact_info', array(
			//'subject'        => '',
			//'capability' => NULL,
			'default' => __('Contact Info Goes Here','email-newsletter'),
			'type' => 'newsletter_save',
		) );
		
		
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
		$instance->add_control( new Builder_TextArea_Control( $instance, 'contact_info', array(
			'label'   => __('Contact Info','email-newsletter'),
			'section' => 'builder_email_content',
			'settings'   => 'contact_info',
		) ) );
		
		
		
		if ( $instance->is_preview() && !is_admin() )
			add_action( 'wp_footer', array( &$this, 'email_builder_customize_preview'), 21);

		$instance->get_setting('email_subject')->transport='postMessage';
		$instance->get_setting('email_content')->transport='postMessage';
		$instance->get_setting('from_name')->transport='postMessage';
		$instance->get_setting('from_email')->transport='postMessage';
		$instance->get_setting('contact_info')->transport='postMessage';
		$instance->get_setting('bg_color')->transport='postMessage';
		$instance->get_setting('link_color')->transport='postMessage';
		$instance->get_setting('header_image')->transport='postMessage';
		
		// Add all the filters we need for all the settings to save and be retreived
		add_action( 'customize_save_subject', array( &$this, 'save_builder') );
		add_filter( 'customize_value_subject', array( &$this, 'get_builder_value') );
		add_filter( 'customize_value_from_name', array( &$this, 'get_builder_value') );
		add_filter( 'customize_value_from_email', array( &$this, 'get_builder_value') );
		add_filter( 'customize_value_email_title', array( &$this, 'get_builder_email_title') );
		add_filter( 'customize_value_email_content', array( &$this, 'get_builder_email_content') );
		add_filter( 'customize_value_contact_info', array( &$this, 'get_builder_value') );
		add_filter( 'customize_value_bg_color', array( &$this, 'get_builder_bg_color') );
		add_filter( 'customize_value_link_color', array( &$this, 'get_builder_link_color') );
		add_filter( 'customize_value_header_image', array( &$this, 'get_builder_header_image') );
		
	}

	function save_builder($new_values = false) {
		global $email_newsletter;
		
		$data = array();
		$default = array(
			'newsletter_template' => 'iletter',
			'subject' => '',
			'content_ecoded' => '',
			'contact_info' => '',
			'from_name' => '',
			'from_email' => '',
			'bounce_email' => '',
			'meta' => array(),
		);
		
		if(!$new_values)
			$new_values = json_decode(stripslashes($_POST['customized']), true);
			
		if(isset($new_values['template']) )
			$data['newsletter_template'] = $new_values['template'];
		
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
		
		if(isset($new_values['header_image']))
			$data['meta']['header_image'] = $new_values['header_image'];

		
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
	function get_builder_header_image($default) {
		global $builder_id, $email_newsletter;
		
		$header_image = $email_newsletter->get_newsletter_meta($builder_id,'header_image');
		if(!empty($header_image))
			return $header_image;
		else
			return $default;
	}

	function get_builder_value($default) {
		global $builder_id, $email_newsletter;
		$data = $email_newsletter->get_newsletter_data($builder_id);

		switch($default) {
			case __('Email Subject','email-newsletter'):
				if(!empty($data['subject']))
					return $data['subject'];
				else
					return $default;
			break;
			case __('From Name','email-newsletter'):
				if(!empty($data['from_name']))
					return $data['from_name'];
				else
					return $default;
			break;
			case __('From Email','email-newsletter'):
				if(!empty($data['from_email']))
					return $data['from_email'];
				else
					return $default;
			break;
			case __('bounce@email.com','email-newsletter'):
				if(!empty($data['bounce_email']))
					return is_email($data['bounce_email']);
				else
					return $default;
			break;
			case __('Email Title','email-newsletter'):
				$title = $email_newsletter->get_newsletter_meta($builder_id,'email_title');
				if(!empty($title))
					return $title;
				else
					return $default;
			break;
			case __('Insert Content Here','email-newsletter'):
				if(!empty($data['content']))
					return $data['content'];
				else
					return $default;
			break;
			case __('Contact Info Goes Here','email-newsletter'):
				if(!empty($data['contact_info']))
					return $data['contact_info'];
				else
					return $default;
			break;
			case __('Your Image','email-newsletter'):
				$header_img = $email_newsletter->get_newsletter_meta($builder_id,'header_image');
				if(!empty($header_img))
					return $header_img;
				else
					return $default;
			break;
			default:
				return apply_filters('email_newsletter_get_value', $default);
			break;
		}
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
						var data = {
							action: 'builder_do_shortcodes',
							content: to,
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
							$('[data-builder="bg_color"]').css( 'background-color', to ? to : '' );
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
				<?php if( in_array('HEADER_IMAGE',$this->settings)) : ?>
					wp.customize('header_image',function( value ) {
						value.bind(function(to) {
							$('[data-builder="header_image"]').attr( 'src', to ? to : '' );
						});
					});
				<?php endif; ?>
			} )( jQuery )
		</script>
	<?php 
	}
	public function ajax_do_shortcodes() {
		if(!defined('DOING_AJAX') || DOING_AJAX != true || !isset($_POST['content']))
			die();
		
		echo stripslashes($this->prepare_preview($_POST['content']));
		die();
		
	}
	public function recent_posts($atts){
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
	public function prepare_preview($content = '') {
		// Fix the tracker image from showing up in the preview
		$content = str_replace('{OPENED_TRACKER}','',$content);
		$content = str_replace('{UNSUBSCRIBE_URL}','#',$content);
		return apply_filters('the_content',$content);
	}
	public function print_preview_head() {
		?>
		<style type="text/css">
		    a:link, a, a:visited{color:#eebb00;}
		    a:hover { text-decoration: none !important; color:#eebb00;}
		    h2, h3, h4, h5, h6{padding: 0px 0px 10px 0px;margin:0px;}
			.alignleft { display: inline; float: left; margin-right: 1.625em; }
			.alignright { display: inline; float: right; margin-left: 1.625em; }
			.aligncenter { clear: both; display: block;	margin-left: auto; margin-right: auto; }
			img.alignleft, img.alignright, img.aligncenter { margin-bottom: 1.625em; }
			.alignleft {
				float: left;
			}
			.alignright {
				float: right;
			}
			.aligncenter {
				display: block;
				margin-left: auto;
				margin-right: auto;
			}
			.entry-content img,
			.comment-content img,
			.widget img,
			img.header-image,
			.author-avatar img,
			img.wp-post-image {
				/* Add fancy borders to all WordPress-added images but not things like badges and icons and the like */
				border-radius: 3px;
				box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
			}
			.wp-caption {
				max-width: 100%; /* Keep wide captions from overflowing their container. */
				padding: 4px;
			}
			.wp-caption .wp-caption-text,
			.gallery-caption,
			.entry-caption {
				font-style: italic;
				font-size: 12px;
				font-size: 0.857142857rem;
				line-height: 2;
				color: #777;
			}
			img.wp-smiley,
			.rsswidget img {
				border: 0;
				border-radius: 0;
				box-shadow: none;
				margin-bottom: 0;
				margin-top: 0;
				padding: 0;
			}
			.entry-content dl.gallery-item {
				margin: 0;
			}
			.gallery-item a,
			.gallery-caption {
				width: 90%;
			}
			.gallery-item a {
				display: block;
			}
			.gallery-caption a {
				display: inline;
			}
			.gallery-columns-1 .gallery-item a {
				max-width: 100%;
				width: auto;
			}
			.gallery .gallery-icon img {
				height: auto;
				max-width: 90%;
				padding: 5%;
			}
			.gallery-columns-1 .gallery-icon img {
				padding: 3%;
			}
		</style>
		<?php
		do_action('builder_head');
		
	}
	function print_preview_footer() {
		do_action('wp_footer');
	}
}
?>