<?php
/**
 * API routes for the Clairvoyance KPI API
 */

require_once __DIR__ . '/../controllers/KpiController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/UserController.php';
// require_once __DIR__ . '/../controllers/WidgetController.php';
// require_once __DIR__ . '/../controllers/ReportController.php';
// require_once __DIR__ . '/../controllers/CategoryController.php';
// require_once __DIR__ . '/../controllers/ImportController.php';
// require_once __DIR__ . '/../controllers/ExportController.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

// Parse the request
$roleMiddleware = new RoleMiddleware();
$method = $_SERVER['REQUEST_METHOD'];

// Get the controller based on the request
$resource = $request[0] ?? '';
$resource = preg_replace('/\.php$/', '', $resource); // Normalize resource name
array_shift($request);
$id = $request[0] ?? null;
$subResource = isset($request[1]) ? $request[1] : null;

// Initialize controllers
$kpiController = new KpiController();
$dashboardController = new DashboardController();
$userController = new UserController();

// Handle request based on the resource and method
switch ($resource) {
    case 'kpis':
        // KPI endpoints - all users can view, editors can modify
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $kpiController->create($data);
        } else {
            Response::error('Method not allowed for KPIs', null, 405);
        }
        break;
    case 'dashboards':
        // Dashboard endpoints
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $dashboardController->create($data);
        } elseif ($method === 'GET') {
            $dashboardController->get();
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    case 'users':
        // User registration endpoint
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $userController->register($data);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    case 'auth':
        // If 'auth' route somehow gets here, redirect to auth.php
        require_once __DIR__ . '/auth.php';
        break;
    default:
        Response::notFound('Resource not found');
        break;
} 