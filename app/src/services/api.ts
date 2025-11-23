const API_BASE_URL = '/wp-json/wp/v2';

export interface ApiError {
  message: string;
  code?: string;
  status?: number;
}

export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;

  const defaultHeaders: HeadersInit = {
    'Content-Type': 'application/json',
  };

  // Add WordPress REST API nonce for authentication
  const wpNonce = (window as any).WP_NONCE;
  if (wpNonce) {
    defaultHeaders['X-WP-Nonce'] = wpNonce;
  }

  const config: RequestInit = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
    credentials: 'same-origin', // Include cookies for WordPress auth
  };

  try {
    const response = await fetch(url, config);

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw {
        message: errorData.message || 'API request failed',
        code: errorData.code,
        status: response.status,
      } as ApiError;
    }

    return await response.json();
  } catch (error) {
    if ((error as ApiError).status) {
      throw error;
    }
    throw {
      message: 'Network error occurred',
      status: 0,
    } as ApiError;
  }
}

export function apiGet<T>(endpoint: string): Promise<T> {
  return apiRequest<T>(endpoint, { method: 'GET' });
}

export function apiPost<T>(endpoint: string, data: unknown): Promise<T> {
  return apiRequest<T>(endpoint, {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export function apiPut<T>(endpoint: string, data: unknown): Promise<T> {
  return apiRequest<T>(endpoint, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export function apiDelete<T>(endpoint: string): Promise<T> {
  return apiRequest<T>(endpoint, { method: 'DELETE' });
}
