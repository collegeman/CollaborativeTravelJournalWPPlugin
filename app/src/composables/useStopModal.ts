import { ref, onUnmounted } from 'vue';
import type { Stop } from '../services/stops';

const isOpen = ref(false);
const editingStop = ref<Stop | null>(null);
const onSavedCallback = ref<(() => void) | null>(null);
const onDeletedCallback = ref<(() => void) | null>(null);

// Global listeners for stop changes (useful for pages to refresh when stops are modified via FAB)
const savedListeners = new Set<() => void>();
const deletedListeners = new Set<() => void>();

export function useStopModal() {
  function openStopModal(stop: Stop | null = null, callbacks?: { onSaved?: () => void; onDeleted?: () => void }) {
    editingStop.value = stop;
    onSavedCallback.value = callbacks?.onSaved || null;
    onDeletedCallback.value = callbacks?.onDeleted || null;
    isOpen.value = true;
  }

  function closeStopModal() {
    isOpen.value = false;
    editingStop.value = null;
  }

  function handleSaved() {
    onSavedCallback.value?.();
    savedListeners.forEach(listener => listener());
  }

  function handleDeleted() {
    onDeletedCallback.value?.();
    deletedListeners.forEach(listener => listener());
  }

  function onStopSaved(callback: () => void) {
    savedListeners.add(callback);
    onUnmounted(() => savedListeners.delete(callback));
  }

  function onStopDeleted(callback: () => void) {
    deletedListeners.add(callback);
    onUnmounted(() => deletedListeners.delete(callback));
  }

  return {
    isOpen,
    editingStop,
    openStopModal,
    closeStopModal,
    handleSaved,
    handleDeleted,
    onStopSaved,
    onStopDeleted,
  };
}
