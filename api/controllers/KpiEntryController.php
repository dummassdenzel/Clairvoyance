<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/KpiEntry.php';

class KpiEntryController {
    public function create() {
        // Middleware handles auth, role checks, and session start.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['kpi_id'], $data['date'], $data['value'])) {
            Response::error('Missing required fields: kpi_id, date, value.', null, 400);
            return;
        }

        $entry = new KpiEntry();
        $result = $entry->create($data['kpi_id'], $data['date'], $data['value']);

        if ($result['success']) {
            Response::success('KPI Entry created successfully.', ['id' => $result['id']], 201);
        } else {
            Response::error($result['error'], null, 400);
        }
    }

    public function uploadCsv() {
        // Middleware handles auth and role checks.
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('CSV file is required or upload failed.', null, 400);
            return;
        }

        $kpiEntry = new KpiEntry();
        $result = $kpiEntry->bulkInsertFromCsv($_FILES['file']['tmp_name']);

        // The model returns a report: ['inserted' => int, 'failed' => int, 'errors' => array]
        // Check if there were any errors during processing.
        if (!empty($result['errors'])) {
            // If there are errors, it's a failure or partial failure.
            // Return a 400 Bad Request with the details.
            Response::error('CSV processing finished with errors.', [
                'inserted' => $result['inserted'],
                'failed' => $result['failed'],
                'errors' => $result['errors']
            ], 400);
        } else {
            // Success case: no errors.
            Response::success('CSV processed successfully.', [
                'inserted' => $result['inserted'],
                'failed' => $result['failed']
            ]);
        }
    }

}