/**
 * Category service for handling category-related operations
 */
import * as api from '../services/api';
import { writable, derived, get } from 'svelte/store';
import { token } from './auth';

// Types
export interface Category {
  id: number;
  name: string;
  user_id: number | null; 
  username?: string | null; // As per API GET /categories example
  is_system?: boolean; // Present in POST request, might be in responses
  created_at?: string; // Common field, add if present
  updated_at?: string; // Common field, add if present
}

// Category store
const categoryStore = writable<Category[]>([]);
export const categories = derived(categoryStore, ($categories) => $categories);

/**
 * Fetch all categories
 */
export async function fetchCategories() {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const fetchedCategories = await api.get('categories', currentToken) as Category[];
    categoryStore.set(fetchedCategories);
    return { success: true, data: fetchedCategories };
  } catch (error) {
    console.error('Error fetching categories:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch categories' 
    };
  }
}

/**
 * Fetch a single category by ID
 */
export async function fetchCategory(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const categoryData = await api.get(`categories/${id}`, currentToken) as Category;
    return { success: true, category: categoryData };
  } catch (error) {
    console.error(`Error fetching category ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to fetch category' 
    };
  }
}

/**
 * Create a new category
 */
export async function createCategory(data: { name: string; is_system?: boolean }) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    const newCategory = await api.post('categories', data, currentToken) as Category;
    categoryStore.update(cats => [...cats, newCategory]);
    return { success: true, category: newCategory };
  } catch (error) {
    console.error('Error creating category:', error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to create category' 
    };
  }
}

/**
 * Update an existing category
 */
export async function updateCategory(id: number, data: { name: string; is_system?: boolean }) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    // The API for PUT /categories/{id} returns the updated category object in the 'data' field.
    const updatedCategory = await api.put(`categories/${id}`, data, currentToken) as Category;
    categoryStore.update(cats => 
      cats.map(cat => (cat.id === id ? updatedCategory : cat))
    );
    return { success: true, category: updatedCategory };
  } catch (error) {
    console.error(`Error updating category ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to update category' 
    };
  }
}

/**
 * Delete a category
 */
export async function deleteCategory(id: number) {
  try {
    const currentToken = get(token);
    if (!currentToken) {
      return { success: false, message: 'Authentication required' };
    }
    await api.del(`categories/${id}`, currentToken);
    categoryStore.update(cats => cats.filter(cat => cat.id !== id));
    return { success: true };
  } catch (error) {
    console.error(`Error deleting category ${id}:`, error);
    return { 
      success: false, 
      message: error instanceof Error ? error.message : 'Failed to delete category' 
    };
  }
}
