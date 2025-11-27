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
