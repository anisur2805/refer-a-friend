<?php
 add_action( 'woocommerce_review_order_before_shipping', 'itc_add_ship_info', 5 );

 function itc_add_ship_info() {
  global $wpdb;
  global $woocommerce;

  $cart_total = $woocommerce->cart->total;
  $points     = $wpdb->get_row(
   $wpdb->prepare(
    "SELECT SUM(total_points) AS total_points FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d", get_current_user_id()
   )
  );
  $message = '';
  $points  = $points->total_points;

//   woocommerce_form_field( 'vat', array(
//     'type'        => 'text',
//     'required'    => true,
//     'label'       => 'VAT',
//     'description' => 'Please enter your VAT',
// ), $checkout->get_value( 'vat' ) );

  if ( $cart_total >= 5 ) {
   if ( $points >= 500 ) {
    $message .= "You have total {$points} points. Wanna use 500 points? <br/>";
    $message .= '<label for="itc_use_one_points">' . __( 'Yes, want to use ', 'itc-refer-a-friend' ) . '</label>' . "<input type='checkbox' value='1' id='itc_use_one_points' name='itc_use_one_points'/>";
   } else {
    $message .= 'No points available'; // TODO: need to empty this
   }
  } else {
   $message .= "buy some more, man";
  }
  echo $message;

 }

 // add_action( 'wp_ajax_update_order_total', 'update_order_total' );
 // add_action( 'wp_ajax_nopriv_update_order_total', 'update_order_total' );
 function update_order_total() {

  // Get the current order object
  $order = wc_get_order( get_the_ID() );

  // Get the use_custom_points parameter from the AJAX request
  $use_custom_points = $_POST['use_custom_points'];

  // Get the order total
  $order_total = $order->get_total();

  // Calculate the new order total based on the custom points
  if ( $use_custom_points == 1 ) {
   // Calculate the new total using the custom points
   $new_total = $order_total - $custom_points_value;
  } else {
   // Use the original order total
   $new_total = $order_total;
  }

  // Update the order total with the new total
  $order->set_total( $new_total );
  $order->save();

  // Get the updated order total HTML
  $order_total_html = $order->get_formatted_order_total();

  // Return the updated order total HTML as the AJAX response
  wp_send_json( $order_total_html );

  // Make sure to exit the function to prevent any extra output
  exit();

 }

 // -=======================================
 add_action( 'woocommerce_review_order_before_submit', 'itc_add_custom_checkbox' );

 function itc_add_custom_checkbox() {
  echo '<input type="checkbox" name="custom_checkbox" id="custom_checkbox" value="1"> Custom Checkbox';
 }

 add_action( 'wp_footer', 'itc_custom_checkbox_script' );

 function itc_custom_checkbox_script() {
    if ( is_checkout() ) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#itc_use_one_points').click(function() {
                    $('body').trigger('update_checkout');
                    if ($(this).is(':checked')) {
                        var new_total = <?php echo WC()->cart->total * 1.1; ?>;
                    } else {
                        var new_total = <?php echo WC()->cart->total; ?>;
                    }
                    $('body').trigger('update_checkout');
                });
            });
        </script>
        <?php
    }
}


add_action( 'woocommerce_cart_calculate_fees', 'ict_calculate_fees' );

function ict_calculate_fees(){
    global $woocommerce;
    
    if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        return;
    }

    if ( isset( $_POST['post_data'] ) ) {
        parse_str( $_POST['post_data'], $post_data );
    } else {
        $post_data = $_POST;
    }

    if ( ! isset( $post_data['itc_use_one_points'] ) ) {
        return;
    }

    $deduct = ($woocommerce->cart->cart_contents_total - 5);
    $deduct = $woocommerce->cart->get_cart_total() - 5;
    echo $deduct;
    // $woocommerce->cart->add_fee( __( 'Has points' ), $deduct );
}