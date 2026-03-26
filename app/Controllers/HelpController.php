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
}
