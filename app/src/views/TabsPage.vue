<template>
  <ion-page>
    <TripMenu />
    <ion-tabs>
      <ion-router-outlet id="main-content"></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="feed" href="/tabs/feed">
          <ion-icon :icon="newspaper"></ion-icon>
        </ion-tab-button>

        <ion-tab-button tab="media" href="/tabs/media">
          <ion-icon :icon="images"></ion-icon>
        </ion-tab-button>

        <ion-tab-button tab="map" href="/tabs/map">
          <ion-icon :icon="map"></ion-icon>
        </ion-tab-button>

        <ion-tab-button tab="music" href="/tabs/music">
          <ion-icon :icon="musicalNotes"></ion-icon>
        </ion-tab-button>

        <ion-tab-button tab="me" href="/tabs/me">
          <ion-icon :icon="person"></ion-icon>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonTabs, IonTabBar, IonTabButton, IonIcon, IonRouterOutlet } from '@ionic/vue';
import { newspaper, images, map, musicalNotes, person } from 'ionicons/icons';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import TripMenu from '../components/TripMenu.vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();

const loading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
  await fetchTrips();

  // Redirect to create trip if no trips exist
  if (trips.value.length === 0 && !error.value) {
    router.replace('/trip/create');
  } else if (trips.value.length > 0 && !currentTrip.value) {
    // Set the first trip as current if none is selected
    setCurrentTrip(trips.value[0]);
  }
});

async function fetchTrips() {
  // Only fetch if we haven't already loaded trips
  if (trips.value.length > 0) {
    loading.value = false;
    return;
  }

  try {
    loading.value = true;
    error.value = null;

    const apiUrl = (window as any).WP_API_URL || '/wp-json/';
    const response = await fetch(apiUrl + 'wp/v2/ctj_trip', {
      headers: {
        'X-WP-Nonce': (window as any).WP_NONCE || ''
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const fetchedTrips = await response.json();
    setTrips(fetchedTrips);
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Unknown error occurred';
    console.error('Error fetching trips:', e);
  } finally {
    loading.value = false;
  }
}
</script>
