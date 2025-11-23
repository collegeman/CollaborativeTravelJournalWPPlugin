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
import { ref, onMounted, watch, nextTick } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import TripSettingsModal from '../components/TripSettingsModal.vue';

const { currentTrip } = useCurrentTrip();
const settingsOpen = ref(false);
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
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=${callbackName}`;
    script.async = true;
    script.defer = true;

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

    // Default center (San Francisco)
    const center = { lat: 37.7749, lng: -122.4194 };

    console.log('Creating map instance...');
    map = new google.maps.Map(mapElement.value, {
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
