<div class="wrap">
    <h1><?php _e('Smart Grocery - Dashboard', 'wp-smart-grocery'); ?></h1>
    <p><?php _e('Resumo do mês atual.', 'wp-smart-grocery'); ?></p>
    <h2><?php _e('Compras de Hoje', 'wp-smart-grocery'); ?></h2>
    <ul>
        <?php foreach ($today_purchases as $p): ?>
            <li><?php echo esc_html($p->product_name); ?> - R$ <?php echo number_format($p->total_price, 2, ',', '.'); ?></li>
        <?php endforeach; ?>
        <?php if (empty($today_purchases)): ?>
            <li><?php _e('Sem compras hoje.', 'wp-smart-grocery'); ?></li>
        <?php endif; ?>
    </ul>
    <h2><?php _e('Estatísticas do Mês', 'wp-smart-grocery'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Categoria', 'wp-smart-grocery'); ?></th>
                <th><?php _e('Itens', 'wp-smart-grocery'); ?></th>
                <th><?php _e('Quantidade', 'wp-smart-grocery'); ?></th>
                <th><?php _e('Total', 'wp-smart-grocery'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($month_stats as $stat): ?>
                <tr>
                    <td><?php echo esc_html($stat->category); ?></td>
                    <td><?php echo intval($stat->total_items); ?></td>
                    <td><?php echo number_format($stat->total_quantity, 3, ',', '.'); ?></td>
                    <td><strong>R$ <?php echo number_format($stat->total_amount, 2, ',', '.'); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($month_stats)): ?>
                <tr><td colspan="4"><?php _e('Sem dados para o mês.', 'wp-smart-grocery'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
