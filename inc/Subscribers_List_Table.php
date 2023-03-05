<?php
if (!defined('ABSPATH')) {
    exit;
}

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Subscribers_List_Table extends \WP_List_Table {
    private $_items;
    public function __construct( $data ) {
        parent::__construct( [
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false,
        ] );
        $this->_items = $data;
    }

    public function get_columns() {

        $columns = array(
            'cb'       => __( '<input type="checkbox" />', 'itc-refer-a-friend' ),
            'created_at'      => __( 'Created At', 'itc-refer-a-friend' ),
            'expire_date'      => __( 'Expired At', 'itc-refer-a-friend' ),
            'user_id'      => __( 'User ID', 'itc-refer-a-friend' ),
            // 'points'     => __( 'Points', 'itc-refer-a-friend' ),
            'accept_total_points'  => 'Total Points',
            'update'     => __( 'Update Points', 'itc-refer-a-friend' ),
            // 'update'     => __( 'Update Points', 'itc-refer-a-friend' ),
            // 'created_at' => __( 'Registration Date', 'itc-refer-a-friend' ),
            // 'expired_at' => __( 'Expired Date', 'itc-refer-a-friend' ),
        );

        return $columns;

    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $per_page = 2;
        $total_items = count( $this->_items );
        $current_page = $this->get_pagenum();
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        $data = array_slice( $this->_items, ($current_page - 1) * $per_page, $per_page );
        $this->items = $data;
        $this->_column_headers = [ $this->get_columns(), [], [] ];

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

        // $items[0]->accepted_user_id;
        // $user_id = $items->accepted_user_id;
        $items = $wpdb->query(
            "SELECT display_name FROM {$wpdb->prefix}users where ID =".  $items
        );


        // $items = $items->display_name;
        // echo gettype($items);
        // return $items;

        // echo '<pre>';
        //       print_r( $this->items );
        // echo '</pre>';
        // die;
        // return $items;

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
        var_dump($item);
        
        // return $item['name'];
    }

    public function column_email( $item ) {
        // return $item['email'];
    }

    public function column_points( $item ) {
        // return $item['points'];
    }

    public function column_update( $item ) {
        wp_nonce_field( "update_point_nonce", "nonce" );
        return sprintf(
            '<form method="post" action="'. admin_url('admin-post.php').'"><input type="number" name="update_point" value="'.$item['accept_total_points'].'"/>
            <input type="hidden" name="action" value="itc_update_point"/>
            <button name="itc_update_point_button" class="update_point_btn button-primary">Update</button></form>'
        );
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