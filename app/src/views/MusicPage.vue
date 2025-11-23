<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title v-if="currentTrip">
          {{ currentTrip.title.rendered }}
        </ion-title>
        <ion-title v-else>Music</ion-title>
        <ion-buttons slot="end" v-if="currentTrip">
          <ion-button @click="openSettings">
            <ion-icon slot="icon-only" :icon="settingsOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="currentTrip">
        <h1>Music</h1>
        <p class="placeholder">Trip playlist and music coming soon...</p>
      </div>
      <div v-else class="empty-state">
        <p>No trip selected</p>
      </div>
    </ion-content>

    <TripSettingsModal :is-open="settingsOpen" @close="closeSettings" />
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonButtons,
  IonButton,
  IonIcon,
  IonMenuButton
} from '@ionic/vue';
import { settingsOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import TripSettingsModal from '../components/TripSettingsModal.vue';

const { currentTrip } = useCurrentTrip();
const settingsOpen = ref(false);

function openSettings() {
  settingsOpen.value = true;
}

function closeSettings() {
  settingsOpen.value = false;
}
</script>

<style scoped>
h1 {
  margin-bottom: 20px;
}

.placeholder {
  color: var(--ion-color-medium);
  font-style: italic;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-medium);
}
</style>
