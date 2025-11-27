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
import { useCurrentTrip } from '../composables/useCurrentTrip';

const { currentTrip } = useCurrentTrip();
</script>

<style scoped>
ion-content {
  --background: #faf8f5;
}

h1 {
  margin-bottom: 20px;
}
</style>
