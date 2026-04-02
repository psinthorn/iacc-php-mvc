<?php
namespace App\Controllers;

/**
 * DevToolsController - Developer tools and debug pages
 * All pages require developer/super-admin access
 * Rendered inside the admin layout (navbar + sidebar)
 */
class DevToolsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Include a view file with global variables in scope.
     * Used for devtools views that have their own PHP logic at the top.
     * The view is rendered inside the admin layout (index.php provides HTML shell).
     */
    private function includeDevView(string $viewFile): void
    {
        $config = $GLOBALS['config'] ?? null;
        $db = $GLOBALS['db'] ?? null;
        include $viewFile;
    }

    /**
     * Include a view as standalone (own HTML, no layout).
     * Used for AJAX/JSON API endpoints within devtools views.
     */
    private function includeStandalone(string $viewFile): void
    {
        $config = $GLOBALS['config'] ?? null;
        $db = $GLOBALS['db'] ?? null;
        include $viewFile;
        exit;
    }

    /** Session Debug — JSON API via ?format=json */
    public function debugSession(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/debug-session.php');
    }

    /** Language Debug — JSON API via ?format=json */
    public function langDebug(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/lang-debug.php');
    }

    /** Dev Roadmap */
    public function roadmap(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/roadmap.php');
    }

    /** CRUD Test */
    public function testCrud(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/test-crud.php');
    }

    /** RBAC Test */
    public function testRbac(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/test-rbac.php');
    }

    /** Container Test */
    public function testContainers(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/test-containers.php');
    }

    /** AI CRUD Test */
    public function testCrudAi(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/test-crud-ai.php');
    }

    /** PHP Debug — AJAX via ?action= handled as standalone */
    public function debugPhp(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/debug-php.php');
    }

    /** PHP Debug AJAX API endpoint */
    public function debugPhpApi(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/debug-php.php');
    }

    /** Session Debug JSON API endpoint */
    public function debugSessionApi(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/debug-session.php');
    }

    /** Language Debug JSON API endpoint */
    public function langDebugApi(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/lang-debug.php');
    }

    /** System Monitoring */
    public function monitoring(): void
    {
        $this->includeDevView(__DIR__ . '/../Views/devtools/monitoring.php');
    }

    /** Container Monitor - partial page (included in admin layout) */
    public function containers(): void
    {
        $this->render('devtools/containers');
    }
}
