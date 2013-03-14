<?php

global $wp_customize, $email_newsletter, $email_builder, $current_user;
if(empty($wp_customize) || !$wp_customize->is_preview())
	die();

$email_data = $email_newsletter->get_newsletter_data($email_builder->ID);
$content = $email_newsletter->make_email_body($email_builder->ID);
get_template_part('header');
$content = $email_builder->prepare_preview($content);
echo $content;
get_template_part('footer');
?>