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
            <ion-icon slot="icon-only" :icon="ellipsisHorizontalOutline"></ion-icon>
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
import { ref, onMounted, watch, nextTick } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import TripSettingsModal from '../components/TripSettingsModal.vue';
import CreateStopModal from '../components/CreateStopModal.vue';
import ActionFab from '../components/ActionFab.vue';

const { currentTrip } = useCurrentTrip();
const settingsOpen = ref(false);
const createStopOpen = ref(false);
const mapElement = ref<HTMLElement | null>(null);
let map: google.maps.Map | null = null;
let googleMapsLoaded = false;

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

async function loadGoogleMaps(apiKey: string): Promise<void> {
  if (googleMapsLoaded) return;

  return new Promise((resolve, reject) => {
    if (typeof google !== 'undefined' && google.maps && google.maps.Map) {
      googleMapsLoaded = true;
      resolve();
      return;
    }

    // Create a global callback function
    const callbackName = 'initGoogleMapsCallback';
    (window as any)[callbackName] = () => {
      googleMapsLoaded = true;
      delete (window as any)[callbackName];
      resolve();
    };

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=${callbackName}&loading=async`;

    script.onerror = () => {
      delete (window as any)[callbackName];
      reject(new Error('Failed to load Google Maps'));
    };

    document.head.appendChild(script);
  });
}

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

  try {
    console.log('Loading Google Maps...');

    // Load Google Maps script
    await loadGoogleMaps(apiKey);

    // Default center (San Francisco) - used as fallback
    let center = { lat: 37.7749, lng: -122.4194 };
    let zoom = 8;

    // Try to get user's current location
    if (navigator.geolocation) {
      try {
        const position = await new Promise<GeolocationPosition>((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject);
        });
        center = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        zoom = 12; // Closer zoom when using user location
        console.log('Using user location:', center);
      } catch (error) {
        console.log('Could not get user location, using default:', error);
      }
    }

    console.log('Creating map instance...');
    map = new google.maps.Map(mapElement.value, {
      center: center,
      zoom: zoom,
      disableDefaultUI: true,
      mapTypeControl: true,
      mapTypeControlOptions: {
        style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
        position: google.maps.ControlPosition.TOP_LEFT,
      },
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

.map-container {
  width: 100%;
  height: 100%;
}

.map {
  width: 100%;
  height: 100%;
}

/* Google Maps control styling */
.map :deep(.gm-style button) {
  font-size: 14px !important;
}

.map :deep(.gm-style-mtc) {
  font-size: 13px !important;
}

.map :deep(.gm-style-mtc *) {
  font-size: 13px !important;
  line-height: 1.4 !important;
}

.map :deep(.gm-style-mtc > div) {
  padding: 6px 12px !important;
}

.map :deep(.gm-style-mtc label) {
  font-size: 13px !important;
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
