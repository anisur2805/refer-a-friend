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

if (!defined('ABSPATH')) {
    exit;
}

// Start or resume a session
if (!session_id()) {
    session_start();
}

require ABSPATH . '/wp-includes/pluggable.php';
require_once __DIR__ . '/vendor/autoload.php';

use Hashids\Hashids;

define("HASHHIDE_SALT", "1e46c03#&227d3()a7_)_*@(!#");
define("HASHHIDE_RAND_LENGTH", 5);
define("HASHHIDE_RAND_ENCODE_MIN_LENGTH", 6);
define("HASHHIDE_RAND_STRING", "3d92a3c3587b5c7c8129ee3e6a077be647590154f889d19ffbcc029cbdabbb11");
define("HASHHIDE_RAND_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWVXYZ1234567890");

/**
 * Generate Referral Code
 */
function generate_user_ref_uuid() {
    global $wpdb;
    $user_id = get_current_user_id();
    $uuid    = uniqid();
    $wpdb->update($wpdb->prefix . 'users', array('ref_uuid' => $uuid), array('ID' => $user_id));
    return $uuid;
}
//   add_action('user_register', 'generate_user_ref_uuid');
// add_action('user_login', 'generate_user_ref_uuid');

/**
 * generate Links for referring
 */
function ic_generate_referral_links() {
    $user_id = get_current_user_id();
    $encoded = idRandEncode($user_id);

    $site_url       = get_site_url().'/?ref='.$encoded;
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

function add_column_user_table() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}users";
    $get_rows   = $wpdb->get_row("SELECT * FROM $table_name");

    if (!isset($get_rows->testxyz)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN testxyz varchar(13) NOT NULL");
    }
}
// add_action('admin_init', 'add_column_user_table');

function check_refer_id_url() {
    if (isset($_REQUEST['ref'])) {
        if (is_user_logged_in()) {            
            wp_safe_redirect(home_url() . '/wp-admin/options-general.php?page=referral-options');
            exit;
        } else {
            wp_redirect(home_url() . '/wp-login.php');
            exit;
        }
    }
}
// TODO: should be use user-register/ login/ admin_init
add_action('init', 'check_refer_id_url');
/**
 * Show award message
 */
add_action('admin_notices', 'ic_show_award_message');
function ic_show_award_message() {
    // global $wpdb;
    $user_id     = get_current_user_id();
    $referred_id = idRandDecode( $user_id );

    // var_dump( $referred_id );
    // $referred_id = $wpdb->get_row(
    //     $wpdb->prepare(
    //         "SELECT  * FROM {$wpdb->prefix}ic_referral WHERE refer_id = %d", $referred_id
    //     )
    // );

    $class      = 'notice notice-success';
    $message    = 'hello';
    // $message    = "You just been referred by {$referred_id->name}. You both receive £5. Once you register, you can do the same and get another £5 for every person you refer.";
    if( $referred_id ) {
    	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }
}

// Add redirect sub-menu page
add_action( 'admin_menu', 'ic_admin_menu' );
function ic_admin_menu() {
		add_options_page(
			__( 'Referred Details', ),
			__( 'Referred Details', ),
			'read',
			'referral-options',
			'referral_page_callback'
		);
	}
function referral_page_callback(){
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
    $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}referral_links`(
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
        uuid varchar(64) NOT NULL,
        created_at timestamp NOT NULL,
        user_id int(11) unsigned NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate";

    dbDelta( $schema );

    // user_referred table
    // $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_referred`(
    //     id int(11) unsigned NOT NULL AUTO_INCREMENT,
    //     accepted_user_id int unsigned NOT NULL,
    //     referred_by_user_id int unsigned NOT NULL,
    //     refer_links_id int unsigned NOT NULL,
    //     PRIMARY KEY (`id`)
    // ) $charset_collate";

   

    dbDelta( $schema );
}

// Expiry date 
function ic_set_expiry_date( $expiry = 30 ){
    $timestamp  = date('Y-m-d H:i:s');
    $start_date = date($timestamp);
    $expires    = strtotime( " + $expiry days", strtotime($timestamp));
    $date_diff  = ($expires-strtotime($timestamp)) / 86400;

    // echo "Start: ".$timestamp."<br>";
    // echo "Expire: ".date('Y-m-d H:i:s', $expires)."<br>";
    $days_left = round($date_diff, 0);

    $user_id     = get_current_user_id(); 

    if( $days_left < 1 ) {
        // delete_user_meta( $user_id, 'referred_id' );
    }
 }

// Insert links to table
function ic_user_table_insert_data() {
    global $wpdb;

    $user_id = get_current_user_id();
    $uuid    = idRandEncode( $user_id );

    $data = [
        'uuid'          => $uuid,
        'created_at'    => current_time( 'mysql' ),
        'user_id'       => $user_id
    ];

    $format     = [ '%d', '%s', '%d' ];

    $current_user = wp_get_current_user();
    $user_name    = $current_user->user_login;

    // $wpdb->update( "{$wpdb->prefix}users", array( 'ref_uuids' => $ic_uuid ), array( 'ID' => $user_id ), $format, array('%d') );

    // if( ! $user_name ) {
        $inserted = $wpdb->insert( "{$wpdb->prefix}referral_links", $data, $format );
    // } 
    //else {
    //     return $wpdb->update(
    //         "{$wpdb->prefix}users",
    //         array(
    //             'ref_uuids' => $ic_uuid
    //         ),
    //         array(
    //             'ID' => $user_id
    //         ),
    //         $format,
    //         array('%d')
    //     );
    // }

    if ( ! $inserted ) {
        return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
    }

    // if( ! $update ) {
    //     return new \WP_Error( 'failed-to-update', __( 'Failed to update' ) );
    // }

    return $wpdb->insert_id;
}

// TODO: this hooks should be user-login/register
add_action('admin_init', 'ic_user_table_insert_data');