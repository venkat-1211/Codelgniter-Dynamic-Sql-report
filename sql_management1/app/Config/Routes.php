<?php
// app/Config/Routes.php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

// Report Builder Routes
$routes->group('reports', ['namespace' => 'App\Controllers'], function($routes) {
    // Dashboard
    $routes->get('/', 'ReportBuilder::index');
    
    // Report CRUD Operations
    $routes->get('create', 'ReportBuilder::create');
    $routes->post('create', 'ReportBuilder::create');
    $routes->get('edit/(:num)', 'ReportBuilder::edit/$1');
    $routes->post('edit/(:num)', 'ReportBuilder::edit/$1');
    $routes->get('delete/(:num)', 'ReportBuilder::delete/$1');
    
    // Preview & Execution
    $routes->get('preview/(:num)', 'ReportBuilder::preview/$1');
    $routes->get('export/(:num)', 'ReportBuilder::export/$1');
    
    // Templates & SQL Testing
    $routes->get('load-template/(:any)', 'ReportBuilder::loadTemplate/$1');
    $routes->post('test-sql', 'ReportBuilder::testSql');
    
    // Metadata & Data
    $routes->get('metadata', 'ReportBuilder::metadata');
    $routes->get('history/(:num)', 'ReportBuilder::history/$1');
    
    // Quick Access Routes
    $routes->get('dashboard', 'ReportBuilder::index');
    $routes->get('builder', 'ReportBuilder::index');
});

// API Routes for AJAX calls (optional, for cleaner separation)
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    // Report API
    $routes->group('reports', function($routes) {
        $routes->get('list', 'ReportBuilder::index');
        $routes->get('preview/(:num)', 'ReportBuilder::preview/$1');
        $routes->get('metadata', 'ReportBuilder::metadata');
        $routes->get('templates', 'ReportBuilder::getTemplates');
        $routes->get('history/(:num)', 'ReportBuilder::history/$1');
        $routes->post('test-query', 'ReportBuilder::testQuery');
    });
});

// Alternative route grouping (choose one approach)
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function($routes) {
    // Admin-specific report routes (if needed)
    $routes->get('reports', 'ReportBuilder::index', ['as' => 'admin.reports']);
    $routes->get('reports/create', 'ReportBuilder::create', ['as' => 'admin.reports.create']);
    $routes->post('reports/create', 'ReportBuilder::create');
});

// Fallback routes for better UX
$routes->get('report-builder', 'ReportBuilder::index');
$routes->get('dynamic-reports', 'ReportBuilder::index');
$routes->get('sql-reports', 'ReportBuilder::index');

// RESTful API Routes (optional, for external access)
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], function($routes) {
    $routes->resource('reports', ['controller' => 'ReportApi']);
    $routes->post('reports/(:num)/execute', 'ReportApi::execute/$1');
    $routes->get('reports/(:num)/data', 'ReportApi::getData/$1');
});

// Debug/Development Routes (remove in production)
if (ENVIRONMENT !== 'production') {
    $routes->get('debug/reports', 'ReportBuilder::debug');
    $routes->get('test/reports/(:num)', 'ReportBuilder::testReport/$1');
}

// Catch-all route (404 handler)
$routes->set404Override(function() {
    return view('errors/html/error_404');
});

// Maintenance mode route (optional)
$routes->get('maintenance', function() {
    return view('errors/html/maintenance');
});

// CLI Routes (for scheduled reports)
$routes->cli('reports/run-scheduled', 'ReportBuilder::runScheduled');
$routes->cli('reports/cleanup', 'ReportBuilder::cleanupOldFiles');