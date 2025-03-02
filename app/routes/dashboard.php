<?php
// Dashboard routes
$router->get('/dashboard/chart', [AdminDashboardController::class, 'getChartData']);
$router->get('/dashboard/activities', [AdminDashboardController::class, 'getActivities']);
