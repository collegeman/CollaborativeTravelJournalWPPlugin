import { ref } from 'vue';

const isOpen = ref(false);

export function useFab() {
  function setOpen(open: boolean) {
    isOpen.value = open;
  }

  return {
    isOpen,
    setOpen,
  };
}
