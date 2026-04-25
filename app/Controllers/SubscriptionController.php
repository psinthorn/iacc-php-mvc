<?php
namespace App\Controllers;

use App\Models\Subscription;

/**
 * SubscriptionController — User-facing billing & plan management.
 *
 * Routes:
 *   billing            GET  — current plan, trial countdown, upgrade CTAs
 *   billing_upgrade    POST — record manual upgrade request / initiate payment
 *   billing_history    GET  — payment history table
 */
class SubscriptionController extends BaseController
{
    private Subscription $sub;

    public function __construct()
    {
        parent::__construct();
        $this->sub = new Subscription();
    }

    // ─── Billing Overview ──────────────────────────────────────────

    public function billing(): void
    {
        $comId = intval($this->user['com_id']);
        $sub   = $this->sub->getByCompanyId($comId);
        $plans = $this->getPlans();

        $trialDaysLeft  = null;
        $isTrialExpired = false;
        $isActive       = $sub ? $this->sub->isActive($sub) : false;

        if ($sub && $sub['plan'] === 'trial' && $sub['trial_end']) {
            $diff = (int) ceil((strtotime($sub['trial_end']) - time()) / 86400);
            $trialDaysLeft  = max(0, $diff);
            $isTrialExpired = $diff < 0;
        }

        $this->render('subscription/billing', compact(
            'sub', 'plans', 'trialDaysLeft', 'isTrialExpired', 'isActive'
        ));
    }

    // ─── Payment History ───────────────────────────────────────────

    public function history(): void
    {
        $comId    = intval($this->user['com_id']);
        $payments = $this->getPaymentHistory($comId);
        $this->render('subscription/history', compact('payments'));
    }

    // ─── Upgrade / Checkout (POST) ─────────────────────────────────

    public function upgrade(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('billing');
            return;
        }
        $this->verifyCsrf();

        $comId   = intval($this->user['com_id']);
        $plan    = trim($_POST['plan']    ?? '');
        $cycle   = trim($_POST['cycle']   ?? 'monthly');
        $method  = trim($_POST['method']  ?? 'manual');

        $validPlans  = ['starter', 'professional', 'enterprise'];
        $validCycles = ['monthly', 'annual'];

        if (!in_array($plan, $validPlans, true) || !in_array($cycle, $validCycles, true)) {
            $_SESSION['billing_error'] = 'Invalid plan selection.';
            $this->redirect('billing');
            return;
        }

        $plans    = $this->getPlans();
        $planData = $plans[$plan] ?? null;
        if (!$planData) {
            $_SESSION['billing_error'] = 'Plan not found.';
            $this->redirect('billing');
            return;
        }

        $amount = $cycle === 'annual'
            ? floatval($planData['price_annual'])
            : floatval($planData['price_monthly']);

        $sub = $this->sub->getByCompanyId($comId);

        // Record payment intent (pending)
        $paymentId = $this->recordPaymentIntent($comId, $sub['id'] ?? 0, $plan, $cycle, $amount, $method);

        if ($method === 'promptpay') {
            // Redirect to PromptPay payment link (existing gateway)
            header('Location: index.php?page=billing_promptpay&payment_id=' . $paymentId);
            exit;
        }

        if ($method === 'stripe') {
            header('Location: index.php?page=billing_stripe&payment_id=' . $paymentId);
            exit;
        }

        // Manual / bank transfer — show instructions
        $_SESSION['billing_pending'] = [
            'payment_id' => $paymentId,
            'plan'       => $plan,
            'cycle'      => $cycle,
            'amount'     => $amount,
        ];
        $this->redirect('billing_pending');
    }

    // ─── Pending payment instructions (manual/bank transfer) ──────

    public function pending(): void
    {
        $info = $_SESSION['billing_pending'] ?? null;
        if (!$info) {
            $this->redirect('billing');
            return;
        }
        $plans = $this->getPlans();
        $this->render('subscription/pending', compact('info', 'plans'));
    }

    // ─── Admin: mark payment complete ─────────────────────────────
    // (Super Admin only — activates plan after manual payment confirmation)

    public function confirmPayment(): void
    {
        header('Content-Type: application/json');

        if ($this->user['level'] < 3) {
            echo json_encode(['success' => false, 'message' => 'Super Admin only']);
            exit;
        }
        $this->verifyCsrf();

        $paymentId = intval($_POST['payment_id'] ?? 0);
        if (!$paymentId) {
            echo json_encode(['success' => false, 'message' => 'Missing payment_id']);
            exit;
        }

        // Load the payment record
        $res = mysqli_query($this->conn,
            "SELECT * FROM subscription_payments WHERE id = $paymentId LIMIT 1"
        );
        $payment = $res ? mysqli_fetch_assoc($res) : null;
        if (!$payment) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }

        // Activate the plan
        $sub = $this->sub->getByCompanyId(intval($payment['company_id']));
        if ($sub) {
            $ok = $this->sub->changePlan(intval($sub['id']), $payment['plan_code']);
        } else {
            $ok = false;
        }

        if ($ok) {
            $now = date('Y-m-d H:i:s');
            mysqli_query($this->conn,
                "UPDATE subscription_payments SET status='completed', paid_at='$now' WHERE id=$paymentId"
            );
        }

        echo json_encode(['success' => $ok, 'message' => $ok ? 'Plan activated.' : 'Failed to activate.']);
        exit;
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function getPlans(): array
    {
        $plans = [];
        $res = mysqli_query($this->conn,
            "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $row['features'] = json_decode($row['features'] ?? '[]', true) ?: [];
            $plans[$row['code']] = $row;
        }
        // Fallback to hardcoded if table empty
        if (empty($plans)) {
            foreach (Subscription::PLANS as $code => $cfg) {
                $plans[$code] = array_merge($cfg, ['code' => $code, 'name' => ucfirst($code), 'price_monthly' => 0, 'price_annual' => 0, 'features' => []]);
            }
        }
        return $plans;
    }

    private function getPaymentHistory(int $comId): array
    {
        $rows = [];
        $res  = mysqli_query($this->conn,
            "SELECT * FROM subscription_payments WHERE company_id = $comId ORDER BY created_at DESC LIMIT 50"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function recordPaymentIntent(int $comId, int $subId, string $plan, string $cycle, float $amount, string $method): int
    {
        $comId  = intval($comId);
        $subId  = intval($subId);
        $plan   = mysqli_real_escape_string($this->conn, $plan);
        $cycle  = mysqli_real_escape_string($this->conn, $cycle);
        $method = mysqli_real_escape_string($this->conn, $method);
        $userId = intval($this->user['id'] ?? 0);

        mysqli_query($this->conn,
            "INSERT INTO subscription_payments
             (company_id, subscription_id, plan_code, billing_cycle, amount, currency, payment_method, status, created_by)
             VALUES ($comId, $subId, '$plan', '$cycle', $amount, 'THB', '$method', 'pending', $userId)"
        );
        return intval(mysqli_insert_id($this->conn));
    }
}
