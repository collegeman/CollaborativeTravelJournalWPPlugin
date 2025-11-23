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

export function useCurrentTrip() {
  function setCurrentTrip(trip: Trip | null) {
    currentTrip.value = trip;
  }

  function getCurrentTrip() {
    return currentTrip.value;
  }

  return {
    currentTrip,
    setCurrentTrip,
    getCurrentTrip
  };
}
