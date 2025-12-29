<div class="wrap">
    <h1><?php _e('Produtos', 'wp-smart-grocery'); ?></h1>
    <p><?php _e('Cadastre e gerencie os produtos diretamente no WordPress.', 'wp-smart-grocery'); ?></p>
    <?php
        $db = new WPSGL_Database();
        $categories = $db->get_categories();
    ?>
    <h2><?php _e('Cadastrar novo produto', 'wp-smart-grocery'); ?></h2>
    <form id="wpsgl-catalog-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wpsgl_name"><?php _e('Nome', 'wp-smart-grocery'); ?></label></th>
                <td><input name="name" id="wpsgl_name" type="text" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="wpsgl_category"><?php _e('Categoria', 'wp-smart-grocery'); ?></label></th>
                <td>
                    <input name="category" id="wpsgl_category" type="text" class="regular-text" list="wpsgl_category_list" required>
                    <datalist id="wpsgl_category_list">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpsgl_unit_price"><?php _e('Preço padrão (opcional)', 'wp-smart-grocery'); ?></label></th>
                <td><input name="unit_price" id="wpsgl_unit_price" type="number" step="0.01" min="0" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="wpsgl_default_unit"><?php _e('Unidade padrão (opcional)', 'wp-smart-grocery'); ?></label></th>
                <td><input name="default_unit" id="wpsgl_default_unit" type="text" class="regular-text"></td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Salvar produto', 'wp-smart-grocery'); ?></button>
        </p>
    </form>
    <hr>
    <h2><?php _e('Categorias existentes', 'wp-smart-grocery'); ?></h2>
    <?php if (!empty($categories)): ?>
        <ul>
            <?php foreach ($categories as $cat): ?>
                <li><?php echo esc_html($cat); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><?php _e('Nenhuma categoria cadastrada ainda.', 'wp-smart-grocery'); ?></p>
    <?php endif; ?>
</div>
