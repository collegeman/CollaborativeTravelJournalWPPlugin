<template>
  <ion-page>
    <TripMenu />
    <ion-tabs>
      <ion-router-outlet id="main-content"></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="feed" href="/tabs/feed">
          <ion-icon :icon="newspaper"></ion-icon>
          <ion-label>Feed</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="media" href="/tabs/media">
          <ion-icon :icon="images"></ion-icon>
          <ion-label>Media</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="map" href="/tabs/map">
          <ion-icon :icon="map"></ion-icon>
          <ion-label>Map</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="music" href="/tabs/music">
          <ion-icon :icon="musicalNotes"></ion-icon>
          <ion-label>Music</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="me" href="/tabs/me">
          <ion-icon :icon="person"></ion-icon>
          <ion-label>Me</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>

    <StopModal
      :is-open="isOpen"
      :stop="editingStop"
      @close="closeStopModal"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <ActionFab
      :current-trip="currentTrip"
      @add-entry="addEntry"
      @add-media="addMedia"
      @add-song="addSong"
      @add-collaborator="addCollaborator"
    />
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel, IonRouterOutlet } from '@ionic/vue';
import { newspaper, images, map, musicalNotes, person } from 'ionicons/icons';
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import TripMenu from '../components/TripMenu.vue';
import StopModal from '../components/StopModal.vue';
import ActionFab from '../components/ActionFab.vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { useStopModal } from '../composables/useStopModal';
import { getTrips } from '../services/trips';

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips, restoreSavedTrip } = useCurrentTrip();
const { isOpen, editingStop, closeStopModal, handleSaved, handleDeleted } = useStopModal();

const loading = ref(true);
const error = ref<string | null>(null);

onMounted(async () => {
  await fetchTrips();

  if (trips.value.length === 0 && !error.value) {
    router.replace('/trip/create');
    return;
  }

  if (trips.value.length > 0) {
    restoreSavedTrip();

    if (!currentTrip.value) {
      setCurrentTrip(trips.value[0]);
    }
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
    const fetchedTrips = await getTrips();
    setTrips(fetchedTrips);
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Unknown error occurred';
    console.error('Error fetching trips:', e);
  } finally {
    loading.value = false;
  }
}

// FAB action handlers
function addEntry() {
  console.log('Add entry');
  // TODO: Navigate to add entry page
}

function addMedia() {
  console.log('Add media');
  // TODO: Open media picker
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
@media (orientation: landscape) and (max-width: 768px) {
  ion-tab-bar {
    display: none;
  }
}
</style>
