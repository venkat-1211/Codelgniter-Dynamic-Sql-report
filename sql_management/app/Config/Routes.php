<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Report Routes
$routes->group('reports', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'ReportController::index');
    $routes->get('create', 'ReportController::create');
    $routes->post('store', 'ReportController::store');
    $routes->get('edit/(:num)', 'ReportController::edit/$1');
    $routes->post('update/(:num)', 'ReportController::update/$1');
    $routes->get('preview/(:num)', 'ReportController::preview/$1');
    $routes->post('preview/(:num)', 'ReportController::preview/$1');
    $routes->get('export/(:num)/(:any)', 'ReportController::export/$1/$2');
    $routes->post('delete/(:num)', 'ReportController::delete/$1');
    $routes->post('validate-query', 'ReportController::validateQuery');
});

// Default route
$routes->get('/', 'ReportController::index');
