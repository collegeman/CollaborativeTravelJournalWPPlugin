<template>
  <ion-fab
    slot="fixed"
    vertical="bottom"
    horizontal="end"
    v-if="currentTrip"
    :activated="isOpen"
    @click="setOpen(!isOpen)"
  >
    <div class="fab-button-container">
      <svg
        v-if="isUploading"
        class="progress-ring"
        width="72"
        height="72"
      >
        <circle
          class="progress-ring__circle"
          stroke="var(--ion-color-primary)"
          stroke-width="4"
          fill="transparent"
          r="32"
          cx="36"
          cy="36"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="circumference - (overallProgress / 100) * circumference"
        />
      </svg>
      <ion-fab-button>
        <ion-icon :icon="add"></ion-icon>
      </ion-fab-button>
    </div>
    <ion-fab-list side="top">
      <ion-fab-button @click="$emit('add-collaborator')" color="light">
        <ion-icon :icon="person"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="$emit('add-song')" color="light">
        <ion-icon :icon="musicalNotes"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="handleAddStop" color="light">
        <ion-icon :icon="locationOutline"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="$emit('add-media')" color="light">
        <ion-icon :icon="images"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="$emit('add-entry')" color="light">
        <ion-icon :icon="newspaper"></ion-icon>
      </ion-fab-button>
    </ion-fab-list>
  </ion-fab>
</template>

<script setup lang="ts">
import { IonFab, IonFabButton, IonFabList, IonIcon } from '@ionic/vue';
import { add, person, musicalNotes, locationOutline, images, newspaper } from 'ionicons/icons';
import type { Trip } from '../composables/useCurrentTrip';
import { useStopModal } from '../composables/useStopModal';
import { useFab } from '../composables/useFab';

defineProps<{
  currentTrip: Trip | null;
}>();

defineEmits<{
  'add-collaborator': [];
  'add-song': [];
  'add-media': [];
  'add-entry': [];
}>();

const { openStopModal } = useStopModal();
const { isOpen, setOpen, isUploading, overallProgress } = useFab();

const circumference = 2 * Math.PI * 32;

function handleAddStop() {
  openStopModal(null);
}
</script>

<style scoped>
ion-fab {
  bottom: calc(var(--ion-safe-area-bottom, 0px) + 76px);
  right: 16px;
}

@media (orientation: landscape) and (max-width: 768px) {
  ion-fab {
    bottom: calc(var(--ion-safe-area-bottom, 16px) + 16px);
  }
}

.fab-button-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.progress-ring {
  position: absolute;
  transform: rotate(-90deg);
  pointer-events: none;
}

.progress-ring__circle {
  transition: stroke-dashoffset 0.1s ease-out;
  stroke-linecap: round;
}
</style>
