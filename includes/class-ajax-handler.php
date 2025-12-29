<?php

class WPSGL_Ajax_Handler {
    public function init() {
        add_action('wp_ajax_wpsgl_add_to_list', [$this, 'add_to_list']);
        add_action('wp_ajax_wpsgl_update_item', [$this, 'update_item']);
        add_action('wp_ajax_wpsgl_save_product', [$this, 'save_product']);
        add_action('wp_ajax_wpsgl_get_reports', [$this, 'get_reports']);
        add_action('wp_ajax_nopriv_wpsgl_save_product', [$this, 'save_product_guest']);
        add_action('wp_ajax_wpsgl_save_catalog_product', [$this, 'save_catalog_product']);
        add_action('wp_ajax_wpsgl_update_catalog_product', [$this, 'update_catalog_product']);
        add_action('wp_ajax_wpsgl_delete_catalog_product', [$this, 'delete_catalog_product']);
        add_action('wp_ajax_wpsgl_delete_item', [$this, 'delete_item']);
        add_action('wp_ajax_wpsgl_lookup_barcode', [$this, 'lookup_barcode']);
        add_action('wp_ajax_wpsgl_openfoodfacts', [$this, 'openfoodfacts']);
        add_action('wp_ajax_wpsgl_export_products', [$this, 'export_products']);
        add_action('wp_ajax_wpsgl_import_products', [$this, 'import_products']);
    }

