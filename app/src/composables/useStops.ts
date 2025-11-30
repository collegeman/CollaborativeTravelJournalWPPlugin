import { ref } from 'vue';
import { getStopsByTrip, getStop, createStop as apiCreateStop, updateStop as apiUpdateStop, deleteStop as apiDeleteStop, type Stop, type StopData, type UpdateStopData } from '../services/stops';

const stopsCache = ref<Map<number, Stop[]>>(new Map());
const lastChange = ref<{ type: 'created' | 'updated' | 'deleted'; stop: Stop; tripId: number } | null>(null);

export function useStops() {
  async function loadStops(tripId: number): Promise<Stop[]> {
    const stops = await getStopsByTrip(tripId);
    stopsCache.value.set(tripId, stops);
    return stops;
  }

  function getStopsForTrip(tripId: number): Stop[] {
    return stopsCache.value.get(tripId) || [];
  }

  async function createStop(data: StopData): Promise<Stop> {
    const stop = await apiCreateStop(data);

    // Update cache immediately
    const tripStops = stopsCache.value.get(data.tripId) || [];
    stopsCache.value.set(data.tripId, [...tripStops, stop]);

    // Notify listeners
    lastChange.value = { type: 'created', stop, tripId: data.tripId };

    return stop;
  }

  async function updateStopById(stopId: number, tripId: number, data: UpdateStopData): Promise<Stop> {
    const stop = await apiUpdateStop(stopId, data);

    // Update cache immediately
    const tripStops = stopsCache.value.get(tripId) || [];
    const index = tripStops.findIndex(s => s.id === stopId);
    if (index !== -1) {
      tripStops[index] = stop;
      stopsCache.value.set(tripId, [...tripStops]);
    }

    // Notify listeners
    lastChange.value = { type: 'updated', stop, tripId };

    return stop;
  }

  async function deleteStopById(stopId: number, tripId: number): Promise<void> {
    const tripStops = stopsCache.value.get(tripId) || [];
    const stop = tripStops.find(s => s.id === stopId);

    await apiDeleteStop(stopId);

    // Update cache immediately
    stopsCache.value.set(tripId, tripStops.filter(s => s.id !== stopId));

    // Notify listeners
    if (stop) {
      lastChange.value = { type: 'deleted', stop, tripId };
    }
  }

  // For event-driven updates (from other users)
  async function handleStopEvent(eventType: string, stopId: number, tripId: number): Promise<void> {
    if (eventType === 'stop.created' || eventType === 'stop.updated') {
      try {
        const stop = await getStop(stopId);
        const tripStops = stopsCache.value.get(tripId) || [];
        const index = tripStops.findIndex(s => s.id === stopId);

        if (index !== -1) {
          tripStops[index] = stop;
        } else {
          tripStops.push(stop);
        }
        stopsCache.value.set(tripId, [...tripStops]);
      } catch {
        // Stop might not exist anymore
      }
    } else if (eventType === 'stop.deleted') {
      const tripStops = stopsCache.value.get(tripId) || [];
      stopsCache.value.set(tripId, tripStops.filter(s => s.id !== stopId));
    }
  }

  return {
    stopsCache,
    lastChange,
    loadStops,
    getStopsForTrip,
    createStop,
    updateStopById,
    deleteStopById,
    handleStopEvent,
  };
}
