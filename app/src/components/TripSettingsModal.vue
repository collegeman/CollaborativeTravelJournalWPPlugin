<template>
  <ion-modal :is-open="isOpen" @didDismiss="closeModal">
    <ion-header>
      <ion-toolbar>
        <ion-title>Trip Settings</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="currentTrip">
        <h2>{{ currentTrip.title.rendered }}</h2>

        <ion-list>
          <ion-item>
            <ion-label>
              <h3>Trip Name</h3>
              <p>{{ currentTrip.title.rendered }}</p>
            </ion-label>
          </ion-item>

          <ion-item>
            <ion-label>
              <h3>Trip ID</h3>
              <p>{{ currentTrip.id }}</p>
            </ion-label>
          </ion-item>
        </ion-list>

        <div class="settings-section">
          <h3>Actions</h3>
          <ion-button expand="block" fill="outline">
            Edit Trip Details
          </ion-button>
          <ion-button expand="block" fill="outline" color="danger" @click="confirmDelete">
            Delete Trip
          </ion-button>
        </div>
      </div>
      <div v-else>
        <p>No trip selected</p>
      </div>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import {
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  alertController
} from '@ionic/vue';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip } from '../composables/useCurrentTrip';

interface Props {
  isOpen: boolean;
}

interface Emits {
  (e: 'close'): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();
const deleting = ref(false);

function closeModal() {
  emit('close');
}

async function confirmDelete() {
  if (!currentTrip.value) return;

  const tripName = currentTrip.value.title.rendered;

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
            deleteTrip();
            return true;
          } else {
            showErrorAlert('Trip name does not match');
            return false;
          }
        }
      }
    ]
  });

  await alert.present();
}

async function showErrorAlert(message: string) {
  const alert = await alertController.create({
    header: 'Error',
    message: message,
    buttons: ['OK']
  });

  await alert.present();
}

async function deleteTrip() {
  if (!currentTrip.value) return;

  try {
    deleting.value = true;
    const tripId = currentTrip.value.id;

    const apiUrl = (window as any).WP_API_URL || '/wp-json/';
    const response = await fetch(apiUrl + `wp/v2/ctj_trip/${tripId}`, {
      method: 'DELETE',
      headers: {
        'X-WP-Nonce': (window as any).WP_NONCE || ''
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    // Remove trip from local list
    const updatedTrips = trips.value.filter(t => t.id !== tripId);
    setTrips(updatedTrips);

    // Close the modal
    closeModal();

    // If there are other trips, select the first one
    if (updatedTrips.length > 0) {
      setCurrentTrip(updatedTrips[0]);
      router.push('/tabs/feed');
    } else {
      // No trips left, redirect to create trip page
      setCurrentTrip(null);
      router.push('/trip/create');
    }
  } catch (e) {
    console.error('Error deleting trip:', e);
    showErrorAlert(e instanceof Error ? e.message : 'Failed to delete trip');
  } finally {
    deleting.value = false;
  }
}
</script>

<style scoped>
h2 {
  margin-bottom: 20px;
}

.settings-section {
  margin-top: 30px;
}

.settings-section h3 {
  margin-bottom: 15px;
  font-size: 18px;
}

.settings-section ion-button:not(:first-of-type) {
  margin-top: 10px;
}
</style>
