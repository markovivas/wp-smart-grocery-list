<?php

class WPSGL_Database {
    private $wpdb;
    private $charset_collate;
    private $table_products;
    private $table_lists;
    private $table_list_items;
    private $table_categories;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        $this->table_products = $wpdb->prefix . 'wpsgl_products';
        $this->table_lists = $wpdb->prefix . 'wpsgl_lists';
        $this->table_list_items = $wpdb->prefix . 'wpsgl_list_items';
        $this->table_categories = $wpdb->prefix . 'wpsgl_categories';
    }

    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_products = "CREATE TABLE IF NOT EXISTS {$this->table_products} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            unit_price DECIMAL(10,2) DEFAULT 0.00,
            default_unit VARCHAR(50),
            image_id BIGINT(20) UNSIGNED,
            icon_svg TEXT,
            barcode VARCHAR(64),
            is_custom BOOLEAN DEFAULT 0,
            user_id BIGINT(20) UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY user_id (user_id),
            UNIQUE KEY barcode (barcode)
        ) {$this->charset_collate};";

        $sql_lists = "CREATE TABLE IF NOT EXISTS {$this->table_lists} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            user_id BIGINT(20) UNSIGNED,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) {$this->charset_collate};";

        $sql_list_items = "CREATE TABLE IF NOT EXISTS {$this->table_list_items} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            list_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED,
            product_name VARCHAR(255) NOT NULL,
            quantity VARCHAR(50),
            unit VARCHAR(50),
            price DECIMAL(10,2),
            total_price DECIMAL(10,2),
            notes TEXT,
            is_checked BOOLEAN DEFAULT 0,
            checked_at DATETIME,
            image_id BIGINT(20) UNSIGNED,
            category VARCHAR(100),
            sort_order INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY list_id (list_id),
            KEY product_id (product_id),
            KEY is_checked (is_checked),
            FOREIGN KEY (list_id) REFERENCES {$this->table_lists}(id) ON DELETE CASCADE
        ) {$this->charset_collate};";

        $sql_purchases = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}wpsgl_purchases (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED,
            product_name VARCHAR(255) NOT NULL,
            quantity DECIMAL(10,3) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            purchase_date DATE NOT NULL,
            purchase_time TIME,
            store VARCHAR(255),
            category VARCHAR(100),
            user_id BIGINT(20) UNSIGNED,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY purchase_date (purchase_date),
            KEY category (category),
            KEY user_id (user_id)
        ) {$this->charset_collate};";

        $sql_categories = "CREATE TABLE IF NOT EXISTS {$this->table_categories} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) {$this->charset_collate};";

        dbDelta($sql_products);
        dbDelta($sql_lists);
        dbDelta($sql_list_items);
        dbDelta($sql_purchases);
        dbDelta($sql_categories);

        $this->insert_default_products();
        
        // Sincroniza categorias existentes dos produtos para a tabela de categorias
        $this->wpdb->query("INSERT IGNORE INTO {$this->table_categories} (name) 
            SELECT DISTINCT category FROM {$this->table_products} 
            WHERE category IS NOT NULL AND category != ''"
        );
    }

    private function insert_default_products() {
        $json_file = WPSGL_PLUGIN_DIR . 'data/produtos.json';
        if (file_exists($json_file)) {
            $json_data = file_get_contents($json_file);
            $data = json_decode($json_data, true);
            if (isset($data['categorias'])) {
                foreach ($data['categorias'] as $category => $products) {
                    foreach ($products as $product_name) {
                        $exists = $this->wpdb->get_var($this->wpdb->prepare(
                            "SELECT COUNT(*) FROM {$this->table_products} WHERE name = %s AND category = %s",
                            $product_name,
                            $category
                        ));
                        if (!$exists) {
                            $this->wpdb->insert($this->table_products, [
                                'name' => $product_name,
                                'category' => $category,
                                'is_custom' => 0
                            ]);
                        }
                        // Garante que a categoria exista na tabela de categorias
                        $this->wpdb->query($this->wpdb->prepare(
                            "INSERT IGNORE INTO {$this->table_categories} (name) VALUES (%s)", $category
                        ));
                    }
                }
            }
        }
    }

    public function init() {
        $column = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'barcode'",
            $this->wpdb->dbname,
            $this->table_products
        ));
        if (!$column) {
            $this->wpdb->query("ALTER TABLE {$this->table_products} ADD COLUMN barcode VARCHAR(64) NULL");
            // Índice único permite múltiplos NULL em MySQL
            $this->wpdb->query("ALTER TABLE {$this->table_products} ADD UNIQUE KEY barcode (barcode)");
        }
    }

    public function get_products($category = null, $search = null) {
        $where = ['1=1'];
        $params = [];
        if ($category) {
            $where[] = 'category = %s';
            $params[] = $category;
        }
        if ($search) {
            $where[] = 'name LIKE %s';
            $params[] = '%' . $search . '%';
        }
        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM {$this->table_products} WHERE {$where_clause} ORDER BY name ASC";
        if ($params) {
            $query = $this->wpdb->prepare($query, $params);
        }
        return $this->wpdb->get_results($query);
    }

    public function save_product($data) {
        if (isset($data['id'])) {
            return $this->wpdb->update($this->table_products, $data, ['id' => $data['id']]);
        } else {
            return $this->wpdb->insert($this->table_products, $data);
        }
    }

    public function save_purchase($data) {
        return $this->wpdb->insert($this->wpdb->prefix . 'wpsgl_purchases', $data);
    }

    public function get_purchases($start_date = null, $end_date = null, $category = null) {
        $where = ['1=1'];
        $params = [];
        if ($start_date && $end_date) {
            $where[] = 'purchase_date BETWEEN %s AND %s';
            $params[] = $start_date;
            $params[] = $end_date;
        }
        if ($category) {
            $where[] = 'category = %s';
            $params[] = $category;
        }
        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM {$this->wpdb->prefix}wpsgl_purchases WHERE {$where_clause} ORDER BY purchase_date DESC, purchase_time DESC";
        if ($params) {
            $query = $this->wpdb->prepare($query, $params);
        }
        return $this->wpdb->get_results($query);
    }

    public function get_purchase_stats($start_date, $end_date) {
        $query = $this->wpdb->prepare(
            "SELECT 
                category,
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(total_price) as total_amount,
                AVG(unit_price) as avg_price
            FROM {$this->wpdb->prefix}wpsgl_purchases 
            WHERE purchase_date BETWEEN %s AND %s
            GROUP BY category
            ORDER BY total_amount DESC",
            $start_date,
            $end_date
        );
        return $this->wpdb->get_results($query);
    }

    public function get_categories() {
        // Busca da tabela de categorias se existir
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_categories}'") === $this->table_categories) {
            return $this->wpdb->get_col("SELECT name FROM {$this->table_categories} ORDER BY name ASC");
        }
        // Fallback para a tabela de produtos
        return $this->wpdb->get_col("SELECT DISTINCT category FROM {$this->table_products} WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
    }

    public function get_product_by_barcode($barcode) {
        if (!$barcode) return null;
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_products} WHERE barcode = %s LIMIT 1",
            $barcode
        ));
    }

    public function get_products_with_barcode($category = null) {
        $where = ['barcode IS NOT NULL', "barcode <> ''"];
        $params = [];
        if ($category) {
            $where[] = 'category = %s';
            $params[] = $category;
        }
        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM {$this->table_products} WHERE {$where_clause} ORDER BY name ASC";
        if ($params) {
            $query = $this->wpdb->prepare($query, $params);
        }
        return $this->wpdb->get_results($query);
    }

    public function ensure_category_exists($name) {
        $name = sanitize_text_field($name);
        if ($name === '') return false;
        // Garante que a tabela exista
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_categories}'") !== $this->table_categories) {
            return false;
        }
        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_categories} WHERE name = %s LIMIT 1", $name
        ));
        if (!$exists) {
            return $this->wpdb->insert($this->table_categories, ['name' => $name]) !== false;
        }
        return true;
    }

    // Métodos de Gerenciamento de Categorias
    public function get_all_categories_objects() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_categories} ORDER BY name ASC");
    }

    public function add_category($name) {
        return $this->wpdb->insert($this->table_categories, ['name' => sanitize_text_field($name)]);
    }

    public function update_category($id, $name) {
        $name = sanitize_text_field($name);
        // Pega o nome antigo para atualizar os produtos
        $old_name = $this->wpdb->get_var($this->wpdb->prepare("SELECT name FROM {$this->table_categories} WHERE id = %d", $id));
        
        $updated = $this->wpdb->update($this->table_categories, ['name' => $name], ['id' => $id]);
        
        if ($updated !== false && $old_name && $old_name !== $name) {
            // Atualiza a string da categoria na tabela de produtos para manter consistência
            $this->wpdb->update(
                $this->table_products, 
                ['category' => $name], 
                ['category' => $old_name]
            );
        }
        return $updated;
    }

    public function delete_category($id) {
        return $this->wpdb->delete($this->table_categories, ['id' => $id]);
    }
}
