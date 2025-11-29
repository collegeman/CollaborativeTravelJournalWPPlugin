import { ref, computed } from 'vue';
import { uploadMedia, type UploadProgress, type MediaItem } from '../services/media';

interface QueueItem {
  id: string;
  file: File;
  tripId: number;
  stopId?: number;
  status: 'pending' | 'uploading' | 'completed' | 'failed';
  progress: number;
  error?: string;
}

const queue = ref<QueueItem[]>([]);
const isProcessing = ref(false);

export function useMediaUpload() {
  const isUploading = computed(() => queue.value.some((item) => item.status === 'uploading'));

  const overallProgress = computed(() => {
    const items = queue.value.filter((item) => item.status === 'uploading' || item.status === 'pending');
    if (items.length === 0) return 0;

    const completed = queue.value.filter((item) => item.status === 'completed').length;
    const uploading = queue.value.find((item) => item.status === 'uploading');
    const total = queue.value.length;

    if (total === 0) return 0;

    const completedProgress = completed * 100;
    const currentProgress = uploading?.progress || 0;

    return Math.round((completedProgress + currentProgress) / total);
  });

  const pendingCount = computed(() => queue.value.filter((item) => item.status === 'pending').length);

  function addFiles(files: FileList, tripId: number, stopId?: number): void {
    const newItems: QueueItem[] = Array.from(files).map((file) => ({
      id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
      file,
      tripId,
      stopId,
      status: 'pending',
      progress: 0,
    }));

    queue.value.push(...newItems);
    processQueue();
  }

  async function processQueue(): Promise<void> {
    if (isProcessing.value) return;

    isProcessing.value = true;

    while (true) {
      const nextItem = queue.value.find((item) => item.status === 'pending');
      if (!nextItem) break;

      nextItem.status = 'uploading';

      try {
        await uploadMedia(nextItem.file, {
          tripId: nextItem.tripId,
          stopId: nextItem.stopId,
          onProgress: (progress: UploadProgress) => {
            nextItem.progress = progress.percent;
          },
        });
        nextItem.status = 'completed';
        nextItem.progress = 100;
      } catch (error) {
        nextItem.status = 'failed';
        nextItem.error = error instanceof Error ? error.message : 'Upload failed';
      }
    }

    isProcessing.value = false;

    // Clear completed items after a short delay
    setTimeout(() => {
      queue.value = queue.value.filter((item) => item.status !== 'completed');
    }, 500);
  }

  function clearQueue(): void {
    queue.value = [];
    isProcessing.value = false;
  }

  return {
    queue,
    isUploading,
    overallProgress,
    pendingCount,
    addFiles,
    clearQueue,
  };
}
