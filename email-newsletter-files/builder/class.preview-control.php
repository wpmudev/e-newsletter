<?php
class Builder_Preview_Control extends WP_Customize_Control {
	public $type = 'email_preview';

	public function render_content() {
		$default = isset($this->default) ? $this->default : '';

		?>
		<span class="customize-control-title"><?php echo $this->label; ?></span>
		<p><input id="previewEmail" type="text" <?php $this->link(); ?> value="<?php echo $default; ?>" placeholder="email@yourdomain.com" /></p>
		<button id="sendPreview" style="width: 100%; text-align: center;" class="button button-primary" href="#"><?php _e('Send Preview','email-newsletter'); ?></button>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				var previewButton = $('#sendPreview'),
					originalText = previewButton.text();

				function sendpreview()
				{
					jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        beforeSend: function() {
                        	previewButton.text("<?php _e('Sending...','email-newsletter') ?>").attr('disabled','disabled');
                        },
                        data: {
                        	action: "send_email_preview",
                        	newsletter_id: "<?php echo (isset($_REQUEST['newsletter_id']) ? $_REQUEST['newsletter_id'] : ''); ?>",
                        	preview_email: $('#previewEmail').val(),
                        },
                        success: function(html){
                        	previewButton.text("<?php _e('Sending...','email-newsletter') ?>");
                        	alert( html );
                            previewButton.text(originalText).removeProp('disabled');
                        },
                        error: function(data) {
                        	alert(data);
                        }
                     });
				}

				$('#sendPreview').on('click', function() {
					if($("#save").is(":disabled"))
						var is_saved = false;
					else
						var is_saved = true;

					if(is_saved) {
						if(confirm("<?php _e('Do you want to save newsletter before sending? It is needed to see latest changes.','email-newsletter'); ?>")) {
							jQuery("#save").click();
							var fix = setInterval(function() {
										if($("#save").is(":disabled")) {
											sendpreview();
											clearInterval(fix);
										}
									}, 150);
							return false;
						}
						else {
							sendpreview();
							return false;
						}
					}
					else {
						sendpreview();
						return false;
					}
				});
			});
		</script>
		<?php
	}
}