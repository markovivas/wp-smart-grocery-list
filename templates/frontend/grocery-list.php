<?php
$list_manager = new WPSGL_List_Manager();
$current_list = $list_manager->get_current_list();
$list_items = $current_list ? $list_manager->get_list_items($current_list->id) : [];
$json_file = WPSGL_PLUGIN_DIR . 'data/produtos.json';
$products_data = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
?>
<div class="wpsgl-container" data-theme="light">
    <header class="wpsgl-header">
        <h1><?php _e('Lista de Compras Inteligente', 'wp-smart-grocery'); ?></h1>
        <div class="wpsgl-actions">
            <button class="wpsgl-btn wpsgl-btn-new-list">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                <?php _e('Nova Lista', 'wp-smart-grocery'); ?>
            </button>
            <button class="wpsgl-btn wpsgl-btn-toggle-theme">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-3.03 0-5.5-2.47-5.5-5.5 0-1.82.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
            </button>
        </div>
    </header>
    <section class="wpsgl-active-list">
        <h2><?php echo $current_list ? esc_html($current_list->name) : __('Minha Lista', 'wp-smart-grocery'); ?></h2>
        <div class="wpsgl-list-items">
            <?php if (empty($list_items)): ?>
                <div class="wpsgl-empty-state">
                    <p><?php _e('Sua lista está vazia. Clique em produtos das categorias para adicionar.', 'wp-smart-grocery'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($list_items as $item): ?>
                <div class="wpsgl-list-item" data-item-id="<?php echo $item->id; ?>">
                    <label class="wpsgl-checkbox">
                        <input type="checkbox" <?php checked($item->is_checked, 1); ?>>
                        <span class="checkmark"></span>
                    </label>
                    <div class="wpsgl-item-content">
                        <div class="wpsgl-item-name"><?php echo esc_html($item->product_name); ?></div>
                        <div class="wpsgl-item-details">
                            <input type="text" class="wpsgl-quantity" value="<?php echo esc_attr($item->quantity); ?>" placeholder="<?php _e('Qtd', 'wp-smart-grocery'); ?>">
                            <input type="number" class="wpsgl-price" value="<?php echo esc_attr($item->price); ?>" step="0.01" min="0" placeholder="<?php _e('R$', 'wp-smart-grocery'); ?>">
                            <textarea class="wpsgl-notes" placeholder="<?php _e('Notas...', 'wp-smart-grocery'); ?>"><?php echo esc_textarea($item->notes); ?></textarea>
                        </div>
                    </div>
                    <div class="wpsgl-item-actions">
                        <button class="wpsgl-btn-more" title="<?php _e('Mais opções', 'wp-smart-grocery'); ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="6" r="2" fill="currentColor"/><circle cx="12" cy="12" r="2" fill="currentColor"/><circle cx="12" cy="18" r="2" fill="currentColor"/></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($list_items): ?>
        <div class="wpsgl-list-total">
            <strong><?php _e('Total Estimado:', 'wp-smart-grocery'); ?></strong>
            <span class="wpsgl-total-amount">
                R$
                <?php
                $total = 0;
                foreach ($list_items as $li) { $total += floatval($li->price); }
                echo number_format($total, 2, ',', '.');
                ?>
            </span>
        </div>
        <?php endif; ?>
    </section>
    <section class="wpsgl-categories">
        <h2><?php _e('Categorias', 'wp-smart-grocery'); ?></h2>
        <div class="wpsgl-categories-grid">
            <?php if (isset($products_data['categorias'])): ?>
                <?php foreach ($products_data['categorias'] as $category_name => $products): ?>
                <div class="wpsgl-category">
                    <h3 class="wpsgl-category-title">
                        <button class="wpsgl-category-toggle" aria-expanded="false">
                            <span><?php echo esc_html($category_name); ?></span>
                            <svg class="wpsgl-chevron" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
                        </button>
                    </h3>
                    <div class="wpsgl-category-products" style="max-height:0;padding:0;">
                        <?php foreach ($products as $product): ?>
                        <button class="wpsgl-product-btn" data-product="<?php echo esc_attr($product); ?>" data-category="<?php echo esc_attr($category_name); ?>">
                            <?php echo esc_html($product); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<div class="wpsgl-modal" id="wpsgl-options-modal">
    <div class="wpsgl-modal-content">
        <h3><?php _e('Opções do Item', 'wp-smart-grocery'); ?></h3>
        <form id="wpsgl-options-form">
            <div class="wpsgl-form-group">
                <label><?php _e('Quantidade', 'wp-smart-grocery'); ?></label>
                <input type="text" name="quantity" placeholder="ex: 1kg, 2 unidades">
            </div>
            <div class="wpsgl-form-group">
                <label><?php _e('Preço (R$)', 'wp-smart-grocery'); ?></label>
                <input type="number" name="price" step="0.01" min="0">
            </div>
            <div class="wpsgl-form-group">
                <label><?php _e('Notas', 'wp-smart-grocery'); ?></label>
                <textarea name="notes" rows="3"></textarea>
            </div>
            <div class="wpsgl-form-group">
                <label><?php _e('Categoria', 'wp-smart-grocery'); ?></label>
                <select name="category">
                    <?php foreach (array_keys($products_data['categorias'] ?? []) as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wpsgl-form-group">
                <label><?php _e('Imagem', 'wp-smart-grocery'); ?></label>
                <button type="button" class="wpsgl-btn-upload-image"><?php _e('Selecionar Imagem', 'wp-smart-grocery'); ?></button>
                <input type="hidden" name="image_id">
            </div>
            <div class="wpsgl-modal-actions">
                <button type="button" class="wpsgl-btn wpsgl-btn-cancel"><?php _e('Cancelar', 'wp-smart-grocery'); ?></button>
                <button type="submit" class="wpsgl-btn wpsgl-btn-primary"><?php _e('Salvar', 'wp-smart-grocery'); ?></button>
            </div>
        </form>
    </div>
    </div>
