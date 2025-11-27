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

            <ion-item v-if="specifyTime">
              <ion-label position="stacked">Timezone</ion-label>
              <ion-select v-model="timezone" interface="action-sheet">
                <ion-select-option v-for="tz in commonTimezones" :key="tz.value" :value="tz.value">
                  {{ tz.label }}
                </ion-select-option>
              </ion-select>
            </ion-item>
          </ion-list>

          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              id="stop-date"
              :value="dateForPicker"
              presentation="date"
              @ionChange="handleDateChange"
            ></ion-datetime>
          </ion-modal>

          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              id="stop-time"
              :value="timeForPicker"
              presentation="time"
              @ionChange="handleTimeChange"
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
  IonSelect,
  IonSelectOption,
  IonSpinner,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent
} from '@ionic/vue';
import type { DatetimeCustomEvent } from '@ionic/vue';
import { close } from 'ionicons/icons';
import { ref, computed, watch, nextTick } from 'vue';
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

// Date stored as YYYY-MM-DD (exactly as user selected)
const date = ref(getLocalDateString(new Date()));
// Time stored as HH:mm (exactly as user selected)
const time = ref('08:00');
// Timezone stored as IANA identifier
const timezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
// Whether user explicitly specified time
const specifyTime = ref(false);

const creating = ref(false);
const error = ref<string | null>(null);
let autocomplete: google.maps.places.Autocomplete | null = null;
let googleMapsLoaded = false;

// Common timezones for the picker
const commonTimezones = [
  { value: 'Pacific/Honolulu', label: 'Hawaii (HST)' },
  { value: 'America/Anchorage', label: 'Alaska (AKST)' },
  { value: 'America/Los_Angeles', label: 'Pacific (PST)' },
  { value: 'America/Denver', label: 'Mountain (MST)' },
  { value: 'America/Chicago', label: 'Central (CST)' },
  { value: 'America/New_York', label: 'Eastern (EST)' },
  { value: 'America/Sao_Paulo', label: 'Brasilia (BRT)' },
  { value: 'Europe/London', label: 'London (GMT)' },
  { value: 'Europe/Paris', label: 'Paris (CET)' },
  { value: 'Europe/Moscow', label: 'Moscow (MSK)' },
  { value: 'Asia/Dubai', label: 'Dubai (GST)' },
  { value: 'Asia/Kolkata', label: 'India (IST)' },
  { value: 'Asia/Bangkok', label: 'Bangkok (ICT)' },
  { value: 'Asia/Shanghai', label: 'China (CST)' },
  { value: 'Asia/Tokyo', label: 'Tokyo (JST)' },
  { value: 'Australia/Sydney', label: 'Sydney (AEDT)' },
  { value: 'Pacific/Auckland', label: 'Auckland (NZDT)' },
];

// Get date string in local timezone (YYYY-MM-DD)
function getLocalDateString(d: Date): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// Value for the date picker - just the date string
const dateForPicker = computed(() => date.value);

// Value for the time picker - combine with date to make valid ISO
const timeForPicker = computed(() => `${date.value}T${time.value}:00`);

// Handle date picker change - extract YYYY-MM-DD
function handleDateChange(event: DatetimeCustomEvent) {
  const value = event.detail.value;
  if (typeof value === 'string') {
    // ion-datetime returns ISO format, extract just YYYY-MM-DD
    date.value = value.split('T')[0];
  }
}

// Handle time picker change - extract HH:mm
function handleTimeChange(event: DatetimeCustomEvent) {
  const value = event.detail.value;
  if (typeof value === 'string' && value.includes('T')) {
    const timePart = value.split('T')[1];
    const match = timePart.match(/^(\d{2}:\d{2})/);
    if (match) {
      time.value = match[1];
    }
  }
}

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    // Reset form when modal opens
    placeName.value = '';
    selectedPlace.value = null;
    date.value = getLocalDateString(new Date());
    time.value = '08:00';
    timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    specifyTime.value = false;
    error.value = null;

    await nextTick();
    setTimeout(() => {
      initAutocomplete();
    }, 100);
  } else {
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

  autocomplete = new google.maps.places.Autocomplete(placeInput.value, {
    fields: ['place_id', 'name', 'formatted_address', 'geometry']
  });

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
      time: time.value,
      timezone: timezone.value,
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
