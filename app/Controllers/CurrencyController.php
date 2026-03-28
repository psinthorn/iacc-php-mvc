<?php
namespace App\Controllers;

use App\Models\Currency;
use App\Services\CurrencyService;

/**
 * CurrencyController — Multi-Currency Management
 * 
 * Handles currency settings, exchange rate management, and rate refresh.
 * 
 * Routes:
 *   currency_list     → index()    — Currency list & settings
 *   currency_rates    → rates()    — View exchange rates
 *   currency_refresh  → refresh()  — Refresh rates from BOT API
 *   currency_toggle   → toggle()   — Enable/disable a currency
 * 
 * @package App\Controllers
 * @version 1.0.0 — Q2 2026
 */
class CurrencyController extends BaseController
{
    private Currency $model;
    private CurrencyService $service;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Currency();
        $this->service = new CurrencyService($this->conn);
    }

    /**
     * Currency management page — list of currencies with toggle
     */
    public function index(): void
    {
        // Admin access required
        if ($this->user['level'] < 1) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied.</div>';
            return;
        }

        $currencies = $this->service->getSupportedCurrencies();
        $activeCurrencies = $this->model->getActiveCurrencies();
        $companyId = $this->companyFilter->getSafeCompanyId();
        $defaultCurrency = $this->model->getCompanyDefaultCurrency($companyId);
        
        $message = $_GET['msg'] ?? '';
        
        $this->render('currency/list', compact('currencies', 'activeCurrencies', 'defaultCurrency', 'message'));
    }

    /**
     * Exchange rates view — show current rates with history
     */
    public function rates(): void
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $rates = $this->service->getLatestRates();
        $supported = $this->service->getSupportedCurrencies();
        
        $this->render('currency/rates', compact('rates', 'supported', 'date'));
    }

    /**
     * Refresh exchange rates from Bank of Thailand API
     */
    public function refresh(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=currency_rates');
            exit;
        }
        
        $this->verifyCsrf();
        
        // Super Admin only
        if ($this->user['level'] < 2) {
            header('Location: index.php?page=currency_rates&msg=access_denied');
            exit;
        }
        
        $result = $this->service->refreshRates();
        
        if ($result['success']) {
            header('Location: index.php?page=currency_rates&msg=refreshed&count=' . $result['rates_updated']);
        } else {
            header('Location: index.php?page=currency_rates&msg=refresh_error');
        }
        exit;
    }

    /**
     * Toggle currency active/inactive
     */
    public function toggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=currency_list');
            exit;
        }
        
        $this->verifyCsrf();
        
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->model->toggleActive($id);
        }
        
        header('Location: index.php?page=currency_list&msg=updated');
        exit;
    }
}
