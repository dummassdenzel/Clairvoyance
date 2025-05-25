<?php

require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class CategoryController
{
    private $categoryModel;
    
    public function __construct()
    {
        $this->categoryModel = new Category();
    }
    
    /**
     * Get all categories
     */
    public function getAll()
    {
        $categories = $this->categoryModel->getAll();
        Response::success('Categories retrieved successfully', $categories);
    }
    
    /**
     * Get a specific category by ID
     * 
     * @param int $id Category ID
     */
    public function getOne($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid category ID');
            return;
        }
        
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            Response::notFound('Category not found');
            return;
        }
        
        Response::success('Category retrieved successfully', $category);
    }
    
    /**
     * Create a new category
     * 
     * @param array $data Category data
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
        
        // Add user_id to data (can be null for system categories)
        $data['user_id'] = isset($data['is_system']) && $data['is_system'] ? null : $user->id;
        
        // Create category
        $result = $this->categoryModel->create($data);
        if (!$result) {
            Response::error('Failed to create category. Name may already exist.');
            return;
        }
        
        Response::success('Category created successfully', $result, 201);
    }
    
    /**
     * Update an existing category
     * 
     * @param int $id Category ID
     * @param array $data Updated category data
     * @param object $user Current user
     */
    public function update($id, $data, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid category ID');
            return;
        }
        
        // Check if category exists
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            Response::notFound('Category not found');
            return;
        }
        
        // Check if user owns the category or if it's a system category
        if ($category['user_id'] !== null && $category['user_id'] != $user->id && $user->role !== 'admin') {
            Response::error('You do not have permission to update this category', null, 403);
            return;
        }
        
        // Sanitize data
        $data = Validator::sanitize($data);
        
        // Update category
        $result = $this->categoryModel->update($id, $data);
        if (!$result) {
            Response::error('Failed to update category');
            return;
        }
        
        Response::success('Category updated successfully');
    }
    
    /**
     * Delete a category
     * 
     * @param int $id Category ID
     * @param object $user Current user
     */
    public function delete($id, $user)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid category ID');
            return;
        }
        
        // Check if category exists
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            Response::notFound('Category not found');
            return;
        }
        
        // Check if user owns the category or if it's a system category
        if ($category['user_id'] !== null && $category['user_id'] != $user->id && $user->role !== 'admin') {
            Response::error('You do not have permission to delete this category', null, 403);
            return;
        }
        
        // Delete category
        $result = $this->categoryModel->delete($id);
        if (!$result) {
            Response::error('Failed to delete category. It may be in use by KPIs.');
            return;
        }
        
        Response::success('Category deleted successfully');
    }
    
    /**
     * Get KPIs for a specific category
     * 
     * @param int $id Category ID
     */
    public function getKpis($id)
    {
        if (!Validator::isNumeric($id)) {
            Response::error('Invalid category ID');
            return;
        }
        
        // Check if category exists
        $category = $this->categoryModel->getById($id);
        if (!$category) {
            Response::notFound('Category not found');
            return;
        }
        
        $kpis = $this->categoryModel->getKpis($id);
        Response::success('KPIs retrieved successfully', $kpis);
    }
}
