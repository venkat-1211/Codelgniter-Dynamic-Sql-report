<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// Web Routes
$routes->group('reports', static function ($routes) {
    $routes->get('/', 'ReportController::index');
    $routes->get('create', 'ReportController::create');
    $routes->post('create', 'ReportController::create');
    $routes->get('edit/(:num)', 'ReportController::edit/$1');
    $routes->post('edit/(:num)', 'ReportController::edit/$1');
    $routes->get('view/(:num)', 'ReportController::view/$1');
    $routes->get('template/(:alphanum)', 'ReportController::template/$1');
    $routes->get('import', 'ReportController::import');
    $routes->post('import', 'ReportController::import');
    $routes->get('export/(:num)', 'ReportController::export/$1');
    $routes->post('clone/(:num)', 'ReportController::clone/$1');
    $routes->delete('delete/(:num)', 'ReportController::delete/$1');
    $routes->get('search', 'ReportController::search');
    $routes->get('get-table-columns', 'ReportController::getTableColumns');
});

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->group('reports', static function ($routes) {
        $routes->get('/', 'ReportApiController::index');
        $routes->post('/', 'ReportApiController::create');
        $routes->get('(:num)', 'ReportApiController::show/$1');
        $routes->put('(:num)', 'ReportApiController::update/$1');
        $routes->delete('(:num)', 'ReportApiController::delete/$1');
        $routes->get('(:num)/generate', 'ReportApiController::generate/$1');
        $routes->get('(:num)/export/csv', 'ReportApiController::exportCsv/$1');
        $routes->get('(:num)/export/excel', 'ReportApiController::exportExcel/$1');
        $routes->get('(:num)/parameters', 'ReportApiController::getParameters/$1');
        $routes->post('(:num)/templates', 'ReportApiController::createTemplate/$1');
        $routes->get('templates', 'ReportApiController::getTemplates');
        $routes->get('templates/(:alphanum)/generate', 'ReportApiController::generateFromTemplate/$1');
        $routes->post('preview', 'ReportApiController::testExpression');
        $routes->post('reports/preview', 'ReportApiController::preview');

    });
    
    $routes->group('database', static function ($routes) {
        $routes->get('tables', 'ReportApiController::getDatabaseTables');
        $routes->get('tables/(:alphanum)/columns', 'ReportApiController::getTableColumns/$1');
    });
});

// Authentication routes (example)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('logout', 'AuthController::logout');

// Home route
$routes->get('/', 'Home::index');

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}