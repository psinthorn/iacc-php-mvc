<?php
namespace App\Controllers;

/**
 * ExportController - Handles CSV/Excel data exports
 * All methods output downloadable files (no admin shell) and exit
 * Requires authentication (standalone route type)
 */
class ExportController extends BaseController
{
    /** Export invoice payments as CSV */
    public function invoicePayments(): void
    {
        include __DIR__ . '/../Views/export/invoice-payments.php';
        exit;
    }

    /** Export business report as CSV */
    public function report(): void
    {
        include __DIR__ . '/../Views/export/report.php';
        exit;
    }
}
