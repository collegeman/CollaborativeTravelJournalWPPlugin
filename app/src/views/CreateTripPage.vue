<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Create New Trip</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="handleCancel" :disabled="creating">
            <ion-icon slot="icon-only" :icon="close"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="create-trip-container">
        <div class="icon-container">
          <ion-icon :icon="locationOutline" class="page-icon"></ion-icon>
        </div>
        <h1>Create New Trip</h1>
        <p>Start documenting a new adventure.</p>

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
  IonButtons,
  IonIcon,
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import { close, locationOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { createTrip } from '../services/trips';

const router = useRouter();
const { setCurrentTrip, addTrip } = useCurrentTrip();

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

    const newTrip = await createTrip({ title: tripTitle.value });

    // Add trip to global trips list
    addTrip(newTrip);

    // Set the new trip as current
    setCurrentTrip(newTrip);

    // Redirect to timeline page
    router.push('/tabs/timeline');
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

.icon-container {
  text-align: left;
  margin-bottom: 20px;
}

.page-icon {
  font-size: 64px;
  color: var(--ion-color-primary);
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

ion-item {
  --padding-start: 0;
  --inner-padding-end: 0;
}
</style>
