<template>
  <ion-menu content-id="main-content" type="overlay">
    <ion-content>
      <ion-searchbar
        v-model="searchQuery"
        placeholder="Search trips..."
        @ionInput="handleSearch"
        :debounce="300"
      ></ion-searchbar>

      <div class="create-button-container">
        <ion-button expand="block" @click="createNewTrip" class="create-button">
          <ion-icon :icon="add" slot="start"></ion-icon>
          Create New Trip
        </ion-button>
      </div>

      <ion-list>
        <!-- Trip Items -->
        <ion-item
          v-for="trip in filteredTrips"
          :key="trip.id"
          button
          :detail="false"
          @click="selectTrip(trip)"
          :class="{ 'selected-trip': isCurrentTrip(trip) }"
        >
          <div slot="start" :class="['trip-icon', isCurrentTrip(trip) ? 'active-icon' : 'inactive-icon']">
            <ion-icon :icon="locationOutline"></ion-icon>
          </div>
          <ion-label>
            <h2 class="trip-title">{{ trip.title.rendered }}</h2>
            <p class="trip-meta">
              <span class="meta-item">0 entries</span>
              <span class="meta-separator">•</span>
              <span class="meta-item">
                <ion-icon :icon="peopleOutline" class="meta-icon"></ion-icon>
                1
              </span>
            </p>
          </ion-label>
        </ion-item>
      </ion-list>

      <div v-if="loading" class="loading-container">
        <ion-spinner></ion-spinner>
        <p>Loading trips...</p>
      </div>

      <div v-if="!loading && trips.length > 0 && filteredTrips.length === 0" class="empty-state">
        <p>No trips match your search</p>
      </div>
    </ion-content>
  </ion-menu>
</template>

<script setup lang="ts">
import {
  IonMenu,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonButton,
  IonIcon,
  IonSpinner,
  IonSearchbar,
  menuController
} from '@ionic/vue';
import { add, locationOutline, peopleOutline } from 'ionicons/icons';
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip, type Trip } from '../composables/useCurrentTrip';

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();

const loading = ref(false);
const searchQuery = ref('');

const filteredTrips = computed(() => {
  if (!searchQuery.value.trim()) {
    return trips.value;
  }

  const query = searchQuery.value.toLowerCase();
  return trips.value.filter(trip =>
    trip.title.rendered.toLowerCase().includes(query)
  );
});

function handleSearch(event: CustomEvent) {
  searchQuery.value = event.detail.value || '';
}

function isCurrentTrip(trip: Trip): boolean {
  return currentTrip.value?.id === trip.id;
}

function selectTrip(trip: Trip) {
  setCurrentTrip(trip);
  menuController.close();
  router.push('/tabs/feed');
}

function createNewTrip() {
  menuController.close();
  router.push('/trip/create');
}
</script>

<style scoped>
ion-searchbar {
  --font-size: 14px;
}

.create-button-container {
  padding: 16px;
}

.create-button {
  --border-radius: 8px;
  font-weight: 500;
  text-transform: none;
  font-size: 15px;
}

.trip-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
}

.active-icon {
  background: var(--ion-color-primary);
  color: white;
}

.inactive-icon {
  background: var(--ion-color-light);
  color: var(--ion-color-medium);
}

.trip-icon ion-icon {
  font-size: 20px;
}

.trip-title {
  font-size: 15px;
  font-weight: 500;
  margin: 0 0 4px 0;
  color: var(--ion-color-dark);
}

.trip-meta {
  font-size: 14px;
  color: var(--ion-color-medium);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.meta-separator {
  margin: 0 2px;
}

.meta-icon {
  font-size: 14px;
  vertical-align: middle;
}

.selected-trip {
  --background: var(--ion-color-light-tint);
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

ion-item {
  --padding-start: 16px;
  --inner-padding-end: 16px;
  --min-height: 60px;
}
</style>
