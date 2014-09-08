<?php
$view_newsletter_code = get_query_var( 'view_newsletter_code' );
$view_newsletter_send_id = get_query_var( 'view_newsletter_send_id' );

$result = $this->get_member_by_join_date($view_newsletter_code);
if($result['member_id'] > 0 || $result['wp_only_user_id'] > 0)
	$ok = 1;
else {
	$result = $this->get_member_id_by_code($view_newsletter_code);
	if($result['member_id'] > 0 || $result['wp_only_user_id'] > 0)
		$ok = 1;
}

if(isset($ok)) {
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
		$first_name = $this->get_firstname($member_data['wp_user_id'], $member_data['member_nicename']);
		$content = $this->personalise_email_body($content, $member_id, $wp_only_user_id, $view_newsletter_code, $member_data['unsubscribe_code'], $view_newsletter_send_id, array('user_name' => $user_name, 'first_name' => $first_name, 'to_email' => $member_data["member_email"]));
		echo $content;
	}
}

?>