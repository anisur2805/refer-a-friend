<?php
/**
 * Plugin Name: Refer A Friend
 * Description: Awesome Desc...
 * Plugin URI:  http://github.com/test
 * Version:     1.0
 * Author:      http://github.com/test
 * Author URI:  http://github.com/anisur2805/
 * Text Domain: test-domain
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Start or resume a session
if ( !session_id() ) {
    session_start();
}

require ABSPATH . '/wp-includes/pluggable.php';
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
    require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

use Hashids\Hashids;

define( "HASHHIDE_SALT", "1e46c03#&227d3()a7_)_*@(!#" );
define( "HASHHIDE_RAND_LENGTH", 5 );
define( "HASHHIDE_RAND_ENCODE_MIN_LENGTH", 6 );
define( "HASHHIDE_RAND_STRING", "3d92a3c3587b5c7c8129ee3e6a077be647590154f889d19ffbcc029cbdabbb11" );
define( "HASHHIDE_RAND_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWVXYZ1234567890" );

require_once plugin_dir_path( __FILE__ ) . 'inc/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/woocom.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/ITC_Subscribers_List_Table.php';

function ic_enqueue_styles() {
    wp_enqueue_style( 'ic-styles', plugins_url( 'assets/style.css', __FILE__ ) );
    wp_enqueue_script( 'ic-main', plugins_url( 'assets/main.js', __FILE__ ), array( 'jquery' ), time(), true );

    wp_localize_script( 'ic-main', 'icAjaxHandler', [
        'ajaxUrl'  => admin_url( 'admin-ajax.php' )
    ]);
}

add_action( 'wp_enqueue_scripts', 'ic_enqueue_styles' );

/**
 * generate Links for referring
 */
function ic_generate_referral_links() {
    $user_id = get_current_user_id();
    $encoded = idRandEncode( $user_id );
    $site_url = get_site_url() . '/?ref=' . $encoded;
    return $site_url;
}
function idRandEncode( $str ) {
    $hashids = new Hashids( HASHHIDE_SALT, 13 );
    return $hashids->encode( $str );
}

function idRandDecode( $str ) {
    $hashids = new Hashids( HASHHIDE_SALT, 13 );
    return $hashids->decode( $str );
}

// Add redirect sub-menu page
add_action( 'admin_menu', 'ic_admin_menu' );
function ic_admin_menu() {
    add_options_page(
        __( 'Referred Details', ),
        __( 'Referred Details', ),
        'manage_options',
        'referral-options',
        'referral_page_callback'
    );
}

function referral_page_callback() { ?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?> </h1>
        <?php $itc_subscriber_table = new ITC_Subscribers_List_Table(); ?>
        <form id="art-search-form" method="GET">
            <?php
                $itc_subscriber_table->prepare_items();
                $itc_subscriber_table->search_box('search', 'search_id');
                $itc_subscriber_table->display();
            ?>
        </form>
    </div> <?php
}

/**
 * Create table for user referral
 * on plugin activation period
 */
