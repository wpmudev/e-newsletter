<?php
global $fusion_slider;
if(isset($fusion_slider)) {
	remove_action( 'plugins_loaded', array( 'FusionCore_Plugin', 'get_instance' ) ); //it might be too late
	remove_action( 'after_setup_theme', array( 'Fusion_Core_PageBuilder', 'get_instance' ) );
	remove_action( 'init', array( $fusion_slider, 'init' ) );
}
add_filter( 'black_studio_tinymce_enable', '__return_false' );

define('NGG_DISABLE_RESOURCE_MANAGER', true);