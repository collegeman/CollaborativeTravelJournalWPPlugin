import { ref, watch, onUnmounted } from 'vue';
import { EventStream, type CTJEvent } from '../services/eventStream';
import { useCurrentTrip } from './useCurrentTrip';

type EventCallback = (event: CTJEvent) => void;

const listeners: Map<string, Set<EventCallback>> = new Map();
let activeStream: EventStream | null = null;
let activeTripId: number | null = null;
const isConnected = ref(false);

function handleEvent(event: CTJEvent) {
  // Notify type-specific listeners
  const typeListeners = listeners.get(event.object_type);
  if (typeListeners) {
    typeListeners.forEach((callback) => callback(event));
  }

  // Notify wildcard listeners
  const allListeners = listeners.get('*');
  if (allListeners) {
    allListeners.forEach((callback) => callback(event));
  }
}

function ensureStream(tripId: number) {
  if (activeTripId === tripId && activeStream) {
    return;
  }

  if (activeStream) {
    activeStream.stop();
  }

  activeStream = new EventStream(tripId, {
    onEvent: handleEvent,
    onConnectionChange: (connected) => {
      isConnected.value = connected;
    },
  });
  activeTripId = tripId;
  activeStream.start();
}

function stopStream() {
  if (activeStream) {
    activeStream.stop();
    activeStream = null;
    activeTripId = null;
  }
}

export function useEventStream() {
  const { currentTrip } = useCurrentTrip();

  // Watch for trip changes and manage the stream
  watch(
    currentTrip,
    (trip) => {
      if (trip) {
        ensureStream(trip.id);
      } else {
        stopStream();
      }
    },
    { immediate: true }
  );

  function onEvent(objectType: string, callback: EventCallback): () => void {
    if (!listeners.has(objectType)) {
      listeners.set(objectType, new Set());
    }
    listeners.get(objectType)!.add(callback);

    onUnmounted(() => {
      listeners.get(objectType)?.delete(callback);
    });

    return () => {
      listeners.get(objectType)?.delete(callback);
    };
  }

  function onStopChange(callback: EventCallback) {
    return onEvent('stop', callback);
  }

  function onEntryChange(callback: EventCallback) {
    return onEvent('entry', callback);
  }

  function onExpenseChange(callback: EventCallback) {
    return onEvent('expense', callback);
  }

  function onSongChange(callback: EventCallback) {
    return onEvent('song', callback);
  }

  function onMediaChange(callback: EventCallback) {
    return onEvent('media', callback);
  }

  function onAnyChange(callback: EventCallback) {
    return onEvent('*', callback);
  }

  return {
    isConnected,
    onEvent,
    onStopChange,
    onEntryChange,
    onExpenseChange,
    onSongChange,
    onMediaChange,
    onAnyChange,
  };
}