    public function add_to_list() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $product_name = sanitize_text_field($_POST['product_name']);
        $category = sanitize_text_field($_POST['category']);
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        $db = new WPSGL_Database();
        $product_id = null;
        if ($barcode !== '') {
            $prod = $db->get_product_by_barcode($barcode);
            if ($prod) {
                $product_id = intval($prod->id);
                $product_name = $prod->name;
                $category = $prod->category;
            }
        }
        $list_manager = new WPSGL_List_Manager();
        $current_list = $list_manager->get_current_list();
        if (!$current_list) {
            $current_list = $list_manager->create_new_list(__('Minha Lista', 'wp-smart-grocery'));
        }
        $insert_id = $list_manager->add_item_to_list($current_list->id, [
            'name' => $product_name,
            'category' => $category,
            'product_id' => $product_id
        ]);
        if ($insert_id) {
            wp_send_json_success(['message' => __('Item adicionado à lista!', 'wp-smart-grocery'), 'item_id' => $insert_id]);
        } else {
            wp_send_json_error(['message' => __('Erro ao adicionar item.', 'wp-smart-grocery')]);
        }
    }

    public function update_item() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $item_id = intval($_POST['item_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = $_POST['value'];
        $list_manager = new WPSGL_List_Manager();
        switch ($field) {
            case 'is_checked':
                $value = boolval($value);
                $data = [
                    'is_checked' => $value,
                    'checked_at' => $value ? current_time('mysql') : null
                ];
                break;
            case 'quantity':
                $data = ['quantity' => sanitize_text_field($value)];
                break;
            case 'price':
                $data = ['price' => floatval($value)];
                break;
            case 'notes':
                $data = ['notes' => sanitize_textarea_field($value)];
                break;
            default:
                wp_send_json_error(['message' => __('Campo inválido.', 'wp-smart-grocery')]);
        }
        $result = $list_manager->update_list_item($item_id, $data);
        if ($result !== false) {
            wp_send_json_success(['message' => __('Item atualizado!', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao atualizar item.', 'wp-smart-grocery')]);
        }
    }

    public function save_product() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $product_manager = new WPSGL_Product_Manager();
        $product_manager->save_product_from_form();
    }

    public function save_product_guest() {
        wp_send_json_error(['message' => __('Faça login para salvar produtos.', 'wp-smart-grocery')]);
    }

    public function get_reports() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $reports = new WPSGL_Reports();
        $data = $reports->generate_chart_data($start_date, $end_date);
        wp_send_json_success($data);
    }

    public function save_catalog_product() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permissão insuficiente.', 'wp-smart-grocery')], 403);
        }
        $name = sanitize_text_field($_POST['name'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $unit_price = isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : null;
        $default_unit = sanitize_text_field($_POST['default_unit'] ?? '');
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : null;
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if ($name === '' || $category === '') {
            wp_send_json_error(['message' => __('Nome e categoria são obrigatórios.', 'wp-smart-grocery')]);
        }
        $db = new WPSGL_Database();
        $data = [
            'name' => $name,
            'category' => $category,
            'barcode' => $barcode ?: null,
            'is_custom' => 1,
            'user_id' => get_current_user_id()
        ];
        $db->ensure_category_exists($category);
        if ($unit_price !== null) {
            $data['unit_price'] = $unit_price;
        }
        if ($default_unit !== '') {
            $data['default_unit'] = $default_unit;
        }
        if ($image_id) {
            $data['image_id'] = $image_id;
        }
        $result = $db->save_product($data);
        if ($result) {
            wp_send_json_success(['message' => __('Produto cadastrado.', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao cadastrar produto.', 'wp-smart-grocery')]);
        }
    }

    public function update_catalog_product() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permissão insuficiente.', 'wp-smart-grocery')], 403);
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(['message' => __('ID inválido.', 'wp-smart-grocery')]);
        }
        $name = sanitize_text_field($_POST['name'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $unit_price = isset($_POST['unit_price']) ? floatval($_POST['unit_price']) : null;
        $default_unit = sanitize_text_field($_POST['default_unit'] ?? '');
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if ($name === '' || $category === '') {
            wp_send_json_error(['message' => __('Nome e categoria são obrigatórios.', 'wp-smart-grocery')]);
        }
        $db = new WPSGL_Database();
        $db->ensure_category_exists($category);
        $data = [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'unit_price' => $unit_price,
            'default_unit' => $default_unit,
            'barcode' => $barcode ?: null,
            'updated_at' => current_time('mysql')
        ];
        $result = $db->save_product($data);
        if ($result !== false) {
            wp_send_json_success(['message' => __('Produto atualizado.', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao atualizar produto.', 'wp-smart-grocery')]);
        }
    }

    public function delete_catalog_product() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permissão insuficiente.', 'wp-smart-grocery')], 403);
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(['message' => __('ID inválido.', 'wp-smart-grocery')]);
        }
        $db = new WPSGL_Database();
        $deleted = $db->delete_product($id);
        if ($deleted) {
            wp_send_json_success(['message' => __('Produto excluído.', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao excluir produto.', 'wp-smart-grocery')]);
        }
    }

    public function export_products() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'wpsgl_nonce')) {
            wp_die(__('Nonce inválido', 'wp-smart-grocery'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissão insuficiente', 'wp-smart-grocery'));
        }
        $db = new WPSGL_Database();
        $products = $db->get_products();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wp-smart-grocery-products.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','name','category','unit_price','default_unit','barcode','is_custom']);
        if ($products) {
            foreach ($products as $p) {
                fputcsv($out, [
                    intval($p->id),
                    $p->name,
                    $p->category,
                    isset($p->unit_price) ? $p->unit_price : '',
                    isset($p->default_unit) ? $p->default_unit : '',
                    isset($p->barcode) ? $p->barcode : '',
                    isset($p->is_custom) ? intval($p->is_custom) : 0
                ]);
            }
        }
        fclose($out);
        wp_die();
    }

    public function import_products() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permissão insuficiente.', 'wp-smart-grocery')], 403);
        }
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            wp_send_json_error(['message' => __('Arquivo inválido.', 'wp-smart-grocery')]);
        }
        $tmp = $_FILES['file']['tmp_name'];
        $mime = $_FILES['file']['type'];
        $size = filesize($tmp);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => __('Tamanho de arquivo não suportado.', 'wp-smart-grocery')]);
        }
        $db = new WPSGL_Database();
        $inserted = 0; $updated = 0; $errors = 0;
        $handle = fopen($tmp, 'r');
        if (!$handle) {
            wp_send_json_error(['message' => __('Falha ao abrir arquivo.', 'wp-smart-grocery')]);
        }
        $header = fgetcsv($handle, 0, ',');
        $map = [];
        $expected = ['id','name','category','unit_price','default_unit','barcode','is_custom'];
        if (is_array($header)) {
            foreach ($header as $i => $col) {
                $key = strtolower(trim($col));
                $map[$key] = $i;
            }
        }
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $name = isset($map['name']) ? sanitize_text_field($row[$map['name']]) : '';
            $category = isset($map['category']) ? sanitize_text_field($row[$map['category']]) : '';
            $unit_price = isset($map['unit_price']) ? floatval($row[$map['unit_price']]) : null;
            $default_unit = isset($map['default_unit']) ? sanitize_text_field($row[$map['default_unit']]) : '';
            $barcode = isset($map['barcode']) ? sanitize_text_field($row[$map['barcode']]) : '';
            $id = isset($map['id']) ? intval($row[$map['id']]) : 0;
            if ($name === '' || $category === '') { $errors++; continue; }
            $db->ensure_category_exists($category);
            $data = [
                'name' => $name,
                'category' => $category
            ];
            if ($unit_price !== null) $data['unit_price'] = $unit_price;
            if ($default_unit !== '') $data['default_unit'] = $default_unit;
            if ($barcode !== '') $data['barcode'] = $barcode;
            if ($id > 0) {
                $data['id'] = $id;
                $res = $db->save_product($data);
                if ($res !== false) $updated++; else $errors++;
            } else if ($barcode !== '') {
                $existing = $db->get_product_by_barcode($barcode);
                if ($existing) {
                    $data['id'] = intval($existing->id);
                    $res = $db->save_product($data);
                    if ($res !== false) $updated++; else $errors++;
                } else {
                    $data['is_custom'] = 1;
                    $data['user_id'] = get_current_user_id();
                    $res = $db->save_product($data);
                    if ($res) $inserted++; else $errors++;
                }
            } else {
                $data['is_custom'] = 1;
                $data['user_id'] = get_current_user_id();
                $res = $db->save_product($data);
                if ($res) $inserted++; else $errors++;
            }
        }
        fclose($handle);
        wp_send_json_success(['inserted' => $inserted, 'updated' => $updated, 'errors' => $errors]);
    }

    public function delete_item() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $item_id = intval($_POST['item_id']);
        if ($item_id <= 0) {
            wp_send_json_error(['message' => __('Item inválido.', 'wp-smart-grocery')]);
        }
        global $wpdb;
        $table_items = $wpdb->prefix . 'wpsgl_list_items';
        $deleted = $wpdb->delete($table_items, ['id' => $item_id]);
        if ($deleted) {
            wp_send_json_success(['message' => __('Item excluído da lista.', 'wp-smart-grocery')]);
        } else {
            wp_send_json_error(['message' => __('Erro ao excluir item.', 'wp-smart-grocery')]);
        }
    }

    public function lookup_barcode() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if ($barcode === '') {
            wp_send_json_error(['message' => __('Código de barras vazio.', 'wp-smart-grocery')]);
        }
        $db = new WPSGL_Database();
        $prod = $db->get_product_by_barcode($barcode);
        if ($prod) {
            wp_send_json_success([
                'id' => intval($prod->id),
                'name' => $prod->name,
                'category' => $prod->category,
                'unit_price' => isset($prod->unit_price) ? floatval($prod->unit_price) : null,
                'default_unit' => isset($prod->default_unit) ? $prod->default_unit : null
            ]);
        } else {
            wp_send_json_error(['message' => __('Produto não encontrado para o código informado.', 'wp-smart-grocery')]);
        }
    }

    public function openfoodfacts() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        if ($barcode === '') {
            wp_send_json_error(['message' => __('Código de barras vazio.', 'wp-smart-grocery')]);
        }
        $url = 'https://world.openfoodfacts.org/api/v0/product/' . rawurlencode($barcode) . '.json';
        $response = wp_remote_get($url, ['timeout' => 8]);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Falha ao consultar Open Food Facts.', 'wp-smart-grocery')]);
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        if ($code !== 200 || !is_array($json) || !isset($json['status']) || intval($json['status']) !== 1) {
            wp_send_json_error(['message' => __('Produto não encontrado na Open Food Facts.', 'wp-smart-grocery')]);
        }
        $p = $json['product'];
        $name = isset($p['product_name']) ? sanitize_text_field($p['product_name']) : '';
        $brand = isset($p['brands']) ? sanitize_text_field($p['brands']) : '';
        $cats = [];
        if (isset($p['categories_tags']) && is_array($p['categories_tags'])) {
            foreach ($p['categories_tags'] as $c) {
                $cats[] = sanitize_text_field($c);
            }
        }
        $image = isset($p['image_front_url']) ? esc_url_raw($p['image_front_url']) : '';
        wp_send_json_success([
            'name' => $name,
            'brand' => $brand,
            'categories' => $cats,
            'image' => $image
        ]);
    }
}
