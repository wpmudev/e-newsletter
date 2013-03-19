<?php 
global $wp_customize, $email_newsletter, $email_builder, $current_user;

if(empty($wp_customize) || !$wp_customize->is_preview())
	die();
?>

<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Minimise</title>
        <?php $email_builder->print_preview_head(); ?>
    </head>
    <body marginheight="0" topmargin="0" marginwidth="0" leftmargin="0">
	<?php
	
	$email_data = $email_newsletter->get_newsletter_data($email_builder->ID);
	$content = $email_newsletter->make_email_body($email_builder->ID);
	
	$content = $email_builder->prepare_preview($content);
	echo $content;
	
	$email_builder->print_preview_footer(); 
	
	?>
    </body>
</html>