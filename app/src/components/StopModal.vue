<template>
  <ion-modal :is-open="isOpen" @didDismiss="handleClose">
    <ion-page>
      <ion-header>
        <ion-toolbar>
          <ion-title>{{ isEditMode ? 'Edit Stop' : 'Create Stop' }}</ion-title>
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
              <div class="place-input-wrapper">
                <input
                  ref="placeInput"
                  type="text"
                  v-model="placeName"
                  placeholder="Search for a place..."
                  class="place-input"
                  :disabled="hasLocation && !isSearching"
                />
                <ion-button
                  v-if="hasLocation && !isSearching"
                  fill="clear"
                  size="small"
                  class="clear-place-button"
                  @click="clearPlace"
                >
                  <ion-icon slot="icon-only" :icon="closeCircle"></ion-icon>
                </ion-button>
              </div>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Date</ion-label>
              <ion-datetime-button :datetime="datePickerId"></ion-datetime-button>
            </ion-item>

            <ion-item>
              <ion-checkbox v-model="specifyTime">Specify time</ion-checkbox>
            </ion-item>

            <ion-item v-if="specifyTime">
              <ion-label position="stacked">Time</ion-label>
              <ion-datetime-button :datetime="timePickerId"></ion-datetime-button>
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
              :id="datePickerId"
              :value="dateForPicker"
              presentation="date"
              @ionChange="handleDateChange"
            ></ion-datetime>
          </ion-modal>

          <ion-modal :keep-contents-mounted="true">
            <ion-datetime
              :id="timePickerId"
              :value="timeForPicker"
              presentation="time"
              @ionChange="handleTimeChange"
            ></ion-datetime>
          </ion-modal>

          <div class="button-container">
            <ion-button expand="block" type="submit" :disabled="!canSubmit || saving || deleting">
              <ion-spinner v-if="saving" slot="start"></ion-spinner>
              {{ saving ? 'Saving...' : 'Save Stop' }}
            </ion-button>
            <ion-button
              v-if="isEditMode"
              expand="block"
              fill="outline"
              color="danger"
              :disabled="saving || deleting"
              @click="confirmDelete"
            >
              <ion-spinner v-if="deleting" slot="start"></ion-spinner>
              <ion-icon v-else :icon="trashOutline" slot="start"></ion-icon>
              {{ deleting ? 'Deleting...' : 'Delete Stop' }}
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
import { close, closeCircle, trashOutline } from 'ionicons/icons';
import { ref, computed, watch, nextTick } from 'vue';
import { createStop, updateStop, deleteStop, type Stop } from '../services/stops';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { loadGoogleMaps } from '../composables/useGoogleMaps';
import { useAlerts } from '../composables/useAlerts';

const props = defineProps<{
  isOpen: boolean;
  stop?: Stop | null;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
  deleted: [];
}>();

const { currentTrip } = useCurrentTrip();
const { confirmDelete: confirmDeleteAlert } = useAlerts();

// Unique IDs for datetime pickers (to avoid conflicts if multiple modals)
const datePickerId = computed(() => props.stop ? `edit-stop-date-${props.stop.id}` : 'create-stop-date');
const timePickerId = computed(() => props.stop ? `edit-stop-time-${props.stop.id}` : 'create-stop-time');

const isEditMode = computed(() => !!props.stop);

const placeInput = ref<HTMLInputElement | null>(null);
const placeName = ref('');
const selectedPlace = ref<google.maps.places.PlaceResult | null>(null);
const isSearching = ref(false);

// Has a location if we have a selected place OR editing an existing stop with location data (and not cleared)
const hasLocation = computed(() => {
  if (selectedPlace.value) return true;
  if (isEditMode.value && props.stop?.meta.place_id && !isSearching.value) return true;
  return false;
});

const date = ref(getLocalDateString(new Date()));
const time = ref('08:00');
const timezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone);
const specifyTime = ref(false);

const saving = ref(false);
const deleting = ref(false);
const error = ref<string | null>(null);
let autocomplete: google.maps.places.Autocomplete | null = null;

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

function getLocalDateString(d: Date): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const dateForPicker = computed(() => date.value);
const timeForPicker = computed(() => `${date.value}T${time.value}:00`);

