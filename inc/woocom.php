<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_checkout_before_order_review', 'itc_add_ship_info', 4);
function itc_add_ship_info() {
    global $wpdb;
    global $woocommerce;

    $cart_total = $woocommerce->cart->total;
    $_cart_total = $cart_total;
    $points     = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT SUM(accept_total_points) AS total_points FROM {$wpdb->prefix}user_referred WHERE accepted_user_id = %d",
            get_current_user_id()
        )
    );


    $message = '';
    $points  = $points->total_points; // TODO: need to update

    if ($_cart_total >= 5) {
        if ($points >= 500) {
            $message .= "You have total {$points} points. Wanna use 500 points? <br/>";
            $message .= '<label for="itc_points_once">' . __('Yes, want to use ', 'itc-refer-a-friend') . '</label>' . "<input type='checkbox' value='1' id='itc_points_once' name='itc_points_once'/>";
        }
    }
    echo $message;
}

add_action('wp_footer', 'load_custom_scripts');
function load_custom_scripts() {
    if (is_checkout()) {?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("body").on("click", "#itc_points_once", function() {
                    jQuery('body').trigger('update_checkout');
                });
            });
        </script><?php
    }
}

add_action('woocommerce_cart_calculate_fees', 'itc_discount_rewards_cost', 10, 1);
function itc_discount_rewards_cost($cart) {
    global $wpdb;
    $discount = 5;
    $user_id = get_current_user_id();

    if (!$_POST || (is_admin() && !is_ajax())) {
        return;
    }

    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
    } else {
        $post_data = $_POST;
    }

    if (!isset($post_data['itc_points_once'])) {
        return;
    }

    $cart->add_fee(__('Rewards Discount'), -$discount);
}


/**
 * Display custom message for newly registered user first time
 */
add_action( 'woocommerce_account_content', 'show_custom_message', 7 );
function show_custom_message() {
    global $wpdb;

    echo do_shortcode('[copy_to_clipboard]');
    echo do_shortcode('[email_share]');

    $referred_msg_show_once = get_user_meta( get_current_user_id(), 'referred_msg_show_once', true );
    if( empty($referred_msg_show_once) && "0" !== $referred_msg_show_once){
        add_user_meta( get_current_user_id(), 'referred_msg_show_once', 1 );
    }

    if( $referred_msg_show_once == "1" ) {
        return;
    }
    
    if ( !isset( $_SESSION['PHP_REFID'] ) ) {
        return;
    }

    $referred_id = idRandDecode( $_SESSION['PHP_REFID'] );

    $user_id   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}referral_links WHERE id = %d", (int) $referred_id[0]
        )
    );

    $user_name   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}users WHERE id = %d", (int) $user_id->user_id
        )
    );

    
    $class        = 'ic-referred-message';
    $id           = 'ic-referred-message';
    $message      = "You just been referred by <strong>{$user_name->display_name}</strong>. You both receive £5. Once you register, you can do the same and get another £5 for every person you refer.";
    $allowed_html = array( 'strong' => array() );
    printf( '<div class="%1$s" id="%2$s"><p>%3$s</p></div>', esc_attr( $class ), esc_attr( $id ), wp_kses( $message, $allowed_html ) );
}

/**
 * Update logged-in user total poins
 */
// itc_update_user_total_points_after_uses();
add_action( 'woocommerce_checkout_update_order_meta', 'itc_update_user_total_points_after_uses' );
function itc_update_user_total_points_after_uses() {
    global $wpdb;
    $id         = get_current_user_id();
    $table_name = $wpdb->prefix . 'user_referred';
    $points     = $wpdb->get_var( $wpdb->prepare( "SELECT accept_total_points FROM $table_name WHERE accepted_user_id = %d", $id ) );

    if( $points >= 500 ) {
        $wpdb->update(
            $table_name,
            array(
                'accept_total_points' => $points -500,
                'updated_at'          => date('Y-m-d H:i:s'),
            ),
            array('accepted_user_id' => $id),
            array('%s', '%s' ),
            array('%s')
        );
    }

}

/**
 * Check is the referred person buy minimum 5 pound excluding taxes, 
 */
function check_referrer_purchase_minimum_5_pound() {
    global $wpdb;

    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        return;
    }

    $user_id = $wpdb->query(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = 45 AND accepted_user_id != %d", 46
        )
    );

    $customer_orders = wc_get_orders( array(
        'customer_id' => 42,
        'status' => array( 'wc-completed', 'wc-processing' )
    ) );
    $total_spent = 0;
    foreach ( $customer_orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            if ( $item instanceof WC_Order_Item_Product ) {
                $total_spent += $item->get_total();
            }
        }
    }
    return wc_price($total_spent);
}