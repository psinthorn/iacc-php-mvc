<?php
namespace App\Controllers;

/**
 * PdfController - Handles PDF/print document generation
 * All methods render standalone pages (no admin shell) and exit
 * Requires authentication (standalone route type)
 */
class PdfController extends BaseController
{
    /** Quotation PDF */
    public function quotation(): void
    {
        include __DIR__ . '/../Views/pdf/quotation.php';
        exit;
    }

    /** Quotation email/mail version */
    public function quotationMail(): void
    {
        include __DIR__ . '/../Views/pdf/quotation-mail.php';
        exit;
    }

    /** Invoice PDF */
    public function invoice(): void
    {
        include __DIR__ . '/../Views/pdf/invoice.php';
        exit;
    }

    /** Invoice email/mail version */
    public function invoiceMail(): void
    {
        include __DIR__ . '/../Views/pdf/invoice-mail.php';
        exit;
    }

    /** Tax Invoice PDF */
    public function taxInvoice(): void
    {
        include __DIR__ . '/../Views/pdf/tax-invoice.php';
        exit;
    }

    /** Tax Invoice email/mail version */
    public function taxInvoiceMail(): void
    {
        include __DIR__ . '/../Views/pdf/tax-invoice-mail.php';
        exit;
    }

    /** Receipt / Delivery Note PDF */
    public function receipt(): void
    {
        include __DIR__ . '/../Views/pdf/receipt.php';
        exit;
    }

    /** Split Invoice PDF */
    public function splitInvoice(): void
    {
        include __DIR__ . '/../Views/pdf/split-invoice.php';
        exit;
    }
}
