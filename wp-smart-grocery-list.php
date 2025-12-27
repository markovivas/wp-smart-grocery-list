<?php
/**
 * Plugin Name: WP Smart Grocery List
 * Plugin URI: https://seusite.com/
 * Description: Sistema completo de lista de compras e cadastro de produtos
 * Version: 1.0.0
 * Author: Seu Nome
 * License: GPL v2 or later
 * Text Domain: wp-smart-grocery
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPSGL_VERSION', '1.0.0');
define('WPSGL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSGL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSGL_PLUGIN_FILE', __FILE__);

class WP_Smart_Grocery_List {
    private static $instance = null;
    private $database;
    private $product_manager;
    private $list_manager;
    private $reports;
    private $ajax_handler;

    public static function activate() {
        $db = new WPSGL_Database();
        $db->create_tables();
    }

    public static function deactivate() {
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once WPSGL_PLUGIN_DIR . 'includes/class-database.php';
        require_once WPSGL_PLUGIN_DIR . 'includes/class-product-manager.php';
        require_once WPSGL_PLUGIN_DIR . 'includes/class-list-manager.php';
        require_once WPSGL_PLUGIN_DIR . 'includes/class-reports.php';
        require_once WPSGL_PLUGIN_DIR . 'includes/class-ajax-handler.php';

        $this->database = new WPSGL_Database();
        $this->product_manager = new WPSGL_Product_Manager();
        $this->list_manager = new WPSGL_List_Manager();
        $this->reports = new WPSGL_Reports();
        $this->ajax_handler = new WPSGL_Ajax_Handler();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);

        add_action('plugins_loaded', [$this, 'init']);

        add_shortcode('smart_grocery_list', [$this->list_manager, 'render_grocery_list']);
        add_shortcode('product_registration', [$this->product_manager, 'render_product_form']);

        add_action('admin_menu', [$this, 'add_admin_menu']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        $this->database->init();
        $this->ajax_handler->init();
    }

    public function init() {
        load_plugin_textdomain('wp-smart-grocery', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'wpsgl-frontend',
            WPSGL_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            WPSGL_VERSION
        );

        wp_enqueue_script(
            'wpsgl-frontend',
            WPSGL_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            WPSGL_VERSION,
            true
        );

        wp_localize_script('wpsgl-frontend', 'wpsgl_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpsgl_nonce'),
            'products_data' => $this->get_products_data()
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wpsgl') !== false) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.7.0',
                true
            );

            wp_enqueue_style(
                'wpsgl-admin',
                WPSGL_PLUGIN_URL . 'assets/css/admin.css',
                [],
                WPSGL_VERSION
            );

            wp_enqueue_script(
                'wpsgl-admin',
                WPSGL_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'chartjs'],
                WPSGL_VERSION,
                true
            );

            wp_localize_script('wpsgl-admin', 'wpsgl_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpsgl_nonce')
            ]);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('WP Smart Grocery', 'wp-smart-grocery'),
            __('Smart Grocery', 'wp-smart-grocery'),
            'manage_options',
            'wpsgl-dashboard',
            [$this->reports, 'render_dashboard'],
            'dashicons-cart',
            30
        );

        add_submenu_page(
            'wpsgl-dashboard',
            __('Relatórios', 'wp-smart-grocery'),
            __('Relatórios', 'wp-smart-grocery'),
            'manage_options',
            'wpsgl-reports',
            [$this->reports, 'render_reports_page']
        );

        add_submenu_page(
            'wpsgl-dashboard',
            __('Produtos', 'wp-smart-grocery'),
            __('Produtos', 'wp-smart-grocery'),
            'manage_options',
            'wpsgl-products',
            [$this->product_manager, 'render_admin_products']
        );

        add_submenu_page(
            'wpsgl-dashboard',
            __('Configurações', 'wp-smart-grocery'),
            __('Configurações', 'wp-smart-grocery'),
            'manage_options',
            'wpsgl-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        include WPSGL_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    private function get_products_data() {
        $json_file = WPSGL_PLUGIN_DIR . 'data/produtos.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            return json_decode($json_data, true);
        }
        return [];
    }
}

WP_Smart_Grocery_List::get_instance();
