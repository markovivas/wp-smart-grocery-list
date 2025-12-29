<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Gerenciar Categorias', 'wp-smart-grocery'); ?></h1>
    <hr class="wp-header-end">

    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <!-- Formulário -->
        <div style="flex: 1; max-width: 400px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2><?php echo isset($_GET['edit']) ? __('Editar Categoria', 'wp-smart-grocery') : __('Adicionar Nova Categoria', 'wp-smart-grocery'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('wpsgl_manage_categories'); ?>
                <input type="hidden" name="wpsgl_action" value="<?php echo isset($_GET['edit']) ? 'update_category' : 'add_category'; ?>">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="category_id" value="<?php echo intval($_GET['edit']); ?>">
                <?php endif; ?>

                <div class="form-field form-required">
                    <label for="category_name"><?php _e('Nome', 'wp-smart-grocery'); ?></label>
                    <input name="category_name" id="category_name" type="text" value="<?php echo isset($current_category) ? esc_attr($current_category->name) : ''; ?>" required style="width: 100%; margin-bottom: 15px;">
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo isset($_GET['edit']) ? __('Atualizar', 'wp-smart-grocery') : __('Adicionar', 'wp-smart-grocery'); ?>">
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="<?php echo remove_query_arg(['edit', 'action']); ?>" class="button"><?php _e('Cancelar', 'wp-smart-grocery'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- Lista -->
        <div style="flex: 2;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nome', 'wp-smart-grocery'); ?></th>
                        <th style="width: 150px;"><?php _e('Ações', 'wp-smart-grocery'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                                <td>
                                    <a href="<?php echo add_query_arg(['edit' => $cat->id]); ?>" class="button button-small"><?php _e('Editar', 'wp-smart-grocery'); ?></a>
                                    <form method="post" action="" style="display:inline-block;" onsubmit="return confirm('<?php _e('Tem certeza? Isso não excluirá os produtos, mas removerá a categoria da lista.', 'wp-smart-grocery'); ?>');">
                                        <?php wp_nonce_field('wpsgl_manage_categories'); ?>
                                        <input type="hidden" name="wpsgl_action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?php echo $cat->id; ?>">
                                        <button type="submit" class="button button-small button-link-delete" style="color: #a00;"><?php _e('Excluir', 'wp-smart-grocery'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2"><?php _e('Nenhuma categoria encontrada.', 'wp-smart-grocery'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>