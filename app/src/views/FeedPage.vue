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
        <ion-title v-else>Feed</ion-title>
        <ion-buttons slot="end" v-if="currentTrip">
          <ion-button @click="openSettings">
            <ion-icon slot="icon-only" :icon="settingsOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="currentTrip">
        <h1>{{ currentTrip.title.rendered }}</h1>
        <p v-if="currentTrip.content?.rendered" v-html="currentTrip.content.rendered"></p>
        <p v-else class="no-content">No description yet.</p>
      </div>
      <div v-else class="empty-state">
        <p>No trip selected</p>
      </div>
    </ion-content>

    <ion-fab slot="fixed" vertical="bottom" horizontal="end" v-if="currentTrip">
      <ion-fab-button size="small">
        <ion-icon :icon="add"></ion-icon>
      </ion-fab-button>
      <ion-fab-list side="top">
        <ion-fab-button size="small" @click="addEntry" color="light">
          <ion-icon :icon="documentTextOutline"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addStop" color="light">
          <ion-icon :icon="locationOutline"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addMedia" color="light">
          <ion-icon :icon="cameraOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab-list>
    </ion-fab>

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
  IonMenuButton,
  IonFab,
  IonFabButton,
  IonFabList
} from '@ionic/vue';
import { settingsOutline, add, documentTextOutline, locationOutline, cameraOutline } from 'ionicons/icons';
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

function addEntry() {
  console.log('Add entry');
  // TODO: Navigate to add entry page
}

function addStop() {
  console.log('Add stop');
  // TODO: Navigate to add stop page
}

function addMedia() {
  console.log('Add media');
  // TODO: Open media picker
}
</script>

<style scoped>
h1 {
  margin-bottom: 20px;
}

.no-content {
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
