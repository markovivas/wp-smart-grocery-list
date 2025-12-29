<?php

class WPSGL_Reports {
    private $database;

    public function __construct() {
        $this->database = new WPSGL_Database();
    }

    public function render_dashboard() {
        $today = date('Y-m-d');
        $first_day_month = date('Y-m-01');
        $last_day_month = date('Y-m-t');
        $today_purchases = $this->database->get_purchases($today, $today);
        $month_purchases = $this->database->get_purchases($first_day_month, $last_day_month);
        $month_stats = $this->database->get_purchase_stats($first_day_month, $last_day_month);
        include WPSGL_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function render_reports_page() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        // Exportação via admin-ajax (wpsgl_export_purchases)
        $purchases = $this->database->get_purchases($start_date, $end_date, $category);
        $stats = $this->database->get_purchase_stats($start_date, $end_date);
        $product_manager = new WPSGL_Product_Manager();
        $categories = $product_manager->get_categories();
        include WPSGL_PLUGIN_DIR . 'templates/admin/reports.php';
    }

    public function generate_chart_data($start_date, $end_date) {
        $stats = $this->database->get_purchase_stats($start_date, $end_date);
        $labels = [];
        $data = [];
        $colors = [];
        foreach ($stats as $stat) {
            $labels[] = $stat->category;
            $data[] = (float) $stat->total_amount;
            $colors[] = $this->generate_color($stat->category);
        }
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __('Gasto por Categoria', 'wp-smart-grocery'),
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 1
            ]]
        ];
    }

    private function generate_color($text) {
        $hash = md5($text);
        return sprintf('#%s', substr($hash, 0, 6));
    }

    // Export removido: agora realizado via WPSGL_Ajax_Handler::export_purchases
}
