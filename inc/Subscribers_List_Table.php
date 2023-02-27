<?php

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Subscribers_List_Table extends \WP_List_Table {
    public function __construct() {
        parent::__construct( [
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false,
        ] );
    }

    public function get_columns() {

        $columns = array(
            // 'name'       => __( 'Name', 'itc-refer-a-friend' ),
            // 'email'      => __( 'Email', 'itc-refer-a-friend' ),
            'points'     => __( 'Points', 'itc-refer-a-friend' ),
            'update'     => __( 'Update Points', 'itc-refer-a-friend' ),
            'created_at' => __( 'Registration Date', 'itc-refer-a-friend' ),
            'expired_at' => __( 'Expired Date', 'itc-refer-a-friend' ),
            'total_points'  => 'total points'
        );

        return $columns;

    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $column   = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $per_page = 20;

        $this->_column_headers = [$column, $hidden, $sortable];

        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        $args = [
            'number' => $per_page,
            'offset' => $offset,
        ];

        $this->items = $this->itc_get_subscribers( $args );
        echo '<pre>';
              print_r( $this->itc_get_subscribers( $args ) );
        echo '</pre>';
        
        $this->set_pagination_args( [
            'total_items' => $this->itc_subscribers_count(),
            'per_page'    => $per_page,
        ] );

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

        return $items;

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
        // return "<input type='checkbox' name='bulk-delete[]' value='{$item["id"]}'/>";
    } 
    
    public function column_total_points( $item ) {
        // return "<input type='checkbox' name='bulk-delete[]' value='{$item["id"]}'/>";
    }

    public function column_name( $item ) {
        // return $item['name'];
    }

    public function column_email( $item ) {
        // return $item['email'];
    }

    public function column_points( $item ) {
        // return $item['points'];
    }

    public function column_update( $item ) {
        // return $item['update'];
        // return sprintf(
        //     '<a href="%1$s"><strong>%2$s</strong></a> %3$s',
        //     admin_url( '#' . 1 ),
        //     $item->name
        // );
    }

    public function column_created_at( $item ) {
        // return $item['created_at'];
    }

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
        // switch ( $column_name ) {
        // case 'value':
        //     break;

        // default:
        //     return isset( $item[$column_name] ) ? $item[$column_name] : '';
        // }

        return $item->$column_name;
    }
}
