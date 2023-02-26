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

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
    require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

use Hashids\Hashids;

define( "HASHHIDE_SALT", "1e46c03#&227d3()a7_)_*@(!#" );
define( "HASHHIDE_RAND_LENGTH", 5 );
define( "HASHHIDE_RAND_ENCODE_MIN_LENGTH", 6 );
define( "HASHHIDE_RAND_STRING", "3d92a3c3587b5c7c8129ee3e6a077be647590154f889d19ffbcc029cbdabbb11" );
define( "HASHHIDE_RAND_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWVXYZ1234567890" );

/**
 * Generate Referral Code
 */
function generate_user_ref_uuid() {
    global $wpdb;
    $user_id = get_current_user_id();
    $uuid    = uniqid();
    $wpdb->update( $wpdb->prefix . 'users', array( 'ref_uuid' => $uuid ), array( 'ID' => $user_id ) );
    return $uuid;
}

//   add_action('user_register', 'generate_user_ref_uuid');
// add_action('user_login', 'generate_user_ref_uuid');

function ic_enqueue_styles() {
    wp_enqueue_style( 'ic-styles', plugins_url( 'assets/style.css', __FILE__ ) );
    wp_enqueue_script( 'ic-main', plugins_url( 'assets/main.js', __FILE__ ), array( 'jquery' ), time(), true );
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

function add_column_user_table() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}users";
    $get_rows   = $wpdb->get_row( "SELECT * FROM $table_name" );

    if ( !isset( $get_rows->testxyz ) ) {
        $wpdb->query( "ALTER TABLE $table_name ADD COLUMN testxyz varchar(13) NOT NULL" );
    }
}

// add_action('admin_init', 'add_column_user_table');

/**
 * Show award message
 */
// add_action('admin_notices', 'ic_show_award_message');
function ic_show_award_message() {
    global $wpdb;
    $user_id     = get_current_user_id();
    $referred_id = get_referred_data();

    $user_name = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT  display_name FROM {$wpdb->prefix}users WHERE ID = %d", $referred_id
        )
    );

    $class        = 'notice notice-success';
    $message      = "You just been referred by <strong>{$user_name->display_name}</strong>. You both receive £5. Once you register, you can do the same and get another £5 for every person you refer.";
    $allowed_html = array( 'strong' => array() );
    // if( $referred_id ) {
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses( $message, $allowed_html ) );
    // }
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

function referral_page_callback() {
    echo '<div class="wrap"><h1>' . get_admin_page_title() . '</h1></div>';
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
        total_rewards int(20) DEFAULT 0,
        PRIMARY KEY (`id`)
    ) $charset_collate";

    dbDelta( $schema );
}

// Expiry date
function ic_set_expiry_date( $expiry = 30 ) {
    $timestamp = date( 'Y-m-d H:i:s' );
    $expires   = strtotime( " + $expiry days", strtotime( $timestamp ) );
    $date_diff = ( $expires - strtotime( $timestamp ) ) / 86400;
    $days_left = round( $date_diff, 0 );

    $user_id = get_current_user_id();
    return $days_left;
}

// Insert referral links to table
function ic_create_referral_link() {
    global $wpdb;

    $data = [
        'created_at'  => date( 'Y-m-d H:i:s' ),
        'expire_date' => date( 'Y-m-d H:i:s', strtotime( ' +30 days' ) ),
        'user_id'     => get_current_user_id(),
    ];

    $inserted = $wpdb->insert( "{$wpdb->prefix}referral_links", $data, ['%s', '%s', '%d'] );

    if ( !$inserted ) {
        return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
    }

    return idRandEncode( $wpdb->insert_id );
}

// die( ic_create_referral_link());

// TODO: this hooks should be user-login/register
// add_action('admin_init', 'ic_create_referral_link');

function check_refer_id_url() {
    if ( !isset( $_GET['ref'] ) || empty( $_GET['ref'] ) ) {
        return;
    }

    $_SESSION['PHP_REFID'] = $_GET['ref'];
    // die($_SESSION['PHP_REFID']);
}

// TODO: should be use user-register/ login/ admin_init
// add_action('admin_init', 'check_refer_id_url');
check_refer_id_url();

// die( idRandEncode(42));
// 42: vzXDxZEblJkdA
function ic_user_has_referred( $user_id ) {
    global $wpdb;

    if ( $user_id === 0 ) {
        return;
    }
    if ( !isset( $_SESSION['PHP_REFID'] ) ) {
        return;
    }
    $decode = idRandDecode( $_SESSION['PHP_REFID'] );
    if ( empty( $decode ) || $decode[0] === 0 ) {
        return;
    }
    $link = $wpdb->get_row( "SELECT * from {$wpdb->prefix}referral_links WHERE id = " . $decode[0] );

    if ( empty( $link ) ) {
        return;
    }
    // check date
    // if( $link->expire_date >=  )

    $user = $wpdb->get_row( "SELECT * from {$wpdb->prefix}user_referred WHERE accepted_user_id = " . $user_id );
    if ( !empty( $user ) ) {
        return;
    }

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
        'total_rewards'       => 0,
    ];
    $inserted = $wpdb->insert( "{$wpdb->prefix}user_referred", $data, ['%d', '%d', '%d', '%s', '%s', '%d', '%d'] );
    return $inserted->insert_id;
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
    $refer_links_id   = $wpdb->get_row( "SELECT id FROM {$wpdb->prefix}referral_links" );

    $data = [
        'accepted_user_id'    => $accepted_user_id,
        'referred_by_user_id' => $referrer_id,
        'refer_links_id'      => $refer_links_id->id,
    ];

    $format   = ['%d', '%d', '%d'];
    $inserted = $wpdb->insert( "{$wpdb->prefix}user_referred", $data, $format );

    if ( !$inserted ) {
        return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
    }

    return $wpdb->insert_id;
}

// TODO: this hooks should be user-login/register
add_action( 'admin_init', 'ic_insert_data_user_referred' );

// Get Who referred
function get_referred_data() {
    global $wpdb;

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
    $age_verified   = get_user_meta( $id, 'one_acc_woo_av_status', true );
    if ( $email_verified && 'av_success' == $age_verified ) {

        $old_rewards = get_total_points( $id );

        $wpdb->update(
            $wpdb->prefix . 'user_referred',
            array(
                'status'        => 1,
                'updated_at'    => date( 'Y-m-d H:i:s' ),
                'total_rewards' => (int) $old_rewards + 500,
            ),
            array( 'accepted_user_id' => $id ),
            array( '%s', '%s', '%d' ),
            array( '%s' )
        );
        // die( $wpdb->last_query);
    }
}

ic_update_user_status();

function get_total_points( $user_id ) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT total_rewards FROM {$wpdb->prefix}user_referred WHERE accepted_user_id = %d", $user_id
        )
    );
}

// die( get_total_points( $user_id ) );

function ic_calculate_points_to_pound() {
    $id     = get_current_user_id();
    $points = get_total_points( $id );

    die( $points );

}

// ic_calculate_points_to_pound();