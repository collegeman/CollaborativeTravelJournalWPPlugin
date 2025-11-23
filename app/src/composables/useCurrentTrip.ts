import { ref, watch } from 'vue';

export interface Trip {
  id: number;
  title: {
    rendered: string;
  };
  content?: {
    rendered: string;
  };
}

const STORAGE_KEY = 'ctj_current_trip_id';

const currentTrip = ref<Trip | null>(null);
const trips = ref<Trip[]>([]);

watch(currentTrip, (trip) => {
  if (trip) {
    localStorage.setItem(STORAGE_KEY, trip.id.toString());
  } else {
    localStorage.removeItem(STORAGE_KEY);
  }
});

export function useCurrentTrip() {
  function setCurrentTrip(trip: Trip | null) {
    currentTrip.value = trip;
  }

  function getCurrentTrip() {
    return currentTrip.value;
  }

  function setTrips(newTrips: Trip[]) {
    trips.value = newTrips;
  }

  function addTrip(trip: Trip) {
    trips.value.push(trip);
  }

  function getTrips() {
    return trips.value;
  }

  function restoreSavedTrip() {
    const savedTripId = localStorage.getItem(STORAGE_KEY);
    if (!savedTripId) {
      return;
    }

    const trip = trips.value.find(t => t.id === parseInt(savedTripId));
    if (trip) {
      currentTrip.value = trip;
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  }

  return {
    currentTrip,
    trips,
    setCurrentTrip,
    getCurrentTrip,
    setTrips,
    addTrip,
    getTrips,
    restoreSavedTrip
  };
}
