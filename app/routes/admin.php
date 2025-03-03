<?php
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\PlanController;

// Admin routes
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function($router) {
    // Dashboard
    $router->get('/dashboard', [DashboardController::class, 'index']);
    
    // Plans
    $router->get('/plans', [PlanController::class, 'index']);
    $router->get('/plans/create', [PlanController::class, 'create']);
    $router->post('/plans/store', [PlanController::class, 'store']);
    $router->get('/plans/edit/{id}', [PlanController::class, 'edit']);
    $router->post('/plans/update/{id}', [PlanController::class, 'update']);
    $router->delete('/plans/{id}', [PlanController::class, 'delete']);

    // Customers
    $router->get('/customers', [CustomerController::class, 'index']);
    $router->get('/customers/create', [CustomerController::class, 'create']);
    $router->post('/customers/store', [CustomerController::class, 'store']);
    $router->get('/customers/edit/{id}', [CustomerController::class, 'edit']);
    $router->post('/customers/update/{id}', [CustomerController::class, 'update']);
    $router->delete('/customers/{id}', [CustomerController::class, 'delete']);
    $router->post('/customers/{id}/suspend', [CustomerController::class, 'suspend']);
    $router->post('/customers/bulk-action', [CustomerController::class, 'bulkAction']);
    $router->get('/customers/export', [CustomerController::class, 'export']);
});
