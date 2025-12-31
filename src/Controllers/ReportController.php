<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ReportController - Report generation endpoints
 */
class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * GET /api/reports
     */
    public function index()
    {
        try {
            $reports = $this->reportService->getAvailableReports();
            return $this->json(['data' => $reports]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/reports/:code/execute
     */
    public function execute($code)
    {
        try {
            $parameters = $this->all();

            $results = $this->reportService->executeReport($code, $parameters);

            return $this->json([
                'report' => $code,
                'rows' => count($results),
                'data' => $results,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/reports/sales-summary
     */
    public function getSalesSummary()
    {
        try {
            $startDate = $this->get('start_date', date('Y-m-01'));
            $endDate = $this->get('end_date', date('Y-m-d'));

            $summary = $this->reportService->getSalesSummary($startDate, $endDate);

            return $this->json([
                'type' => 'sales_summary',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/reports/inventory-status
     */
    public function getInventoryStatus()
    {
        try {
            $inventory = $this->reportService->getInventoryStatus();

            return $this->json([
                'type' => 'inventory_status',
                'data' => $inventory,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/reports/outstanding-invoices
     */
    public function getOutstandingInvoices()
    {
        try {
            $invoices = $this->reportService->getOutstandingInvoices();

            return $this->json([
                'type' => 'outstanding_invoices',
                'count' => count($invoices),
                'data' => $invoices,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
