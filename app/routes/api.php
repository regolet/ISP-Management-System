<?php
use App\Controllers\Api\PlanController;
use App\Controllers\Api\DeductionController;
use App\Middleware\ApiMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Core\Application;

// Get router instance from application
$router = Application::getInstance()->getRouter();

// API Routes
$router->group(['prefix' => '/api', 'middleware' => 'ApiMiddleware'], function($router) {
    
    // Plans API
    $router->get('/plans', [PlanController::class, 'index']);
    $router->get('/plans/:id', [PlanController::class, 'show']);
    $router->post('/plans', [PlanController::class, 'store']);
    $router->put('/plans/:id', [PlanController::class, 'update']);
    $router->delete('/plans/:id', [PlanController::class, 'delete']);

    // Deductions API
    $router->get('/deductions', [DeductionController::class, 'index']);
    $router->get('/deductions/:id', [DeductionController::class, 'show']);
    $router->post('/deductions', [DeductionController::class, 'store']);
    $router->put('/deductions/:id', [DeductionController::class, 'update']);
    $router->delete('/deductions/:id', [DeductionController::class, 'delete']);
    $router->get('/deductions/history', [DeductionController::class, 'history']);
    $router->get('/deductions/report', [DeductionController::class, 'report']);

    // API Documentation
    $router->get('/docs', function() use ($router) {
        $routes = $router->getRoutes();
        $docs = [];

        foreach ($routes as $route) {
            if (strpos($route['path'], '/api/') === 0) {
                $docs[] = [
                    'method' => $route['method'],
                    'path' => $route['path'],
                    'handler' => is_array($route['callback']) 
                        ? get_class($route['callback'][0]) . '@' . $route['callback'][1]
                        : 'Closure',
                    'middleware' => $route['middleware']
                ];
            }
        }

        return [
            'title' => 'API Documentation',
            'version' => '1.0.0',
            'base_url' => '/api',
            'endpoints' => $docs,
            'auth' => [
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer <token>'
            ],
            'rate_limit' => [
                'max_requests' => Application::getInstance()->getConfig('api.throttle.max_attempts', 60),
                'window' => Application::getInstance()->getConfig('api.throttle.decay_minutes', 1) . ' minutes'
            ]
        ];
    });
});

// Return router for chaining
return $router;
