<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Profile</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">

      <ion-button expand="block" color="medium" @click="signOut">
        <ion-icon :icon="logOutOutline" slot="start"></ion-icon>
        Sign Out
      </ion-button>
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
  IonMenuButton,
  IonButton,
  IonIcon,
} from '@ionic/vue';
import { logOutOutline } from 'ionicons/icons';

async function signOut() {
  try {
    const response = await fetch('/wp-json/ctj/v1/logout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': (window as any).WP_NONCE || '',
      },
    });
    const data = await response.json();
    if (data.redirect) {
      window.location.href = data.redirect;
    }
  } catch {
    // Fallback to login page on error
    window.location.href = '/wp-login.php';
  }
}
</script>

<style scoped>
</style>
