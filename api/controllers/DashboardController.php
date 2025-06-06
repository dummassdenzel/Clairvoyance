<?php

require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/Widget.php';

class DashboardController
{
    private $dashboardModel;
    private $widgetModel;
    
    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
        $this->widgetModel = new Widget();
    }
    
    /**
     * Get all dashboards for a user
     * 
     * @param object $user Current user
     */
    public function getAll($user)
    {
        $dashboards = $this->dashboardModel->getAllForUser($user->id);
        Response::success('Dashboards retrieved successfully', $dashboards);
    }
    
    /**
     * Get a specific dashboard by ID
     * 
     * @param int $id Dashboard ID
     * @param object $user Current user
     */
    public function getOne($id, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid dashboard ID');
            return;
        }
        
        $dashboard = $this->dashboardModel->getById($id);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        // Check if the user has access to this dashboard
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to access this dashboard');
            return;
        }
        
        Response::success('Dashboard retrieved successfully', $dashboard);
    }
    
    /**
     * Create a new dashboard
     * 
     * @param array $data Dashboard data
     * @param object $user Current user
     */
    public function create($data, $user)
    {
        // Validate required fields
        $validation = Validator::validateRequired($data, ['name']);
        if (!$validation['isValid']) {
            Response::error($validation['message']);
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Add user_id to data
        $data['user_id'] = $user->id;
        
        // Create dashboard
        $result = $this->dashboardModel->create($data);
        if (!$result) {
            Response::error('Failed to create dashboard');
            return;
        }
        
        Response::success('Dashboard created successfully', $result, 201);
    }
    
    /**
     * Update an existing dashboard
     * 
     * @param int $id Dashboard ID
     * @param array $data Updated dashboard data
     * @param object $user Current user
     */
    public function update($id, $data, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid dashboard ID');
            return;
        }
        
        // Check if dashboard exists
        $dashboard = $this->dashboardModel->getById($id);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        // Check if the user has access to this dashboard
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to update this dashboard');
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Update dashboard
        $result = $this->dashboardModel->update($id, $data);
        if (!$result) {
            Response::error('Failed to update dashboard');
            return;
        }
        
        Response::success('Dashboard updated successfully');
    }
    
    /**
     * Delete a dashboard
     * 
     * @param int $id Dashboard ID
     * @param object $user Current user
     */
    public function delete($id, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid dashboard ID');
            return;
        }
        
        // Check if dashboard exists
        $dashboard = $this->dashboardModel->getById($id);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }
        
        // Check if the user has access to this dashboard
        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to delete this dashboard');
            return;
        }
        
        // Delete dashboard
        $result = $this->dashboardModel->delete($id);
        if (!$result) {
            Response::error('Failed to delete dashboard');
            return;
        }
        
        Response::success('Dashboard deleted successfully');
    }

    /**
     * Get all widgets for a specific dashboard
     * 
     * @param int $dashboard_id Dashboard ID
     * @param object $user Current user
     */
    public function getWidgetsForDashboard($dashboard_id, $user)
    {
        if (!Validator::isNumeric($dashboard_id)) {
            Response::error('Invalid dashboard ID');
            return;
        }

        // First, check if dashboard exists and user has access to it
        $dashboard = $this->dashboardModel->getById($dashboard_id);
        if (!$dashboard) {
            Response::notFound('Dashboard not found');
            return;
        }

        if ($dashboard['user_id'] != $user->id && $user->role !== 'admin') {
            Response::unauthorized('You do not have permission to access this dashboard\'s widgets');
            return;
        }

        // Assuming WidgetModel will have a method like this
        $widgets = $this->widgetModel->getAllForDashboard($dashboard_id); 
        Response::success('Widgets for dashboard retrieved successfully', $widgets);
    }
}