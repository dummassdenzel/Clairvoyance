/**
 * Base API service for handling API requests
 */

// API base URL
export const API_URL = 'http://localhost/clairvoyance/api';

// Default headers
const DEFAULT_HEADERS = {
  'Content-Type': 'application/json'
};

// Helper function to handle API responses
async function handleResponse(response: Response) {
  const responseData = await response.json();

  if (!response.ok || !responseData.success) {
    // If response.ok is false, or if response.ok is true but backend indicates failure (responseData.success === false)
    const errorMessage = responseData.error?.message || responseData.message || 'API request failed';
    throw new Error(errorMessage);
  }

  // For successful responses, the actual payload is in responseData.data
  // Some successful operations might not return data (e.g., DELETE), so data can be null/undefined.
  return responseData.data;
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