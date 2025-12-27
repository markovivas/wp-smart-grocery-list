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
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->export_csv($start_date, $end_date);
            return;
        }
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

    public function export_csv($start_date, $end_date) {
        $purchases = $this->database->get_purchases($start_date, $end_date);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio-compras-' . $start_date . '-' . $end_date . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('Data', 'wp-smart-grocery'),
            __('Hora', 'wp-smart-grocery'),
            __('Produto', 'wp-smart-grocery'),
            __('Categoria', 'wp-smart-grocery'),
            __('Quantidade', 'wp-smart-grocery'),
            __('Unidade', 'wp-smart-grocery'),
            __('Preço Unitário', 'wp-smart-grocery'),
            __('Total', 'wp-smart-grocery'),
            __('Loja', 'wp-smart-grocery'),
            __('Observações', 'wp-smart-grocery')
        ], ';');
        foreach ($purchases as $purchase) {
            fputcsv($output, [
                $purchase->purchase_date,
                $purchase->purchase_time,
                $purchase->product_name,
                $purchase->category,
                $purchase->quantity,
                $purchase->unit,
                number_format($purchase->unit_price, 2, ',', '.'),
                number_format($purchase->total_price, 2, ',', '.'),
                $purchase->store,
                $purchase->notes
            ], ';');
        }
        fclose($output);
        exit;
    }
}
