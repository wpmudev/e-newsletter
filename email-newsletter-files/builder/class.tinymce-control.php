<?php
class Builder_TinyMCE_Control extends WP_Customize_Control {
	public $type = 'tinymce';
	
	
	public function render_content() {
		global $enewsletter_tinymce;
		?>
		<span class="customize-control-title"><?php echo $this->label; ?></span>
		<textarea id="<?php echo $this->id; ?>" style="display:none" <?php echo $this->link(); ?>><?php echo esc_textarea($this->value()); ?></textarea>
		<?php
		echo $enewsletter_tinymce;
		?>
		
		<script type="text/javascript">
			jQuery(document).ready( function() {
				var content = 0;
				// Our tinyMCE function to fire on every change
				tinymce_check_changes = setInterval(function() {
						var check_content = tinyMCE.activeEditor.getContent({format : 'raw'});
						
						if(check_content != content && check_content != '<p><br data-mce-bogus="1"></p>') {
							content = check_content;

							jQuery('#<?php echo $this->id; ?>').val(content).trigger('change');
						}
				}, 2000);

				//enables resizing of email content box
				var resize;
				var prev_emce_width = 0;
				jQuery('#accordion-section-builder_email_content').on('mousedown', '.mce-i-resize, #content_tinymce_resize', function(){
					resize_start();
				});
				jQuery('#accordion-section-builder_email_content h3').click(function(){
					resize_start();
				});
				jQuery("body").mouseup(function() {
				    clearInterval(resize);
				});

				function resize_start() {
				    resize = setInterval(function() {
						emce_width = jQuery('#content_tinymce_ifr').width()+65;
						
						if(emce_width >= '490' && emce_width != prev_emce_width) {
						    jQuery('#customize-controls').css("-webkit-animation", "none");
						    jQuery('#customize-controls').css("-moz-animation", "none");
						    jQuery('#customize-controls').css("-ms-animation", "none");
						    jQuery('#customize-controls').css("animation", "none");
							prev_emce_width = emce_width;
							jQuery('#customize-controls, #customize-footer-actions').css("width", emce_width+"px");
							jQuery('.wp-full-overlay').css("margin-left", emce_width+"px");
							jQuery('.wp-full-overlay-sidebar').css("margin-left", "-"+emce_width+"px");
						}
				    },50);	
				}
			});
		</script>
		<?php
	}
}
?>