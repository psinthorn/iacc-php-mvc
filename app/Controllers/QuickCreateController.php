<?php
namespace App\Controllers;

use App\Services\QuickCreateService;

/**
 * QuickCreateController — Multiple Entry Points for Document Creation
 * 
 * Supports 3 entry points:
 *   A) Quotation → auto-creates PR upstream
 *   B) Invoice   → auto-creates PR + PO + Delivery upstream
 *   C) Tax Invoice → auto-creates PR + PO + Delivery + Invoice upstream
 * 
 * Traditional flow remains unchanged — this is a separate module.
 */
class QuickCreateController extends BaseController
{
    private QuickCreateService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new QuickCreateService();
    }

    /**
     * Landing page — choose entry point
     */
    public function index(): void
    {
        $this->render('quick-create/index', []);
    }

    /**
     * Quotation entry form (Entry Point A)
     */
    public function quotation(): void
    {
        $comId = $this->getCompanyId();
        $formData = $this->service->getFormData($comId);

        $this->render('quick-create/quotation', array_merge($formData, [
            'entry_type' => 'quotation',
        ]));
    }

    /**
     * Invoice entry form (Entry Point B)
     */
    public function invoice(): void
    {
        $comId = $this->getCompanyId();
        $formData = $this->service->getFormData($comId);

        $this->render('quick-create/invoice', array_merge($formData, [
            'entry_type' => 'invoice',
        ]));
    }

    /**
     * Tax Invoice entry form (Entry Point C)
     */
    public function taxInvoice(): void
    {
        $comId = $this->getCompanyId();
        $formData = $this->service->getFormData($comId);

        $this->render('quick-create/tax-invoice', array_merge($formData, [
            'entry_type' => 'tax_invoice',
        ]));
    }

    /**
     * POST handler — delegates to QuickCreateService based on entry_type
     */
    public function store(): void
    {
        $this->verifyCsrf();
        $comId = $this->getCompanyId();
        $entryType = $this->inputStr('entry_type', '');

        $result = match ($entryType) {
            'quotation'   => $this->service->createFromQuotation($_POST, $comId),
            'invoice'     => $this->service->createFromInvoice($_POST, $comId),
            'tax_invoice' => $this->service->createFromTaxInvoice($_POST, $comId),
            default       => ['success' => false, 'data' => [], 'error' => 'Invalid entry type'],
        };

        if ($result['success']) {
            $_SESSION['flash_success'] = $this->getSuccessMessage($entryType, $result['data']);

            // Redirect to the appropriate view page
            match ($entryType) {
                'quotation'   => $this->redirect('po_view', ['id' => $result['data']['po_id']]),
                'invoice'     => $this->redirect('po_view', ['id' => $result['data']['po_id']]),
                'tax_invoice' => $this->redirect('po_view', ['id' => $result['data']['po_id']]),
                default       => $this->redirect('qc_index'),
            };
        } else {
            $_SESSION['flash_error'] = $result['error'] ?? 'Failed to create documents. Please try again.';
            $this->redirect('qc_' . $entryType);
        }
    }

    /**
     * Build success message for flash notification
     */
    private function getSuccessMessage(string $entryType, array $data): string
    {
        return match ($entryType) {
            'quotation'   => 'Quotation created successfully. PR #' . $data['pr_id'] . ' auto-generated.',
            'invoice'     => 'Invoice created successfully. PR #' . $data['pr_id'] . ', PO #' . $data['po_id'] . ', Delivery auto-generated.',
            'tax_invoice' => 'Tax Invoice ' . ($data['taxrw'] ?? '') . ' created successfully. All upstream documents auto-generated.',
            default       => 'Documents created successfully.',
        };
    }
}
