<?php
/**
 * API routes for the Clairvoyance KPI API
 */

require_once __DIR__ . '/../controllers/KpiController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/WidgetController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

// Parse the request
$roleMiddleware = new RoleMiddleware();
$method = $_SERVER['REQUEST_METHOD'];

// Get the controller based on the request
$resource = $request[0] ?? '';
array_shift($request);
$id = $request[0] ?? null;
$subResource = isset($request[1]) ? $request[1] : null;

// Initialize controllers
$kpiController = new KpiController();
$dashboardController = new DashboardController();
$widgetController = new WidgetController();
$reportController = new ReportController();

// Handle request based on the resource and method
switch ($resource) {
    case 'kpis':
        // KPI endpoints - all users can view, only admins can modify
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id) {
                $kpiController->getOne($id);
            } else {
                $kpiController->getAll();
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $kpiController->create($data, $user);
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $kpiController->update($id, $data);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $kpiController->delete($id);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    
    case 'dashboards':
        // Dashboard endpoints
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id) {
                $dashboardController->getOne($id, $user);
            } else {
                $dashboardController->getAll($user);
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $dashboardController->create($data, $user);
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $dashboardController->update($id, $data, $user);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $dashboardController->delete($id, $user);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    
    case 'widgets':
        // Widget endpoints
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id) {
                $widgetController->getOne($id, $user);
            } else {
                $widgetController->getAll($user);
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $widgetController->create($data, $user);
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $widgetController->update($id, $data, $user);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $widgetController->delete($id, $user);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    
    case 'reports':
        // Report endpoints
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id) {
                $reportController->generate($id, $user);
            } else {
                $reportController->listReports($user);
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $reportController->create($data, $user);
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