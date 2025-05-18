/**
 * Base API service for handling API requests
 */

// API base URL
const API_URL = 'http://localhost/clairvoyance/api';

// Default headers
const DEFAULT_HEADERS = {
  'Content-Type': 'application/json'
};

// Helper function to handle API responses
async function handleResponse(response: Response) {
  const data = await response.json();
  
  if (!response.ok) {
    throw new Error(data.message || 'API request failed');
  }
  
  return data;
}

/**
 * Make a GET request to the API
 */
export async function get(endpoint: string, token?: string) {
  const headers: HeadersInit = { ...DEFAULT_HEADERS };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(`${API_URL}/${endpoint}`, {
    method: 'GET',
    headers
  });
  
  return handleResponse(response);
}

/**
 * Make a POST request to the API
 */
export async function post(endpoint: string, data: any, token?: string) {
  const headers: HeadersInit = { ...DEFAULT_HEADERS };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(`${API_URL}/${endpoint}`, {
    method: 'POST',
    headers,
    body: JSON.stringify(data)
  });
  
  return handleResponse(response);
}

/**
 * Make a PUT request to the API
 */
export async function put(endpoint: string, data: any, token?: string) {
  const headers: HeadersInit = { ...DEFAULT_HEADERS };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(`${API_URL}/${endpoint}`, {
    method: 'PUT',
    headers,
    body: JSON.stringify(data)
  });
  
  return handleResponse(response);
}

/**
 * Make a DELETE request to the API
 */
export async function del(endpoint: string, token?: string) {
  const headers: HeadersInit = { ...DEFAULT_HEADERS };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(`${API_URL}/${endpoint}`, {
    method: 'DELETE',
    headers
  });
  
  return handleResponse(response);
} 