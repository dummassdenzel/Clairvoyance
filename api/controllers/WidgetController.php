<?php

require_once __DIR__ . '/../utils/Response.php';

/**
 * Controller for Widget operations (stub for now)
 */
class WidgetController
{
    public function __construct()
    {
        // Stub constructor
    }
    
    /**
     * Get all widgets
     */
    public function getAll($user)
    {
        Response::success('Widget functionality not yet implemented', []);
    }
    
    /**
     * Get a widget by ID
     */
    public function getOne($id, $user)
    {
        Response::success('Widget functionality not yet implemented', []);
    }
    
    /**
     * Create a widget
     */
    public function create($data, $user)
    {
        Response::success('Widget functionality not yet implemented', []);
    }
    
    /**
     * Update a widget
     */
    public function update($id, $data, $user)
    {
        Response::success('Widget functionality not yet implemented', []);
    }
    
    /**
     * Delete a widget
     */
    public function delete($id, $user)
    {
        Response::success('Widget functionality not yet implemented', []);
    }
} 