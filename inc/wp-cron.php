<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// ================================ //
// Schedule an event to run update_referrer_points_after_10mins() function every 10 minutes
function schedule_referrer_points_update() {
    if ( !wp_next_scheduled( 'update_referrer_points_event' ) ) {
        wp_schedule_event( time(), 'every_10_seconds', 'update_referrer_points_event' );
    }
}

add_action( 'wp', 'schedule_referrer_points_update' );

// Define the custom cron interval
function add_custom_cron_intervals( $schedules ) {
    $schedules['every_10_seconds'] = array(
        'interval' => 10,
        'display'  => __( 'Every 10 Seconds' ),
    );
    return $schedules;
}

add_filter( 'cron_schedules', 'add_custom_cron_intervals' );

// Execute the update_referrer_points_after_10mins() function when the scheduled event fires
function update_referrer_points_cron_callback() {
    update_referrer_points_after_10mins();
}

add_action( 'update_referrer_points_event', 'update_referrer_points_cron_callback' );

// Update the referrer points after 10 minutes
function update_referrer_points_after_10mins() {
    global $wpdb;

    $table_name            = $wpdb->prefix . 'user_referred';
    $referrer_total_points = 500;
    $referred_by_user_id   = 42;

    $buy_min_5_pounds = check_referrer_purchase_minimum_5_pound();
    var_dump( $buy_min_5_pounds );
die( 'you must die' );
    $query = $wpdb->prepare(
        "UPDATE {$table_name}
        SET referrer_total_points = referrer_total_points + %d
        WHERE referred_by_user_id = %d
        AND updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)
        LIMIT 1",
        $referrer_total_points,
        $referred_by_user_id
    );

    $wpdb->query( $query );
}

update_referrer_points_after_10mins();

add_action( 'woocommerce_init', 'xyz' );
function xyz() {
    global $wpdb;

    $table_name            = $wpdb->prefix . 'user_referred';
    $referrer_total_points = 500;
    $referred_by_user_id   = 42;

    // $buy_min_5_pounds = check_referrer_purchase_minimum_5_pound();
    // var_dump( $buy_min_5_pounds ); 
    $query = $wpdb->prepare(
        "UPDATE {$table_name}
        SET referrer_total_points = referrer_total_points + %d
        WHERE referred_by_user_id = %d
        AND updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)
        LIMIT 1",
        $referrer_total_points,
        $referred_by_user_id
    );

    $wpdb->query( $query );
}

xyz();