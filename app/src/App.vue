<template>
  <ion-app>
    <TripMenu />
    <ion-router-outlet id="main-content" />
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import TripMenu from './components/TripMenu.vue';
</script>
