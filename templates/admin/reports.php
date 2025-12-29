<?php
$reports = new WPSGL_Reports();
?>
<div class="wrap wpsgl-admin-wrap">
    <h1><?php _e('Relatórios de Compras', 'wp-smart-grocery'); ?></h1>
    <div class="wpsgl-filters">
        <form id="filter-form" method="get" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="wpsgl-reports">
            <div class="wpsgl-filter-row">
                <div class="wpsgl-filter-group">
                    <label for="start_date"><?php _e('Data Inicial', 'wp-smart-grocery'); ?></label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>" required>
                </div>
                <div class="wpsgl-filter-group">
                    <label for="end_date"><?php _e('Data Final', 'wp-smart-grocery'); ?></label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>" required>
                </div>
                <div class="wpsgl-filter-group">
                    <label for="category"><?php _e('Categoria', 'wp-smart-grocery'); ?></label>
                    <select id="category" name="category">
                        <option value=""><?php _e('Todas', 'wp-smart-grocery'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpsgl-filter-group">
                    <button type="submit" class="button button-primary"><?php _e('Filtrar', 'wp-smart-grocery'); ?></button>
                    <a href="<?php echo add_query_arg([
                        'action' => 'wpsgl_export_purchases',
                        'nonce' => wp_create_nonce('wpsgl_nonce'),
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'category' => $category
                    ], admin_url('admin-ajax.php')); ?>" class="button"><?php _e('Exportar CSV', 'wp-smart-grocery'); ?></a>
                </div>
            </div>
        </form>
    </div>
    <div class="wpsgl-stats-cards">
        <div class="wpsgl-stat-card">
            <div class="stat-number"><?php echo count($purchases); ?></div>
            <div class="stat-label"><?php _e('Itens Comprados', 'wp-smart-grocery'); ?></div>
        </div>
        <div class="wpsgl-stat-card">
            <div class="stat-number">
                R$
                <?php
                $total = 0;
                foreach ($purchases as $p) { $total += floatval($p->total_price); }
                echo number_format($total, 2, ',', '.');
                ?>
            </div>
            <div class="stat-label"><?php _e('Total Gasto', 'wp-smart-grocery'); ?></div>
        </div>
        <div class="wpsgl-stat-card">
            <div class="stat-number">
                R$
                <?php
                $avg = count($purchases) ? $total / count($purchases) : 0;
                echo number_format($avg, 2, ',', '.');
                ?>
            </div>
            <div class="stat-label"><?php _e('Média por Item', 'wp-smart-grocery'); ?></div>
        </div>
    </div>
    <div class="wpsgl-data-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Data', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Produto', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Categoria', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Quantidade', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Preço Unit.', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Total', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Loja', 'wp-smart-grocery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="7" class="text-center"><?php _e('Nenhuma compra encontrada no período.', 'wp-smart-grocery'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($purchase->purchase_date)); ?></td>
                        <td><?php echo esc_html($purchase->product_name); ?></td>
                        <td><?php echo esc_html($purchase->category); ?></td>
                        <td><?php echo number_format($purchase->quantity, 3, ',', '.'); ?> <?php echo esc_html($purchase->unit); ?></td>
                        <td>R$ <?php echo number_format($purchase->unit_price, 2, ',', '.'); ?></td>
                        <td><strong>R$ <?php echo number_format($purchase->total_price, 2, ',', '.'); ?></strong></td>
                        <td><?php echo esc_html($purchase->store); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="wpsgl-chart-container compact">
        <canvas id="wpsgl-chart" style="height:240px;"></canvas>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('wpsgl-chart').getContext('2d');
    $.ajax({
        url: wpsgl_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'wpsgl_get_reports',
            nonce: wpsgl_admin.nonce,
            start_date: '<?php echo $start_date; ?>',
            end_date: '<?php echo $end_date; ?>'
        },
        success: function(response) {
            if (response.success) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: response.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '60%',
                        plugins: {
                            legend: { 
                                position: 'bottom',
                                labels: { boxWidth: 10, color: '#555', font: { size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return label + ': R$ ' + Number(value).toFixed(2) + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        layout: { padding: 4 }
                    }
                });
            }
        }
    });
});
</script>
