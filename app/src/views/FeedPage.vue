<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title v-if="currentTrip">
          {{ currentTrip.title.rendered }}
        </ion-title>
        <ion-title v-else>Feed</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div v-if="!currentTrip" class="empty-state">
        <p>No trip selected</p>
      </div>
      <div v-else class="stops-list">
        <div v-if="loading" class="loading-state">
          <ion-spinner></ion-spinner>
          <p>Loading stops...</p>
        </div>

        <div v-else-if="error" class="error-state">
          <p>{{ error }}</p>
          <ion-button @click="loadStops">Retry</ion-button>
        </div>

        <template v-else>
          <ion-card v-for="stop in stops" :key="stop.id" class="stop-card">
            <ion-card-header>
              <ion-card-title>{{ stop.title.rendered }}</ion-card-title>
              <ion-card-subtitle>{{ formatDate(stop.meta.date, stop.meta.time, stop.meta.specify_time, stop.meta.timezone) }}</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content v-if="stop.meta.formatted_address">
              {{ stop.meta.formatted_address }}
            </ion-card-content>
          </ion-card>

          <div v-if="stops.length === 0" class="no-stops">
            <p>No stops yet. Add your first stop to begin your journey!</p>
          </div>
        </template>
      </div>
    </ion-content>

    <ActionFab
      :current-trip="currentTrip"
      @add-entry="addEntry"
      @add-media="addMedia"
      @add-stop="addStop"
      @add-song="addSong"
      @add-collaborator="addCollaborator"
    />

    <CreateStopModal :is-open="createStopOpen" @close="closeCreateStop" @stop-created="handleStopCreated" />
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonButtons,
  IonButton,
  IonMenuButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonSpinner
} from '@ionic/vue';
import { ref, watch, onMounted } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import CreateStopModal from '../components/CreateStopModal.vue';
import ActionFab from '../components/ActionFab.vue';
import { getStopsByTrip, type Stop as ApiStop } from '../services/stops';

const { currentTrip } = useCurrentTrip();
const createStopOpen = ref(false);
const stops = ref<ApiStop[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

async function loadStops() {
  if (!currentTrip.value) {
    stops.value = [];
    return;
  }

  try {
    loading.value = true;
    error.value = null;
    stops.value = await getStopsByTrip(currentTrip.value.id);
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load stops';
    console.error('Error loading stops:', e);
  } finally {
    loading.value = false;
  }
}

// Format date/time for display WITHOUT any timezone conversion
// We display exactly what was stored
function formatDate(dateString: string, timeString: string, specifyTime: boolean, timezone?: string): string {
  // Parse YYYY-MM-DD directly - no Date object to avoid timezone issues
  const [year, month, day] = dateString.split('-').map(Number);

  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  // Get day of week (using UTC to avoid any shifts)
  const tempDate = new Date(Date.UTC(year, month - 1, day));
  const dayOfWeek = dayNames[tempDate.getUTCDay()];

  let result = `${dayOfWeek}, ${monthNames[month - 1]} ${day}, ${year}`;

  // Only show time if user explicitly specified it
  if (specifyTime && timeString) {
    const [hours, minutes] = timeString.split(':').map(Number);
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    result += ` at ${hour12}:${String(minutes).padStart(2, '0')} ${ampm}`;

    // Show timezone abbreviation
    if (timezone) {
      const tzAbbr = getTimezoneAbbreviation(timezone, year, month, day, hours);
      result += ` ${tzAbbr}`;
    }
  }

  return result;
}

// Get timezone abbreviation from IANA timezone identifier
function getTimezoneAbbreviation(timezone: string, year: number, month: number, day: number, hours: number): string {
  try {
    // Create a date in the specified timezone to get the correct abbreviation
    const date = new Date(year, month - 1, day, hours);
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: timezone,
      timeZoneName: 'short'
    });
    const parts = formatter.formatToParts(date);
    const tzPart = parts.find(p => p.type === 'timeZoneName');
    return tzPart?.value || timezone;
  } catch {
    // Fallback: extract short name from IANA identifier
    return timezone.split('/').pop()?.replace(/_/g, ' ') || timezone;
  }
}

// Load stops when component mounts or when trip changes
onMounted(() => {
  loadStops();
});

watch(currentTrip, () => {
  loadStops();
});

function addEntry() {
  console.log('Add entry');
  // TODO: Navigate to add entry page
}

function addMedia() {
  console.log('Add media');
  // TODO: Open media picker
}

function addStop() {
  createStopOpen.value = true;
}

function closeCreateStop() {
  createStopOpen.value = false;
}

function handleStopCreated() {
  loadStops();
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

ion-toolbar ion-menu-button {
  --color: white;
}

ion-content {
  --background: #faf8f5;
}

.stops-list {
  padding: 16px 0;
}

.stop-card {
  margin: 0 0 16px 0;
  border-radius: 0;
}

.stop-card:last-of-type {
  margin-bottom: 80px; /* Add space for FAB */
}

.no-stops {
  padding: 40px 16px;
  text-align: center;
  color: var(--ion-color-medium);
  font-style: italic;
}

.loading-state,
.error-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  text-align: center;
  color: var(--ion-color-medium);
}

.loading-state {
  gap: 16px;
}

.error-state {
  gap: 16px;
}

.empty-state {
  height: 100%;
}

@media (orientation: landscape) and (max-width: 768px) {
  ion-header {
    display: none;
  }
}
</style>
