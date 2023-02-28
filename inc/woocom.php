<?php
add_action('woocommerce_review_order_before_shipping', 'itc_add_ship_info', 4);
function itc_add_ship_info() {
    global $wpdb;
    global $woocommerce;

    $cart_total = $woocommerce->cart->total;
    $_cart_total = $cart_total;
    $points     = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT SUM(total_points) AS total_points FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d",
            get_current_user_id()
        )
    );

    // $points  = 500;
    $message = '';
    $points  = $points->total_points; // TODO: need to update

    if ($_cart_total >= 5) {
        if ($points >= 0) {
            $message .= "You have total {$points} points. Wanna use 500 points? <br/>";
            $message .= '<label for="itc_points_once">' . __('Yes, want to use ', 'itc-refer-a-friend') . '</label>' . "<input type='checkbox' value='1' id='itc_points_once' name='itc_points_once'/>";
        } else {
            $message .= 'No points available'; // TODO: need to empty this
        }
    } else {
        $message .= "buy some more, man"; // TODO: need to empty this
    }
    echo $message;
}

add_action('wp_footer', 'load_custom_scripts');
function load_custom_scripts() {
    if (is_checkout()) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("body").on("click", "#itc_points_once", function() {
                    jQuery('body').trigger('update_checkout');
                });
            });
        </script> <?php
    }
}

add_action('woocommerce_cart_calculate_fees', 'itc_discount_rewards_cost', 10, 1);
function itc_discount_rewards_cost($cart) {
    $discount = 5;

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