register_activation_hook( __FILE__, 'create_referral_links_table' );
function create_referral_links_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    if ( !function_exists( 'dbDelta' ) ) {
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

    dbDelta( $links_schema );

    // user_referred table
    $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_referred`(
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        accepted_user_id int unsigned NOT NULL,
        referred_by_user_id int unsigned NOT NULL,
        refer_links_id int(11) NOT NULL,
        created_at timestamp NOT NULL,
        updated_at timestamp NOT NULL,
        status tinyint NOT NULL DEFAULT 0,
        total_points int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) $charset_collate";

    dbDelta( $schema );
}

// Insert referral links to table
function ic_create_referral_link() {
    global $wpdb;
    $id             = get_current_user_id();
    $email_verified = get_user_meta( $id, 'wcemailverified', true );
    if(isset($_COOKIE['age-verification'])) {
        $age_verified   = $_COOKIE['age-verification'];
    }

    if ( $email_verified && $age_verified ) {
        $data = [
            'created_at'  => date( 'Y-m-d H:i:s' ),
            'expire_date' => date( 'Y-m-d H:i:s', strtotime( ' +30 days' ) ),
            'user_id'     => $id,
        ];
        $inserted = $wpdb->insert( "{$wpdb->prefix}referral_links", $data, ['%s', '%s', '%d'] );
        if ( !$inserted ) {
            return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
        }
    }

    return idRandEncode( $wpdb->insert_id );
}

ic_create_referral_link();

// TODO: this hooks should be user-login/register
// add_action('user_login', 'ic_create_referral_link');

function check_refer_id_url() {
    if ( !isset( $_GET['ref'] ) || empty( $_GET['ref'] ) ) {
        return;
    }

    $_SESSION['PHP_REFID'] = $_GET['ref'];
}

// TODO: should be use user-register/ login/ admin_init
// add_action('admin_init', 'check_refer_id_url');
check_refer_id_url();

// die( idRandEncode(46));
// 42: vzXDxZEblJkdA
function ic_user_has_referred() {
    global $wpdb;

    $user_id = get_current_user_id();
    if ( $user_id == 0 ) {
        return;
    }

    if ( !isset( $_SESSION['PHP_REFID'] ) ) {
        return;
    }

    $decode = idRandDecode( $_SESSION['PHP_REFID'] );
    
    if ( empty( $decode ) || $decode[0] === 0 ) {
        return;
    }
    $link = $wpdb->get_row( "SELECT * from {$wpdb->prefix}referral_links WHERE user_id = " . $decode[0] );

    if ( empty( $link ) ) {
        return;
    }

    if( $link->user_id == NULL ) {
        return;
    }
    // check date
    if( $link->expire_date > $link->created_at ) {
        return;
    }

    // TODO: gul
    // $user = $wpdb->get_row( "SELECT * from {$wpdb->prefix}user_referred WHERE referred_by_user_id = " . $user_id );
    // if ( ! empty( $user ) ) {
    //     return;
    // } else {
    //     echo '<pre>';
    //           print_r( $user );
    //     echo '</pre>';
    //     die;
    // }

    // check referred by user id count == 5
    $total_referred = $wpdb->get_row( "SELECT count(id) from {$wpdb->prefix}user_referred WHERE status = 1 AND referred_by_user_id = " . $link->user_id );

    if ( $total_referred > 5 ) {
        return;
    }

    $data = [
        'accepted_user_id'    => $user_id,
        'referred_by_user_id' => $link->user_id,
        'refer_links_id'      => $link->id,
        'created_at'          => date( 'Y-m-d H:i:s' ),
        'updated_at'          => '',
        'status'              => 0,
        'total_points'       => 0,
    ];
    $wpdb->insert( "{$wpdb->prefix}user_referred", $data, ['%d', '%d', '%d', '%s', '%s', '%d', '%d'] );
    return $wpdb->insert_id;
}

// add_action( 'user_register', 'ic_user_has_referred' );
add_action( 'init', 'ic_user_has_referred' );

// Insert user referred data
function ic_insert_data_user_referred() {
    global $wpdb;

    $user_id          = get_current_user_id();
    $uuid             = idRandEncode( $user_id );
    $decode_id        = idRandDecode( $uuid );
    $accepted_user_id = get_current_user_id();
    $referrer_id      = get_referred_data();
    $refer_links_id   = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}referral_links" );

    if( is_admin() ) {
        return;
    }
    $data = [
        'accepted_user_id'    => $accepted_user_id,
        'referred_by_user_id' => $referrer_id,
        'refer_links_id'      => $refer_links_id->id,
    ];

    $inserted = $wpdb->insert( "{$wpdb->prefix}user_referred", $data, ['%d', '%d', '%d'] );

    if ( !$inserted ) {
        return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
    }

    return $wpdb->insert_id;
}

// TODO: this hooks should be user-login/register
add_action( 'init', 'ic_insert_data_user_referred' );

// Get Who referred
function get_referred_data() {
    if ( isset( $_SESSION['referrer_id'] ) ) {
        $data     = $_SESSION['referrer_id'];
        $refer_id = idRandDecode( $data );
        return $refer_id[0];
    }
}

// get_referred_data();
add_action( 'init', 'get_referred_data' );

/**
 * Check currently on 'my-account' page and
 * show the subscriber a message
 */
function show_register_user_message() {
    global $wp;
    $request = explode( '/', $wp->request );
    if ( end( $request ) == 'my-account' && is_account_page() ) {
    }
}

// add_action( 'init', 'show_register_user_message' );
// link: 26pDbLAX7wJdg, oEeQ27NB7K0jB

/**
 * Show resister user welcome message
 */

function ic_check_admin() {
    if ( !is_admin() ) {
        add_action( 'woocommerce_account_content', 'show_custom_message', 7 );
    }
}

ic_check_admin();

function show_custom_message() {
    global $wpdb;
    if ( !isset( $_SESSION['PHP_REFID'] ) ) {
        return;
    }
    $referred_id = idRandDecode( $_SESSION['PHP_REFID'] );
    $user_name   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}users WHERE ID = %d", $referred_id
        )
    );

    echo do_shortcode('[copy_to_clipboard]');
    echo do_shortcode('[email_share]');
    $class        = 'ic-referred-message';
    $id           = 'ic-referred-message';
    $message      = "You just been referred by <strong>{$user_name->display_name}</strong>. You both receive £5. Once you register, you can do the same and get another £5 for every person you refer.";
    $allowed_html = array( 'strong' => array() );
    printf( '<div class="%1$s" id="%2$s"><p>%3$s</p></div>', esc_attr( $class ), esc_attr( $id ), wp_kses( $message, $allowed_html ) );
}

// Set status and updated_at column when user verify age and email
function ic_update_user_status() {
    global $wpdb;
    $id             = get_current_user_id();
    $email_verified = get_user_meta( $id, 'wcemailverified', true );
    if( isset( $_COOKIE['age-verification'] ) ) {
        $age_verified   = $_COOKIE['age-verification'];
    }

    if ( $email_verified && $age_verified ) {
        $old_rewards = get_total_points( $id );
        $wpdb->update(
            $wpdb->prefix . 'user_referred',
            array(
                'status'        => 1,
                'updated_at'    => date( 'Y-m-d H:i:s' ),
                // 'total_points' => $old_rewards->total_points + 500,
                'total_points' => $old_rewards + 500,
            ),
            array( 'accepted_user_id' => $id ),
            array( '%s', '%s', '%d' ),
            array( '%s' )
        );
    }
}

ic_update_user_status();

function get_total_points( $user_id ) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT total_points FROM {$wpdb->prefix}user_referred WHERE accepted_user_id = %d", $user_id
        )
    );
}

function ic_calculate_points_to_pound() {
    $id     = get_current_user_id();
    if( $id ) {
        $points = get_total_points( $id );
        if( $points ) {
            $points = $points->total_points;
            $pound  = round( $points / 100 );
            return $pound;
        }
    }
}
ic_calculate_points_to_pound();



// session_destroy();

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
            "SELECT * FROM {$wpdb->prefix}user_referred WHERE referred_by_user_id = %d AND accepted_user_id != 0", get_current_user_id()
        )
    );

    $customer_orders = wc_get_orders( array(
        'customer_id' => $user_id,
        'status' => array( 'wc-completed', 'wc-processing' )
    ) );
    $total_spent = 0;
    foreach ( $customer_orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            $total_spent += $item->get_total();
        }
    }
    return wc_price($total_spent);
}
// TODO: hook 
// add_action('init', 'check_referrer_purchase_minimum_5_pound');
// check_referrer_purchase_minimum_5_pound();
// die("hello");