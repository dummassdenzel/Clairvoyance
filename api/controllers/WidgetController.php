<?php

require_once __DIR__ . '/../models/Widget.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Kpi.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Controller for Widget operations
 */
class WidgetController
{
    private $widgetModel;
    private $dashboardModel;
    private $kpiModel;
    
    public function __construct()
    {
        $this->widgetModel = new Widget();
        $this->dashboardModel = new Dashboard();
        $this->kpiModel = new Kpi();
    }
    
    /**
     * Get all widgets for a user
     * 
     * @param object $user Current user
     */
    public function getAll($user)
    {
        $widgets = $this->widgetModel->getAllForUser($user->id);
        Response::success('Widgets retrieved successfully', $widgets);
    }
    
    /**
     * Get a specific widget by ID
     * 
     * @param int $id Widget ID
     * @param object $user Current user
     */
    public function getOne($id, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid widget ID');
            return;
        }
        
        $widget = $this->widgetModel->getById($id);
        if (!$widget) {
            Response::notFound('Widget not found');
            return;
        }
        
        // Check if the user has access to this widget
        if (!$this->widgetModel->userHasAccess($id, $user->id) && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to access this widget');
            return;
        }
        
        Response::success('Widget retrieved successfully', $widget);
    }
    
    /**
     * Create a new widget
     * 
     * @param array $data Widget data
     * @param object $user Current user
     */
    public function create($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['dashboard_id', 'kpi_id', 'title', 'widget_type']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Validate widget type
        if (!in_array($data['widget_type'], ['line', 'bar', 'pie', 'donut', 'card'])) {
            Response::error('Invalid widget type. Must be one of: line, bar, pie, donut, card');
            return;
        }
        
        // Check if dashboard exists and user has access
        $dashboard = $this->dashboardModel->getById($data['dashboard_id']);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to add widgets to this dashboard');
            return;
        }
        
        // Check if KPI exists
        $kpi = $this->kpiModel->getById($data['kpi_id']);
        if (!$kpi) {
            Response::notFound('KPI not found');
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Create widget
        $result = $this->widgetModel->create($data);
        if (!$result) {
            Response::error('Failed to create widget');
            return;
        }
        
        Response::success('Widget created successfully', $result, 201);
    }
    
    /**
     * Update an existing widget
     * 
     * @param int $id Widget ID
     * @param array $data Updated widget data
     * @param object $user Current user
     */
    public function update($id, $data, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid widget ID');
            return;
        }
        
        // Check if widget exists
        $widget = $this->widgetModel->getById($id);
        if (!$widget) {
            Response::notFound('Widget not found');
            return;
        }
        
        // Check if the user has access to this widget
        if (!$this->widgetModel->userHasAccess($id, $user->id) && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to update this widget');
            return;
        }
        
        // If dashboard_id is being updated, check if user has access to the new dashboard
        if (isset($data['dashboard_id']) && $data['dashboard_id'] != $widget['dashboard_id']) {
            $dashboard = $this->dashboardModel->getById($data['dashboard_id']);
            if (!$dashboard) {
                Response::notFound('Target dashboard not found');
                return;
            }
            
            if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
                Response::unauthorized('You do not have permission to move widgets to this dashboard');
                return;
            }
        }
        
        // If KPI is being updated, check if it exists
        if (isset($data['kpi_id']) && $data['kpi_id'] != $widget['kpi_id']) {
            $kpi = $this->kpiModel->getById($data['kpi_id']);
            if (!$kpi) {
                Response::notFound('KPI not found');
                return;
            }
        }
        
        // Validate widget type if provided
        if (isset($data['widget_type']) && !in_array($data['widget_type'], ['line', 'bar', 'pie', 'donut', 'card'])) {
            Response::error('Invalid widget type. Must be one of: line, bar, pie, donut, card');
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Update widget
        $result = $this->widgetModel->update($id, $data);
        if (!$result) {
            Response::error('Failed to update widget');
            return;
        }
        
        Response::success('Widget updated successfully');
    }
    
    /**
     * Delete a widget
     * 
     * @param int $id Widget ID
     * @param object $user Current user
     */
    public function delete($id, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid widget ID');
            return;
        }
        
        // Check if widget exists
        $widget = $this->widgetModel->getById($id);
        if (!$widget) {
            Response::notFound('Widget not found');
            return;
        }
        
        // Check if the user has access to this widget
        if (!$this->widgetModel->userHasAccess($id, $user->id) && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to delete this widget');
            return;
        }
        
        // Delete widget
        $result = $this->widgetModel->delete($id);
        if (!$result) {
            Response::error('Failed to delete widget');
            return;
        }
        
        Response::success('Widget deleted successfully');
    }
}