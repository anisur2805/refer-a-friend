<?php
/**
 * Copy to Clipboard
 */
add_shortcode( 'copy_to_clipboard', 'ic_copy_to_clipboard' );
function ic_copy_to_clipboard( $atts, $content = ''){

    $user_id        = get_current_user_id();
    $site_url       = get_site_url().'/?ref='. idRandEncode( $user_id );
    $total_rewards  = total_refer_friends();
    $button_attr   = ( $total_rewards > 5 ) ? 'disabled="true"' : '';
    $output = '';

    ob_start();

    $output .= '<div id="ic_copy-to-clipboard-wrapper">';
    $output .= '<h5 class="ic_title">Invite friends using the button below:</h5>';
    $output .= '<form>
        <div class="ic_form_group">
            <input readonly type="text" id="invite_friend" name="invite_friend" class="form-control" placeholder="" value="'. $site_url.'" />
            <button type="button" id="copy-referral-code" class="ic-button" '.$button_attr.'>Copy link to Clipboard</button>
        </div>
        <p class="limit-over">You already referred to '.$total_rewards.' persons this year. You can\'t refer anymore in this year.</p>';
    $output .= '</form> </div>';

    $output .= ob_get_clean();
    return $output;
}

/**
 * Display user total referred count
 */
add_shortcode('total_referred', 'total_refer_friends');
function total_refer_friends() {
    global $wpdb;
    // TODO: total refer and points
    $user_id        = get_current_user_id();
    $total_referred = $wpdb->query(
        $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d", $user_id )
    );
    return $total_referred;
}

/**
 * Display login user total rewards as points
 */
add_shortcode('current_reward_points', 'current_reward_points');
function current_reward_points() {
    global $wpdb;
    $user_id        = get_current_user_id();
    $total_referred = $wpdb->query(
        $wpdb->prepare( "SELECT total_points FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d", $user_id )
    );
    return $total_referred;
}


/**
 * Display login user total rewards as points
 */
add_shortcode('email_share', 'ic_email_share');
function ic_email_share() {
    // global $wpdb;
    // $user_id        = get_current_user_id();
    // $total_referred = $wpdb->query(
    //     $wpdb->prepare( "SELECT total_points FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d", $user_id )
    // );
    $output = '';
    $output .= '<a href="" id="send_email"><i class="icon-default-style fa fa-envelope-o extra-color-2"></i> Email</a>';
    return $output;
}
add_action('wp_ajax_ic_send_email', 'ic_send_email');
// add_action('wp_ajax_nopriv_ic_send_email', 'ic_send_email');

function ic_send_email() {
    $to             = 'anisur2805@gmail.com';
    $subject        = 'Test Email';
    $refer_link     = ic_generate_referral_links();
    $message        = 'This is a refer mail. Please open the link to register and you will get 500 points. Here is the link: ' . esc_url( $refer_link);
    $headers        = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);
    die;
}