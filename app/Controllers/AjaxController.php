<?php
namespace App\Controllers;

/**
 * AjaxController - Handles AJAX/modal endpoints
 * All methods return partial HTML (no admin shell) and exit
 * Requires authentication (standalone route type)
 */
class AjaxController extends BaseController
{
    /** Dynamic product option dropdowns (brand/model/price by type) */
    public function productOptions(): void
    {
        include __DIR__ . '/../Views/ajax/product-options.php';
        exit;
    }

    /** Email preview/send modal content */
    public function emailPreview(): void
    {
        include __DIR__ . '/../Views/ajax/email-preview.php';
        exit;
    }
}
