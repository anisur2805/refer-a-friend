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
require_once plugin_dir_path( __FILE__ ) . 'inc/Subscribers_List_Table.php';

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
    $referral_link_id = ic_create_referral_link();
    $encoded = idRandEncode( $referral_link_id );
    // $site_url = get_site_url() . '/?ref=' . $encoded; // TODO: this line create issue
    $site_url = get_site_url() . '/?ref=' . $referral_link_id;
    return $site_url;
}
function idRandEncode( $str ) {
    // return $str;
    $hashids = new Hashids( HASHHIDE_SALT, 13 );
    return $hashids->encode( $str );
}

function idRandDecode( $str ) {
    // return [$str];
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
        <?php $itc_subscriber_table = new Subscribers_List_Table(); ?>
        <form id="art-search-form" method="GET">
            <?php
                $itc_subscriber_table->prepare_items();
                // $itc_subscriber_table->search_box('search', 'search_id');
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
        referred_by_user_id int unsigned DEFAULT 0,
        refer_links_id int(11) DEFAULT 0,
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
    $age_verified   = isset($_COOKIE['age-verification']) ? $_COOKIE['age-verification'] : false;

    // if ( ! $email_verified || ! $age_verified ) { 
    //     return;
    // }

    // select * from referral_links where usrid = id order by desc limit 1 and 
    $today = date('Y-m-d')." 23:59:59";
    $query = "SELECT * FROM {$wpdb->prefix}referral_links WHERE user_id = $id AND expire_date > '$today' ORDER BY id DESC LIMIT 1";
    $get_row = $wpdb->get_row( $query );

    if( ! empty( $get_row) ) {
        return idRandEncode( $get_row->id );
    }

    $data = [
        'created_at'  => date( 'Y-m-d H:i:s' ),
        'expire_date' => date( 'Y-m-d H:i:s', strtotime( ' +30 days' ) ),
        'user_id'     => $id,
    ];
    $inserted = $wpdb->insert( "{$wpdb->prefix}referral_links", $data, ['%s', '%s', '%d'] );
    if ( !$inserted ) {
        return new \WP_Error( 'failed-to-insert', __( 'Failed to insert' ) );
    }

    return idRandEncode( $wpdb->insert_id );
}

// ic_create_referral_link();

// TODO: this hooks should be user-login/register
// add_action('init', 'ic_create_referral_link');


function check_refer_id_url() {
    if ( !isset( $_GET['ref'] ) || empty( $_GET['ref'] ) ) {
        return;
    }

    $_SESSION['PHP_REFID'] = $_GET['ref'];
    if( isset( $_GET['ref'] ) && !empty( $_GET['ref'] ) ) {
        if( is_user_logged_in() ) {
            wp_safe_redirect( home_url() . '/my-account' );
            exit;
        } else {
            wp_redirect( home_url() . '/register' );
            exit;
        }
    } 
    
}

// TODO: should be use user-register/ login/ admin_init
add_action('init', 'check_refer_id_url');

function ic_user_has_referred() {
    global $wpdb;
    
    if( empty( $_COOKIE['age-verification'])){
        return;
    }
    
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
    
    $link = $wpdb->get_row( "SELECT * from {$wpdb->prefix}referral_links WHERE id = " . $decode[0] );
    
    if ( empty( $link ) ) {
        return;
    }
 
    if( $link->user_id == NULL ) {
        return;
    }

    // check date TODO: strtotomestamp - date param time() > expire_date
    // if( $link->expire_date > $link->created_at ) {
    //     return;
    // }

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
    if( is_admin() ) {
        return;
    }
    // check referred by user id count == 5
    $total_referred = $wpdb->get_row( "SELECT count(id) as total from {$wpdb->prefix}user_referred WHERE status = 1 AND referred_by_user_id = " . $link->user_id );

    // if ( intval( $total_referred->total ) > 5 ) {
    //     return;
    // }

    $raf_av = get_user_meta($user_id, 'raf_av', true);
    if( empty($raf_av) && "0" !== $raf_av ){
        add_user_meta( $user_id, 'raf_av', $_COOKIE['age-verification']==="true", true);
    } else {
        update_user_meta($user_id, 'raf_av', $_COOKIE['age-verification']==="true");
    }
    
    $data = [
        'accepted_user_id'    => $user_id,
        'referred_by_user_id' => $link->user_id,
        'refer_links_id'      => $link->id,
        'created_at'          => date( 'Y-m-d H:i:s' ),
        'updated_at'          => date( 'Y-m-d H:i:s' ),
        'status'              => 0,
        'total_points'        => 0,
    ];

    $wpdb->insert( "{$wpdb->prefix}user_referred", $data, ['%d', '%d', '%d', '%s', '%s', '%d', '%d'] );
    return $wpdb->insert_id;
}
ic_user_has_referred();
// add_action( 'user_register', 'ic_user_has_referred' );
// add_action( 'init', 'ic_user_has_referred' );

// Insert user referred data
function ic_insert_data_user_referred() {
    global $wpdb;

    if( is_login() ) {
        return;
    }

    
    $referrer_link_id       = get_referred_data();
    $refer_link   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}referral_links WHERE id = %d LIMIT 1", $referrer_link_id ) );
    
    if( is_null( $refer_link ) ) {  
        return;
    }


    $data = [
        'accepted_user_id'    => get_current_user_id(),
        'referred_by_user_id' => $refer_link->user_id,
        'refer_links_id'      => $refer_link->id,
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
    if ( isset( $_SESSION['PHP_REFID'] ) ) {
        $data     = $_SESSION['PHP_REFID'];
        $refer_id = idRandDecode( $data );
        return $refer_id[0];
    }
}

// get_referred_data();
add_action( 'init', 'get_referred_data' );

/**
 * Set status and updated_at column when 
 * user verify age and email
 * */
function ic_update_user_status() {
    global $wpdb;
    $id             = get_current_user_id();
    $email_verified = get_user_meta( $id, 'wcemailverified', true );

    // get age verify from url param
    $age_verified = false;
    if( ! empty( $_COOKIE['age-verification'] ) ) {
        $age_verified   = $_COOKIE['age-verification']==="true";
    }

    if ( $email_verified && $age_verified ) {
        
        $old_rewards = get_total_points( $id );
        $wpdb->update(
            $wpdb->prefix . 'user_referred',
            array(
                'status'        => 1,
                'updated_at'    => date( 'Y-m-d H:i:s' ),
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



// $arr = [
//     'raf_av'  => 1,
//     'raf_rlid'        => 999

// ];
// $token = md5( base64_encode( json_encode( $arr ) ) );
// $query = http_build_query( $arr, '', '&');
// $query .= '&token='.$token;

///
// $retrive = '';
// parse_str($query, $retrive);
// $rToken = $retrive['token'];
// unset( $retrive['token']);

// $testToken = md5( base64_encode( json_encode( $arr ) ) );

// if( $testToken === $rtoken){
//     echo 'Match';
// } else {
//     echo 'Not match';
// }

// echo '<pre>';
//       var_dump($token);
//       var_dump($query);
//       var_dump($retrive);
//       var_dump($testToken);
// echo '</pre>';

// TODO: new column while 