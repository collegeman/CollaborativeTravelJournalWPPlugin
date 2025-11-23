<template>
  <ion-menu content-id="main-content" type="overlay">
    <ion-content>
      <ion-searchbar
        v-model="searchQuery"
        placeholder="Search trips..."
        @ionInput="handleSearch"
        debounce="300"
      ></ion-searchbar>

      <ion-list v-if="!loading && filteredTrips.length > 0">
        <ion-item
          v-for="trip in filteredTrips"
          :key="trip.id"
          button
          :detail="false"
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

      <div v-if="!loading && trips.length > 0 && filteredTrips.length === 0" class="empty-state">
        <p>No trips match your search</p>
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
import { add, checkmark } from 'ionicons/icons';
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
