<?php
add_action( 'woocommerce_checkout_before_customer_details', 'add_popup_to_checkout_page');

function add_popup_to_checkout_page(){

    $imgLogo        = get_theme_mod( 'dav_logo' );
    $bgImage = get_theme_mod( 'dav_bgImage' );
    $minAge = get_theme_mod( 'dav_minAge', '18' ); 
    $title          = get_theme_mod( 'dav_title', esc_attr__( 'Age Verification', 'dispensary-age-verification' ) );

    $copy = get_theme_mod( 'dav_copy', esc_attr__( 'You must be 18 years old to enter.', 'dispensary-age-verification' ) );
    $btnYes = get_theme_mod( 'dav_button_yes', esc_attr__( 'YES', 'dispensary-age-verification' ) );
    //     'btnNo'          => get_theme_mod( 'dav_button_no', esc_attr__( 'NO', 'dispensary-age-verification' ) ),
    //     'successTitle'   => esc_attr__( 'Success!', 'dispensary-age-verification' ),
    //     'successText'    => esc_attr__( 'You are now being redirected back to the site ...', 'dispensary-age-verification' ),
    //     'successMessage' => get_theme_mod( 'dav_success_message' ),
    //     'failTitle'      => esc_attr__( 'Sorry!', 'dispensary-age-verification' ),
    //     'failText'       => esc_attr__( 'You are not old enough to view the site ...', 'dispensary-age-verification' ),
    //     'messageTime'    => get_theme_mod( 'dav_message_display_time' ),
    //     'redirectOnFail' => $redirectOnFail,
    //     'beforeContent'  => $beforeContent,
    //     'afterContent'   => $afterContent,
    // );  
    ?>
    <div class="itc-av-wrapper">
        <div class="avwp-av-overlay itc-avwp-av-overlay" style="opacity: 0;"></div>
        <div class="avwp-av itc-avwp-av">
            <img src="<?php echo $imgLogo; ?>" alt="Age Verification">
            <h2><?php echo $title; ?></h2>
            <p><?php echo $copy; ?></p>
            <p><button class="yes">I am 18 or over</button><button class="no">I am under 18</button></p>
        </div>
    </div>

    <style>
        .itc-av-wrapper {
            display: none;
        }

        .itc-av-wrapper .avwp-av.itc-avwp-av {
            left: 50%;
            transform: translateX(-50%);
            top: 120px;
        }

        .itc-av-wrapper.active-popup {
            display: block !important;
        }

        .itc-av-wrapper.active-popup .avwp-av-overlay.itc-avwp-av-overlay {
            opacity: 1 !important;
        }

        .itc-av-wrapper.active-popup .avwp-av.itc-avwp-av {
            opacity: 1 !important;
        }

        #place_order.button.alt.wp-element-button:not(.second-btn) {
            display: none !important;
        }

    </style>
<?php
}


add_action( 'woocommerce_review_order_after_submit', 'add_second_button_after_submit_button');
function add_second_button_after_submit_button() {
    echo '<button type="button" class="button second-btn wp-element-button" name="woocommerce_checkout_place_order" id="place_order" value="Secure Checkout" data-value="Secure Checkout">Secure Checkout</button>';
}