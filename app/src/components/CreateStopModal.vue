<template>
  <ion-modal :is-open="isOpen" @didDismiss="handleClose">
    <ion-page>
      <ion-header>
        <ion-toolbar>
          <ion-title>Create Stop</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="handleClose">
              <ion-icon slot="icon-only" :icon="close"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>

      <ion-content class="ion-padding">
        <form @submit.prevent="handleSubmit">
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Location</ion-label>
              <input
                ref="placeInput"
                type="text"
                v-model="placeName"
                placeholder="Search for a place..."
                class="place-input"
              />
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Date</ion-label>
              <ion-datetime-button datetime="stop-date"></ion-datetime-button>
            </ion-item>

            <ion-item>
              <ion-checkbox v-model="specifyTime">Specify time</ion-checkbox>
            </ion-item>

            <ion-item v-if="specifyTime">
              <ion-label position="stacked">Time</ion-label>
              <ion-datetime-button datetime="stop-time"></ion-datetime-button>
            </ion-item>
          </ion-list>

          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              id="stop-date"
              v-model="date"
              presentation="date"
            ></ion-datetime>
          </ion-modal>

          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              id="stop-time"
              v-model="time"
              presentation="time"
            ></ion-datetime>
          </ion-modal>

          <div class="button-container">
            <ion-button expand="block" type="submit" :disabled="!selectedPlace || creating">
              <ion-spinner v-if="creating" slot="start"></ion-spinner>
              {{ creating ? 'Saving...' : 'Save Stop' }}
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
      </ion-content>
    </ion-page>
  </ion-modal>
</template>

<script setup lang="ts">
import {
  IonModal,
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonButton,
  IonIcon,
  IonList,
  IonItem,
  IonLabel,
  IonDatetime,
  IonDatetimeButton,
  IonCheckbox,
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import { close } from 'ionicons/icons';
import { ref, watch, nextTick } from 'vue';
import { createStop } from '../services/stops';
import { useCurrentTrip } from '../composables/useCurrentTrip';

const props = defineProps<{
  isOpen: boolean;
}>();

const emit = defineEmits<{
  close: [];
  stopCreated: [];
}>();

const { currentTrip } = useCurrentTrip();
const placeInput = ref<HTMLInputElement | null>(null);
const placeName = ref('');
const selectedPlace = ref<google.maps.places.PlaceResult | null>(null);
const date = ref(new Date().toISOString().split('T')[0]); // YYYY-MM-DD format
const specifyTime = ref(false); // Whether to show time field
const time = ref<string | undefined>(undefined); // Optional time in HH:mm format
const creating = ref(false);
const error = ref<string | null>(null);
let autocomplete: google.maps.places.Autocomplete | null = null;
let googleMapsLoaded = false;

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    // Reset form when modal opens
    placeName.value = '';
    selectedPlace.value = null;
    date.value = new Date().toISOString().split('T')[0];
    specifyTime.value = false;
    time.value = undefined;
    error.value = null;

    // Initialize autocomplete after DOM is ready
    // Wait for Ionic modal to be fully mounted
    await nextTick();
    setTimeout(() => {
      initAutocomplete();
    }, 100);
  } else {
    // Clean up autocomplete when modal closes
    autocomplete = null;
  }
});

async function loadGoogleMaps(apiKey: string): Promise<void> {
  if (googleMapsLoaded) return;

  return new Promise((resolve, reject) => {
    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
      googleMapsLoaded = true;
      resolve();
      return;
    }

    // Create a global callback function
    const callbackName = 'initGoogleMapsCallback';
    if (!(window as any)[callbackName]) {
      (window as any)[callbackName] = () => {
        googleMapsLoaded = true;
        resolve();
      };

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=${callbackName}&loading=async`;

      script.onerror = () => {
        delete (window as any)[callbackName];
        reject(new Error('Failed to load Google Maps'));
      };

      document.head.appendChild(script);
    } else {
      // Script is already loading, wait for it
      const checkLoaded = setInterval(() => {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
          googleMapsLoaded = true;
          clearInterval(checkLoaded);
          resolve();
        }
      }, 100);
    }
  });
}

async function initAutocomplete() {
  if (autocomplete || !placeInput.value) return;

  // Get Google Maps API key from WordPress or fallback to env
  const apiKey = (window as any).GOOGLE_MAPS_API_KEY || import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

  if (!apiKey) {
    error.value = 'Google Maps API key not configured';
    return;
  }

  try {
    await loadGoogleMaps(apiKey);
  } catch (err) {
    error.value = 'Failed to load Google Maps';
    return;
  }

  if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
    error.value = 'Google Maps Places not available';
    return;
  }

  // Create the Autocomplete
  autocomplete = new google.maps.places.Autocomplete(placeInput.value, {
    fields: ['place_id', 'name', 'formatted_address', 'geometry']
  });

  // Listen for place selection
  autocomplete.addListener('place_changed', () => {
    const place = autocomplete?.getPlace();
    if (place && place.place_id) {
      selectedPlace.value = place;
      placeName.value = place.name || place.formatted_address || '';
    }
  });
}

function handleClose() {
  emit('close');
}

async function handleSubmit() {
  if (!selectedPlace.value || !currentTrip.value) {
    return;
  }

  try {
    creating.value = true;
    error.value = null;

    const place = selectedPlace.value;
    const latitude = place.geometry?.location?.lat() || 0;
    const longitude = place.geometry?.location?.lng() || 0;

    await createStop({
      tripId: currentTrip.value.id,
      name: place.name || place.formatted_address || '',
      formattedAddress: place.formatted_address || '',
      placeId: place.place_id || '',
      latitude,
      longitude,
      date: date.value,
      time: specifyTime.value ? time.value : undefined,
      specifyTime: specifyTime.value,
    });

    emit('stopCreated');
    handleClose();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create stop';
    console.error('Error creating stop:', e);
  } finally {
    creating.value = false;
  }
}
</script>

<style scoped>
ion-toolbar {
  --background: var(--ion-color-primary);
  --color: white;
}

ion-toolbar ion-button {
  --color: white;
}

ion-toolbar ion-icon {
  color: white;
}

ion-item {
  --padding-start: 0;
  --inner-padding-end: 0;
}

.place-input {
  width: 100%;
  padding: 10px;
  font-size: 16px;
  border: none;
  outline: none;
  background: transparent;
}

.button-container {
  margin-top: 30px;
}

ion-card {
  margin-top: 20px;
}
</style>
