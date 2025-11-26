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

    <ActionFab
      :current-trip="currentTrip"
      @add-entry="addEntry"
      @add-media="addMedia"
      @add-stop="addStop"
      @add-song="addSong"
      @add-collaborator="addCollaborator"
    />

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
  IonMenuButton
} from '@ionic/vue';
import { ref } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import CreateStopModal from '../components/CreateStopModal.vue';
import ActionFab from '../components/ActionFab.vue';

const { currentTrip } = useCurrentTrip();
const createStopOpen = ref(false);

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

@media (orientation: landscape) and (max-width: 768px) {
  ion-header {
    display: none;
  }
}
</style>
