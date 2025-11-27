import { apiGet, apiPost, apiDelete } from './api';

export interface Trip {
  id: number;
  title: {
    rendered: string;
  };
  status: string;
  meta: {
    start_date?: string;
    end_date?: string;
  };
}

export interface CreateTripData {
  title: string;
}

export async function getTrips(): Promise<Trip[]> {
  return apiGet<Trip[]>('/ctj_trip');
}

export async function getTrip(tripId: number): Promise<Trip> {
  return apiGet<Trip>(`/ctj_trip/${tripId}`);
}

export async function createTrip(data: CreateTripData): Promise<Trip> {
  return apiPost<Trip>('/ctj_trip', {
    title: data.title,
    status: 'publish',
  });
}

export async function deleteTrip(tripId: number): Promise<void> {
  await apiDelete(`/ctj_trip/${tripId}`);
}
