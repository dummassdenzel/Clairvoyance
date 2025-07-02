<?php
require_once __DIR__ . '/../controllers/KpiEntryController.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

$roleMiddleware = new RoleMiddleware();
$controller = new KpiEntryController();

// The global $request variable is parsed in index.php
// Example: /api/kpi_entries/123 -> $request = ['kpi_entries', '123']
$part1 = $request[1] ?? null;
$id = is_numeric($part1) ? $part1 : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $roleMiddleware->requireEditor();
        // Differentiate between a JSON body and a file upload
        if (isset($_FILES['file'])) {
            $controller->uploadCsv();
        } else {
            $controller->create();
        }
        break;

    case 'PUT':
        $roleMiddleware->requireEditor();
        if (!$id) {
            Response::error('A numeric KPI Entry ID is required for updating.', null, 400);
        }
        // The update method is not yet implemented in the controller
        Response::error('Updating a KPI entry is not yet supported.', null, 501);
        // $controller->update($id);
        break;

    case 'DELETE':
        $roleMiddleware->requireEditor();
        if (!$id) {
            Response::error('A numeric KPI Entry ID is required for deletion.', null, 400);
        }
        // The delete method is not yet implemented in the controller
        Response::error('Deleting a KPI entry is not yet supported.', null, 501);
        // $controller->delete($id);
        break;

    default:
        Response::error('Method not allowed.', null, 405);
        break;
}