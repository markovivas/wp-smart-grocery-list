<?php

class WPSGL_List_Manager {
    private $database;

    public function __construct() {
        $this->database = new WPSGL_Database();
    }

    public function render_grocery_list() {
        ob_start();
        include WPSGL_PLUGIN_DIR . 'templates/frontend/grocery-list.php';
        return ob_get_clean();
    }

    public function get_current_list($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        global $wpdb;
        $table_lists = $wpdb->prefix . 'wpsgl_lists';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_lists} WHERE user_id = %d AND is_active = 1 ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }

    public function create_new_list($name, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        global $wpdb;
        $table_lists = $wpdb->prefix . 'wpsgl_lists';
        $wpdb->update($table_lists, ['is_active' => 0], ['user_id' => $user_id]);
        $wpdb->insert($table_lists, [
            'name' => $name,
            'user_id' => $user_id,
            'is_active' => 1,
            'created_at' => current_time('mysql')
        ]);
        return $this->get_current_list($user_id);
    }

    public function add_item_to_list($list_id, $item_data) {
        global $wpdb;
        $table_items = $wpdb->prefix . 'wpsgl_list_items';
        return $wpdb->insert($table_items, [
            'list_id' => $list_id,
            'product_name' => $item_data['name'],
            'category' => $item_data['category'],
            'quantity' => $item_data['quantity'] ?? 1,
            'price' => $item_data['price'] ?? 0,
            'created_at' => current_time('mysql')
        ]);
    }

    public function update_list_item($item_id, $data) {
        global $wpdb;
        $table_items = $wpdb->prefix . 'wpsgl_list_items';
        return $wpdb->update($table_items, $data, ['id' => $item_id]);
    }

    public function get_list_items($list_id) {
        global $wpdb;
        $table_items = $wpdb->prefix . 'wpsgl_list_items';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_items} WHERE list_id = %d ORDER BY is_checked, sort_order, created_at",
            $list_id
        ));
    }
}
