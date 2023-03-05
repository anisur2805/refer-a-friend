<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

/**
 * Clear schedule during uninstall time
 */
register_uninstall_hook( __FILE__, 'itc_uninstall_hook' );
function itc_uninstall_hook() {
    wp_clear_scheduled_hook( 'update_referrer_points_event' );
}