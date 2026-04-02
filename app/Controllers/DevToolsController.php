<?php
namespace App\Controllers;

/**
 * DevToolsController - Developer tools and debug pages
 * All pages require developer/super-admin access
 * Most pages are standalone (own HTML shell)
 */
class DevToolsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Include a standalone view file with global variables in scope.
     *
     * Standalone views use require_once("inc/sys.configs.php") which is a no-op
     * when already loaded by index.php. This method makes $config (and $db)
     * available in the included file's scope so DbConn can be instantiated.
     */
    private function includeStandalone(string $viewFile): void
    {
        $config = $GLOBALS['config'] ?? null;
        $db = $GLOBALS['db'] ?? null;
        include $viewFile;
        exit;
    }

    /** Session Debug - standalone page */
    public function debugSession(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/debug-session.php');
    }

    /** Language Debug - standalone page */
    public function langDebug(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/lang-debug.php');
    }

    /** Dev Roadmap - standalone page */
    public function roadmap(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/roadmap.php');
    }

    /** CRUD Test - standalone page */
    public function testCrud(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/test-crud.php');
    }

    /** RBAC Test - standalone page */
    public function testRbac(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/test-rbac.php');
    }

    /** Container Test - standalone page */
    public function testContainers(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/test-containers.php');
    }

    /** AI CRUD Test - standalone page */
    public function testCrudAi(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/test-crud-ai.php');
    }

    /** PHP Debug - standalone page */
    public function debugPhp(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/debug-php.php');
    }

    /** System Monitoring - standalone page */
    public function monitoring(): void
    {
        $this->includeStandalone(__DIR__ . '/../Views/devtools/monitoring.php');
    }

    /** Container Monitor - partial page (included in admin layout) */
    public function containers(): void
    {
        $this->render('devtools/containers');
    }
}
