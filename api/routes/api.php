<?php
/**
 * API routes for the Clairvoyance KPI API
 */

require_once __DIR__ . '/../controllers/KpiController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/WidgetController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/CategoryController.php';
require_once __DIR__ . '/../controllers/ImportController.php';
require_once __DIR__ . '/../controllers/ExportController.php';
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
$userController = new UserController();
$categoryController = new CategoryController();
$importController = new ImportController();
$exportController = new ExportController();

// Handle request based on the resource and method
switch ($resource) {
    case 'kpis':
        // KPI endpoints - all users can view, editors and admins can modify
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id && $subResource === 'measurements') { // This is for GET /kpis/{kpi_id}/measurements
                $kpiController->getMeasurements($id, $user); // $id is kpi_id
            } elseif ($id) { // This is for GET /kpis/{kpi_id}
                $kpiController->getOne($id);
            } else { // This is for GET /kpis
                $kpiController->getAll();
            }
        } elseif ($method === 'POST') {
            if ($subResource === 'measurements' && $id) { // $id is kpi_id
                $user = $roleMiddleware->requireEditor();
                if (!$user) break;
                
                $data = json_decode(file_get_contents('php://input'), true);
                $kpiController->addMeasurement($id, $data, $user); // $id is kpi_id
            } elseif (!$id && !$subResource) { // This is for creating a NEW KPI
                $user = $roleMiddleware->requireEditor();
                if (!$user) break;
                
                $data = json_decode(file_get_contents('php://input'), true);
                $kpiController->create($data, $user);
            } else {
                Response::error('Invalid POST request for KPIs. Use /kpis to create a KPI or /kpis/{id}/measurements to add a measurement.', null, 400);
            }
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireEditor();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $kpiController->update($id, $data);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $kpiController->delete($id);
        } else {
            Response::error('Method not allowed for KPIs', null, 405);
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
                if ($subResource === 'download') {
                    $reportController->downloadReport($id, $user);
                } else {
                    $reportController->getOne($id, $user);
                }
            } else {
                $reportController->getAll($user);
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id === 'generate') {
                $data = json_decode(file_get_contents('php://input'), true);
                $reportController->create($data, $user);
            } else {
                $data = json_decode(file_get_contents('php://input'), true);
                $reportController->create($data, $user);
            }
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $reportController->delete($id, $user);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    
    case 'users':
        // User management endpoints - admin only
        if ($method === 'GET') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            if ($id) {
                $userController->getOne($id);
            } else {
                $userController->getAll();
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $userController->create($data);
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $userController->update($id, $data);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $userController->delete($id);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
    
    case 'categories':
        // Category endpoints - all users can view, editors can create/update, only admins can delete
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id) {
                if ($subResource === 'kpis') {
                    $categoryController->getKpis($id);
                } else {
                    $categoryController->getOne($id);
                }
            } else {
                $categoryController->getAll();
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireEditor();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $categoryController->create($data, $user);
        } elseif ($method === 'PUT') {
            $user = $roleMiddleware->requireEditor();
            if (!$user) break;
            
            $data = json_decode(file_get_contents('php://input'), true);
            $categoryController->update($id, $data, $user);
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireAdmin();
            if (!$user) break;
            
            $categoryController->delete($id, $user);
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;

    case 'import':
        // Data import endpoints - editors and admins can import, all users can view templates
        if ($method === 'POST') {
            $user = $roleMiddleware->requireEditor();
            if (!$user) break;
            
            if ($subResource === 'validate') {
                $importController->validateImport($_POST);
            } else {
                $importController->importData($_POST, $user);
            }
        } elseif ($method === 'GET' && $id === 'templates') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $importController->getTemplates();
        } else {
            Response::error('Method not allowed', null, 405);
        }
        break;
        
    case 'export':
        // Data export endpoints
        if ($method === 'GET') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id === 'kpi' && isset($request[1]) && is_numeric($request[1])) {
                $kpiId = $request[1];
                $data = isset($_GET) ? $_GET : [];
                $exportController->exportKpi($kpiId, $data, $user);
            } elseif ($id === 'dashboard' && isset($request[1]) && is_numeric($request[1])) {
                $dashboardId = $request[1];
                $data = isset($_GET) ? $_GET : [];
                $exportController->exportDashboard($dashboardId, $data, $user);
            } else {
                Response::notFound('Export resource not found');
            }
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
                if ($subResource === 'download') {
                    $reportController->downloadReport($id, $user);
                } else {
                    $reportController->getOne($id, $user);
                }
            } else {
                $reportController->getAll($user);
            }
        } elseif ($method === 'POST') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            if ($id === 'generate') {
                $data = json_decode(file_get_contents('php://input'), true);
                $reportController->create($data, $user);
            } else {
                $data = json_decode(file_get_contents('php://input'), true);
                $reportController->create($data, $user);
            }
        } elseif ($method === 'DELETE') {
            $user = $roleMiddleware->requireViewer();
            if (!$user) break;
            
            $reportController->delete($id, $user);
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