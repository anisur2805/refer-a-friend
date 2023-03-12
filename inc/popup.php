<?php
add_action( 'woocommerce_checkout_before_customer_details', 'add_popup_to_checkout_page');

function add_popup_to_checkout_page(){

    $imgLogo        = get_theme_mod( 'dav_logo' );
    $bgImage = get_theme_mod( 'dav_bgImage' );
    $minAge = get_theme_mod( 'dav_minAge', '18' ); 
    $title          = get_theme_mod( 'dav_title', esc_attr__( 'Age Verification', 'dispensary-age-verification' ) );

    $copy = get_theme_mod( 'dav_copy', esc_attr__( 'You must be 18 years old to enter.', 'dispensary-age-verification' ) );
    ?>
    <div class="itc-av-wrapper">
        <div class="avwp-av-overlay itc-avwp-av-overlay" style="opacity: 0;"></div>
        <div class="avwp-av itc-avwp-av">
            <div class="itc-first-part">
                <img src="<?php echo $imgLogo; ?>" alt="Age Verification">
                <h2><?php echo $title; ?></h2>
                <p><?php echo $copy; ?></p>
                <p><button class="yes">I am 18 or over</button><button class="no">I am under 18</button></p>
            </div>
            <div class="itc-second-part">
                <h2>Sorry!</h2>
                <p>You are not old enough to view the site ...</p>
            </div>
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

        .woocommerce-checkout .nectar-global-section.before-footer {
            z-index: 2;
        }

    </style>
<?php
}


add_action( 'woocommerce_review_order_after_submit', 'add_second_button_after_submit_button');
function add_second_button_after_submit_button() {
    echo '<button type="button" class="button second-btn wp-element-button" name="woocommerce_checkout_place_order" id="place_order" value="Secure Checkout" data-value="Secure Checkout">Secure Checkout</button>';
}