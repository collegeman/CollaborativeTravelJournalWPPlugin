import { apiGet, apiPost } from './api';

export interface StopData {
  tripId: number;
  name: string;
  formattedAddress: string;
  placeId: string;
  latitude: number;
  longitude: number;
  date: string;       // YYYY-MM-DD format
  time: string;       // HH:mm format (defaults to 08:00 if not specified)
  timezone: string;   // IANA timezone (e.g., America/New_York)
  specifyTime: boolean;
}

export interface Stop {
  id: number;
  title: {
    rendered: string;
  };
  meta: {
    trip_id: number;
    place_id: string;
    formatted_address: string;
    latitude: number;
    longitude: number;
    date: string;
    time: string;
    timezone: string;
    specify_time: boolean;
  };
}

export async function getStopsByTrip(tripId: number): Promise<Stop[]> {
  return apiGet<Stop[]>(`/stops?trip_id=${tripId}&per_page=100`);
}

export async function createStop(stopData: StopData): Promise<Stop> {
  const payload = {
    title: stopData.name,
    status: 'publish',
    meta: {
      trip_id: stopData.tripId,
      place_id: stopData.placeId,
      formatted_address: stopData.formattedAddress,
      latitude: stopData.latitude,
      longitude: stopData.longitude,
      date: stopData.date,
      time: stopData.time,
      timezone: stopData.timezone,
      specify_time: stopData.specifyTime,
    },
  };

  return apiPost<Stop>('/stops', payload);
}

export interface UpdateStopData {
  name?: string;
  date: string;
  time: string;
  timezone: string;
  specifyTime: boolean;
  placeId?: string;
  formattedAddress?: string;
  latitude?: number;
  longitude?: number;
}

export async function updateStop(stopId: number, data: UpdateStopData): Promise<Stop> {
  const apiUrl = (window as any).WP_API_URL || '/wp-json/';

  const meta: Record<string, any> = {
    date: data.date,
    time: data.time,
    timezone: data.timezone,
    specify_time: data.specifyTime,
  };

  // Include location fields if provided
  if (data.placeId !== undefined) {
    meta.place_id = data.placeId;
  }
  if (data.formattedAddress !== undefined) {
    meta.formatted_address = data.formattedAddress;
  }
  if (data.latitude !== undefined) {
    meta.latitude = data.latitude;
  }
  if (data.longitude !== undefined) {
    meta.longitude = data.longitude;
  }

  const response = await fetch(`${apiUrl}wp/v2/stops/${stopId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': (window as any).WP_NONCE || ''
    },
    body: JSON.stringify({
      title: data.name,
      meta,
    }),
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }

  return response.json();
}

export async function deleteStop(stopId: number): Promise<void> {
  const apiUrl = (window as any).WP_API_URL || '/wp-json/';
  const response = await fetch(`${apiUrl}wp/v2/stops/${stopId}`, {
    method: 'DELETE',
    headers: {
      'X-WP-Nonce': (window as any).WP_NONCE || ''
    },
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }
}
