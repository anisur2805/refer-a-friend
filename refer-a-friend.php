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
if ( ! session_id() ) {
    session_start();
}

require ABSPATH . '/wp-includes/pluggable.php';
require_once __DIR__ . '/vendor/autoload.php';

use Hashids\Hashids;


define( "HASHHIDE_SALT", "1e46c03#&227d3()a7_)_*@(!#");
define( "HASHHIDE_RAND_LENGTH", 5);
define( "HASHHIDE_RAND_ENCODE_MIN_LENGTH", 6);
define( "HASHHIDE_RAND_STRING", "3d92a3c3587b5c7c8129ee3e6a077be647590154f889d19ffbcc029cbdabbb11");
define( "HASHHIDE_RAND_CHARS", "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWVXYZ1234567890");

// $hashids = new Hashids();

// $user_id = get_current_user_id();
// $hashids->encode( $user_id );

/**
 * http://localhost:10019/?awraf=63f11b8701c06
 * http://wppro.test/?awraf=63f11b8701c06
*/

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
  add_action('user_login', 'generate_user_ref_uuid');

/**
 * generate Links for referring
 */
function ic_generate_referral_links() {
    $user_id = get_current_user_id();
    $encoded = idRandEncode( $user_id);
    
    // $site_url       = get_site_url().'/?ref='.$encoded;
    $site_url = add_query_arg(
        array('ref' => $encoded),
        site_url()
    );
    return $site_url;
}

// die(ic_generate_referral_links());

function idRandEncode($str) {
    $hashids = new Hashids( HASHHIDE_SALT, 13);
    return $hashids->encode( $str );
}



$user_id = get_current_user_id();

//idRandEncode( $user_id );
function idRandDecode($str)
    {
        $hashids = new Hashids( HASHHIDE_SALT, 13 );
        return $hashids->decode( $str)[0];
    }

// idRandDecode( 1 );


function add_column_user_table(){
    global $wpdb;
    $table_name = "{$wpdb->prefix}users";
    $get_rows   = $wpdb->get_row("SELECT * FROM $table_name");

    if( ! isset($get_rows->testxyz)){
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN testxyz varchar(13) NOT NULL");
    }
} 

add_action( 'admin_init', 'add_column_user_table');


// 
function check_refer_id_url(){ 
    if( isset( $_GET['ref'] ) ) {


    if( is_user_logged_in() ) {
        wp_safe_redirect( home_url() . '/wp-admin' );
        exit;
    }

    wp_redirect( home_url() . '/wp-login.php' );
    exit;
    
    add_action('admin_notices', 'ic_show_award_message');
}

    
}

// // check_refer_id_url();
// // add_filter( 'the_permalink', 'add_custom_query_param' );

function get_current_url() {
    $page_url = 'http';
    if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
        $page_url .= "s";
    }
    $page_url .= "://";
    if ( isset( $_SERVER["SERVER_PORT"] ) && ( $_SERVER["SERVER_PORT"] != "80" ) ) {
        $page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $page_url;
}
$url = get_current_url();

// add_custom_query_param( $url );
// function add_custom_query_param( $url ) {
//     $user_id = get_current_user_id();
//     $encoded = idRandEncode( $user_id);

//     $query_param = "ref=$encoded"; // replace with your desired query parameter
//     // $query_param = "test=234"; // replace with your desired query parameter


//     $url_parts = parse_url( $url );
//     $url_query = isset( $url_parts['query'] ) ? $url_parts['query'] : '';
//     if ( ! empty( $url_query ) ) {
//         $url .= '&' . $query_param;
//     } else {
//         $url .= '?' . $query_param;
//     }
//     var_dump($url);
//     return $url;
// }



$user_id = get_current_user_id();
$encoded = idRandEncode( $user_id);

$_SESSION['ref'] =  $encoded;

function add_custom_query_param( $url ) {
    $url_parts = parse_url( $url );

    $url_query = isset( $url_parts['query'] ) ? $url_parts['query'] : '';
    
    echo '<pre>';
          print_r( $url_query );
    echo '</pre>';
    // if( isset( $url_query['ref'] ) && ! empty( $url_query['ref'] )) {
    //     return;
    // }
    
    if ( ! empty( $url_query ) ) {
        $url .= '&';
    } else {
        $url .= '?';
    }

    // Check for the session variable and add it to the URL
    if ( isset( $_SESSION['ref'] ) ) {
        $url .= 'ref=' . $_SESSION['ref'];
    }

    echo '<pre>';
          print_r( $url );
    echo '</pre>';
    return $url;
}
// echo '<pre>';
//       print_r( $_SESSION );
// echo '</pre>';
add_custom_query_param( $url );
// add_filter( 'the_permalink', 'add_custom_query_param' );