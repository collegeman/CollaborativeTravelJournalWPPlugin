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
        <ion-title v-else>Map</ion-title>
        <ion-buttons slot="end" v-if="currentTrip">
          <ion-button @click="openSettings">
            <ion-icon slot="icon-only" :icon="settingsOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div v-if="currentTrip" class="map-container">
        <div ref="mapElement" class="map"></div>
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
import { ref, onMounted, watch, nextTick } from 'vue';
import { setOptions, importLibrary } from '@googlemaps/js-api-loader';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import TripSettingsModal from '../components/TripSettingsModal.vue';

const { currentTrip } = useCurrentTrip();
const settingsOpen = ref(false);
const mapElement = ref<HTMLElement | null>(null);
let map: google.maps.Map | null = null;
let optionsSet = false;

onMounted(async () => {
  await nextTick();
  if (currentTrip.value) {
    await initMap();
  }
});

watch(currentTrip, async (newTrip) => {
  await nextTick();
  if (newTrip && !map) {
    await initMap();
  }
});

async function initMap() {
  if (!mapElement.value) {
    console.warn('Map element not found');
    return;
  }

  // Get Google Maps API key from WordPress or fallback to env
  const apiKey = (window as any).GOOGLE_MAPS_API_KEY || import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

  if (!apiKey) {
    console.error('Please set a valid Google Maps API key');
    return;
  }

  // Set options only once with the API key
  if (!optionsSet) {
    console.log('Setting Google Maps options with API key');
    setOptions({
      apiKey: apiKey,
      version: 'weekly',
    });
    optionsSet = true;
  }

  try {
    console.log('Loading Google Maps...');
    // Load the Maps library
    const { Map } = await importLibrary('maps') as google.maps.MapsLibrary;

    // Default center (San Francisco)
    const center = { lat: 37.7749, lng: -122.4194 };

    console.log('Creating map instance...');
    map = new Map(mapElement.value, {
      center: center,
      zoom: 8,
      mapTypeControl: true,
      streetViewControl: false,
      fullscreenControl: false,
    });
    console.log('Map created successfully');
  } catch (error) {
    console.error('Error loading Google Maps:', error);
  }
}

function openSettings() {
  settingsOpen.value = true;
}

function closeSettings() {
  settingsOpen.value = false;
}
</script>

<style scoped>
.map-container {
  width: 100%;
  height: 100%;
}

.map {
  width: 100%;
  height: 100%;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-medium);
}
</style>
