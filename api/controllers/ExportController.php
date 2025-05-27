<?php

require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class ExportController
{
    private $exportService;
    private $kpiModel;
    private $dashboardModel;
    
    public function __construct()
    {
        $this->exportService = new ExportService();
        $this->kpiModel = new Kpi();
        $this->dashboardModel = new Dashboard();
    }
    
    /**
     * Export KPI data
     * 
     * @param int $kpiId KPI ID
     * @param array $data Export configuration
     * @param object $user Current user
     */
    public function exportKpi($kpiId, $data, $user)
    {
        // Validate KPI ID
        if (!Validator::isNumeric($kpiId)) {
            Response::error('Invalid KPI ID');
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($kpiId);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Get export format
        $format = isset($data['format']) ? strtolower($data['format']) : 'csv';
        if (!in_array($format, ['csv', 'json'])) {
            Response::error('Unsupported export format. Please use CSV or JSON.');
            return;
        }
        
        // Get time range if provided
        $timeRange = null;
        if (isset($data['time_range']) && isset($data['time_range']['start']) && isset($data['time_range']['end'])) {
            $timeRange = [
                'start' => $data['time_range']['start'],
                'end' => $data['time_range']['end']
            ];
        }
        
        try {
            $exportFile = null;
            $contentType = null;
            $downloadFilename = null;
            
            // Export based on format
            if ($format === 'csv') {
                $exportFile = $this->exportService->exportKpiToCsv($kpiId, $timeRange);
                $contentType = 'text/csv';
                $downloadFilename = sanitizeFilename($kpi['name']) . '_export_' . date('Ymd') . '.csv';
            } else if ($format === 'xlsx') {
                $exportFile = $this->exportService->exportKpiToExcel($kpiId, $timeRange);
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $downloadFilename = sanitizeFilename($kpi['name']) . '_export_' . date('Ymd') . '.xlsx';
            } else if ($format === 'pdf') {
                $exportFile = $this->exportService->exportKpiToPdf($kpiId, $timeRange);
                $contentType = 'application/pdf';
                $downloadFilename = sanitizeFilename($kpi['name']) . '_export_' . date('Ymd') . '.pdf';
            } else if ($format === 'json') {
                $exportFile = $this->exportService->exportKpiToJson($kpiId, $timeRange);
                $contentType = 'application/json';
                $downloadFilename = sanitizeFilename($kpi['name']) . '_export_' . date('Ymd') . '.json';
            }
            
            // Check if export was successful
            if (!$exportFile || !file_exists($exportFile)) {
                Response::error('Failed to generate export file');
                return;
            }
            
            // Set headers for file download
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
            header('Content-Length: ' . filesize($exportFile));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output file contents
            readfile($exportFile);
            
            // Delete temporary file
            @unlink($exportFile);
            exit;
            
        } catch (Exception $e) {
            Response::error('Export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Export dashboard data
     * 
     * @param int $dashboardId Dashboard ID
     * @param array $data Export configuration
     * @param object $user Current user
     */
    public function exportDashboard($dashboardId, $data, $user)
    {
        // Validate dashboard ID
        if (!Validator::isNumeric($dashboardId)) {
            Response::error('Invalid dashboard ID');
            return;
        }
        
        // Check if dashboard exists and user has access
        $dashboard = $this->dashboardModel->getById($dashboardId);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        // Check if user has access to the dashboard
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::error('You do not have permission to export this dashboard', null, 403);
            return;
        }
        
        // Get export format
        $format = isset($data['format']) ? strtolower($data['format']) : 'csv';
        if (!in_array($format, ['csv', 'json'])) {
            Response::error('Unsupported export format. Please use CSV or JSON.');
            return;
        }
        
        // Get time range if provided
        $timeRange = null;
        if (isset($data['time_range']) && isset($data['time_range']['start']) && isset($data['time_range']['end'])) {
            $timeRange = [
                'start' => $data['time_range']['start'],
                'end' => $data['time_range']['end']
            ];
        }
        
        try {
            $exportFile = null;
            $contentType = null;
            $downloadFilename = null;
            
            // Export based on format
            if ($format === 'csv') {
                $exportFile = $this->exportService->exportDashboardToCsv($dashboardId, $timeRange);
                $contentType = 'text/csv';
                $downloadFilename = sanitizeFilename($dashboard['name']) . '_export_' . date('Ymd') . '.csv';
            } else if ($format === 'json') {
                $exportFile = $this->exportService->exportDashboardToJson($dashboardId, $timeRange);
                $contentType = 'application/json';
                $downloadFilename = sanitizeFilename($dashboard['name']) . '_export_' . date('Ymd') . '.json';
            }
            
            // Check if export was successful
            if (!$exportFile || !file_exists($exportFile)) {
                Response::error('Failed to generate export file');
                return;
            }
            
            // Set headers for file download
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
            header('Content-Length: ' . filesize($exportFile));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output file contents
            readfile($exportFile);
            
            // Delete temporary file
            @unlink($exportFile);
            exit;
            
        } catch (Exception $e) {
            Response::error('Export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a report
     * 
     * @param array $data Report configuration
     * @param object $user Current user
     */
    public function generateReport($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['dashboard_id', 'format']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Check if dashboard exists
        $dashboard = $this->dashboardModel->getById($data['dashboard_id']);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        // Check if user has access to the dashboard
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::error('You do not have permission to generate a report for this dashboard', null, 403);
            return;
        }
        
        // Get export format
        $format = strtolower($data['format']);
        if (!in_array($format, ['csv', 'xlsx', 'pdf', 'json'])) {
            Response::error('Unsupported report format. Please use CSV, Excel, PDF, or JSON.');
            return;
        }
        
        // Get time range if provided
        $timeRange = null;
        if (isset($data['time_range']) && isset($data['time_range']['start']) && isset($data['time_range']['end'])) {
            $timeRange = [
                'start' => $data['time_range']['start'],
                'end' => $data['time_range']['end']
            ];
        }
        
        try {
            $exportFile = null;
            
            // Generate report based on format
            if ($format === 'csv') {
                $exportFile = $this->exportService->exportDashboardToCsv($data['dashboard_id'], $timeRange);
            } else if ($format === 'json') {
                $exportFile = $this->exportService->exportDashboardToJson($data['dashboard_id'], $timeRange);
            }
            
            // Check if report generation was successful
            if (!$exportFile || !file_exists($exportFile)) {
                Response::error('Failed to generate report');
                return;
            }
            
            // Create report record in database
            $reportName = isset($data['name']) ? $data['name'] : $dashboard['name'] . ' Report - ' . date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO reports (user_id, dashboard_id, name, format, file_path, created_at)
                VALUES (:user_id, :dashboard_id, :name, :format, :file_path, NOW())
            ");
            
            $userId = $user->id;
            $dashboardId = $data['dashboard_id'];
            $formatParam = $format;
            $filePath = $exportFile;
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':dashboard_id', $dashboardId, PDO::PARAM_INT);
            $stmt->bindParam(':name', $reportName, PDO::PARAM_STR);
            $stmt->bindParam(':format', $formatParam, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                Response::error('Failed to save report record');
                return;
            }
            
            $reportId = $this->pdo->lastInsertId();
            
            // Return success response
            Response::success('Report generated successfully', [
                'id' => $reportId,
                'name' => $reportName,
                'format' => $format,
                'download_url' => '/api/reports/' . $reportId . '/download'
            ]);
            
        } catch (Exception $e) {
            Response::error('Report generation failed: ' . $e->getMessage());
        }
    }
}

/**
 * Helper function to sanitize filename
 * 
 * @param string $filename Filename to sanitize
 * @return string Sanitized filename
 */
function sanitizeFilename($filename)
{
    // Remove any character that is not alphanumeric, underscore, dash, or dot
    $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}
