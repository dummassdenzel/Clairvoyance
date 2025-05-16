<?php
/**
 * Authentication routes for the Clairvoyance KPI API
 */

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/Response.php';

// Parse the request
$authController = new AuthController();
$method = $_SERVER['REQUEST_METHOD'];

// Remove 'auth' from the beginning of the request
array_shift($request);
$action = $request[0] ?? '';

switch ($method) {
    case 'POST':
        // Get the request data
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            Response::error('Invalid request data');
            break;
        }
        
        switch ($action) {
            case 'login':
                $authController->login($data);
                break;
            
            case 'register':
                $authController->register($data);
                break;
            
            default:
                Response::notFound('Authentication endpoint not found');
                break;
        }
        break;
    
    case 'GET':
        if ($action === 'verify') {
            $authController->verifyToken();
        } else {
            Response::notFound('Authentication endpoint not found');
        }
        break;
    
    default:
        Response::error('Method not allowed', null, 405);
        break;
} 