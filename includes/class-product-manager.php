<?php

class WPSGL_Product_Manager {
    private $database;

    public function __construct() {
        $this->database = new WPSGL_Database();
    }

    public function render_product_form() {
        ob_start();
        include WPSGL_PLUGIN_DIR . 'templates/frontend/product-form.php';
        return ob_get_clean();
    }

    public function render_admin_products() {
        include WPSGL_PLUGIN_DIR . 'templates/admin/products.php';
    }

    public function save_product_from_form() {
        if (!wp_verify_nonce($_POST['nonce'], 'wpsgl_nonce')) {
            wp_die('Nonce inválido');
        }

        $data = [
            'product_name' => sanitize_text_field($_POST['product_name']),
            'quantity' => floatval($_POST['quantity']),
            'unit' => sanitize_text_field($_POST['unit']),
            'unit_price' => floatval($_POST['unit_price']),
            'total_price' => floatval($_POST['quantity']) * floatval($_POST['unit_price']),
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'purchase_time' => sanitize_text_field($_POST['purchase_time']),
            'store' => sanitize_text_field($_POST['store']),
            'category' => sanitize_text_field($_POST['category']),
            'user_id' => get_current_user_id(),
            'notes' => sanitize_textarea_field($_POST['notes'])
        ];

        $result = $this->database->save_purchase($data);

        if ($result) {
            wp_send_json_success(['message' => __('Produto salvo com sucesso!', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao salvar produto.', 'wp-smart-grocery')]);
        }
    }

    public function get_categories() {
        $json_file = WPSGL_PLUGIN_DIR . 'data/produtos.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $data = json_decode($json_data, true);
            if (isset($data['categorias'])) {
                return array_keys($data['categorias']);
            }
        }
        return [];
    }

    public function get_products_by_category($category) {
        $json_file = WPSGL_PLUGIN_DIR . 'data/produtos.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $data = json_decode($json_data, true);
            if (isset($data['categorias'][$category])) {
                return $data['categorias'][$category];
            }
        }
        return [];
    }
}
