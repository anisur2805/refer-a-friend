<?php
/**
 * Plugin Name: Refer A Friend
 * Description: Awesome Desc...
 * Plugin URI:  http://github.com/test
 * Version:     1.0
 * Author:      http://github.com/test
 * Author URI:  http://github.com/anisur2805/
 * Text Domain: itc-refer-a-friend
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
if (!session_id()) {
    session_start();
}

if (!defined('ABSPATH')) {
    exit;
}

require ABSPATH . '/wp-includes/pluggable.php';
include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

use Hashids\Hashids;

define("HASHHIDE_SALT", "1e46c03#&227d3()a7_)_*@(!#");
define("HASHHIDE_RAND_LENGTH", 5);
define("HASHHIDE_RAND_ENCODE_MIN_LENGTH", 6);
define("HASHHIDE_RAND_STRING", "3d92a3c3587b5c7c8129ee3e6a077be647590154f889d19ffbcc029cbdabbb11");
define("HASHHIDE_RAND_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWVXYZ1234567890");

require_once plugin_dir_path(__FILE__) . 'inc/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'inc/woocom.php';
require_once plugin_dir_path(__FILE__) . 'inc/Subscribers_List_Table.php';
require_once plugin_dir_path(__FILE__) . 'inc/wp-cron.php';
require_once plugin_dir_path(__FILE__) . 'inc/popup.php';

function ic_enqueue_styles() {
    wp_enqueue_style('ic-styles', plugins_url('assets/style.css', __FILE__));
    wp_enqueue_script('ic-main', plugins_url('assets/main.js', __FILE__), array('jquery', 'dispensary-age-verification' ), time(), true);
    wp_enqueue_script('ic-popup', plugins_url('assets/popup.js', __FILE__), array('jquery' ), time(), true);

    wp_localize_script('ic-main', 'icAjaxHandler', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
    
    wp_localize_script('ic-popup', 'icPopupObj', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
}

add_action('wp_enqueue_scripts', 'ic_enqueue_styles');

/**
 * generate Links for referring
 */
function ic_generate_referral_links() {
    $referral_link_id = ic_create_referral_link();
    $site_url         = get_site_url() . '/?ref=' . $referral_link_id;
    return $site_url;
}

function idRandEncode($str) {
    $hashids = new Hashids(HASHHIDE_SALT, 13);
    return $hashids->encode($str);
}

function idRandDecode($str) {
    $hashids = new Hashids(HASHHIDE_SALT, 13);
    return $hashids->decode($str);
}

// Add redirect sub-menu page
add_action('admin_menu', 'ic_admin_menu');
function ic_admin_menu() {
    add_options_page(
        __('Referred Details',),
        __('Referred Details',),
        'manage_options',
        'referral-options',
        'referral_page_callback'
    );
}

function referral_page_callback() {
?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?></h1>

        <!-- <form id="art-search-form" method="POST"> -->
<?php
            global $wpdb;

            $query = "SELECT user.id, user.display_name as name, user.user_email as email, refer.accept_total_points
          FROM {$wpdb->prefix}users as user, {$wpdb->prefix}user_referred as refer WHERE user.id = refer.accepted_user_id AND status IN (0, 1)";
            $results = $wpdb->get_results($query, ARRAY_A);

            /**
             * Update user point
             */
            // if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-point' ) {
            //     $nonce = sanitize_text_field( isset($_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '' );
            //     if( wp_verify_nonce( $nonce, 'itc-update-nonce' ) ) {
            //         $user_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : 0;
            //         $points  = isset( $_REQUEST['point'] ) ? $_REQUEST['point'] : 0;
                    
                    
            //         $wpdb->update(
            //             $wpdb->prefix. 'user_referred',
            //             [
            //                 'accept_total_points'              => $points,
            //                 'updated_at'          => date('Y-m-d H:i:s'),
            //             ],
            //             [ 'accepted_user_id' => $user_id ], // 'status' => 0
            //             [ '%d', '%s' ],
            //             [ '%d' ],
            //             [ 1 ]
            //         );
                    
            //     }
            // }




            

            $itc_subscriber_table = new Subscribers_List_Table( $results );
            $itc_subscriber_table->prepare_items();
            $itc_subscriber_table->display();
            ?>
        <!-- </form> -->
    </div>
<?php
}

add_action( 'admin_post_manually_submit_form', function(){
    if ( isset( $_POST['manually_submit_form'] ) ) { //&& wp_verify_nonce( $_POST['nonce'], 'itc-update-nonce' ) 
        die( 'you must die' );
        $user_id = $_POST['user_id'];
        $point   = intval( $_POST['point'] );
        
        // TODO: Update database with new value
        
        echo "Point updated successfully.";
        wp_safe_redirect( admin_url( 'options-general.php?page=referral-options' ) );
        exit;
    }
});

/**
 * Create table for user referral
 * on plugin activation period
 */
