import { ref } from 'vue';

export interface Trip {
  id: number;
  title: {
    rendered: string;
  };
  content?: {
    rendered: string;
  };
}

const currentTrip = ref<Trip | null>(null);
const trips = ref<Trip[]>([]);

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

  return {
    currentTrip,
    trips,
    setCurrentTrip,
    getCurrentTrip,
    setTrips,
    addTrip,
    getTrips
  };
}
