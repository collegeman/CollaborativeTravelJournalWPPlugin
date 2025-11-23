<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Create Your First Trip</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="create-trip-container">
        <h1>Start Your Journey</h1>
        <p>Create your first trip to begin documenting your travels.</p>

        <form @submit.prevent="handleSubmit">
          <ion-list>
            <ion-item>
              <ion-input
                v-model="tripTitle"
                label="Trip Name"
                label-placement="stacked"
                placeholder="e.g., Summer Europe Adventure"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-textarea
                v-model="tripDescription"
                label="Description (optional)"
                label-placement="stacked"
                placeholder="What's this trip about?"
                :rows="4"
              ></ion-textarea>
            </ion-item>
          </ion-list>

          <div class="button-container">
            <ion-button
              expand="block"
              type="submit"
              :disabled="!tripTitle || creating"
            >
              <ion-spinner v-if="creating" slot="start"></ion-spinner>
              {{ creating ? 'Creating...' : 'Create Trip' }}
            </ion-button>

            <ion-button
              expand="block"
              fill="outline"
              @click="handleCancel"
              :disabled="creating"
            >
              Cancel
            </ion-button>
          </div>
        </form>

        <ion-card v-if="error" color="danger">
          <ion-card-header>
            <ion-card-title>Error</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            {{ error }}
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonInput,
  IonTextarea,
  IonButton,
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip } from '../composables/useCurrentTrip';

const router = useRouter();
const { setCurrentTrip } = useCurrentTrip();

const tripTitle = ref('');
const tripDescription = ref('');
const creating = ref(false);
const error = ref<string | null>(null);

async function handleSubmit() {
  if (!tripTitle.value) {
    return;
  }

  try {
    creating.value = true;
    error.value = null;

    const apiUrl = (window as any).WP_API_URL || '/wp-json/';
    const response = await fetch(apiUrl + 'wp/v2/ctj_trip', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (window as any).WP_NONCE || ''
      },
      body: JSON.stringify({
        title: tripTitle.value,
        content: tripDescription.value,
        status: 'publish'
      })
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
    }

    const newTrip = await response.json();

    // Set the new trip as current
    setCurrentTrip(newTrip);

    // Redirect to home page (which will show the trip list)
    router.push('/home');
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create trip';
    console.error('Error creating trip:', e);
  } finally {
    creating.value = false;
  }
}

function handleCancel() {
  router.back();
}
</script>

<style scoped>
.create-trip-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px 0;
}

h1 {
  font-size: 28px;
  margin-bottom: 10px;
}

.create-trip-container > p {
  color: var(--ion-color-medium);
  margin-bottom: 30px;
}

.button-container {
  margin-top: 30px;
}

.button-container ion-button:not(:first-child) {
  margin-top: 12px;
}

ion-card {
  margin-top: 20px;
}
</style>