register_activation_hook(__FILE__, 'create_referral_links_table');
function create_referral_links_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    // referral_links table
    $links_schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}referral_links`(
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        created_at timestamp NOT NULL,
        expire_date timestamp NOT NULL,
        user_id int(11) unsigned NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate";

    dbDelta($links_schema);

    // user_referred table
    $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_referred`(
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        accepted_user_id int unsigned NOT NULL,
        referred_by_user_id int unsigned DEFAULT 0,
        refer_links_id int(11) DEFAULT 0,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        status tinyint NOT NULL DEFAULT 0,
        accept_total_points int(11) NOT NULL DEFAULT 0,
        referrer_total_points int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) $charset_collate";

    dbDelta($schema);
}

// Insert referral links to table
function ic_create_referral_link() {
    global $wpdb;
    $id             = get_current_user_id();
    $email_verified = get_user_meta($id, 'wcemailverified', true);
    $age_verified   = isset($_COOKIE['age-verification']) ? $_COOKIE['age-verification'] : false;

    // TODO: un-commit this
    // if ( ! $email_verified || ! $age_verified ) {
    //     return;
    // }

    // select * from referral_links where user_id = id order by desc limit 1 and
    $today   = date('Y-m-d') . " 23:59:59";
    $query   = "SELECT * FROM {$wpdb->prefix}referral_links WHERE user_id = $id AND expire_date > '$today' ORDER BY id DESC LIMIT 1";
    $get_row = $wpdb->get_row($query);

    if (!empty($get_row)) {
        return idRandEncode($get_row->id);
    }

    $data = [
        'created_at'  => date('Y-m-d H:i:s'),
        'expire_date' => date('Y-m-d H:i:s', strtotime(' +30 days')),
        'user_id'     => $id,
    ];
    $inserted = $wpdb->insert("{$wpdb->prefix}referral_links", $data, ['%s', '%s', '%d']);
    if (!$inserted) {
        return new \WP_Error('failed-to-insert', __('Failed to insert'));
    }


    return idRandEncode($wpdb->insert_id);
}

// TODO: this hooks should be user-login/register
// add_action('init', 'ic_create_referral_link');

function check_refer_id_url() {
    if (!isset($_GET['ref']) || empty($_GET['ref'])) {
        return;
    }

    $_SESSION['PHP_REFID'] = $_GET['ref'];

    if ( isset( $_SESSION['PHP_REFID'] ) ) {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url() . '/my-account');
            exit;
        } else {
            wp_redirect(home_url() . '/register');
            exit;
        }
    }

}

// TODO: should be use user-register/ login/ admin_init
// add_action('init', 'check_refer_id_url');
check_refer_id_url();

function ic_user_has_referred() {
    global $wpdb;

    if (empty($_COOKIE['age-verification'])) {
        return;
    }

    $user_id = get_current_user_id();

    if ($user_id == 0) {
        return;
    }

    if (!isset($_SESSION['PHP_REFID'])) {
        return;
    }

    $decode = idRandDecode($_SESSION['PHP_REFID']);

    if (empty($decode) || $decode[0] === 0) {
        return;
    }

    $link = $wpdb->get_row("SELECT * from {$wpdb->prefix}referral_links WHERE id = " . $decode[0]);

    if (empty($link)) {
        return;
    }

    if ($link->user_id == NULL) {
        return;
    }

    // check date TODO: strtotomestamp - date param time() > expire_date
    // if( $link->expire_date > $link->created_at ) { 
    //     return; }

    if (is_admin()) {
        return;
    }

    // check referred by user id count == 5
    $total_referred = $wpdb->get_row($wpdb->prepare("SELECT count(id) as total from {$wpdb->prefix}user_referred WHERE status = 1 AND referred_by_user_id = %d", $link->user_id));

    $raf_av = get_user_meta($user_id, 'raf_av', true);

    if (empty($raf_av) && "0" !== $raf_av) {
        add_user_meta($user_id, 'raf_av', $_COOKIE['age-verification'] === "true", true);
    } else {
        update_user_meta($user_id, 'raf_av', $_COOKIE['age-verification'] === "true");
    }

    $data = [
        'accepted_user_id'      => $user_id,
        'referred_by_user_id'   => $link->user_id,
        'refer_links_id'        => $link->id,
        'created_at'            => date('Y-m-d H:i:s'),
        'updated_at'            => date('Y-m-d H:i:s'),
        'status'                => 0,
        'accept_total_points'   => 0, // 500
        'referrer_total_points' => 0,
    ];

    $user_referred_table = $wpdb->prefix.'user_referred';
    $id                  = get_current_user_id();

    $updated_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $user_referred_table WHERE accepted_user_id = %d", $id
        )
    );

    // need to fix here - after register only then get the user data
    if( (int) $updated_row->accepted_user_id == $id ) {
        $register_ft = get_user_meta( $id, 'register_ft', true );

        if( empty( $register_ft ) ) {
            
            $wpdb->update(
                $user_referred_table,
                [
                    'status'              => 1,
                    'updated_at'          => date('Y-m-d H:i:s'),
                    'accept_total_points' => 500,
                ],
                [ 'accepted_user_id' => $id ], // 'status' => 0
                [ '%d', '%s', '%d' ],
                [ '%d' ],
                [ 1 ]
            );
            
            update_user_meta( $id, 'register_ft', 1 );
        }
    } else {
        if ( intval( $total_referred->total ) == 5 ) {
            return;
        }
        $wpdb->insert( $user_referred_table, $data, ['%d', '%d', '%d', '%s', '%s', '%d', '%d', '%d'] );
    }

    return $wpdb->insert_id;
}

