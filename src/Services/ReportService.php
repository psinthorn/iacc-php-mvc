<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ReportService - Report generation and management
 */
class ReportService extends Service
{
    protected $repository;
    protected $database;

    public function __construct(
        ReportRepository $repository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->database = $database;
    }

    /**
     * Get available reports
     */
    public function getAvailableReports()
    {
        return array_filter($this->repository->all(), fn($r) => $r->status == 1);
    }

    /**
     * Get report by code
     */
    public function getReport($code)
    {
        $report = $this->repository->findByCode($code);
        if (!$report) {
            throw new NotFoundException("Report not found");
        }
        return $report;
    }

    /**
     * Execute report query
     */
    public function executeReport($code, array $parameters = [])
    {
        $report = $this->getReport($code);

        try {
            $query = $report->query;

            // Replace parameters
            foreach ($parameters as $key => $value) {
                $query = str_replace(':' . $key, '?', $query);
            }

            $results = $this->database->select($query, array_values($parameters));

            $this->log('report_executed', [
                'report_code' => $code,
                'rows' => count($results),
            ]);

            return $results;
        } catch (\Exception $e) {
            throw new BusinessException("Failed to execute report: " . $e->getMessage());
        }
    }

    /**
     * Get sales summary
     */
    public function getSalesSummary($startDate, $endDate)
    {
        $query = "
            SELECT 
                DATE(so_date) as sale_date,
                COUNT(DISTINCT so.id) as order_count,
                SUM(so.total_amount) as total_amount
            FROM sales_order so
            WHERE so.so_date BETWEEN ? AND ?
            GROUP BY DATE(so.so_date)
            ORDER BY so.so_date DESC
        ";

        return $this->database->select($query, [$startDate, $endDate]);
    }

    /**
     * Get inventory status
     */
    public function getInventoryStatus()
    {
        $query = "
            SELECT 
                p.id,
                p.code,
                p.name,
                SUM(s.quantity) as total_quantity,
                (SELECT SUM(quantity) FROM stock_movement WHERE product_id = p.id AND movement_type = 'out') as out_quantity
            FROM product p
            LEFT JOIN stock s ON p.id = s.product_id
            GROUP BY p.id
            ORDER BY p.name
        ";

        return $this->database->select($query, []);
    }

    /**
     * Get outstanding invoices
     */
    public function getOutstandingInvoices()
    {
        $query = "
            SELECT 
                i.id,
                i.invoice_number,
                i.invoice_date,
                c.name as customer_name,
                i.total_amount,
                COALESCE(SUM(p.amount), 0) as paid_amount,
                (i.total_amount - COALESCE(SUM(p.amount), 0)) as outstanding,
                i.due_date
            FROM invoice i
            JOIN customer c ON i.customer_id = c.id
            LEFT JOIN payment p ON i.id = p.invoice_id
            WHERE i.payment_status = 'pending'
            GROUP BY i.id
            ORDER BY i.due_date ASC
        ";

        return $this->database->select($query, []);
    }
}
