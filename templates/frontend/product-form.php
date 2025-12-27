<?php
$product_manager = new WPSGL_Product_Manager();
$categories = $product_manager->get_categories();
?>
<div class="wpsgl-product-form-container">
    <h2><?php _e('Cadastro de Compras', 'wp-smart-grocery'); ?></h2>
    <form id="wpsgl-product-form" class="wpsgl-form">
        <div class="wpsgl-form-grid">
            <div class="wpsgl-form-group">
                <label for="product_name"><?php _e('Produto *', 'wp-smart-grocery'); ?></label>
                <input type="text" id="product_name" name="product_name" required list="product_suggestions" autocomplete="off">
                <datalist id="product_suggestions">
                    <?php foreach ($categories as $category): ?>
                        <?php $products = $product_manager->get_products_by_category($category); ?>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo esc_attr($product); ?>">
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="wpsgl-form-group">
                <label for="category"><?php _e('Categoria *', 'wp-smart-grocery'); ?></label>
                <select id="category" name="category" required>
                    <option value=""><?php _e('Selecione...', 'wp-smart-grocery'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wpsgl-form-group">
                <label for="quantity"><?php _e('Quantidade *', 'wp-smart-grocery'); ?></label>
                <input type="number" id="quantity" name="quantity" step="0.001" min="0" required>
            </div>
            <div class="wpsgl-form-group">
                <label for="unit"><?php _e('Unidade *', 'wp-smart-grocery'); ?></label>
                <select id="unit" name="unit" required>
                    <option value=""><?php _e('Selecione...', 'wp-smart-grocery'); ?></option>
                    <option value="un"><?php _e('Unidade', 'wp-smart-grocery'); ?></option>
                    <option value="kg"><?php _e('Quilograma (kg)', 'wp-smart-grocery'); ?></option>
                    <option value="g"><?php _e('Grama (g)', 'wp-smart-grocery'); ?></option>
                    <option value="l"><?php _e('Litro (l)', 'wp-smart-grocery'); ?></option>
                    <option value="ml"><?php _e('Mililitro (ml)', 'wp-smart-grocery'); ?></option>
                    <option value="cx"><?php _e('Caixa', 'wp-smart-grocery'); ?></option>
                    <option value="pct"><?php _e('Pacote', 'wp-smart-grocery'); ?></option>
                    <option value="dz"><?php _e('Dúzia', 'wp-smart-grocery'); ?></option>
                </select>
            </div>
            <div class="wpsgl-form-group">
                <label for="unit_price"><?php _e('Preço Unitário (R$) *', 'wp-smart-grocery'); ?></label>
                <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" required>
            </div>
            <div class="wpsgl-form-group">
                <label for="total_price"><?php _e('Total (R$)', 'wp-smart-grocery'); ?></label>
                <input type="number" id="total_price" name="total_price" step="0.01" min="0" readonly>
            </div>
            <div class="wpsgl-form-group">
                <label for="purchase_date"><?php _e('Data da Compra *', 'wp-smart-grocery'); ?></label>
                <input type="date" id="purchase_date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="wpsgl-form-group">
                <label for="purchase_time"><?php _e('Hora', 'wp-smart-grocery'); ?></label>
                <input type="time" id="purchase_time" name="purchase_time" value="<?php echo date('H:i'); ?>">
            </div>
            <div class="wpsgl-form-group">
                <label for="store"><?php _e('Loja/Supermercado', 'wp-smart-grocery'); ?></label>
                <input type="text" id="store" name="store">
            </div>
        </div>
        <div class="wpsgl-form-group">
            <label for="notes"><?php _e('Observações', 'wp-smart-grocery'); ?></label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="wpsgl-form-actions">
            <button type="submit" class="wpsgl-btn wpsgl-btn-primary"><?php _e('Salvar Compra', 'wp-smart-grocery'); ?></button>
            <button type="reset" class="wpsgl-btn wpsgl-btn-secondary"><?php _e('Limpar', 'wp-smart-grocery'); ?></button>
        </div>
        <input type="hidden" name="action" value="wpsgl_save_product">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpsgl_nonce'); ?>">
    </form>
    <div id="wpsgl-response-message" class="wpsgl-response"></div>
</div>
<script>
jQuery(document).ready(function($) {
    $('#quantity, #unit_price').on('input', function() {
        const quantity = parseFloat($('#quantity').val()) || 0;
        const unitPrice = parseFloat($('#unit_price').val()) || 0;
        const total = quantity * unitPrice;
        $('#total_price').val(total.toFixed(2));
    });
    $('#product_name').on('input', function() {
        const product = $(this).val().toLowerCase();
    });
    $('#wpsgl-product-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Salvando...');
        $.ajax({
            url: wpsgl_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                const $response = $('#wpsgl-response-message');
                if (response.success) {
                    $response.removeClass('error').addClass('success').text(response.data.message);
                    $('#wpsgl-product-form')[0].reset();
                    $('#total_price').val('');
                } else {
                    $response.removeClass('success').addClass('error').text(response.data.message);
                }
            },
            error: function() {
                $('#wpsgl-response-message').removeClass('success').addClass('error').text('Erro ao conectar com o servidor.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