/**
 * Check is verified
 */
function check_verification() {
    $id             = get_current_user_id();
    $email_verified = get_user_meta($id, 'wcemailverified', true);
    // get age verify from url param
    $age_verified = false;
    if (!empty($_COOKIE['age-verification'])) {
        $age_verified = $_COOKIE['age-verification'] === "true";
    }
    if ( $email_verified && $age_verified) {
        // ic_insert_data_user_referred();
        ic_user_has_referred();
    }
}

check_verification();

// Get Who referred
function get_referred_data() {
    if (isset($_SESSION['PHP_REFID'])) {
        $data     = $_SESSION['PHP_REFID'];
        $refer_id = idRandDecode($data);
        return $refer_id[0];
    }
}

get_referred_data();
// add_action('init', 'get_referred_data');

/**
 * Set status and updated_at column when
 * user verify age and email
 * */

 function ic_update_user_status() {
    global $wpdb;
    $id = get_current_user_id();
    $email_verified = get_user_meta($id, 'wcemailverified', true);

    // get age verify from url param
    $age_verified = false;
    if (!empty($_COOKIE['age-verification'])) {
        $age_verified = $_COOKIE['age-verification'] === "true";
    }

    if ($email_verified && $age_verified) {
        $old_rewards = get_total_points($id);
        $user_referred_table = $wpdb->prefix . 'user_referred';

        $updated_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $user_referred_table WHERE accepted_user_id = %d",
                $id
            )
        );

        if ($updated_row) {
            $wpdb->update(
                $user_referred_table,
                [
                    'status'              => 1,
                    'updated_at'          => date('Y-m-d H:i:s'),
                    // 'accept_total_points' => $old_rewards->accept_total_points + 500,
                    'accept_total_points' => 500,
                ],
                [ 'accepted_user_id' => $id ],
                [ '%d', '%s', '%d' ],
                [ '%s' ],
                [ 1 ]
            );
        } else {
            $wpdb->insert(
                $user_referred_table,
                [
                    'accepted_user_id'    => $id,
                    'status'              => 0,
                    'updated_at'          => date('Y-m-d H:i:s'),
                    'accept_total_points' => 0,
                ],
                [ '%d', '%d', '%s', '%d' ]
            );
        }
    }
}

// TODO: 1 wrong msg
// ic_update_user_status(); // TODO: this run continueusly

function get_total_points($user_id) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT accept_total_points FROM {$wpdb->prefix}user_referred WHERE accepted_user_id = %d",
            $user_id
        )
    );
}

function ic_calculate_points_to_pound() {
    $id = get_current_user_id();
    if ($id) {
        $points = get_total_points($id);
        if ($points) {
            $points = $points->accept_total_points;
            $pound  = round($points / 100);
            return $pound;
        }
    }
}

// ic_calculate_points_to_pound();

// session_destroy();

// include ABSPATH . 'wp-content/plugins/dispensary-age-verification/public/class-dispensary-age-verification-public.php';

if( isset( $_POST['reload_page'])) {
    ?>

<script>
    var date = new Date();
    var res = false;
	var nameEQ = "age-reverification";
	var ca = document.cookie.split(";");

	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
        var name = c.split("=")[0]
        res = (name.trim(" ") === nameEQ)
        console.log( c )
	}

    if( res === false ) {
        document.cookie = "age-verification='' ; expires="+date.toUTCString()+ "; path=/";
    }

</script>
    <?php
    if( ! isset( $_COOKIE['age-reverification'])) {
        run_avwp(); 
        $_COOKIE['age-reverification'] = true;
        ?>
        <script>
            var now = new Date();
            var time = now.getTime();
            var expireTime = time + 60*60*24*30;
            now.setTime(expireTime);
            document.cookie = "age-reverification=true; expires="+now.toUTCString()+ "; path=/";
        </script>
        <?php

    }
}

// add_action( 'init', 'run_avwp' );
// add_action('wp_footer', 'abcd');
function abcd(){?>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelector('.abcd').addEventListener('click', function(){
            var date = new Date();
            document.cookie = "age-verification='' ; expires="+date.toGMTString()+ "; path=/";
            <?php
                // $av = new Age_Verification();
                // $av->run();
                // var_dump( $av );
                // echo "hello";

                run_avwp();
            ?>
            console.log( 'hey' )
            
        });
    });
</script>
    <?php
}

// add_action( 'wp_footer', 'avwp_public_js' );
//     add_action( 'wp_head', 'avwp_public_css' );