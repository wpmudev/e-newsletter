<?php
class Builder_TextArea_Control extends WP_Customize_Control {
	public $type = 'textarea';
	
	public function inject_js() {
		
	}
	
	public function render_content() {
		?>
		<label><span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span></label>
		<textarea rows="5" style="width:98%;" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
		<?php
	}
}