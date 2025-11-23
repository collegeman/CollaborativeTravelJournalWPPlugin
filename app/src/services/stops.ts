import { apiGet, apiPost } from './api';

export interface StopData {
  tripId: number;
  name: string;
  formattedAddress: string;
  placeId: string;
  latitude: number;
  longitude: number;
  date: string;
  time?: string;
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
    time?: string;
  };
}

export async function getStopsByTrip(tripId: number): Promise<Stop[]> {
  return apiGet<Stop[]>(`/stops?meta_key=trip_id&meta_value=${tripId}&per_page=100`);
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
      time: stopData.time || '',
      specify_time: stopData.specifyTime,
    },
  };

  return apiPost<Stop>('/stops', payload);
}
