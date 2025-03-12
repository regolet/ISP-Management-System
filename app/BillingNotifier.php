<?php
require_once dirname(__DIR__) . '/includes/TemplateRenderer.php';
require_once dirname(__DIR__) . '/includes/BillingUtils.php';

class BillingNotifier {
    private $db;
    private $renderer;
    private $utils;
    private $config;
    private $logFile;

    public function __construct($db) {
        $this->db = $db;
        $this->renderer = new TemplateRenderer();
        $this->utils = new BillingUtils($db);
        $this->config = require dirname(__DIR__) . '/config/billing.php';
        $this->logFile = dirname(__DIR__) . '/logs/notifications.log';

        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Send invoice notification
     */
    public function sendInvoiceNotification($invoice) {
        if (!$this->config['notifications']['invoice']['enabled']) {
            return false;
        }

        try {
            // Get client details
            $stmt = $this->db->prepare("
                SELECT c.*, cs.subscription_number, p.name as plan_name
                FROM clients c
                JOIN client_subscriptions cs ON c.id = cs.client_id
                JOIN plans p ON cs.plan_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$invoice['client_id']]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            // Prepare template data
            $data = array_merge($invoice, $client, [
                'company' => $this->utils->getCompanyInfo(),
                'payment_link' => $this->generatePaymentLink($invoice['id']),
                'year' => date('Y')
            ]);

            // Send email
            $emailId = $this->renderer->sendEmail(
                $client['email'],
                'New Invoice Available - ' . $invoice['invoice_number'],
                'invoice_notification',
                $data
            );

            // Log notification
            $this->logNotification('invoice', $invoice['id'], $emailId);

            return true;

        } catch (\Exception $e) {
            $this->log("Error sending invoice notification: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Send payment reminder
     */
    public function sendPaymentReminder($invoice, $type = 'reminder') {
        if (!$this->config['notifications']['reminder']['enabled']) {
            return false;
        }

        try {
            // Get client and subscription details
            $stmt = $this->db->prepare("
                SELECT c.*, cs.subscription_number, p.name as plan_name,
                       DATEDIFF(b.due_date, CURRENT_DATE) as days_until_due,
                       DATEDIFF(CURRENT_DATE, b.due_date) as days_overdue
                FROM clients c
                JOIN client_subscriptions cs ON c.id = cs.client_id
                JOIN plans p ON cs.plan_id = p.id
                JOIN billing b ON b.id = ?
                WHERE c.id = ?
            ");
            $stmt->execute([$invoice['id'], $invoice['client_id']]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            // Add invoice and company data
            $data = array_merge($data, $invoice, [
                'company' => $this->utils->getCompanyInfo(),
                'payment_link' => $this->generatePaymentLink($invoice['id']),
                'year' => date('Y')
            ]);

            // Determine template and subject
            if ($type === 'overdue') {
                $template = 'payment_reminder';
                $subject = 'Payment Overdue - Invoice #' . $invoice['invoice_number'];
                $data['overdue'] = true;
            } else {
                $template = 'payment_reminder';
                $subject = 'Payment Reminder - Invoice #' . $invoice['invoice_number'];
                $data['overdue'] = false;
            }

            // Send email
            $emailId = $this->renderer->sendEmail(
                $data['email'],
                $subject,
                $template,
                $data
            );

            // Log notification
            $this->logNotification($type, $invoice['id'], $emailId);

            return true;

        } catch (\Exception $e) {
            $this->log("Error sending payment reminder: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Send service suspension notice
     */
    public function sendSuspensionNotice($invoice) {
        if (!$this->config['notifications']['suspension']['enabled']) {
            return false;
        }

        try {
            // Get all overdue invoices for client
            $stmt = $this->db->prepare("
                SELECT b.*, 
                       DATEDIFF(CURRENT_DATE, b.due_date) as days_overdue
                FROM billing b
                WHERE b.client_id = ?
                AND b.status = 'overdue'
                ORDER BY b.due_date ASC
            ");
            $stmt->execute([$invoice['client_id']]);
            $overdueInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get client details
            $stmt = $this->db->prepare("
                SELECT c.*, cs.subscription_number, p.name as plan_name
                FROM clients c
                JOIN client_subscriptions cs ON c.id = cs.client_id
                JOIN plans p ON cs.plan_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$invoice['client_id']]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate total amount due
            $totalAmount = array_sum(array_column($overdueInvoices, 'total_amount'));

            // Prepare template data
            $data = array_merge($client, [
                'company' => $this->utils->getCompanyInfo(),
                'invoices' => $overdueInvoices,
                'total_amount' => $totalAmount,
                'suspension_date' => date('Y-m-d', strtotime('+' . $this->config['notifications']['suspension']['days_until_suspension'] . ' days')),
                'reconnection_fee' => $this->config['collection']['reconnection_fee'],
                'payment_link' => $this->generatePaymentLink($invoice['id']),
                'year' => date('Y')
            ]);

            // Send email
            $emailId = $this->renderer->sendEmail(
                $client['email'],
                'Service Suspension Notice - Action Required',
                'service_suspension',
                $data
            );

            // Log notification
            $this->logNotification('suspension', $invoice['id'], $emailId);

            return true;

        } catch (\Exception $e) {
            $this->log("Error sending suspension notice: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Process all pending notifications
     */
    public function processNotifications() {
        $this->log("Starting notification processing...");

        try {
            // Process new invoices
            $this->processNewInvoices();

            // Process payment reminders
            $this->processReminders();

            // Process overdue notices
            $this->processOverdueNotices();

            // Process suspension notices
            $this->processSuspensionNotices();

            $this->log("Notification processing completed");

        } catch (\Exception $e) {
            $this->log("Error processing notifications: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Process new invoices
     */
    private function processNewInvoices() {
        $stmt = $this->db->prepare("
            SELECT *
            FROM billing
            WHERE notification_sent = 0
            AND status = 'pending'
        ");
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($invoices as $invoice) {
            if ($this->sendInvoiceNotification($invoice)) {
                $this->markNotificationSent($invoice['id']);
            }
        }
    }

    /**
     * Process payment reminders
     */
    private function processReminders() {
        $config = $this->config['notifications']['reminder']['schedule'];
        
        foreach ($config as $reminder) {
            $stmt = $this->db->prepare("
                SELECT b.*
                FROM billing b
                LEFT JOIN notification_log nl ON b.id = nl.invoice_id 
                    AND nl.type = ? 
                    AND nl.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                WHERE b.status = 'pending'
                AND b.due_date = DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                AND nl.id IS NULL
            ");
            $stmt->execute(['reminder', $reminder['days_before']]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoices as $invoice) {
                $this->sendPaymentReminder($invoice);
            }
        }
    }

    /**
     * Process overdue notices
     */
    private function processOverdueNotices() {
        $config = $this->config['notifications']['overdue']['schedule'];
        
        foreach ($config as $notice) {
            $stmt = $this->db->prepare("
                SELECT b.*
                FROM billing b
                LEFT JOIN notification_log nl ON b.id = nl.invoice_id 
                    AND nl.type = ? 
                    AND nl.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                WHERE b.status = 'overdue'
                AND DATEDIFF(CURRENT_DATE, b.due_date) = ?
                AND nl.id IS NULL
            ");
            $stmt->execute(['overdue', $notice['days_after']]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoices as $invoice) {
                $this->sendPaymentReminder($invoice, 'overdue');
            }
        }
    }

    /**
     * Process suspension notices
     */
    private function processSuspensionNotices() {
        $warningDays = $this->config['notifications']['suspension']['warning_days'];
        $daysUntilSuspension = $this->config['notifications']['suspension']['days_until_suspension'];

        foreach ($warningDays as $days) {
            $stmt = $this->db->prepare("
                SELECT b.*
                FROM billing b
                LEFT JOIN notification_log nl ON b.id = nl.invoice_id 
                    AND nl.type = 'suspension' 
                    AND nl.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
                WHERE b.status = 'overdue'
                AND DATEDIFF(CURRENT_DATE, b.due_date) = ?
                AND nl.id IS NULL
            ");
            $stmt->execute([$daysUntilSuspension - $days]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($invoices as $invoice) {
                $this->sendSuspensionNotice($invoice);
            }
        }
    }

    /**
     * Generate payment link
     */
    private function generatePaymentLink($invoiceId) {
        return $this->config['company']['website'] . '/billing.php?invoice=' . $invoiceId;
    }

    /**
     * Mark notification as sent
     */
    private function markNotificationSent($invoiceId) {
        $stmt = $this->db->prepare("
            UPDATE billing
            SET notification_sent = 1,
                notification_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$invoiceId]);
    }

    /**
     * Log notification
     */
    private function logNotification($type, $invoiceId, $emailId) {
        $stmt = $this->db->prepare("
            INSERT INTO notification_log 
            (type, invoice_id, email_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$type, $invoiceId, $emailId]);
    }

    /**
     * Log message
     */
    private function log($message, $level = 'INFO') {
        $logMessage = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}
