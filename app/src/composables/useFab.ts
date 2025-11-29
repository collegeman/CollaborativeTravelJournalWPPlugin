import { ref } from 'vue';
import { useMediaUpload } from './useMediaUpload';

const isOpen = ref(false);

export function useFab() {
  const { isUploading, overallProgress, addFiles } = useMediaUpload();

  function setOpen(open: boolean) {
    isOpen.value = open;
  }

  return {
    isOpen,
    setOpen,
    isUploading,
    overallProgress,
    addFiles,
  };
}
