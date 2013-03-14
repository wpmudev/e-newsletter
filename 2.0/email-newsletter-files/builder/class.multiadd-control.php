<?php
class Builder_MultiAdd_Control extends WP_Customize_Control {
	public $type = 'tinymce';

	public function render_content() {
		?><select>
			<option>Recent Posts</option>
		</select>
		<a href="#">Add</a>
		<?php
	}
	
	public function enqueue() {
		
	}
}
?>