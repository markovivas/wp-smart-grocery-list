<?php

class WPSGL_Ajax_Handler {
    public function init() {
        add_action('wp_ajax_wpsgl_add_to_list', [$this, 'add_to_list']);
        add_action('wp_ajax_wpsgl_update_item', [$this, 'update_item']);
        add_action('wp_ajax_wpsgl_save_product', [$this, 'save_product']);
        add_action('wp_ajax_wpsgl_get_reports', [$this, 'get_reports']);
        add_action('wp_ajax_nopriv_wpsgl_save_product', [$this, 'save_product_guest']);
    }

    public function add_to_list() {
        check_ajax_referer('wpsgl_nonce', 'nonce');
        $product_name = sanitize_text_field($_POST['product_name']);
        $category = sanitize_text_field($_POST['category']);
        $list_manager = new WPSGL_List_Manager();
        $current_list = $list_manager->get_current_list();
        if (!$current_list) {
            $current_list = $list_manager->create_new_list(__('Minha Lista', 'wp-smart-grocery'));
        }
        $result = $list_manager->add_item_to_list($current_list->id, [
            'name' => $product_name,
            'category' => $category
        ]);
        if ($result) {
            wp_send_json_success(['message' => __('Item adicionado à lista!', 'wp-smart-grocery')]);
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
}
