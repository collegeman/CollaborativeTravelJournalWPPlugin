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
        <ion-buttons slot="end">
          <ion-button @click="openFilter">
            <ion-icon slot="icon-only" :icon="filterOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment v-model="feedView">
          <ion-segment-button value="live">
            <ion-label>Live</ion-label>
          </ion-segment-button>
          <ion-segment-button value="plan">
            <ion-label>Plan</ion-label>
          </ion-segment-button>
        </ion-segment>
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
          <div v-if="feedView === 'plan'">
            <div
              v-for="stop in stops"
              :key="stop.id"
              class="stop-card"
              @click="editStop(stop)"
            >
              <div class="stop-header">
                <div class="stop-icon">
                  <ion-icon :icon="locationOutline"></ion-icon>
                </div>
                <div class="stop-info">
                  <div class="stop-title">{{ stop.title.rendered }}</div>
                  <div class="stop-meta">
                    <span class="meta-item">
                      <ion-icon :icon="timeOutline"></ion-icon>
                      {{ formatDateCompact(stop.meta.date) }}
                    </span>
                    <span class="meta-item">
                      <ion-icon :icon="peopleOutline"></ion-icon>
                      2 contributors
                    </span>
                  </div>
                </div>
                <div class="stop-actions">
                  <div class="contributor-avatars">
                    <div class="avatar"></div>
                    <div class="avatar"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <template v-else>
            <div v-for="stop in stops" :key="stop.id" class="stop-card">
              <div class="stop-header">
                <div class="stop-icon">
                  <ion-icon :icon="locationOutline"></ion-icon>
                </div>
                <div class="stop-info">
                  <div class="stop-title">{{ stop.title.rendered }}</div>
                  <div class="stop-meta">
                    <span class="meta-item">
                      <ion-icon :icon="timeOutline"></ion-icon>
                      {{ formatDateCompact(stop.meta.date) }}
                    </span>
                    <span class="meta-item">
                      <ion-icon :icon="peopleOutline"></ion-icon>
                      2 contributors
                    </span>
                  </div>
                </div>
                <div class="stop-actions">
                  <div class="contributor-avatars">
                    <div class="avatar"></div>
                    <div class="avatar"></div>
                  </div>
                  <ion-icon :icon="chevronUpOutline" class="expand-icon"></ion-icon>
                </div>
              </div>
            </div>
          </template>

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

    <StopModal
      :is-open="stopModalOpen"
      :stop="selectedStop"
      @close="closeStopModal"
      @saved="handleStopSaved"
      @deleted="handleStopDeleted"
    />
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
  IonIcon,
  IonSpinner,
  IonSegment,
  IonSegmentButton,
  IonLabel
} from '@ionic/vue';
import { locationOutline, timeOutline, peopleOutline, chevronUpOutline, filterOutline } from 'ionicons/icons';
import { ref, watch, onMounted } from 'vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import StopModal from '../components/StopModal.vue';
import ActionFab from '../components/ActionFab.vue';
import { getStopsByTrip, type Stop as ApiStop } from '../services/stops';

const { currentTrip } = useCurrentTrip();
const feedView = ref<'live' | 'plan'>('plan');
const stopModalOpen = ref(false);
const selectedStop = ref<ApiStop | null>(null);
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

// Compact date format for card header (e.g., "Nov 9")
function formatDateCompact(dateString: string): string {
  const [, month, day] = dateString.split('-').map(Number);
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${monthNames[month - 1]} ${day}`;
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
  selectedStop.value = null;
  stopModalOpen.value = true;
}

function closeStopModal() {
  stopModalOpen.value = false;
  selectedStop.value = null;
}

function handleStopSaved() {
  loadStops();
}

function handleStopDeleted() {
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

function openFilter() {
  console.log('Open filter');
  // TODO: Open filter modal/popover
}

function editStop(stop: ApiStop) {
  selectedStop.value = stop;
  stopModalOpen.value = true;
}

</script>

<style scoped>
ion-toolbar {
  --background: var(--ion-color-primary);
  --color: white;
}

ion-segment {
  --background: transparent;
}

ion-segment-button {
  --background: transparent;
  --background-checked: rgba(0, 0, 0, 0.2);
  --color: rgba(255, 255, 255, 0.7);
  --color-checked: white;
  --indicator-color: transparent;
  --border-radius: 8px;
  --padding-start: 16px;
  --padding-end: 16px;
  min-width: 80px;
  text-transform: none;
  font-weight: 500;
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
  cursor: pointer;
  background: white;
  border-radius: 0;
  margin-bottom: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.stop-card:last-of-type {
  margin-bottom: 80px;
}

.stop-header {
  display: flex;
  align-items: center;
  padding: 16px;
  gap: 12px;
  background: linear-gradient(to right, #fbf0eb, #fdf9f0);
}

.stop-icon {
  width: 48px;
  height: 48px;
  background: #c4703c;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stop-icon ion-icon {
  font-size: 24px;
  color: white;
}

.stop-info {
  flex: 1;
  min-width: 0;
}

.stop-title {
  font-size: 17px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 4px;
}

.stop-meta {
  display: flex;
  gap: 16px;
  color: #666;
  font-size: 13px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.meta-item ion-icon {
  font-size: 14px;
  color: #888;
}

.stop-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.contributor-avatars {
  display: flex;
}

.contributor-avatars .avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: 2px solid white;
  margin-left: -8px;
}

.contributor-avatars .avatar:first-child {
  margin-left: 0;
}

.expand-icon {
  font-size: 20px;
  color: #999;
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
