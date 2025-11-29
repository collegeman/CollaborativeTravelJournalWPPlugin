import { ref, onUnmounted } from 'vue';
import type { MediaItem } from '../services/media';

const isOpen = ref(false);
const editingMedia = ref<MediaItem | null>(null);
const onSavedCallback = ref<(() => void) | null>(null);
const onDeletedCallback = ref<(() => void) | null>(null);

const savedListeners = new Set<() => void>();
const deletedListeners = new Set<() => void>();

export function useMediaModal() {
  function openMediaModal(media: MediaItem, callbacks?: { onSaved?: () => void; onDeleted?: () => void }) {
    editingMedia.value = media;
    onSavedCallback.value = callbacks?.onSaved || null;
    onDeletedCallback.value = callbacks?.onDeleted || null;
    isOpen.value = true;
  }

  function closeMediaModal() {
    isOpen.value = false;
    editingMedia.value = null;
  }

  function handleSaved() {
    onSavedCallback.value?.();
    savedListeners.forEach(listener => listener());
  }

  function handleDeleted() {
    onDeletedCallback.value?.();
    deletedListeners.forEach(listener => listener());
  }

  function onMediaSaved(callback: () => void) {
    savedListeners.add(callback);
    onUnmounted(() => savedListeners.delete(callback));
  }

  function onMediaDeleted(callback: () => void) {
    deletedListeners.add(callback);
    onUnmounted(() => deletedListeners.delete(callback));
  }

  return {
    isOpen,
    editingMedia,
    openMediaModal,
    closeMediaModal,
    handleSaved,
    handleDeleted,
    onMediaSaved,
    onMediaDeleted,
  };
}