const canSubmit = computed(() => {
  // Need either a selected place OR (in edit mode with existing location that wasn't cleared)
  if (selectedPlace.value) return true;
  if (isEditMode.value && props.stop?.meta.place_id && !isSearching.value) return true;
  return false;
});

function handleDateChange(event: DatetimeCustomEvent) {
  const value = event.detail.value;
  if (typeof value === 'string') {
    date.value = value.split('T')[0];
  }
}

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
    error.value = null;
    selectedPlace.value = null;
    isSearching.value = false;

    if (props.stop) {
      // Edit mode: populate from existing stop
      placeName.value = props.stop.title.rendered;
      date.value = props.stop.meta.date;
      time.value = props.stop.meta.time || '08:00';
      timezone.value = props.stop.meta.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
      specifyTime.value = props.stop.meta.specify_time;
    } else {
      // Create mode: reset form
      placeName.value = '';
      date.value = getLocalDateString(new Date());
      time.value = '08:00';
      timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
      specifyTime.value = false;
      isSearching.value = true; // Enable searching immediately in create mode
    }

    // Initialize autocomplete for both modes
    await nextTick();
    setTimeout(() => {
      initAutocomplete();
    }, 100);
  } else {
    autocomplete = null;
  }
});

async function initAutocomplete() {
  if (autocomplete || !placeInput.value) return;

  try {
    await loadGoogleMaps();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load Google Maps';
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
      isSearching.value = false;
    }
  });
}

function handleClose() {
  emit('close');
}

function clearPlace() {
  selectedPlace.value = null;
  placeName.value = '';
  isSearching.value = true;

  // Re-focus the input and reinitialize autocomplete
  nextTick(() => {
    if (placeInput.value) {
      placeInput.value.focus();
    }
  });
}

async function confirmDelete() {
  if (!props.stop) return;

  const confirmed = await confirmDeleteAlert(props.stop.title.rendered, 'Delete Stop');
  if (confirmed) {
    await handleDelete();
  }
}

async function handleDelete() {
  if (!props.stop) return;

  try {
    deleting.value = true;
    error.value = null;
    await deleteStop(props.stop.id);
    emit('deleted');
    handleClose();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to delete stop';
    console.error('Error deleting stop:', e);
  } finally {
    deleting.value = false;
  }
}

async function handleSubmit() {
  if (!canSubmit.value || !currentTrip.value) {
    return;
  }

  try {
    saving.value = true;
    error.value = null;

    if (isEditMode.value && props.stop) {
      // Update existing stop
      const updateData: any = {
        name: placeName.value,
        date: date.value,
        time: time.value,
        timezone: timezone.value,
        specifyTime: specifyTime.value,
      };

      // If user selected a new place, include location data
      if (selectedPlace.value) {
        updateData.placeId = selectedPlace.value.place_id || '';
        updateData.formattedAddress = selectedPlace.value.formatted_address || '';
        updateData.latitude = selectedPlace.value.geometry?.location?.lat() || 0;
        updateData.longitude = selectedPlace.value.geometry?.location?.lng() || 0;
      }

      await updateStop(props.stop.id, updateData);
    } else if (selectedPlace.value) {
      // Create new stop
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
    }

    emit('saved');
    handleClose();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to save stop';
    console.error('Error saving stop:', e);
  } finally {
    saving.value = false;
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

.place-input-wrapper {
  display: flex;
  align-items: center;
  width: 100%;
}

.place-input {
  flex: 1;
  padding: 10px;
  font-size: 16px;
  border: none;
  outline: none;
  background: transparent;
}

.place-input:disabled {
  color: var(--ion-color-dark);
  opacity: 1;
}

.clear-place-button {
  --padding-start: 4px;
  --padding-end: 4px;
  margin: 0;
}

.clear-place-button ion-icon {
  font-size: 20px;
  color: var(--ion-color-medium);
}

.location-display {
  padding: 10px 0;
  font-size: 16px;
  color: var(--ion-color-dark);
  margin: 0;
}

.button-container {
  margin-top: 30px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

ion-card {
  margin-top: 20px;
}
</style>
