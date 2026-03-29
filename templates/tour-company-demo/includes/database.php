<?php
/**
 * iACC Template — Local Database (SQLite)
 * Caches products and categories fetched from iACC API for fast display.
 * No MySQL needed on the template hosting side.
 */

class LocalDatabase
{
    private PDO $db;
    private string $dbPath;

    public function __construct(string $dbPath = null)
    {
        $this->dbPath = $dbPath ?: __DIR__ . '/../data/template.db';
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->db = new PDO('sqlite:' . $this->dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }

    private function createTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS types (
                id INTEGER PRIMARY KEY,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                product_count INTEGER DEFAULT 0,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                price REAL NOT NULL,
                description TEXT,
                type_id INTEGER,
                type_name TEXT,
                category_id INTEGER,
                category_name TEXT,
                brand_id INTEGER,
                brand_name TEXT,
                image_url TEXT,
                is_active INTEGER DEFAULT 1,
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (type_id) REFERENCES types(id),
                FOREIGN KEY (category_id) REFERENCES categories(id)
            );

            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER,
                guest_name TEXT NOT NULL,
                guest_email TEXT,
                guest_phone TEXT,
                product_id INTEGER,
                product_name TEXT,
                guests INTEGER DEFAULT 1,
                tour_date TEXT,
                total_amount REAL,
                notes TEXT,
                status TEXT DEFAULT 'pending',
                api_response TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS sync_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                items_count INTEGER,
                status TEXT,
                message TEXT,
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    /**
     * Sync categories from API response
     */
    public function syncCategories(array $categories): int
    {
        $this->db->exec("DELETE FROM types");
        $this->db->exec("DELETE FROM categories");

        $catStmt = $this->db->prepare("INSERT INTO categories (id, name, description) VALUES (?, ?, ?)");
        $typeStmt = $this->db->prepare("INSERT INTO types (id, category_id, name, description, product_count) VALUES (?, ?, ?, ?, ?)");

        $count = 0;
        foreach ($categories as $cat) {
            $catStmt->execute([$cat['id'], $cat['name'], $cat['description'] ?? '']);
            $count++;
            foreach ($cat['types'] ?? [] as $type) {
                $typeStmt->execute([$type['id'], $cat['id'], $type['name'], $type['description'] ?? '', $type['product_count'] ?? 0]);
            }
        }

        $this->logSync('categories', $count, 'success');
        return $count;
    }

    /**
     * Sync products from API response
     */
    public function syncProducts(array $products): int
    {
        $this->db->exec("DELETE FROM products");

        $stmt = $this->db->prepare("INSERT INTO products (id, name, price, description, type_id, type_name, category_id, category_name, brand_id, brand_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $count = 0;
        foreach ($products as $p) {
            $stmt->execute([
                $p['id'], $p['name'], $p['price'], $p['description'] ?? '',
                $p['type_id'], $p['type_name'], $p['category_id'], $p['category_name'],
                $p['brand_id'], $p['brand_name']
            ]);
            $count++;
        }

        $this->logSync('products', $count, 'success');
        return $count;
    }

    /**
     * Get all categories with their types
     */
    public function getCategories(): array
    {
        $cats = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cats as &$cat) {
            $stmt = $this->db->prepare("SELECT * FROM types WHERE category_id = ? ORDER BY name");
            $stmt->execute([$cat['id']]);
            $cat['types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $cats;
    }

    /**
     * Get all products, optionally by category
     * @param bool $activeOnly When true, only returns is_active=1 products
     */
    public function getProducts(?int $categoryId = null, bool $activeOnly = true): array
    {
        $where = $activeOnly ? " WHERE is_active = 1" : "";
        if ($categoryId) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE category_id = ?" . ($activeOnly ? " AND is_active = 1" : "") . " ORDER BY name");
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $this->db->query("SELECT * FROM products" . $where . " ORDER BY category_name, type_name, name");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single product by ID
     */
    public function getProduct(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get products grouped by category
     */
    public function getProductsByCategory(): array
    {
        $products = $this->getProducts();
        $grouped = [];
        foreach ($products as $p) {
            $catName = $p['category_name'] ?: 'Other';
            $grouped[$catName][] = $p;
        }
        return $grouped;
    }

    /**
     * Save a booking locally
     */
    public function saveBooking(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO bookings (order_id, guest_name, guest_email, guest_phone, product_id, product_name, guests, tour_date, total_amount, notes, status, api_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['order_id'] ?? null,
            $data['guest_name'],
            $data['guest_email'] ?? '',
            $data['guest_phone'] ?? '',
            $data['product_id'] ?? null,
            $data['product_name'] ?? '',
            $data['guests'] ?? 1,
            $data['tour_date'] ?? '',
            $data['total_amount'] ?? 0,
            $data['notes'] ?? '',
            $data['status'] ?? 'pending',
            $data['api_response'] ?? '',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Get sync status
     */
    public function getLastSync(): ?array
    {
        $stmt = $this->db->query("SELECT * FROM sync_log ORDER BY synced_at DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getProductCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    }

    public function getCategoryCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    }

    /**
     * Toggle product active/inactive status
     */
    public function toggleProduct(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    /**
     * Get recent bookings
     */
    public function getBookings(int $limit = 20): array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get booking count
     */
    public function getBookingCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    }

    /**
     * Ensure is_active column exists (migration for existing databases)
     */
    public function migrateAddIsActive(): void
    {
        try {
            $this->db->query("SELECT is_active FROM products LIMIT 1");
        } catch (\PDOException $e) {
            $this->db->exec("ALTER TABLE products ADD COLUMN is_active INTEGER DEFAULT 1");
        }
    }

    private function logSync(string $type, int $count, string $status, string $message = ''): void
    {
        $stmt = $this->db->prepare("INSERT INTO sync_log (type, items_count, status, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $count, $status, $message]);
    }
}
