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

    <ActionFab
      :current-trip="currentTrip"
      @add-entry="addEntry"
      @add-media="addMedia"
      @add-stop="addStop"
      @add-song="addSong"
      @add-collaborator="addCollaborator"
    />

    <TripSettingsModal :is-open="settingsOpen" @close="closeSettings" />
    <CreateStopModal :is-open="createStopOpen" @close="closeCreateStop" />
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
import { ellipsisHorizontalOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import TripSettingsModal from '../components/TripSettingsModal.vue';
import CreateStopModal from '../components/CreateStopModal.vue';
import ActionFab from '../components/ActionFab.vue';

const { currentTrip } = useCurrentTrip();
const settingsOpen = ref(false);
const createStopOpen = ref(false);

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
  createStopOpen.value = true;
}

function closeCreateStop() {
  createStopOpen.value = false;
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

@media (orientation: landscape) {
  ion-header {
    display: none;
  }
}
</style>
