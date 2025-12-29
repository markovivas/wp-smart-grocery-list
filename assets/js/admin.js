(function($){
    'use strict';
    $(function(){
        const $start = $('#start_date');
        const $end = $('#end_date');
        if ($start.length && $end.length) {
            $start.on('change', function(){ $('#filter-form').submit(); });
            $end.on('change', function(){ $('#filter-form').submit(); });
        }
        const $catalogForm = $('#wpsgl-catalog-form');
        if ($catalogForm.length) {
            $catalogForm.on('submit', function(e){
                e.preventDefault();
                const data = {
                    action: 'wpsgl_save_catalog_product',
                    nonce: wpsgl_admin.nonce,
                    name: $('#wpsgl_name').val(),
                    category: $('#wpsgl_category').val(),
                    unit_price: $('#wpsgl_unit_price').val(),
                    default_unit: $('#wpsgl_default_unit').val()
                };
                $.post(wpsgl_admin.ajax_url, data)
                    .done(function(response){
                        if (response && response.success) {
                            alert('Produto salvo com sucesso.');
                            location.reload();
                        } else {
                            alert(response && response.data && response.data.message ? response.data.message : 'Erro ao salvar produto.');
                        }
                    })
                    .fail(function(){
                        alert('Erro na requisição.');
                    });
            });
        }
    });
})(jQuery);
