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

    /**
     * Get all measurements for a specific KPI
     * 
     * @param int $kpi_id KPI ID
     * @param object $user Current user (not used in this method but good for consistency)
     */
    public function getMeasurements($kpi_id, $user)
    {
        if (!Validator::isNumeric($kpi_id)) {
            Response::error('Invalid KPI ID');
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($kpi_id);
        if (!$kpi) {
            Response::notFound('KPI not found, cannot retrieve measurements.');
            return;
        }
        
        $measurements = $this->kpiModel->getMeasurementsByKpiId($kpi_id);
        
        // The model method returns [] on error or no results, which is fine.
        Response::success('Measurements retrieved successfully', $measurements);
    }

    /**
     * Add a measurement to a KPI
     * 
     * @param int $kpi_id KPI ID
     * @param array $data Measurement data (value, date, notes)
     * @param object $user Current user (for logging or ownership if needed, though measurements are usually tied to KPI)
     */
    public function addMeasurement($kpi_id, $data, $user)
    {
        if (!Validator::isNumeric($kpi_id)) {
            Response::error('Invalid KPI ID');
            return;
        }

        // Check if KPI exists
        $kpi = $this->kpiModel->getById($kpi_id);
        if (!$kpi) {
            Response::notFound('KPI not found, cannot add measurement.');
            return;
        }

        // Validate required fields for measurement
        $validation = Validator::validateRequired($data, ['value', 'date']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }

        // Validate data types
        if (!Validator::isNumeric($data['value'])) {
            Response::error('Measurement value must be numeric.');
            return;
        }
        if (!Validator::isValidDate($data['date'])) {
            Response::error('Invalid date format for measurement. Use YYYY-MM-DD.');
            return;
        }

        // Sanitize data
        $data = Validator::sanitize($data);

        // Prepare data for the model, aligning with the 'measurements' table schema
        $measurementData = [
            'kpi_id' => (int)$kpi_id,
            'value' => (float)$data['value'], // Assuming 'value' is sanitized by parseFloat or is numeric
            'timestamp' => $data['date'] // Use the input 'date' for the 'timestamp' column
            // 'notes' field is omitted as the table does not have it
        ];

        // Temporary logging before calling the model
        error_log('[KpiController] Attempting to add measurement. KPI ID: ' . $kpi_id . '. Data: ' . json_encode($measurementData));

        $result = $this->kpiModel->addMeasurement($measurementData);

        // Temporary logging after calling the model
        error_log('[KpiController] Result from kpiModel->addMeasurement: ' . json_encode($result));

        if (!$result) {
            Response::error('Failed to add measurement');
            return;
        }
        
        // Optionally, update the KPI's current_value if applicable
        // This might involve a separate model call or a trigger in the DB
        // For now, just return the created measurement
        
        Response::success('Measurement added successfully', $result, 201);
    }
}