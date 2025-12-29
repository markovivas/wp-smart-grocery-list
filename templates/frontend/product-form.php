<?php
/**
 * Template do Formulário de Registro de Produto (Frontend)
 * Disponível via $this (WPSGL_Product_Manager)
 */
global $wpdb;
$table_name = $wpdb->prefix . 'wpsgl_products';
$product_map = [];
$all_products = [];
$db_categories = [];

// Recupera histórico do banco de dados para sugestões (substituindo o JSON)
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
    // Busca histórico do catálogo para sugestões
    $results = $wpdb->get_results("SELECT name, category FROM $table_name");
    
    $seen_products = [];
    $seen_categories = [];

    if ($results) {
        foreach ($results as $row) {
            $p_name = trim($row->name);
            $c_name = trim($row->category);

            if (empty($p_name) || empty($c_name)) continue;

            // Chaves normalizadas (minúsculas) para verificação de duplicidade
            $p_key = mb_strtolower($p_name, 'UTF-8');
            $c_key = mb_strtolower($c_name, 'UTF-8');

            // Formatação visual (Primeira letra maiúscula)
            $display_prod = ucfirst($p_name);
            $display_cat  = ucfirst($c_name);

            if (!isset($seen_products[$p_key])) {
                $product_map[$display_prod] = $display_cat;
                $seen_products[$p_key] = true;
            }

            if (!isset($seen_categories[$c_key])) {
                $db_categories[] = $display_cat;
                $seen_categories[$c_key] = true;
            }
        }
        $all_products = array_keys($product_map);
        sort($all_products);
    }
}

$categories = $this->get_categories();
if (empty($categories)) {
    $categories = ['GERAL'];
}

