<template>
  <ion-fab slot="fixed" vertical="bottom" horizontal="end" v-if="currentTrip">
    <ion-fab-button>
      <ion-icon :icon="add"></ion-icon>
    </ion-fab-button>
    <ion-fab-list side="top">
      <ion-fab-button @click="$emit('add-collaborator')" color="light">
        <ion-icon :icon="person"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="$emit('add-song')" color="light">
        <ion-icon :icon="musicalNotes"></ion-icon>
      </ion-fab-button>
      <ion-fab-button @click="$emit('add-stop')" color="light">
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

defineProps<{
  currentTrip: Trip | null;
}>();

defineEmits<{
  'add-collaborator': [];
  'add-song': [];
  'add-stop': [];
  'add-media': [];
  'add-entry': [];
}>();
</script>
