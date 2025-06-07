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
  // Ensure response body is read only once.
  const responseData = await response.json().catch(() => {
    // Handle cases where response is not JSON or empty.
    if (!response.ok) throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
    // If response.ok but not JSON, it's an API contract issue.
    throw new Error('API response was not valid JSON or was empty.');
  });

  // Check if the response indicates success
  if (response.ok && responseData.status === 'success') {
    return responseData.data;
  }

  // If we get here, either the response wasn't OK or the status wasn't success
  const errorMessage = responseData.message || `API request failed with status ${response.status}`;
  throw new Error(errorMessage);
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