<?php
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Controllers\Admin\PlanController;
use App\Controllers\Admin\BillingController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\SubscriptionController;
use App\Controllers\Admin\EmployeeController;
use App\Controllers\Admin\AttendanceController;
use App\Controllers\Admin\LeaveController;
use App\Controllers\Admin\PayrollController;
use App\Controllers\Admin\DeductionController;
use App\Controllers\Admin\NetworkController;
use App\Controllers\Admin\AssetController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\Staff\StaffController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\StaffMiddleware;
use App\Core\Application;

// Get router instance from application
$router = Application::getInstance()->getRouter();

// Guest routes
$router->group(['middleware' => 'GuestMiddleware'], function($router) {
    // Root route
    $router->get('/', [AuthController::class, 'showLogin']);
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// Auth required routes
$router->group(['middleware' => 'AuthMiddleware'], function($router) {
    $router->get('/logout', [AuthController::class, 'logout']);
    
    // Admin routes
    $router->group(['prefix' => '/admin', 'middleware' => 'RoleMiddleware:admin'], function($router) {
        // Dashboard
        $router->get('/dashboard', [AdminDashboardController::class, 'index']);
        $router->get('/dashboard/chart', [AdminDashboardController::class, 'getChartData']);
        $router->get('/dashboard/activities', [AdminDashboardController::class, 'getActivities']);
        
        // Customers
        $router->get('/customers', [AdminCustomerController::class, 'index']);
        $router->get('/customers/create', [AdminCustomerController::class, 'create']);
        $router->post('/customers', [AdminCustomerController::class, 'store']);
        $router->get('/customers/:id', [AdminCustomerController::class, 'show']);
        $router->get('/customers/:id/edit', [AdminCustomerController::class, 'edit']);
        $router->put('/customers/:id', [AdminCustomerController::class, 'update']);
        $router->delete('/customers/:id', [AdminCustomerController::class, 'delete']);
        
        // Plans
        $router->get('/plans', [PlanController::class, 'index']);
        $router->get('/plans/create', [PlanController::class, 'create']);
        $router->post('/plans', [PlanController::class, 'store']);
        $router->get('/plans/:id', [PlanController::class, 'show']);
        $router->get('/plans/:id/edit', [PlanController::class, 'edit']);
        $router->put('/plans/:id', [PlanController::class, 'update']);
        $router->delete('/plans/:id', [PlanController::class, 'delete']);
        
        // Billing
        $router->get('/billing', [BillingController::class, 'index']);
        $router->get('/billing/create', [BillingController::class, 'create']);
        $router->post('/billing', [BillingController::class, 'store']);
        $router->get('/billing/:id', [BillingController::class, 'show']);
        $router->get('/billing/:id/edit', [BillingController::class, 'edit']);
        $router->put('/billing/:id', [BillingController::class, 'update']);
        
        // Payments
        $router->get('/payments', [PaymentController::class, 'index']);
        $router->get('/payments/create', [PaymentController::class, 'create']);
        $router->post('/payments', [PaymentController::class, 'store']);
        $router->get('/payments/:id', [PaymentController::class, 'show']);
        
        // Subscriptions
        $router->get('/subscriptions', [SubscriptionController::class, 'index']);
        $router->get('/subscriptions/create', [SubscriptionController::class, 'create']);
        $router->post('/subscriptions', [SubscriptionController::class, 'store']);
        $router->get('/subscriptions/:id', [SubscriptionController::class, 'show']);
        $router->get('/subscriptions/:id/edit', [SubscriptionController::class, 'edit']);
        $router->put('/subscriptions/:id', [SubscriptionController::class, 'update']);
        
        // Network Management
        $router->get('/network/dashboard', [NetworkController::class, 'dashboard']);
        $router->get('/network/map', [NetworkController::class, 'map']);
        $router->get('/network/health', [NetworkController::class, 'health']);
        
        // Asset Management
        $router->get('/assets', [AssetController::class, 'index']);
        $router->get('/assets/create', [AssetController::class, 'create']);
        $router->post('/assets', [AssetController::class, 'store']);
        $router->get('/assets/:id', [AssetController::class, 'show']);
        $router->get('/assets/:id/edit', [AssetController::class, 'edit']);
        $router->put('/assets/:id', [AssetController::class, 'update']);
        
        // Settings
        $router->get('/settings/general', [SettingsController::class, 'general']);
        $router->post('/settings/update', [SettingsController::class, 'update']);
        $router->get('/settings/roles', [SettingsController::class, 'roles']);
        
        // Backup
        $router->get('/backup', [BackupController::class, 'index']);
        $router->post('/backup/create', [BackupController::class, 'create']);
        $router->get('/backup/:id/download', [BackupController::class, 'download']);
        $router->post('/backup/:id/restore', [BackupController::class, 'restore']);
        $router->delete('/backup/:id', [BackupController::class, 'delete']);
        
        // Audit Logs
        $router->get('/audit', [AuditLogController::class, 'index']);
        $router->get('/audit/:id', [AuditLogController::class, 'show']);
        $router->get('/audit/export', [AuditLogController::class, 'export']);
        $router->post('/audit/clean', [AuditLogController::class, 'clean']);
    });
    
    // Staff routes
    $router->group(['prefix' => '/staff', 'middleware' => 'StaffMiddleware'], function($router) {
        $router->get('/dashboard', [StaffController::class, 'dashboard']);
        $router->get('/profile', [StaffController::class, 'profile']);
        $router->get('/attendance', [StaffController::class, 'attendance']);
        $router->get('/payments', [StaffController::class, 'payments']);
    });
    
    // Customer routes
    $router->group(['prefix' => '/customer', 'middleware' => 'RoleMiddleware:customer'], function($router) {
        $router->get('/dashboard', [CustomerController::class, 'dashboard']);
        $router->get('/profile', [CustomerController::class, 'profile']);
        $router->get('/billing', [CustomerController::class, 'billing']);
        $router->get('/subscription', [CustomerController::class, 'subscription']);
        $router->get('/payments', [CustomerController::class, 'payments']);
    });
});

// Return router for chaining
return $router;
