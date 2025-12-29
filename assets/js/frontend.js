(function($) {
    'use strict';
    const WPSGL = {
        init: function() {
            this.cache();
            this.bind();
            this.loadRecent();
            this.initTheme();
        },
        cache: function() {
            this.elements = {
                container: document.querySelector('.wpsgl-container'),
                productButtons: document.querySelectorAll('.wpsgl-product-btn'),
                categoryToggles: document.querySelectorAll('.wpsgl-category-toggle'),
                listItems: document.querySelectorAll('.wpsgl-list-item'),
                checkboxes: document.querySelectorAll('.wpsgl-checkbox input'),
                moreButtons: document.querySelectorAll('.wpsgl-btn-more'),
                deleteButtons: document.querySelectorAll('.wpsgl-btn-delete'),
                modal: document.getElementById('wpsgl-options-modal'),
                modalForm: document.getElementById('wpsgl-options-form'),
                cancelButton: document.querySelector('.wpsgl-btn-cancel'),
                newListButton: document.querySelector('.wpsgl-btn-new-list'),
                themeButton: document.querySelector('.wpsgl-btn-toggle-theme'),
                printButton: document.querySelector('.wpsgl-btn-print'),
                searchInput: document.getElementById('wpsgl-search'),
                viewToggleButtons: document.querySelectorAll('.wpsgl-view-toggle button'),
                categoriesGrid: document.querySelector('.wpsgl-categories-grid')
            };
            this.currentItem = null;
        },
        bind: function() {
            this.elements.productButtons.forEach(btn => {
                btn.addEventListener('click', this.addProductToList.bind(this));
            });
            this.elements.categoryToggles.forEach(toggle => {
                toggle.addEventListener('click', this.toggleCategory.bind(this));
            });
            this.elements.checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', this.toggleChecked.bind(this));
            });
            this.elements.moreButtons.forEach(btn => {
                btn.addEventListener('click', this.showOptionsModal.bind(this));
            });
            this.elements.deleteButtons && this.elements.deleteButtons.forEach(btn => {
                btn.addEventListener('click', this.deleteItem.bind(this));
            });
            if (this.elements.modal) {
                this.elements.cancelButton.addEventListener('click', this.hideModal.bind(this));
                this.elements.modalForm.addEventListener('submit', this.saveItemOptions.bind(this));
                this.elements.modal.addEventListener('click', (e) => {
                    if (e.target === this.elements.modal) {
                        this.hideModal();
                    }
                });
            }
            if (this.elements.newListButton) {
                this.elements.newListButton.addEventListener('click', this.createNewList.bind(this));
            }
            if (this.elements.themeButton) {
                this.elements.themeButton.addEventListener('click', this.toggleTheme.bind(this));
            }
            if (this.elements.printButton) {
                this.elements.printButton.addEventListener('click', this.printList.bind(this));
            }
            this.bindRealTimeUpdates();
        },
        bindRealTimeUpdates: function() {
            document.addEventListener('input', (e) => {
                if (e.target.classList.contains('wpsgl-quantity')) {
                    this.updateItem(e.target.closest('.wpsgl-list-item'), 'quantity', e.target.value);
                }
            });
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('wpsgl-price')) {
                    this.updateItem(e.target.closest('.wpsgl-list-item'), 'price', e.target.value);
                    this.updateTotal();
                }
            });
            document.addEventListener('blur', (e) => {
                if (e.target.classList.contains('wpsgl-notes')) {
                    this.updateItem(e.target.closest('.wpsgl-list-item'), 'notes', e.target.value);
                }
            }, true);
            if (this.elements.searchInput) {
                this.elements.searchInput.addEventListener('input', this.filterProducts.bind(this));
            }
            if (this.elements.viewToggleButtons && this.elements.viewToggleButtons.length) {
                this.elements.viewToggleButtons.forEach(btn => {
                    btn.addEventListener('click', this.changeViewMode.bind(this));
                });
            }
        },
        changeViewMode: function(e) {
            const btn = e.currentTarget;
            const mode = btn.getAttribute('data-view');
            this.elements.viewToggleButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (!this.elements.categoriesGrid) return;
            if (mode === 'list') {
                this.elements.categoriesGrid.classList.add('compact-list');
            } else {
                this.elements.categoriesGrid.classList.remove('compact-list');
            }
        },
        addProductToList: function(e) {
            const button = e.currentTarget;
            const productName = button.getAttribute('data-product');
            const category = button.getAttribute('data-category');
            const barcode = button.getAttribute('data-barcode') || '';
            button.classList.add('wpsgl-added');
            setTimeout(() => {
                button.classList.remove('wpsgl-added');
            }, 1000);
            this.saveToRecent(productName);
            $.ajax({
                url: wpsgl_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsgl_add_to_list',
                    nonce: wpsgl_ajax.nonce,
                    product_name: productName,
                    category: category,
                    barcode: barcode
                },
                success: (response) => {
                    if (response.success) {
                        const itemId = response.item_id || 'new';
                        this.addItemToUI(productName, category, itemId);
                    }
                }
            });
        },
        filterProducts: function() {
            const q = (this.elements.searchInput.value || '').toLowerCase().trim();
            const categories = document.querySelectorAll('.wpsgl-category');
            let globalMatches = 0;
            categories.forEach(cat => {
                const titleEl = cat.querySelector('.wpsgl-category-title span');
                const productsContainer = cat.querySelector('.wpsgl-category-products');
                const toggle = cat.querySelector('.wpsgl-category-toggle');
                const catName = titleEl ? titleEl.textContent.toLowerCase() : '';
                const buttons = productsContainer ? productsContainer.querySelectorAll('.wpsgl-product-btn') : [];
                let matches = 0;
                buttons.forEach(btn => {
                    const name = (btn.getAttribute('data-product') || btn.textContent || '').toLowerCase();
                    const visible = q === '' || name.includes(q) || catName.includes(q);
                    btn.style.display = visible ? '' : 'none';
                    if (visible) matches++;
                });
                const showCat = q === '' ? true : (matches > 0 || catName.includes(q));
                cat.style.display = showCat ? '' : 'none';
                if (showCat) {
                    globalMatches += matches;
                    if (q !== '') {
                        toggle && toggle.setAttribute('aria-expanded', 'true');
                        if (productsContainer) {
                            productsContainer.style.maxHeight = 'none';
                            productsContainer.style.padding = '15px';
                        }
                    } else {
                        toggle && toggle.setAttribute('aria-expanded', 'false');
                        if (productsContainer) {
                            productsContainer.style.maxHeight = '0';
                            productsContainer.style.padding = '0';
                        }
                    }
                }
            });
            const emptyMsg = document.querySelector('.wpsgl-search-empty');
            if (emptyMsg) {
                emptyMsg.style.display = (q !== '' && globalMatches === 0) ? 'block' : 'none';
            }
        },
        addItemToUI: function(productName, category, itemId) {
            const listItems = document.querySelector('.wpsgl-list-items');
            const emptyState = listItems.querySelector('.wpsgl-empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            const itemHTML = `
                <div class="wpsgl-list-item" data-item-id="${itemId}">
                    <label class="wpsgl-checkbox">
                        <input type="checkbox">
                        <span class="checkmark"></span>
                    </label>
                    <div class="wpsgl-item-content">
                        <div class="wpsgl-item-name">${this.escapeHtml(productName)}</div>
                        <div class="wpsgl-item-details">
                            <input type="text" class="wpsgl-quantity" value="1" placeholder="Qtd">
                            <input type="number" class="wpsgl-price" value="0" step="0.01" min="0" placeholder="R$">
                            <textarea class="wpsgl-notes" placeholder="Notas..."></textarea>
                        </div>
                    </div>
                    <div class="wpsgl-item-actions">
                        <button class="wpsgl-btn-more" title="Mais opções">
                            <svg width="24" height="24" viewBox="0 0 24 24">
                                <circle cx="12" cy="6" r="2" fill="currentColor"/>
                                <circle cx="12" cy="12" r="2" fill="currentColor"/>
                                <circle cx="12" cy="18" r="2" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsgl-btn-delete" title="Excluir item">
                            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3h6a1 1 0 0 1 1 1v2h4v2h-1l-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 8H4V6h4V4a1 1 0 0 1 1-1zm2 5v10h2V8h-2z"/></svg>
                        </button>
                    </div>
                </div>
            `;
            listItems.insertAdjacentHTML('beforeend', itemHTML);
            const newItem = listItems.lastElementChild;
            newItem.querySelector('.wpsgl-checkbox input').addEventListener('change', this.toggleChecked.bind(this));
            newItem.querySelector('.wpsgl-btn-more').addEventListener('click', this.showOptionsModal.bind(this));
            newItem.querySelector('.wpsgl-btn-delete').addEventListener('click', this.deleteItem.bind(this));
            newItem.style.animation = 'slideIn 0.3s ease';
        },
        toggleCategory: function(e) {
            const toggle = e.currentTarget;
            const category = toggle.closest('.wpsgl-category');
            const products = category.querySelector('.wpsgl-category-products');
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !isExpanded);
            if (isExpanded) {
                products.style.maxHeight = '0';
                products.style.padding = '0';
            } else {
                products.style.maxHeight = 'none';
                products.style.padding = '15px';
            }
        },
        toggleChecked: function(e) {
            const checkbox = e.currentTarget;
            const listItem = checkbox.closest('.wpsgl-list-item');
            const itemId = listItem.getAttribute('data-item-id');
            if (itemId && itemId !== 'new') {
                this.updateItem(listItem, 'is_checked', checkbox.checked ? 1 : 0);
            }
            if (checkbox.checked) {
                listItem.style.opacity = '0.7';
                listItem.style.textDecoration = 'line-through';
            } else {
                listItem.style.opacity = '1';
                listItem.style.textDecoration = 'none';
            }
        },
        showOptionsModal: function(e) {
            const button = e.currentTarget;
            this.currentItem = button.closest('.wpsgl-list-item');
            const quantity = this.currentItem.querySelector('.wpsgl-quantity').value;
            const price = this.currentItem.querySelector('.wpsgl-price').value;
            const notes = this.currentItem.querySelector('.wpsgl-notes').value;
            const form = this.elements.modalForm;
            form.querySelector('input[name="quantity"]').value = quantity;
            form.querySelector('input[name="price"]').value = price;
            form.querySelector('textarea[name="notes"]').value = notes;
            this.elements.modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        },
        hideModal: function() {
            this.elements.modal.style.display = 'none';
            document.body.style.overflow = '';
            this.currentItem = null;
        },
        saveItemOptions: function(e) {
            e.preventDefault();
            if (!this.currentItem) return;
            const formData = new FormData(this.elements.modalForm);
            const itemId = this.currentItem.getAttribute('data-item-id');
            const quantityInput = this.currentItem.querySelector('.wpsgl-quantity');
            const priceInput = this.currentItem.querySelector('.wpsgl-price');
            const notesInput = this.currentItem.querySelector('.wpsgl-notes');
            quantityInput.value = formData.get('quantity');
            priceInput.value = formData.get('price');
            notesInput.value = formData.get('notes');
            if (itemId && itemId !== 'new') {
                $.ajax({
                    url: wpsgl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpsgl_update_item',
                        nonce: wpsgl_ajax.nonce,
                        item_id: itemId,
                        field: 'notes',
                        value: formData.get('notes')
                    }
                });
                $.ajax({
                    url: wpsgl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpsgl_update_item',
                        nonce: wpsgl_ajax.nonce,
                        item_id: itemId,
                        field: 'quantity',
                        value: formData.get('quantity')
                    }
                });
                $.ajax({
                    url: wpsgl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpsgl_update_item',
                        nonce: wpsgl_ajax.nonce,
                        item_id: itemId,
                        field: 'price',
                        value: formData.get('price')
                    }
                });
            }
            this.hideModal();
            this.updateTotal();
        },
        deleteItem: function(e) {
            const item = e.currentTarget.closest('.wpsgl-list-item');
            const itemId = item.getAttribute('data-item-id');
            if (!itemId || itemId === 'new') {
                item.classList.add('fade-out');
                setTimeout(() => {
                    item.remove();
                    this.updateTotal();
                }, 200);
                return;
            }
            $.ajax({
                url: wpsgl_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpsgl_delete_item',
                    nonce: wpsgl_ajax.nonce,
                    item_id: itemId
                },
                success: (response) => {
                    if (response && response.success) {
                        item.classList.add('fade-out');
                        setTimeout(() => {
                            item.remove();
                            this.updateTotal();
                        }, 200);
                    }
                }
            });
        },
        updateItem: function(item, field, value) {
            const itemId = item.getAttribute('data-item-id');
            if (itemId && itemId !== 'new') {
                $.ajax({
                    url: wpsgl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpsgl_update_item',
                        nonce: wpsgl_ajax.nonce,
                        item_id: itemId,
                        field: field,
                        value: value
                    }
                });
            }
        },
        updateTotal: function() {
            const priceInputs = document.querySelectorAll('.wpsgl-price');
            let total = 0;
            priceInputs.forEach(input => {
                const price = parseFloat(input.value) || 0;
                total += price;
            });
            const totalElement = document.querySelector('.wpsgl-total-amount');
            if (totalElement) {
                totalElement.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            }
        },
        createNewList: function() {
            const name = prompt('Nome da nova lista:', 'Lista de Compras');
            if (name) {
                alert('Nova lista criada: ' + name);
            }
        },
        toggleTheme: function() {
            const currentTheme = this.elements.container.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            this.elements.container.setAttribute('data-theme', newTheme);
            localStorage.setItem('wpsgl_theme', newTheme);
        },
        initTheme: function() {
            const savedTheme = localStorage.getItem('wpsgl_theme') || 'light';
            this.elements.container.setAttribute('data-theme', savedTheme);
        },
        saveToRecent: function(productName) {
            let recent = JSON.parse(localStorage.getItem('wpsgl_recent') || '[]');
            recent = recent.filter(item => item !== productName);
            recent.unshift(productName);
            recent = recent.slice(0, 10);
            localStorage.setItem('wpsgl_recent', JSON.stringify(recent));
        },
        loadRecent: function() {
            const recent = JSON.parse(localStorage.getItem('wpsgl_recent') || '[]');
            if (recent.length > 0) {
            }
        },
        printList: function() {
            window.print();
        },
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    document.addEventListener('DOMContentLoaded', () => {
        WPSGL.init();
    });
})(jQuery);
