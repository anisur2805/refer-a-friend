<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

function schedule_referrer_points_update() {
    if ( !wp_next_scheduled( 'update_referrer_points_event' ) ) {
        wp_schedule_event( time(), 'every_10_seconds', 'update_referrer_points_event' );
    }
}
add_action( 'wp', 'schedule_referrer_points_update' );

function add_custom_cron_intervals( $schedules ) {
    $schedules['every_10_seconds'] = array(
        'interval' => 10,
        'display'  => __( 'Every 10 Seconds' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'add_custom_cron_intervals' );

function update_referrer_points_cron_callback() {
    update_referrer_points_after_10mins();
}
add_action( 'update_referrer_points_event', 'update_referrer_points_cron_callback' );

add_action('wp', 'update_referrer_points_after_10mins');
function update_referrer_points_after_10mins() {
    global $wpdb;

    // wp_insert_post( ['post_title' => 'Hello world abcd']);

    /**
     *  
    * check_referrer_purchase_minimum_5_pound(); 
    */
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        return;
    }

    $uId = get_current_user_id();
    $user_id = $wpdb->get_results(
        "SELECT id, referrer_total_points FROM {$wpdb->prefix}user_referred WHERE referrer_total_points != 0 and accepted_user_id = {$uId}"
    );
 
    $customer_orders = wc_get_orders( [
        'customer_id' => $uId,
        'status'      => [ 'wc-completed', 'wc-processing' ]
    ] );

    $buy_amount = 0;
    foreach ( $customer_orders as $order ) {
        $sub_total = $order->get_subtotal();
        $discount_total = $order->get_total_discount();
        $tax_total = $order->get_total_tax();
        $shipping_total = $order->get_shipping_total();
        $order_total = $sub_total - $discount_total + $tax_total + $shipping_total;
        $buy_amount += $order_total;
    }

    $refer_first_time = get_user_meta( $uId, 'is_first_time_refer', true );

   // Check if the buy amount is greater than or equal to 5
   if ( $buy_amount >= 5 ) {
    if( empty( $refer_first_time ) ) {
           $table_name            = $wpdb->prefix . 'user_referred';
           $referrer_total_points = 500;

           $query = $wpdb->prepare(
           "UPDATE {$table_name}
               SET referrer_total_points = %d
               WHERE accepted_user_id = %d
               AND updated_at < ( DATE_SUB( NOW(), INTERVAL 10 SECOND ) )
               LIMIT 1",
               $referrer_total_points,
               $uId
           );
           
           $wpdb->query( $query );

           update_user_meta( $uId, 'is_first_time_refer', 'yes' );
       }
   }

   return wc_price($buy_amount);
}