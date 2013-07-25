<?php
$member_id = get_query_var( 'unsubscribe_member_id' );
$unsubscribe_code = get_query_var( 'unsubscribe_code' );

global $wpdb;

if ( $this->unsubscribe( $unsubscribe_code, false ) ) {
    echo "<center><br /><br /><br /><h2 style='color: #19700A;'>" . __( 'You are successfully unsubscribed!', 'email-newsletter' ) . "</h2></center>";
	exit;
}
else {
	echo "<center><br /><br /><br /><h2 style='color: #ff0000;'>" . __( 'You are already unsubscribed or are not subscribed yet!', 'email-newsletter' ) . "</h2></center>";
	exit;
}
?>