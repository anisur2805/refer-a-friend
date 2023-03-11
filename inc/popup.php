<?php
add_action( 'wp_head', function(){
    if (!class_exists('WooCommerce')) return;
    if (is_checkout()) {
    ?>
    <h2>Hello world</h2>
    <div class="itc-av-wrapper">
        <div class="avwp-av-overlay itc-avwp-av-overlay" style="opacity: 0;"></div>
        <div class="avwp-av itc-avwp-av">
            <img src="http://testwp.test/wp-content/uploads/2022/09/Layer-0-min.png" alt="Age Verification">
            <h2>Age Verification</h2>
            <p>You must be <strong>18</strong> years old to enter.</p>
            <p><button class="yes">I am 18 or over</button><button class="no">I am under 18</button></p>
        </div>
    </div>

    <style>
        .itc-av-wrapper {
            display: none;
        }
    </style>
<?php
}
});