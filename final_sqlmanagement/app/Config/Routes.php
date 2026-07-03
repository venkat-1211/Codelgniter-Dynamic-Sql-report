<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


// Report Routes
$routes->group('reports', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'ReportController::index');
    $routes->get('builder', 'ReportController::builder');
    $routes->get('builder/(:num)', 'ReportController::builder/$1');
    $routes->post('save', 'ReportController::save');
    $routes->get('execute/(:num)', 'ReportController::execute/$1');
    $routes->get('export-csv/(:num)', 'ReportController::exportCSV/$1');
    $routes->get('export-excel/(:num)', 'ReportController::exportExcel/$1');
    $routes->get('clone/(:num)', 'ReportController::clone/$1');
    $routes->post('delete', 'ReportController::delete');
    $routes->post('preview-sql', 'ReportController::previewSql');
    $routes->get('get-table-columns', 'ReportController::getTableColumns');

    // Enhanced features
    $routes->get('complex/(:num)', 'EnhancedReportController::executeComplex/$1');
    $routes->post('save-enhanced', 'EnhancedReportController::saveEnhanced');
    $routes->post('preview-complex-sql', 'EnhancedReportController::previewComplexSql');
    $routes->get('order-options', 'EnhancedReportController::getOrderByOptions');
    $routes->get('group-options', 'EnhancedReportController::getGroupByOptions');

});

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->group('reports', function($routes) {
        $routes->get('/', 'ReportApiController::index');
        $routes->get('(:num)', 'ReportApiController::show/$1');
        $routes->post('/', 'ReportApiController::create');
        $routes->put('(:num)', 'ReportApiController::update/$1');
        $routes->delete('(:num)', 'ReportApiController::delete/$1');
        $routes->get('(:num)/execute', 'ReportApiController::execute/$1');
        $routes->get('(:num)/export/csv', 'ReportApiController::exportCsv/$1');
        $routes->get('(:num)/export/excel', 'ReportApiController::exportExcel/$1');
        $routes->post('(:num)/validate', 'ReportApiController::validate/$1');
        $routes->get('tables', 'ReportApiController::tables');
        $routes->get('columns/(:any)', 'ReportApiController::columns/$1');

        $routes->get('complex/(:num)/execute', 'EnhancedReportApiController::executeComplex/$1');
        $routes->get('order-options', 'EnhancedReportApiController::getOrderOptions');
        $routes->get('group-options', 'EnhancedReportApiController::getGroupOptions');
        $routes->post('(:num)/window-function', 'EnhancedReportApiController::addWindowFunction/$1');
        
    });
});