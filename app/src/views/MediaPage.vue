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
        <ion-title v-else>Media</ion-title>
        <ion-buttons slot="end" v-if="currentTrip">
          <ion-button @click="openSettings">
            <ion-icon slot="icon-only" :icon="ellipsisHorizontalOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div v-if="currentTrip">
        <h1>Media</h1>
        <p class="placeholder">Photo and video gallery coming soon...</p>
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
        <ion-fab-button size="small" @click="addCollaborator" color="light">
          <ion-icon :icon="person"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addSong" color="light">
          <ion-icon :icon="musicalNotes"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addStop" color="light">
          <ion-icon :icon="locationOutline"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addMedia" color="light">
          <ion-icon :icon="images"></ion-icon>
        </ion-fab-button>
        <ion-fab-button size="small" @click="addEntry" color="light">
          <ion-icon :icon="newspaper"></ion-icon>
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
import { ellipsisHorizontalOutline, add, newspaper, images, locationOutline, musicalNotes, person } from 'ionicons/icons';
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

function addMedia() {
  console.log('Add media');
  // TODO: Open media picker
}

function addStop() {
  console.log('Add stop');
  // TODO: Navigate to add stop page
}

function addSong() {
  console.log('Add song');
  // TODO: Open song picker
}

function addCollaborator() {
  console.log('Add collaborator');
  // TODO: Open collaborator invite
}
</script>

<style scoped>
ion-toolbar {
  --background: var(--ion-color-primary);
  --color: white;
}

ion-toolbar ion-button {
  --color: white;
}

ion-toolbar ion-icon {
  color: white;
}

ion-toolbar ion-menu-button {
  --color: white;
}

ion-content {
  --background: #faf8f5;
}

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
