<?php
namespace App\Controllers;

/**
 * BaseController - Foundation for all MVC controllers
 * 
 * Provides shared functionality:
 * - View rendering with layout support
 * - Redirect helpers
 * - JSON response helpers
 * - Access to DB connection, i18n, session, company filter
 * - CSRF verification
 * 
 * All controllers extend this class.
 */
class BaseController
{
    /** @var \mysqli Database connection */
    protected $conn;

    /** @var \DbConn Database connection object */
    protected $db;

    /** @var \HardClass Database abstraction */
    protected $hard;

    /** @var \SimpleXMLElement i18n strings */
    protected $xml;

    /** @var \CompanyFilter Multi-tenant filter */
    protected $companyFilter;

    /** @var array Current user session data */
    protected $user;

    /**
     * Initialize controller with shared dependencies
     * Called by the dispatcher in index.php
     */
    public function __construct()
    {
        global $db, $xml;

        $this->db = $db;
        $this->conn = $db->conn;
        $this->xml = $xml;

        // Initialize HardClass for DB operations
        $this->hard = new \HardClass();
        $this->hard->setConnection($this->conn);

        // Initialize company filter
        require_once __DIR__ . '/../../inc/class.company_filter.php';
        $this->companyFilter = \CompanyFilter::getInstance();

        // Module feature gating helper
        require_once __DIR__ . '/../../inc/module-helper.php';

        // Load user session data
        $this->user = [
            'id'        => $_SESSION['user_id'] ?? 0,
            'email'     => $_SESSION['user_email'] ?? '',
            'level'     => intval($_SESSION['user_level'] ?? 0),
            'com_id'    => intval($_SESSION['com_id'] ?? 0),
            'com_name'  => $_SESSION['com_name'] ?? '',
            'lang'      => $_SESSION['lang'] ?? 'en',
        ];
    }

    /**
     * Render a view file with data, wrapped in the app layout
     * 
     * @param string $view   View path relative to app/Views/ (e.g., 'category/list')
     * @param array  $data   Variables to extract into the view scope
     * @param string $layout Layout name (default: null = no layout, rendered inline by index.php)
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        // Make common vars available to view
        $data['xml'] = $this->xml;
        $data['user'] = $this->user;
        $data['companyFilter'] = $this->companyFilter;

        // Extract data into variables for the view
        extract($data);

        // Build view file path
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view ($viewFile)");
        }

        if ($layout) {
            // Capture view content for layout injection
            ob_start();
            include $viewFile;
            $content = ob_get_clean();

            $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout not found: $layout");
            }
            include $layoutFile;
        } else {
            // No layout — index.php provides the HTML shell
            include $viewFile;
        }
    }

    /**
     * Redirect to a page using the standard routing
     * 
     * @param string $page  Page name (route key)
     * @param array  $params Additional query parameters
     */
    protected function redirect(string $url, array $params = []): void
    {
        // Support both full URLs ("index.php?page=foo") and page names ("foo")
        if (strpos($url, 'index.php') === false && strpos($url, '/') === false && strpos($url, 'http') === false) {
            $url = 'index.php?page=' . urlencode($url);
        }
        if (!empty($params)) {
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $separator . http_build_query($params);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * Send a JSON response and exit
     * 
     * @param mixed $data Data to encode as JSON
     * @param int   $status HTTP status code
     */
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Verify CSRF token for POST requests
     * Uses the existing csrf_verify() from security.php
     * 
     * @throws \RuntimeException if CSRF validation fails
     */
    protected function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                http_response_code(403);
                // Return JSON if the endpoint already set JSON content type
                if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json') ||
                    str_contains(implode('', headers_list()), 'application/json')) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'CSRF token expired. Please refresh the page and try again.']);
                    exit;
                }
                die('CSRF token validation failed. Please refresh the page and try again.');
            }
        }
    }

    /**
     * Get a request parameter (GET or POST) with optional default
     * 
     * @param string $key     Parameter name
     * @param mixed  $default Default value
     * @return mixed
     */
    protected function input(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get sanitized integer from request
     */
    protected function inputInt(string $key, int $default = 0): int
    {
        return \sql_int($_REQUEST[$key] ?? $default);
    }

    /**
     * Get sanitized string from request
     */
    protected function inputStr(string $key, string $default = ''): string
    {
        return \sql_escape($_REQUEST[$key] ?? $default);
    }

    /**
     * Get the company ID for insert operations
     */
    protected function getCompanyId(): int
    {
        return $this->user['com_id'];
    }

    /**
     * Execute a raw SQL query and return results
     * For complex queries not suited to HardClass
     * 
     * @param string $sql Raw SQL query
     * @return \mysqli_result|false
     */
    protected function query(string $sql)
    {
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Fetch all rows from a query result
     */
    protected function fetchAll($result): array
    {
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Fetch a single row from a query result
     */
    protected function fetchOne($result): ?array
    {
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }
}
