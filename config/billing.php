<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Billing Settings
    |--------------------------------------------------------------------------
    |
    | Configure billing-related settings for the ISP Management System
    |
    */

    // Company Information
    'company' => [
        'name' => 'ISP Management System',
        'address' => '123 ISP Street',
        'city' => 'Network City',
        'state' => 'ST',
        'postal_code' => '12345',
        'phone' => '(555) 123-4567',
        'email' => 'billing@isp-system.com',
        'website' => 'https://isp-system.com',
        'tax_id' => '12-3456789',
    ],

    // Invoice Settings
    'invoice' => [
        'prefix' => 'INV',
        'start_number' => 1000,
        'due_days' => 14, // Number of days until invoice is due
        'tax_rate' => 10.0, // Default tax rate (percentage)
        'late_fee' => 5.0, // Late fee percentage
        'grace_period' => 3, // Grace period in days before late fee applies
        'currency' => 'USD',
        'decimal_places' => 2,
    ],

    // Payment Settings
    'payment' => [
        'methods' => [
            'cash' => [
                'enabled' => true,
                'display_name' => 'Cash',
                'icon' => 'fas fa-money-bill-wave',
            ],
            'credit_card' => [
                'enabled' => true,
                'display_name' => 'Credit Card',
                'icon' => 'fas fa-credit-card',
                'processor' => 'stripe', // Payment processor to use
            ],
            'bank_transfer' => [
                'enabled' => true,
                'display_name' => 'Bank Transfer',
                'icon' => 'fas fa-university',
                'account_info' => [
                    'bank_name' => 'Example Bank',
                    'account_name' => 'ISP Management System',
                    'account_number' => 'XXXX-XXXX-XXXX',
                    'routing_number' => 'XXXXXXXXX',
                    'swift_code' => 'EXAMPEXXX',
                ],
            ],
            'online' => [
                'enabled' => true,
                'display_name' => 'Online Payment',
                'icon' => 'fas fa-globe',
            ],
        ],
        'minimum_amount' => 5.00,
        'receipt_prefix' => 'PAY',
    ],

    // Subscription Settings
    'subscription' => [
        'billing_cycles' => [
            'monthly' => [
                'display_name' => 'Monthly',
                'months' => 1,
                'discount' => 0,
            ],
            'quarterly' => [
                'display_name' => 'Quarterly',
                'months' => 3,
                'discount' => 5, // 5% discount
            ],
            'yearly' => [
                'display_name' => 'Yearly',
                'months' => 12,
                'discount' => 10, // 10% discount
            ],
        ],
        'prorate_upgrades' => true,
        'prorate_downgrades' => false,
    ],

    // Notification Settings
    'notifications' => [
        'invoice' => [
            'enabled' => true,
            'send_days_before' => 3, // Send invoice notification 3 days before due
        ],
        'payment' => [
            'enabled' => true,
            'send_receipt' => true,
        ],
        'reminder' => [
            'enabled' => true,
            'schedule' => [
                ['days_before' => 7, 'template' => 'first_reminder'],
                ['days_before' => 3, 'template' => 'second_reminder'],
                ['days_before' => 1, 'template' => 'final_reminder'],
            ],
        ],
        'overdue' => [
            'enabled' => true,
            'schedule' => [
                ['days_after' => 1, 'template' => 'first_overdue'],
                ['days_after' => 7, 'template' => 'second_overdue'],
                ['days_after' => 14, 'template' => 'final_overdue'],
            ],
        ],
        'suspension' => [
            'enabled' => true,
            'days_until_suspension' => 30,
            'warning_days' => [14, 7, 3, 1], // Send warnings these days before suspension
        ],
    ],

    // Report Settings
    'reports' => [
        'monthly' => [
            'enabled' => true,
            'recipients' => [
                'billing@isp-system.com',
                'finance@isp-system.com',
            ],
            'include_charts' => true,
            'format' => 'pdf',
        ],
        'retention' => [
            'invoices' => 84, // Keep invoices for 7 years (84 months)
            'receipts' => 84,
            'reports' => 84,
        ],
    ],

    // Collection Settings
    'collection' => [
        'auto_suspend' => [
            'enabled' => true,
            'days_overdue' => 30,
            'minimum_amount' => 50.00,
        ],
        'reconnection_fee' => 25.00,
        'payment_plan' => [
            'enabled' => true,
            'minimum_amount' => 100.00,
            'maximum_months' => 6,
            'interest_rate' => 5.0,
        ],
    ],

    // PDF Settings
    'pdf' => [
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'margin_top' => 30,
        'margin_right' => 20,
        'margin_bottom' => 30,
        'margin_left' => 20,
        'font' => 'Arial',
        'font_size' => 12,
    ],

    // Storage Settings
    'storage' => [
        'invoices' => 'billing/invoices',
        'receipts' => 'billing/receipts',
        'reports' => 'billing/reports',
        'temp' => 'billing/temp',
    ],

    // Integration Settings
    'integrations' => [
        'accounting' => [
            'enabled' => false,
            'provider' => 'quickbooks',
            'auto_sync' => true,
        ],
        'payment_gateway' => [
            'provider' => 'stripe',
            'test_mode' => true,
            'webhook_secret' => '', //config('stripe_webhook_secret', ''),
        ],
        'sms' => [
            'enabled' => false,
            'provider' => 'twilio',
            'from_number' => '', //config('sms_from_number', ''),
        ],
    ],

    // Audit Settings
    'audit' => [
        'enabled' => true,
        'events' => [
            'invoice_created',
            'invoice_sent',
            'payment_received',
            'payment_failed',
            'subscription_changed',
            'service_suspended',
            'service_restored',
        ],
    ],
];
