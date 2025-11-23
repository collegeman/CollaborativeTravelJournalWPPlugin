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
        <ion-title v-else>Journ - Travel Journal</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <h1>WordPress REST API Test</h1>

      <div v-if="loading" style="text-align: center; padding: 40px;">
        <ion-spinner></ion-spinner>
        <p>Loading trips from WordPress...</p>
      </div>

      <div v-else-if="error" style="color: var(--ion-color-danger); padding: 20px;">
        <h2>Connection Error</h2>
        <p>{{ error }}</p>
      </div>

      <div v-else>
        <h2>Trips ({{ trips.length }})</h2>

        <ion-list v-if="trips.length > 0">
          <ion-item v-for="trip in trips" :key="trip.id">
            <ion-label>
              <h3>{{ trip.title.rendered }}</h3>
              <p>ID: {{ trip.id }}</p>
            </ion-label>
          </ion-item>
        </ion-list>

        <ion-card v-else>
          <ion-card-header>
            <ion-card-title>No Trips Yet</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            Create your first trip using the WordPress REST API!
          </ion-card-content>
        </ion-card>
      </div>

      <ion-card style="margin-top: 30px;">
        <ion-card-header>
          <ion-card-title>API Connection Info</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <p><strong>Status:</strong> <span :style="{ color: error ? 'var(--ion-color-danger)' : 'var(--ion-color-success)' }">{{ error ? 'Failed' : 'Connected' }}</span></p>
          <p><strong>API Base:</strong> {{ apiUrl }}</p>
          <p><strong>Trips Endpoint:</strong> {{ apiUrl }}wp/v2/ctj_trip</p>
        </ion-card-content>
      </ion-card>
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
  IonList,
  IonItem,
  IonLabel,
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonButtons,
  IonMenuButton
} from '@ionic/vue';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip } from '../composables/useCurrentTrip';

interface Trip {
  id: number;
  title: {
    rendered: string;
  };
}

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();

const loading = ref(true);
const error = ref<string | null>(null);
const apiUrl = ref('');

onMounted(async () => {
  // Get API URL from window object (set by WordPress)
  apiUrl.value = (window as any).WP_API_URL || '/wp-json/';

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
  try {
    loading.value = true;
    error.value = null;

    const response = await fetch(apiUrl.value + 'wp/v2/ctj_trip', {
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

<style scoped>
h1 {
  margin-bottom: 20px;
}

h2 {
  margin-top: 20px;
  margin-bottom: 10px;
}
</style>
