const API_BASE_URL = '/wp-json/ctj/v1';

export interface Collaborator {
  user_id: number;
  email: string;
  display_name: string;
  role: 'owner' | 'contributor' | 'viewer';
}

export interface InviteData {
  email: string;
  role?: 'contributor' | 'viewer';
}

async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;

  const defaultHeaders: HeadersInit = {
    'Content-Type': 'application/json',
  };

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
    credentials: 'same-origin',
  };

  const response = await fetch(url, config);

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || 'API request failed');
  }

  return await response.json();
}

export async function getCollaborators(tripId: number): Promise<Collaborator[]> {
  return apiRequest<Collaborator[]>(`/trips/${tripId}/collaborators`);
}

export async function inviteCollaborator(tripId: number, data: InviteData): Promise<Collaborator> {
  return apiRequest<Collaborator>(`/trips/${tripId}/collaborators`, {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function removeCollaborator(tripId: number, userId: number): Promise<void> {
  await apiRequest<{ success: boolean }>(`/trips/${tripId}/collaborators/${userId}`, {
    method: 'DELETE',
  });
}
