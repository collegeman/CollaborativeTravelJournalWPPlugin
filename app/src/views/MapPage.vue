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
import { ref, onMounted, watch, nextTick } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { loadGoogleMaps } from '../composables/useGoogleMaps';

const { currentTrip } = useCurrentTrip();
const mapElement = ref<HTMLElement | null>(null);
let map: google.maps.Map | null = null;

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

  try {
    await loadGoogleMaps();

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
</style>
