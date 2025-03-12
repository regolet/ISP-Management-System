<?php
class BillingUtils {
    private $db;
    private $config;

    public function __construct($db) {
        $this->db = $db;
        $this->config = require dirname(__DIR__) . '/config/billing.php';
    }

    /**
     * Generate a unique invoice number
     */
    public function generateInvoiceNumber() {
        $prefix = $this->config['invoice']['prefix'];
        $year = date('Y');
        
        $query = "SELECT MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)) as max_num 
                 FROM billing 
                 WHERE invoice_number LIKE ?";
        
        $prefixLength = strlen($prefix) + strlen($year);
        $stmt = $this->db->prepare($query);
        $searchPrefix = $prefix . $year . '%';
        $stmt->execute([$prefixLength + 1, $searchPrefix]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($row['max_num'] ?? 0) + 1;
        
        return $prefix . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate prorated amount for subscription changes
     */
    public function calculateProratedAmount($oldPlanPrice, $newPlanPrice, $daysRemaining, $daysInMonth) {
        if (!$this->config['subscription']['prorate_upgrades'] && $newPlanPrice > $oldPlanPrice) {
            return $newPlanPrice;
        }
        
        if (!$this->config['subscription']['prorate_downgrades'] && $newPlanPrice < $oldPlanPrice) {
            return $newPlanPrice;
        }

        $dailyOldRate = $oldPlanPrice / $daysInMonth;
        $dailyNewRate = $newPlanPrice / $daysInMonth;
        $refundAmount = $dailyOldRate * $daysRemaining;
        $chargeAmount = $dailyNewRate * $daysRemaining;

        return $chargeAmount - $refundAmount;
    }

    /**
     * Calculate late fee for overdue invoice
     */
    public function calculateLateFee($amount, $daysOverdue) {
        if ($daysOverdue <= $this->config['invoice']['grace_period']) {
            return 0;
        }

        return $amount * ($this->config['invoice']['late_fee'] / 100);
    }

    /**
     * Calculate payment plan installments
     */
    public function calculatePaymentPlan($totalAmount, $months) {
        if (!$this->config['collection']['payment_plan']['enabled']) {
            throw new Exception('Payment plans are not enabled');
        }

        if ($totalAmount < $this->config['collection']['payment_plan']['minimum_amount']) {
            throw new Exception('Amount is below minimum for payment plan');
        }

        if ($months > $this->config['collection']['payment_plan']['maximum_months']) {
            throw new Exception('Requested months exceed maximum allowed');
        }

        $interestRate = $this->config['collection']['payment_plan']['interest_rate'] / 100 / 12;
        $monthlyPayment = $totalAmount * ($interestRate * pow(1 + $interestRate, $months)) 
                         / (pow(1 + $interestRate, $months) - 1);

        $schedule = [];
        $balance = $totalAmount;
        $totalInterest = 0;

        for ($i = 1; $i <= $months; $i++) {
            $interest = $balance * $interestRate;
            $principal = $monthlyPayment - $interest;
            $balance -= $principal;
            $totalInterest += $interest;

            $schedule[] = [
                'payment_number' => $i,
                'payment_amount' => round($monthlyPayment, 2),
                'principal' => round($principal, 2),
                'interest' => round($interest, 2),
                'balance' => round($balance, 2)
            ];
        }

        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payments' => round($monthlyPayment * $months, 2),
            'total_interest' => round($totalInterest, 2),
            'schedule' => $schedule
        ];
    }

    /**
     * Calculate subscription discount based on billing cycle
     */
    public function calculateCycleDiscount($amount, $cycle) {
        if (!isset($this->config['subscription']['billing_cycles'][$cycle])) {
            return $amount;
        }

        $discount = $this->config['subscription']['billing_cycles'][$cycle]['discount'];
        return $amount * (1 - ($discount / 100));
    }

    /**
     * Format currency amount
     */
    public function formatAmount($amount) {
        return number_format(
            $amount,
            $this->config['invoice']['decimal_places'],
            '.',
            ','
        );
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax($amount) {
        return $amount * ($this->config['invoice']['tax_rate'] / 100);
    }

    /**
     * Check if service should be suspended
     */
    public function shouldSuspendService($daysOverdue, $amount) {
        if (!$this->config['collection']['auto_suspend']['enabled']) {
            return false;
        }

        if ($daysOverdue < $this->config['collection']['auto_suspend']['days_overdue']) {
            return false;
        }

        if ($amount < $this->config['collection']['auto_suspend']['minimum_amount']) {
            return false;
        }

        return true;
    }

    /**
     * Get payment method details
     */
    public function getPaymentMethod($method) {
        if (!isset($this->config['payment']['methods'][$method])) {
            throw new Exception('Invalid payment method');
        }

        return $this->config['payment']['methods'][$method];
    }

    /**
     * Validate payment amount
     */
    public function validatePaymentAmount($amount) {
        if ($amount < $this->config['payment']['minimum_amount']) {
            throw new Exception(sprintf(
                'Payment amount must be at least $%s',
                $this->formatAmount($this->config['payment']['minimum_amount'])
            ));
        }

        return true;
    }

    /**
     * Get notification schedule for invoice
     */
    public function getNotificationSchedule($dueDate, $status = 'pending') {
        $schedule = [];
        $now = time();
        $dueTimestamp = strtotime($dueDate);

        if ($status === 'pending') {
            foreach ($this->config['notifications']['reminder']['schedule'] as $reminder) {
                $sendDate = strtotime("-{$reminder['days_before']} days", $dueTimestamp);
                if ($sendDate > $now) {
                    $schedule[] = [
                        'type' => 'reminder',
                        'template' => $reminder['template'],
                        'send_date' => date('Y-m-d', $sendDate)
                    ];
                }
            }
        } elseif ($status === 'overdue') {
            foreach ($this->config['notifications']['overdue']['schedule'] as $notice) {
                $sendDate = strtotime("+{$notice['days_after']} days", $dueTimestamp);
                if ($sendDate > $now) {
                    $schedule[] = [
                        'type' => 'overdue',
                        'template' => $notice['template'],
                        'send_date' => date('Y-m-d', $sendDate)
                    ];
                }
            }
        }

        return $schedule;
    }

    /**
     * Get file storage path
     */
    public function getStoragePath($type, $filename = '') {
        if (!isset($this->config['storage'][$type])) {
            throw new Exception('Invalid storage type');
        }

        $path = dirname(__DIR__) . '/storage/' . $this->config['storage'][$type];
        
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        return $filename ? $path . '/' . $filename : $path;
    }

    /**
     * Get PDF configuration
     */
    public function getPdfConfig() {
        return $this->config['pdf'];
    }

    /**
     * Get company information
     */
    public function getCompanyInfo() {
        return $this->config['company'];
    }
}
