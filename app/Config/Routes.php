<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Dashboard::index');

//custom routing for custom pages
//this route will move 'about/any-text' to 'domain.com/about/index/any-text'
$routes->add('about/(:any)', 'About::index/$1');

//add routing for controllers
$excluded_controllers = array("About", "App_Controller", "Security_Controller");
$controller_dropdown = array();
$dir = "./app/Controllers/";
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $controller_name = substr($file, 0, -4);
            if ($file && $file != "." && $file != ".." && $file != "index.html" && $file != ".gitkeep" && !in_array($controller_name, $excluded_controllers)) {
                $controller_dropdown[] = $controller_name;
            }
        }
        closedir($dh);
    }
}

foreach ($controller_dropdown as $controller) {
    $routes->get(strtolower($controller), "$controller::index");
    $routes->get(strtolower($controller) . '/(:any)', "$controller::$1");
    $routes->post(strtolower($controller) . '/(:any)', "$controller::$1");
}

//add uppercase links
$routes->get("Plugins", "Plugins::index");
$routes->get("Plugins/(:any)", "Plugins::$1");
$routes->post("Plugins/(:any)", "Plugins::$1");

$routes->get("Updates", "Updates::index");
$routes->get("Updates/(:any)", "Updates::$1");
$routes->post("Updates/(:any)", "Updates::$1");
$routes->post('events/bulk_delete_day', 'Events::bulkDeleteDay', ['filter' => 'csrf']);
$routes->get('events/import_csv_modal_form', 'Events::import_csv_modal_form');
$routes->get('events/timeline_events', 'Events::get_timeline_events');


$routes->group('ingestas', ['namespace' => 'App\Controllers'], static function($r) {
    $r->get('board', 'Ingests::board');         // Vista de tablero
$r->match(['get','post'], 'board_modal', 'Ingests::board_modal');
    $r->get('status', 'Ingests::status');       // JSON con ocupación
});

$routes->get('ird/status', 'Ird::status');




/*
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
