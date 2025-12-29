<div class="wrap">
    <h1><?php _e('Produtos', 'wp-smart-grocery'); ?></h1>
    <p><?php _e('Edite e exclua produtos cadastrados.', 'wp-smart-grocery'); ?></p>
    <?php
        $db = new WPSGL_Database();
        $categories = $db->get_categories();
        $products = $db->get_products();
    ?>
    <?php if (!empty($products)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Nome', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Categoria', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Preço padrão', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Unidade padrão', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Código de barras', 'wp-smart-grocery'); ?></th>
                    <th><?php _e('Ações', 'wp-smart-grocery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr data-id="<?php echo intval($p->id); ?>">
                    <td><?php echo intval($p->id); ?></td>
                    <td><input type="text" class="regular-text" value="<?php echo esc_attr($p->name); ?>" data-field="name"></td>
                    <td>
                        <select class="regular-text" data-field="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat); ?>" <?php selected($p->category, $cat); ?>>
                                    <?php echo esc_html($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0" class="regular-text" value="<?php echo esc_attr($p->unit_price); ?>" data-field="unit_price"></td>
                    <td><input type="text" class="regular-text" value="<?php echo esc_attr($p->default_unit); ?>" data-field="default_unit"></td>
                    <td><input type="text" class="regular-text" value="<?php echo esc_attr($p->barcode); ?>" data-field="barcode"></td>
                    <td>
                        <button class="button button-primary wpsgl-btn-update-product"><?php _e('Salvar', 'wp-smart-grocery'); ?></button>
                        <button class="button button-danger wpsgl-btn-delete-product"><?php _e('Excluir', 'wp-smart-grocery'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('Nenhum produto cadastrado.', 'wp-smart-grocery'); ?></p>
    <?php endif; ?>
</div>
