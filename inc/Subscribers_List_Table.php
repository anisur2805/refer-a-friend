<?php
if (!defined('ABSPATH')) {
    exit;
}

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

add_action( 'admin_post_delete_point', [ 'Subscribers_List_Table', 'delete_point' ]);

class Subscribers_List_Table extends \WP_List_Table {
    private $_items;
    public function __construct( $data ) {
        parent::__construct( [
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false,
        ] );
        $this->_items = $data;

        // add_action('admin_post_handle_form', [$this, 'handle_admin_post']);
        
    }

    public function get_columns() {

        $columns = array(
            'cb'                  => __( '<input type="checkbox" />', 'itc-refer-a-friend' ),
            'name'                => __( 'Name', 'itc-refer-a-friend' ),
            // 'user_id'                => __( 'User ID', 'itc-refer-a-friend' ),
            'email'               => __( 'Email', 'itc-refer-a-friend' ),
            'accept_total_points' => __( 'Points', 'itc-refer-a-friend' ),
            'update'              => __( 'Update', 'itc-refer-a-friend' ),
        );

        return $columns;

    }


    // public function single_row( $item ) {
	// 	echo '<tr><form name="hello-form">';
	// 	$this->single_row_columns( $item );
	// 	echo '</form></tr>';
	// }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $per_page = 20;
        $total_items = count( $this->_items );
        $current_page = $this->get_pagenum();
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $data = array_slice( $this->_items, ($current_page - 1) * $per_page, $per_page );
        $this->items = $data;
        $this->_column_headers = [ $this->get_columns(), [], [] ];

        // if (isset($_POST['point'])) {
        //     $this->manually_submit_form();
        // }

        // $column   = $this->get_columns();
        // $hidden   = [];
        // $sortable = $this->get_sortable_columns();
        // $per_page = 20;

        // $this->_column_headers = [$column, $hidden, $sortable];

        // $current_page = $this->get_pagenum();
        // $offset       = ( $current_page - 1 ) * $per_page;

        // $args = [
        //     'number' => $per_page,
        //     'offset' => $offset,
        // ];

        // $this->items = $this->itc_get_subscribers( $args );
        // echo '<pre>';
        //       print_r( $this->itc_get_subscribers( $args ) );
        // echo '</pre>';
        
        // $this->set_pagination_args( [
        //     'total_items' => $this->itc_subscribers_count(),
        //     'per_page'    => $per_page,
        // ] );

    }

    public function get_sortable_columns() {
        $sortable_columns = [
            'name'       => ['name', true],
            'created_at' => ['created_at', true],
        ];

        return $sortable_columns;
    }

    public function itc_get_subscribers( $args = [] ) {
        global $wpdb;

        $defaults = [
            "offset"  => 0,
            "number"  => 20,
            "orderby" => "id",
            "order"   => "ASC",
        ];

        $args = wp_parse_args( $args, $defaults );

        $items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_referred
            ORDER BY {$args["orderby"]} {$args["order"]}
            LIMIT %d OFFSET %d",
            $args["number"], $args["offset"] )
        );
        $items = $wpdb->query(
            "SELECT display_name FROM {$wpdb->prefix}users where ID =".  $items
        );
    }

    public function itc_subscribers_count() {
        global $wpdb;

        return (int) $wpdb->get_var( "SELECT count(id) FROM {$wpdb->prefix}user_referred " );
    }

    public function column_cb( $item ) {
        return "<input type='checkbox' value='{$item["id"]}'/>";
    } 
    
    // public function created_at( $item ) {
        // return "<input type='checkbox' name='bulk-delete[]' value='{$item["id"]}'/>";
    // }
    
    public function column_total_points( $item ) {
        // return "<input type='checkbox' name='bulk-delete[]' value='{$item["id"]}'/>";
    }



    public function column_name( $item ) {
        $actions = [];

        $user_id = $item['id'];
        $nonce = wp_create_nonce( 'delete-point-nc' );

        // $actions['delete'] = sprintf(
        //     '<a href="%s" class="delete-point" data-point-id="%s">%s</a>',
        //     admin_url( "options-general.php?page=referral-options&action=delete-point&user_id={$user_id}&_wpnonce={$nonce}" ),
        //     esc_attr( $user_id ),
        //     __( 'Delete Point', 'itc-refer-a-friend')
        // );

        $actions['delete'] = sprintf(
            '<form method="post" action='. admin_url( 'admin-post.php' ) .'><input type="hidden" name="nonce" value="%s" /><input type="hidden" name="user_id" value="%s" /><input type="hidden" name="action" value="delete_point" /><button name="itc-delete-point" type="submit" class="delete-point" data-point-id="%s">%s</button></form>',
            $nonce,
            esc_attr( $user_id ),
            esc_attr( $user_id ),
            __( 'Delete Point', 'itc-refer-a-friend')
        );

        return sprintf(
            '%s %s',
            $item['name'],
            $this->row_actions( $actions )
        );
    }

    public function delete_point() {
        global $wpdb;
        if( isset( $_POST['action']) && $_POST['action'] == 'delete_point' ) {
            $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
            if( wp_verify_nonce( $nonce, 'delete-point-nc' ) ) {
                $uId = isset( $_POST['user_id'] ) ? $_POST['user_id'] : 0;

                $wpdb->update(
                    $wpdb->prefix. 'user_referred',
                    [
                        'status'              => 9,
                        'updated_at'          => date('Y-m-d H:i:s'),
                    ],
                    [ 'accepted_user_id'      => $uId ],
                    [ '%d', '%s' ],
                    [ '%d' ]
                );
            }
        }
        wp_redirect( admin_url( 'options-general.php?page=referral-options' ) );
    }
    

    public function column_update( $item ) {
        $nonce   = wp_create_nonce( 'itc-update-nonce');
        $user_id = $item['id'];
        $point   = $item['accept_total_points'];
    
        return sprintf(
            '<form method="post"></form><input type="number" name="point" value="%s"/>' .
            "<input type='hidden' name='user_id' value='%s'/>" .
            "<input type='hidden' name='nonce' value='%s'/>" .
            "<input type='hidden' name='action' value='manually_submit_form'/>" .
            "<button type='submit' name='update_points' class='button'>Update</button>",
            esc_attr( $item['accept_total_points'] ),
            esc_attr( $user_id ),
            esc_attr( $nonce )
        );
    }

    public function manually_submit_form() {
        // wp_redirect( admin_url( 'options-general.php?page=referral-options' ) );
    }

    // public function column_created_at( $item ) {
        // return $item['created_at'];
    // }

    public function column_updated_at( $item ) {
        // return $item['updated_at'];
    }

    public function no_items() {
        _e( 'No subscribers available.', $this->plugin_text_domain );
    }

    public function get_hidden_columns() {
        return array();
    }

    public function column_default( $item, $column_name ) {
        return $item[$column_name];
    }
}

add_action('wp_head', function() {
?>
<style>
.delete-point {
    color: #b32d2e;
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
}
</style>
<?php
});