(function($){
    'use strict';
    $(function(){
        const $start = $('#start_date');
        const $end = $('#end_date');
        if ($start.length && $end.length) {
            $start.on('change', function(){ $('#filter-form').submit(); });
            $end.on('change', function(){ $('#filter-form').submit(); });
        }
        $(document).on('click', '.wpsgl-btn-update-product', function(){
            const $row = $(this).closest('tr');
            const id = parseInt($row.data('id'), 10);
            const payload = {
                action: 'wpsgl_update_catalog_product',
                nonce: wpsgl_admin.nonce,
                id: id,
                name: $row.find('input[data-field="name"]').val(),
                category: $row.find('[data-field="category"]').val(),
                unit_price: $row.find('input[data-field="unit_price"]').val(),
                default_unit: $row.find('input[data-field="default_unit"]').val(),
                barcode: $row.find('input[data-field="barcode"]').val()
            };
            $.post(wpsgl_admin.ajax_url, payload)
             .done(function(response){
                if (response && response.success) {
                    alert('Produto atualizado.');
                } else {
                    alert(response && response.data && response.data.message ? response.data.message : 'Erro ao atualizar produto.');
                }
             })
             .fail(function(){ alert('Erro na requisição.'); });
        });
        $(document).on('click', '.wpsgl-btn-delete-product', function(){
            if (!confirm('Tem certeza que deseja excluir este produto?')) return;
            const $row = $(this).closest('tr');
            const id = parseInt($row.data('id'), 10);
            const payload = {
                action: 'wpsgl_delete_catalog_product',
                nonce: wpsgl_admin.nonce,
                id: id
            };
            $.post(wpsgl_admin.ajax_url, payload)
             .done(function(response){
                if (response && response.success) {
                    $row.fadeOut(150, function(){ $(this).remove(); });
                } else {
                    alert(response && response.data && response.data.message ? response.data.message : 'Erro ao excluir produto.');
                }
             })
             .fail(function(){ alert('Erro na requisição.'); });
        });
        $(document).on('click', '#wpsgl-export-btn', function(){
            const url = wpsgl_admin.ajax_url + '?action=wpsgl_export_products&nonce=' + encodeURIComponent(wpsgl_admin.nonce);
            window.location.href = url;
        });
        const $importForm = $('#wpsgl-import-form');
        if ($importForm.length) {
            $importForm.on('submit', function(e){
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'wpsgl_import_products');
                formData.append('nonce', wpsgl_admin.nonce);
                $.ajax({
                    url: wpsgl_admin.ajax_url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function(resp){
                    if (resp && resp.success) {
                        alert('Importação concluída. Inseridos: ' + resp.data.inserted + ', Atualizados: ' + resp.data.updated + ', Erros: ' + resp.data.errors);
                        location.reload();
                    } else {
                        alert(resp && resp.data && resp.data.message ? resp.data.message : 'Erro na importação.');
                    }
                }).fail(function(){
                    alert('Erro na requisição.');
                });
            });
        }
    });
})(jQuery);
