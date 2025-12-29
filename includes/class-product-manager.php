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

        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        $product_name = sanitize_text_field($_POST['product_name']);
        $category = sanitize_text_field($_POST['category']);

        if ($barcode !== '' && !$this->is_valid_barcode($barcode)) {
            wp_send_json_error(['message' => __('Código de barras inválido.', 'wp-smart-grocery')]);
        }

        $product_id = null;
        if ($barcode !== '') {
            $existing = $this->database->get_product_by_barcode($barcode);
            if ($existing) {
                $product_id = intval($existing->id);
                $product_name = $existing->name;
                $category = $existing->category;
                $this->database->ensure_category_exists($category);
            } else {
                $this->database->save_product([
                    'name' => $product_name,
                    'category' => $category,
                    'barcode' => $barcode,
                    'is_custom' => 1,
                    'user_id' => get_current_user_id(),
                    'unit_price' => isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : null,
                    'default_unit' => sanitize_text_field($_POST['unit'] ?? '')
                ]);
                // Obtém o produto recém cadastrado
                $created = $this->database->get_product_by_barcode($barcode);
                if ($created) {
                    $product_id = intval($created->id);
                    $this->database->ensure_category_exists($category);
                }
            }
        }

        if ($category === '') {
            $category = 'GERAL';
        }
        $this->database->ensure_category_exists($category);

        $data = [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'quantity' => floatval($_POST['quantity']),
            'unit' => sanitize_text_field($_POST['unit']),
            'unit_price' => floatval($_POST['unit_price']),
            'total_price' => floatval($_POST['quantity']) * floatval($_POST['unit_price']),
            'purchase_date' => sanitize_text_field($_POST['purchase_date']),
            'purchase_time' => sanitize_text_field($_POST['purchase_time']),
            'store' => sanitize_text_field($_POST['store']),
            'category' => $category,
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

    private function is_valid_barcode($code) {
        if (!preg_match('/^\d+$/', $code)) return false;
        $len = strlen($code);
        if ($len === 13) {
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $n = intval($code[$i]);
                $sum += ($i % 2 === 0) ? $n : $n * 3;
            }
            $check = (10 - ($sum % 10)) % 10;
            return $check === intval($code[12]);
        }
        if ($len === 8) {
            $sum = 0;
            for ($i = 0; $i < 7; $i++) {
                $n = intval($code[$i]);
                $sum += ($i % 2 === 0) ? $n * 3 : $n;
            }
            $check = (10 - ($sum % 10)) % 10;
            return $check === intval($code[7]);
        }
        if ($len === 12) {
            $sumOdd = 0; $sumEven = 0;
            for ($i = 0; $i < 11; $i++) {
                $n = intval($code[$i]);
                if ($i % 2 === 0) $sumOdd += $n; else $sumEven += $n;
            }
            $total = ($sumOdd * 3) + $sumEven;
            $check = (10 - ($total % 10)) % 10;
            return $check === intval($code[11]);
        }
        return false;
    }

    public function get_categories() {
        return $this->database->get_categories();
    }

    public function get_products_by_category($category) {
        $rows = $this->database->get_products($category);
        if (!$rows) {
            return [];
        }
        $names = [];
        foreach ($rows as $row) {
            $names[] = $row->name;
        }
        return $names;
    }

    public function get_product_rows_by_category($category) {
        return $this->database->get_products_with_barcode($category);
    }
}
