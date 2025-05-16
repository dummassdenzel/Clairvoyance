<?php

require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class KpiController
{
    private $kpiModel;
    
    public function __construct()
    {
        $this->kpiModel = new Kpi();
    }
    
    /**
     * Get all KPIs
     */
    public function getAll()
    {
        $kpis = $this->kpiModel->getAll();
        Response::success('KPIs retrieved successfully', $kpis);
    }
    
    /**
     * Get a specific KPI by ID
     * 
     * @param int $id KPI ID
     */
    public function getOne($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid KPI ID');
            return;
        }
        
        $kpi = $this->kpiModel->getById($id);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        Response::success('KPI retrieved successfully', $kpi);
    }
    
    /**
     * Create a new KPI
     * 
     * @param array $data KPI data
     * @param object $user Current user
     */
    public function create($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['name', 'category_id']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Add user_id to data
        $data['user_id'] = $user->id;
        
        // Create KPI
        $result = $this->kpiModel->create($data);
        if (!$result) {
            Response::error('Failed to create KPI');
            return;
        }
        
        Response::success('KPI created successfully', $result, 201);
    }
    
    /**
     * Update an existing KPI
     * 
     * @param int $id KPI ID
     * @param array $data Updated KPI data
     */
    public function update($id, $data)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid KPI ID');
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($id);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Update KPI
        $result = $this->kpiModel->update($id, $data);
        if (!$result) {
            Response::error('Failed to update KPI');
            return;
        }
        
        Response::success('KPI updated successfully');
    }
    
    /**
     * Delete a KPI
     * 
     * @param int $id KPI ID
     */
    public function delete($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid KPI ID');
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($id);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Delete KPI
        $result = $this->kpiModel->delete($id);
        if (!$result) {
            Response::error('Failed to delete KPI');
            return;
        }
        
        Response::success('KPI deleted successfully');
    }
} 