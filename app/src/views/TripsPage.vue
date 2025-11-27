<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/feed"></ion-back-button>
        </ion-buttons>
        <ion-title>Trips</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-list>
        <ion-item-sliding v-for="trip in trips" :key="trip.id">
          <ion-item button :detail="false" @click="openTripSettings(trip)">
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

          <ion-item-options side="end">
            <ion-item-option color="danger" @click="confirmDeleteTrip(trip)">
              <ion-icon slot="icon-only" :icon="trashOutline"></ion-icon>
            </ion-item-option>
          </ion-item-options>
        </ion-item-sliding>
      </ion-list>

      <div v-if="trips.length === 0" class="empty-state">
        <p>No trips yet</p>
        <ion-button @click="createNewTrip">Create Your First Trip</ion-button>
      </div>
    </ion-content>

    <TripSettingsModal :is-open="settingsOpen" @close="closeSettings" />
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonBackButton,
  IonContent,
  IonList,
  IonItem,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
  IonLabel,
  IonIcon,
  IonButton,
  alertController
} from '@ionic/vue';
import { locationOutline, peopleOutline, trashOutline } from 'ionicons/icons';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip, type Trip } from '../composables/useCurrentTrip';
import { useAlerts } from '../composables/useAlerts';
import { deleteTrip as deleteTripApi, getTrips } from '../services/trips';
import TripSettingsModal from '../components/TripSettingsModal.vue';

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();

onMounted(async () => {
  if (trips.value.length === 0) {
    try {
      const fetchedTrips = await getTrips();
      setTrips(fetchedTrips);
    } catch (e) {
      console.error('Error fetching trips:', e);
    }
  }
});
const { showError } = useAlerts();
const settingsOpen = ref(false);

function isCurrentTrip(trip: Trip): boolean {
  return currentTrip.value?.id === trip.id;
}

function openTripSettings(trip: Trip) {
  setCurrentTrip(trip);
  settingsOpen.value = true;
}

function closeSettings() {
  settingsOpen.value = false;
}

function createNewTrip() {
  router.push('/trip/create');
}

async function confirmDeleteTrip(trip: Trip) {
  const tripName = trip.title.rendered;

  const alert = await alertController.create({
    header: 'Delete Trip',
    message: `This action cannot be undone. To confirm deletion, please type the trip name:\n\n"${tripName}"`,
    inputs: [
      {
        name: 'confirmName',
        type: 'text',
        placeholder: 'Type trip name here'
      }
    ],
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: (data) => {
          if (data.confirmName === tripName) {
            handleDeleteTrip(trip);
            return true;
          } else {
            showError('Trip name does not match');
            return false;
          }
        }
      }
    ]
  });

  await alert.present();
}

async function handleDeleteTrip(trip: Trip) {
  try {
    await deleteTripApi(trip.id);

    const updatedTrips = trips.value.filter(t => t.id !== trip.id);
    setTrips(updatedTrips);

    if (currentTrip.value?.id === trip.id) {
      if (updatedTrips.length > 0) {
        setCurrentTrip(updatedTrips[0]);
      } else {
        setCurrentTrip(null);
        router.push('/trip/create');
      }
    }
  } catch (e) {
    console.error('Error deleting trip:', e);
    showError(e instanceof Error ? e.message : 'Failed to delete trip');
  }
}
</script>

<style scoped>
ion-back-button {
  --color: white;
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

ion-item {
  --padding-start: 16px;
  --inner-padding-end: 16px;
  --min-height: 60px;
}
</style>
