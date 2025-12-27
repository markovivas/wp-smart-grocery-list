<div class="wrap">
    <h1><?php _e('Configurações do WP Smart Grocery', 'wp-smart-grocery'); ?></h1>
    <p><?php _e('Ajuste as preferências do plugin.', 'wp-smart-grocery'); ?></p>
    <form method="post" action="options.php">
        <?php settings_fields('wpsgl_settings'); ?>
        <?php do_settings_sections('wpsgl_settings'); ?>
        <?php submit_button(); ?>
    </form>
</div>
