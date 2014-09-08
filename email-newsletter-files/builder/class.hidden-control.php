<?php
class Builder_Hidden_Control extends WP_Customize_Control {
	public $type = 'hidden';
	
	public function render_content() {
		?>
		<input type="hidden" <?php $this->link(); ?> value="<?php echo esc_textarea( $this->value() ); ?>" />
		<?php
	}
}