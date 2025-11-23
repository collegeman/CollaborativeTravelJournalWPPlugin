<template>
  <ion-menu content-id="main-content" type="overlay">
    <ion-header>
      <ion-toolbar>
        <ion-title>Your Trips</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list v-if="!loading && trips.length > 0">
        <ion-item
          v-for="trip in trips"
          :key="trip.id"
          button
          @click="selectTrip(trip)"
          :class="{ 'selected-trip': isCurrentTrip(trip) }"
        >
          <ion-label>
            <h3>{{ trip.title.rendered }}</h3>
          </ion-label>
          <ion-icon
            v-if="isCurrentTrip(trip)"
            :icon="checkmark"
            slot="end"
            color="primary"
          ></ion-icon>
        </ion-item>
      </ion-list>

      <div v-if="loading" class="loading-container">
        <ion-spinner></ion-spinner>
        <p>Loading trips...</p>
      </div>

      <div v-if="!loading && trips.length === 0" class="empty-state">
        <p>No trips yet</p>
      </div>

      <div class="menu-footer">
        <ion-button expand="block" @click="createNewTrip">
          <ion-icon :icon="add" slot="start"></ion-icon>
          Create New Trip
        </ion-button>
      </div>
    </ion-content>
  </ion-menu>
</template>

<script setup lang="ts">
import {
  IonMenu,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonButton,
  IonIcon,
  IonSpinner,
  menuController
} from '@ionic/vue';
import { add, checkmark } from 'ionicons/icons';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip, type Trip } from '../composables/useCurrentTrip';

const router = useRouter();
const { currentTrip, setCurrentTrip } = useCurrentTrip();

const trips = ref<Trip[]>([]);
const loading = ref(false);

onMounted(async () => {
  await fetchTrips();
});

async function fetchTrips() {
  try {
    loading.value = true;
    const apiUrl = (window as any).WP_API_URL || '/wp-json/';
    const response = await fetch(apiUrl + 'wp/v2/ctj_trip', {
      headers: {
        'X-WP-Nonce': (window as any).WP_NONCE || ''
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    trips.value = await response.json();
  } catch (e) {
    console.error('Error fetching trips:', e);
  } finally {
    loading.value = false;
  }
}

function isCurrentTrip(trip: Trip): boolean {
  return currentTrip.value?.id === trip.id;
}

function selectTrip(trip: Trip) {
  setCurrentTrip(trip);
  menuController.close();
}

function createNewTrip() {
  menuController.close();
  router.push('/trip/create');
}
</script>

<style scoped>
.selected-trip {
  --background: var(--ion-color-light);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
}

.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: var(--ion-color-medium);
}

.menu-footer {
  padding: 16px;
  border-top: 1px solid var(--ion-color-light);
  margin-top: auto;
}
</style>
