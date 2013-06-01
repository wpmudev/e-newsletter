<?php
$view_newsletter_code = get_query_var( 'view_newsletter_code' );
$view_newsletter_send_id = get_query_var( 'view_newsletter_send_id' );

$member =  $this->get_member_id_by_code($view_newsletter_code);
if($member > 0) {
	$member_id = $member['member_id'];
	$member_data = $this->get_member( $member_id );

	$send_details = $this->get_sent_email($view_newsletter_send_id, $member_id);

	if($send_details > 0) {
		$content = $send_details['email_body'];

		$user_name = $this->get_nicename($member_data['wp_user_id'], $member_data['member_nicename']);

		$content = $this->personalise_email_body($content, $member_id, $view_newsletter_code, $view_newsletter_send_id, array('user_name' => $user_name, 'member_email' => $member_data["member_email"], 'disable_view_link' => 1));

		echo $content;
	}
}

?>