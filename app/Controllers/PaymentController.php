<?php
namespace App\Controllers;

use App\Models\Payment;

/**
 * PaymentController - Handles payment method CRUD
 * Replaces: payment-list.php, payment.php, core-function.php case payment
 */
class PaymentController extends BaseController
{
    private Payment $payment;

    public function __construct()
    {
        parent::__construct();
        $this->payment = new Payment();
    }

    public function index(): void
    {
        $comId = $this->getCompanyId();
        $search = $this->input('search', '');
        $this->render('payment/list', [
            'items' => $this->payment->getPayments($comId, $search),
            'total' => $this->payment->countPayments($comId),
            'search' => $search,
            'edit_id' => $this->inputInt('edit', 0),
            'edit_data' => $this->inputInt('edit', 0) > 0
                ? $this->payment->findPayment($this->inputInt('edit', 0), $comId) : null,
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $method = $this->input('method', '');
        $comId = $this->getCompanyId();

        if ($method === 'A') {
            $this->payment->createPayment($comId, $this->input('payment_name', ''), $this->input('payment_des', ''));
        } elseif ($method === 'E') {
            $this->payment->updatePayment($this->inputInt('id', 0), $this->input('payment_name', ''), $this->input('payment_des', ''));
        }
        $this->redirect('index.php?page=payment');
    }
}
