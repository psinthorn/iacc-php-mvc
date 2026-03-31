<?php
namespace App\Controllers;

class HelpController extends BaseController
{
    public function index(): void
    {
        $this->render('help/index');
    }

    /** Master Data Guide - standalone documentation page */
    public function masterDataGuide(): void
    {
        include __DIR__ . '/../Views/help/master-data-guide.php';
        exit;
    }

    /** User Manual - step-by-step user guide */
    public function userManual(): void
    {
        $this->render('help/user-manual');
    }

    /** Developer Summary - standalone technical reference */
    public function devSummary(): void
    {
        include __DIR__ . '/../Views/help/dev-summary.php';
        exit;
    }
}