// Garante que categorias já usadas no banco apareçam na lista
if (!empty($db_categories)) {
    $categories = array_unique(array_merge($categories, $db_categories));
    sort($categories);
}
?>
<div class="wpsgl-product-form-wrapper">
    <style>
        .wpsgl-product-form-wrapper {
            max-width: 600px;
            margin: 20px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            border: 1px solid #e2e4e7;
        }
        .wpsgl-form-group {
            margin-bottom: 18px;
        }
        .wpsgl-form-row {
            display: flex;
            gap: 15px;
        }
        .wpsgl-form-col {
            flex: 1;
        }
        .wpsgl-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3338;
            font-size: 14px;
        }
        .wpsgl-input, .wpsgl-select, .wpsgl-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            color: #3c434a;
        }
        .wpsgl-input:focus, .wpsgl-select:focus, .wpsgl-textarea:focus {
            border-color: #2271b1;
            outline: none;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .wpsgl-button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: background 0.2s;
        }
        .wpsgl-button:hover {
            background: #135e96;
        }
        .wpsgl-button:disabled {
            background: #a7aaad;
            cursor: not-allowed;
        }
        .wpsgl-message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 4px;
            display: none;
            font-size: 14px;
        }
        .wpsgl-message.success {
            background-color: #f0f6fc;
            color: #1d2327;
            border-left: 4px solid #00a32a;
        }
        .wpsgl-message.error {
            background-color: #fcf0f1;
            color: #d63638;
            border-left: 4px solid #d63638;
        }
        @media (max-width: 500px) {
            .wpsgl-form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>

    <form id="wpsgl-frontend-product-form">
        <div class="wpsgl-form-group">
            <label class="wpsgl-label" for="product_name"><?php _e('Nome do Produto', 'wp-smart-grocery'); ?></label>
            <input type="text" name="product_name" id="product_name" class="wpsgl-input" required placeholder="Ex: Arroz Integral" list="wpsgl_product_list" autocomplete="off">
            <datalist id="wpsgl_product_list">
                <?php foreach ($all_products as $prod): ?>
                    <option value="<?php echo esc_attr($prod); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        
        <div class="wpsgl-form-group">
            <label class="wpsgl-label" for="barcode"><?php _e('Código de Barras (CPF do produto)', 'wp-smart-grocery'); ?></label>
            <div class="wpsgl-form-row">
                <div class="wpsgl-form-col">
                    <input type="text" name="barcode" id="barcode" class="wpsgl-input" placeholder="Ex: 7891000053509" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                </div>
                <div class="wpsgl-form-col" style="max-width:180px">
                    <button type="button" id="wpsgl-scan-btn" class="wpsgl-button"><?php _e('Ler com Câmera', 'wp-smart-grocery'); ?></button>
                </div>
            </div>
            <div id="wpsgl-scan-container" style="display:none;margin-top:10px;">
                <video id="wpsgl-video" style="width:100%;border:1px solid #dcdcde;border-radius:4px;" playsinline></video>
                <button type="button" id="wpsgl-stop-scan" class="wpsgl-button" style="margin-top:10px;"><?php _e('Parar Leitura', 'wp-smart-grocery'); ?></button>
            </div>
        </div>

        <div class="wpsgl-form-row">
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="category"><?php _e('Categoria', 'wp-smart-grocery'); ?></label>
                <input type="text" name="category" id="category" class="wpsgl-input" placeholder="<?php _e('Definida automaticamente', 'wp-smart-grocery'); ?>" autocomplete="off" required readonly>
            </div>
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="store"><?php _e('Loja / Mercado', 'wp-smart-grocery'); ?></label>
                <input type="text" name="store" id="store" class="wpsgl-input" placeholder="Ex: Supermercado X">
            </div>
        </div>

        <div class="wpsgl-form-row">
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="quantity"><?php _e('Qtd.', 'wp-smart-grocery'); ?></label>
                <input type="number" name="quantity" id="quantity" class="wpsgl-input" step="0.001" min="0" required>
            </div>
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="unit"><?php _e('Unidade', 'wp-smart-grocery'); ?></label>
                <input type="text" name="unit" id="unit" class="wpsgl-input" placeholder="kg, un">
            </div>
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="unit_price"><?php _e('Preço Unit.', 'wp-smart-grocery'); ?></label>
                <input type="number" name="unit_price" id="unit_price" class="wpsgl-input" step="0.01" min="0" required>
            </div>
        </div>

        <div class="wpsgl-form-row">
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="purchase_date"><?php _e('Data', 'wp-smart-grocery'); ?></label>
                <input type="date" name="purchase_date" id="purchase_date" class="wpsgl-input" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="wpsgl-form-col wpsgl-form-group">
                <label class="wpsgl-label" for="purchase_time"><?php _e('Hora', 'wp-smart-grocery'); ?></label>
                <input type="time" name="purchase_time" id="purchase_time" class="wpsgl-input" value="<?php echo date('H:i'); ?>">
            </div>
        </div>

        <div class="wpsgl-form-group">
            <label class="wpsgl-label" for="notes"><?php _e('Observações', 'wp-smart-grocery'); ?></label>
            <textarea name="notes" id="notes" class="wpsgl-textarea" rows="2"></textarea>
        </div>

        <button type="submit" class="wpsgl-button"><?php _e('Registrar Compra', 'wp-smart-grocery'); ?></button>
        
        <div id="wpsgl-form-message" class="wpsgl-message"></div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Mapa de produtos para categorias gerado via PHP (Banco de Dados)
    var productMap = <?php echo json_encode($product_map); ?>;
    var availableCategories = <?php echo json_encode(array_values($categories)); ?>;

    $('#product_name').on('input', function() {
        var val = $(this).val();
        // Verifica correspondência exata ou case-insensitive
        if (productMap[val]) {
            $('#category').val(productMap[val]);
        } else {
            var lowerVal = val.toLowerCase();
            for (var prod in productMap) {
                if (prod.toLowerCase() === lowerVal) {
                    $('#category').val(productMap[prod]);
                    break;
                }
            }
        }
    });

    $('#wpsgl-frontend-product-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var msg = $('#wpsgl-form-message');
        
        btn.prop('disabled', true).text('<?php _e('Salvando...', 'wp-smart-grocery'); ?>');
        msg.hide().removeClass('success error');

        var inputCat = $('#category').val().trim();
        var finalCat = inputCat || 'GERAL';

        var data = {
            action: 'wpsgl_save_product',
            nonce: wpsgl_ajax.nonce,
            product_name: $('#product_name').val(),
            barcode: $('#barcode').val(),
            category: finalCat,
            store: $('#store').val(),
            quantity: $('#quantity').val(),
            unit: $('#unit').val(),
            unit_price: $('#unit_price').val(),
            purchase_date: $('#purchase_date').val(),
            purchase_time: $('#purchase_time').val(),
            notes: $('#notes').val()
        };

        $.post(wpsgl_ajax.ajax_url, data, function(response) {
            btn.prop('disabled', false).text('<?php _e('Registrar Compra', 'wp-smart-grocery'); ?>');
            if (response.success) {
                msg.addClass('success').text(response.data.message).fadeIn();
                
                // Atualiza o "aprendizado" do formulário imediatamente sem recarregar a página
                var newProd = $('#product_name').val();
                var newCat = finalCat;
                
                // Atualiza mapa de produtos
                productMap[newProd] = newCat;
                if ($('#wpsgl_product_list option[value="' + newProd + '"]').length === 0) {
                    $('#wpsgl_product_list').append('<option value="' + newProd + '">');
                }

                // Atualiza lista de categorias
                if (availableCategories.indexOf(newCat) === -1) {
                    availableCategories.push(newCat);
                    $('#wpsgl_category_list').append('<option value="' + newCat + '">');
                }

                form[0].reset();
                // Reset date/time to current
                // QA FIX: Correção do bug de Timezone (UTC vs Local)
                var now = new Date();
                // Ajusta para o fuso horário local do usuário
                var localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
                
                $('#purchase_date').val(localDate);
                $('#purchase_time').val(now.toTimeString().split(' ')[0].substring(0,5));
            } else {
                msg.addClass('error').text(response.data.message).fadeIn();
            }
        }).fail(function() {
            btn.prop('disabled', false).text('<?php _e('Registrar Compra', 'wp-smart-grocery'); ?>');
            msg.addClass('error').text('<?php _e('Erro de conexão.', 'wp-smart-grocery'); ?>').fadeIn();
        });
    });

    function checksumEAN13(code) {
        if (!/^\d{13}$/.test(code)) return false;
        var sum = 0;
        for (var i = 0; i < 12; i++) {
            var n = parseInt(code[i], 10);
            sum += (i % 2 === 0) ? n : n * 3;
        }
        var check = (10 - (sum % 10)) % 10;
        return check === parseInt(code[12], 10);
    }
    function checksumEAN8(code) {
        if (!/^\d{8}$/.test(code)) return false;
        var sum = 0;
        for (var i = 0; i < 7; i++) {
            var n = parseInt(code[i], 10);
            sum += (i % 2 === 0) ? n * 3 : n;
        }
        var check = (10 - (sum % 10)) % 10;
        return check === parseInt(code[7], 10);
    }
    function checksumUPCA(code) {
        if (!/^\d{12}$/.test(code)) return false;
        var sumOdd = 0, sumEven = 0;
        for (var i = 0; i < 11; i++) {
            var n = parseInt(code[i], 10);
            if ((i % 2) === 0) sumOdd += n; else sumEven += n;
        }
        var total = (sumOdd * 3) + sumEven;
        var check = (10 - (total % 10)) % 10;
        return check === parseInt(code[11], 10);
    }
    function isValidBarcode(code) {
        if (!/^\d+$/.test(code)) return false;
        if (code.length === 13) return checksumEAN13(code);
        if (code.length === 8) return checksumEAN8(code);
        if (code.length === 12) return checksumUPCA(code);
        return false;
    }
    function fillFromLookup(data) {
        if (data && data.success) {
            var p = data.data;
            if (p.name) $('#product_name').val(p.name);
            if (p.category) $('#category').val(p.category);
            if (p.unit_price) $('#unit_price').val(p.unit_price);
            if (p.default_unit) $('#unit').val(p.default_unit);
        }
    }
    function lookupBarcode(code) {
        return $.post(wpsgl_ajax.ajax_url, {
            action: 'wpsgl_lookup_barcode',
            nonce: wpsgl_ajax.nonce,
            barcode: code
        });
    }
    function fetchOFF(code) {
        return $.post(wpsgl_ajax.ajax_url, {
            action: 'wpsgl_openfoodfacts',
            nonce: wpsgl_ajax.nonce,
            barcode: code
        });
    }
    $('#barcode').on('blur', function() {
        var code = $(this).val().trim();
        if (!code) return;
        if (!isValidBarcode(code)) {
            $('#wpsgl-form-message').addClass('error').removeClass('success').text('<?php _e('Código de barras inválido.', 'wp-smart-grocery'); ?>').fadeIn();
            return;
        }
        $('#wpsgl-form-message').hide().removeClass('error success');
        lookupBarcode(code).done(function(resp){
            if (!resp || !resp.success) {
                fetchOFF(code).done(function(off){ fillFromLookup(off); suggestCategoryFromOFF(off); });
            } else {
                fillFromLookup(resp);
            }
        }).fail(function(){
            fetchOFF(code).done(function(off){ fillFromLookup(off); suggestCategoryFromOFF(off); });
        });
    });

    var stream = null, detector = null, running = false, scanTimer = null;
    function stopScan() {
        running = false;
        if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
        if (stream) { stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
        $('#wpsgl-scan-container').hide();
    }
    $('#wpsgl-stop-scan').on('click', stopScan);
    $('#wpsgl-scan-btn').on('click', async function(){
        const startNative = async () => {
            detector = new BarcodeDetector({ formats: ['ean_13','ean_8','upc_a'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            var video = document.getElementById('wpsgl-video');
            video.srcObject = stream;
            await video.play();
            $('#wpsgl-scan-container').show();
            running = true;
            scanTimer = setInterval(async function(){
                if (!running) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes && codes.length > 0) {
                        var value = codes[0].rawValue || codes[0].value;
                        if (value && isValidBarcode(value)) {
                            $('#barcode').val(value);
                            stopScan();
                            lookupBarcode(value).done(function(resp){
                                if (!resp || !resp.success) {
                                    fetchOFF(value).done(function(off){ fillFromLookup(off); });
                                } else {
                                    fillFromLookup(resp);
                                }
                            });
                        }
                    }
                } catch(e) {}
            }, 500);
        };
        const startQuagga = async () => {
            const loadScript = (src) => new Promise(function(resolve, reject){
                var s = document.createElement('script'); s.src = src; s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
            });
            try {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js');
                $('#wpsgl-scan-container').show();
                running = true;
                window.Quagga.init({
                    inputStream: {
                        type: 'LiveStream',
                        constraints: { facingMode: 'environment' },
                        target: document.querySelector('#wpsgl-video')
                    },
                    decoder: { readers: ['ean_reader','ean_8_reader','upc_reader'] },
                    locate: true
                }, function(err){
                    if (err) {
                        $('#wpsgl-form-message').addClass('error').removeClass('success').text('<?php _e('Falha ao iniciar Quagga.', 'wp-smart-grocery'); ?>').fadeIn();
                        return;
                    }
                    window.Quagga.start();
                });
                window.Quagga.onDetected(function(res){
                    if (!running) return;
                    var code = res && res.codeResult && res.codeResult.code;
                    if (code && isValidBarcode(code)) {
                        $('#barcode').val(code);
                        running = false;
                        window.Quagga.stop();
                        $('#wpsgl-scan-container').hide();
                        lookupBarcode(code).done(function(resp){
                            if (!resp || !resp.success) {
                                fetchOFF(code).done(function(off){ fillFromLookup(off); });
                            } else {
                                fillFromLookup(resp);
                            }
                        });
                    }
                });
            } catch(e) {
                $('#wpsgl-form-message').addClass('error').removeClass('success').text('<?php _e('Falha ao carregar Quagga.', 'wp-smart-grocery'); ?>').fadeIn();
            }
        };
        try {
            if ('BarcodeDetector' in window) {
                await startNative();
            } else {
                await startQuagga();
            }
        } catch(e) {
            $('#wpsgl-form-message').addClass('error').removeClass('success').text('<?php _e('Falha ao iniciar a câmera.', 'wp-smart-grocery'); ?>').fadeIn();
        }
    });

    function normalize(str) {
        return (str || '').toString().toLowerCase()
            .replace(/[^\p{L}\p{N}\s&]/gu, '')
            .trim();
    }
    var offToInternal = {
        'en:dairy': 'Laticínios',
        'en:milk-and-dairy-products': 'Laticínios',
        'en:breads': 'Panificação & Confeitaria',
        'en:breakfasts': 'Cereais & Grãos',
        'en:meats': 'Carne & Peixe',
        'en:fish-and-seafood': 'Carne & Peixe',
        'en:condiments': 'Ingredientes & Temperos',
        'en:spices': 'Ingredientes & Temperos',
        'en:frozen-foods': 'Congelados & Conveniência',
        'en:cereals-and-grains': 'Cereais & Grãos',
        'en:snacks': 'Lanches & Doces',
        'en:beverages': 'Bebidas',
        'en:fruit-and-vegetables-based-foods': 'Frutas & Verduras',
        'en:fruit-juices': 'Bebidas',
        'en:household': 'Casa',
        'en:health-and-beauty': 'Higiene & Saúde',
        'pt:laticinios': 'Laticínios',
        'pt:bebidas': 'Bebidas',
        'pt:carnes': 'Carne & Peixe',
        'pt:padaria': 'Panificação & Confeitaria',
        'pt:temperos': 'Ingredientes & Temperos',
        'pt:congelados': 'Congelados & Conveniência',
        'pt:graos-e-cereais': 'Cereais & Grãos',
        'pt:doces-e-lanches': 'Lanches & Doces',
        'pt:frutas-e-verduras': 'Frutas & Verduras'
    };
    function rankInternalCategories(offTags, internalCats) {
        var scores = {};
        var tags = (offTags || []).map(normalize);
        internalCats.forEach(function(cat){
            var ncat = normalize(cat);
            var score = 0;
            tags.forEach(function(tag){
                if (offToInternal[tag] === cat) score += 5;
                if (tag.indexOf(ncat) !== -1) score += 2;
                // match words
                ncat.split(/\s+/).forEach(function(word){
                    if (word.length > 3 && tag.indexOf(word) !== -1) score += 1;
                });
            });
            scores[cat] = score;
        });
        return internalCats
            .map(function(cat){ return { cat: cat, score: scores[cat] || 0 }; })
            .sort(function(a,b){ return b.score - a.score; });
    }
    function suggestCategoryFromOFF(offResponse) {
        if (!offResponse || !offResponse.success) return;
        var tags = offResponse.data && offResponse.data.categories ? offResponse.data.categories : [];
        if (!tags || !tags.length) return;
        var ranked = rankInternalCategories(tags, availableCategories);
        if (!ranked.length || ranked[0].score <= 0) return;
        $('#category').val(ranked[0].cat);
        var $existing = $('#wpsgl-off-suggest');
        if ($existing.length === 0) {
            var html = '<div id="wpsgl-off-suggest" class="wpsgl-form-group"><label class="wpsgl-label"><?php _e('Sugestão de categoria (Open Food Facts)', 'wp-smart-grocery'); ?></label><select id="wpsgl-off-select" class="wpsgl-select"></select></div>';
            $('#category').closest('.wpsgl-form-group').after(html);
            $('#wpsgl-off-select').on('change', function(){ $('#category').val($(this).val()); });
        }
        var $sel = $('#wpsgl-off-select');
        $sel.empty();
        ranked.slice(0, Math.min(5, ranked.length)).forEach(function(item){
            var opt = $('<option>').attr('value', item.cat).text(item.cat + ' (' + item.score + ')');
            $sel.append(opt);
        });
        $sel.val(ranked[0].cat);
    }
});
</script>
