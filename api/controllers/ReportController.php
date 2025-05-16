<?php

require_once __DIR__ . '/../utils/Response.php';

/**
 * Controller for Report operations (stub for now)
 */
class ReportController
{
    public function __construct()
    {
        // Stub constructor
    }
    
    /**
     * List available reports
     */
    public function listReports($user)
    {
        Response::success('Report functionality not yet implemented', []);
    }
    
    /**
     * Generate a report
     */
    public function generate($id, $user)
    {
        Response::success('Report functionality not yet implemented', []);
    }
    
    /**
     * Create a report
     */
    public function create($data, $user)
    {
        Response::success('Report functionality not yet implemented', []);
    }
} 