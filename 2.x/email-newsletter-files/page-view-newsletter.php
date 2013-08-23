<?php
$view_newsletter_code = get_query_var( 'view_newsletter_code' );
$view_newsletter_send_id = get_query_var( 'view_newsletter_send_id' );

$result = $this->get_member_id_by_code($view_newsletter_code);
if($result['member_id'] > 0 || $result['wp_only_user_id'] > 0) {
	$member_id = $wp_only_user_id = 0;
	if($result['member_id'] > 0) {
		$member_id = $result['member_id'];
		$member_data = $this->get_member( $member_id );
	}
	elseif($result['wp_only_user_id'] > 0) {
		$wp_only_user_id = $result['wp_only_user_id'];
		$member_data = $this->get_wp_user_only( $wp_only_user_id );		
	}

	$send_details = $this->get_sent_email($view_newsletter_send_id, $member_id, $wp_only_user_id);

	if($send_details > 0) {
		$content = $send_details['email_body'];

		$user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);

		$content = $this->personalise_email_body($content, $member_id, $wp_only_user_id, $view_newsletter_code, $view_newsletter_send_id, array('user_name' => $user_name, 'member_email' => $member_data["member_email"], 'disable_view_link' => 1));
		?>
		<html lang="en">
		    <head>
		        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		        <title>Newsletter</title>
		    </head>
			<?php
				echo $content;
			?>
		</html>
		<?php
	}
}

?>