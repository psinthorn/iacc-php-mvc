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

    /** Session Debug - standalone page */
    public function debugSession(): void
    {
        include __DIR__ . '/../Views/devtools/debug-session.php';
        exit;
    }

    /** Language Debug - standalone page */
    public function langDebug(): void
    {
        include __DIR__ . '/../Views/devtools/lang-debug.php';
        exit;
    }

    /** Dev Roadmap - standalone page */
    public function roadmap(): void
    {
        include __DIR__ . '/../Views/devtools/roadmap.php';
        exit;
    }

    /** CRUD Test - standalone page */
    public function testCrud(): void
    {
        include __DIR__ . '/../Views/devtools/test-crud.php';
        exit;
    }

    /** RBAC Test - standalone page */
    public function testRbac(): void
    {
        include __DIR__ . '/../Views/devtools/test-rbac.php';
        exit;
    }

    /** Container Test - standalone page */
    public function testContainers(): void
    {
        include __DIR__ . '/../Views/devtools/test-containers.php';
        exit;
    }

    /** AI CRUD Test - standalone page */
    public function testCrudAi(): void
    {
        include __DIR__ . '/../Views/devtools/test-crud-ai.php';
        exit;
    }

    /** PHP Debug - standalone page */
    public function debugPhp(): void
    {
        include __DIR__ . '/../Views/devtools/debug-php.php';
        exit;
    }

    /** System Monitoring - standalone page */
    public function monitoring(): void
    {
        include __DIR__ . '/../Views/devtools/monitoring.php';
        exit;
    }

    /** Container Monitor - partial page (included in admin layout) */
    public function containers(): void
    {
        $this->render('devtools/containers');
    }
}
