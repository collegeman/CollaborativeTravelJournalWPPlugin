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
        <ion-title v-else>Map</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" :scroll-y="false">
      <div v-if="currentTrip" class="map-container">
        <div ref="mapElement" class="map"></div>
      </div>
      <div v-else class="empty-state">
        <p>No trip selected</p>
      </div>
    </ion-content>

    <ion-modal
      :is-open="selectedStop !== null"
      :initial-breakpoint="0.33"
      :breakpoints="[0, 0.33, 0.66]"
      :backdrop-dismiss="true"
      :backdrop-breakpoint="0.5"
      @didDismiss="closeStopModal"
    >
      <div class="stop-modal-content" v-if="selectedStop">
        <div class="stop-modal-header">
          <ion-button fill="clear" size="small" @click="previousStop" :disabled="!hasPreviousStop">
            <ion-icon slot="start" :icon="chevronBack"></ion-icon>
            Prev
          </ion-button>
          <ion-button fill="clear" size="small" @click="nextStop" :disabled="!hasNextStop">
            Next
            <ion-icon slot="end" :icon="chevronForward"></ion-icon>
          </ion-button>
        </div>
        <div class="stop-modal-body">
          <h2>{{ selectedStop.title.rendered }}</h2>
          <p class="stop-address">{{ selectedStop.meta.formatted_address }}</p>
          <p class="stop-date">{{ formatStopDate(selectedStop) }}</p>
        </div>
      </div>
    </ion-modal>

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
  IonMenuButton,
  IonModal,
  IonButton,
  IonIcon
} from '@ionic/vue';
import { chevronBack, chevronForward } from 'ionicons/icons';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { onIonViewWillLeave, menuController } from '@ionic/vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { useEventStream } from '../composables/useEventStream';
import { useStops } from '../composables/useStops';
import { loadGoogleMaps } from '../composables/useGoogleMaps';
import type { Stop } from '../services/stops';

const { currentTrip } = useCurrentTrip();
const { onEvent } = useEventStream();
const { loadStops: loadStopsToCache, getStopsForTrip, lastChange, handleStopEvent } = useStops();
const mapElement = ref<HTMLElement | null>(null);
const selectedStopId = ref<number | null>(null);
let map: google.maps.Map | null = null;
let markers: Map<number, google.maps.marker.AdvancedMarkerElement> = new Map();
let polyline: google.maps.Polyline | null = null;

// Sorted stops for navigation
const sortedStops = computed(() => {
  if (!currentTrip.value) return [];
  const stops = getStopsForTrip(currentTrip.value.id);
  return [...stops]
    .filter(s => s.meta?.latitude && s.meta?.longitude)
    .sort((a, b) => {
      const dateA = `${a.meta?.date || '9999-99-99'}T${a.meta?.time || '00:00'}`;
      const dateB = `${b.meta?.date || '9999-99-99'}T${b.meta?.time || '00:00'}`;
      return dateA.localeCompare(dateB);
    });
});

const selectedStop = computed(() => {
  if (!selectedStopId.value) return null;
  return sortedStops.value.find(s => s.id === selectedStopId.value) || null;
});

const currentStopIndex = computed(() => {
  if (!selectedStopId.value) return -1;
  return sortedStops.value.findIndex(s => s.id === selectedStopId.value);
});

const hasPreviousStop = computed(() => currentStopIndex.value > 0);
const hasNextStop = computed(() => currentStopIndex.value < sortedStops.value.length - 1);

function previousStop() {
  if (!hasPreviousStop.value) return;
  const prevStop = sortedStops.value[currentStopIndex.value - 1];
  selectStop(prevStop.id);
}

function nextStop() {
  if (!hasNextStop.value) return;
  const nextStopItem = sortedStops.value[currentStopIndex.value + 1];
  selectStop(nextStopItem.id);
}

function closeStopModal() {
  const previousId = selectedStopId.value;
  selectedStopId.value = null;
  if (previousId !== null) {
    updateMarkerSelection(previousId, false);
  }
}

function formatStopDate(stop: Stop): string {
  const date = stop.meta?.date;
  if (!date) return '';

  const [year, month, day] = date.split('-').map(Number);
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  let result = `${monthNames[month - 1]} ${day}, ${year}`;

  if (stop.meta?.specify_time && stop.meta?.time) {
    const [hours, minutes] = stop.meta.time.split(':').map(Number);
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    result += ` at ${hour12}:${String(minutes).padStart(2, '0')} ${ampm}`;
  }

  return result;
}

onMounted(async () => {
  await nextTick();
  if (currentTrip.value) {
    await initMap();
  }
});

onIonViewWillLeave(() => {
  if (selectedStopId.value !== null) {
    closeStopModal();
  }
});

// Close modal when sidebar menu opens
function handleMenuOpen() {
  if (selectedStopId.value !== null) {
    closeStopModal();
  }
}

onMounted(() => {
  const menu = document.querySelector('ion-menu');
  menu?.addEventListener('ionWillOpen', handleMenuOpen);
});

onUnmounted(() => {
  const menu = document.querySelector('ion-menu');
  menu?.removeEventListener('ionWillOpen', handleMenuOpen);
});

watch(currentTrip, async (newTrip) => {
  await nextTick();
  if (newTrip) {
    if (!map) {
      await initMap();
    } else {
      await loadStops(newTrip.id);
    }
  }
});

// Watch for instant local updates (when current user creates/updates/deletes stops)
watch(lastChange, (change) => {
  if (!change || !currentTrip.value) return;
  if (change.tripId === currentTrip.value.id) {
    renderMarkers();
  }
});

// Handle events from other users (eventual consistency)
onEvent('stop', async (event) => {
  if (!currentTrip.value) return;

  // Extract tripId from the event data or fetch the stop to get it
  const tripId = currentTrip.value.id;
  await handleStopEvent(event.event_type, event.object_id, tripId);
  renderMarkers();
});

async function initMap() {
  if (!mapElement.value) {
    console.warn('Map element not found');
    return;
  }

  try {
    await loadGoogleMaps();

    map = new google.maps.Map(mapElement.value, {
      center: { lat: 37.7749, lng: -122.4194 },
      zoom: 3,
      disableDefaultUI: true,
      keyboardShortcuts: false,
      mapTypeControl: true,
      mapTypeControlOptions: {
        style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
        position: google.maps.ControlPosition.TOP_LEFT,
      },
      mapId: 'ctj-map',
    });

    if (currentTrip.value) {
      await loadStops(currentTrip.value.id);
    }
  } catch (error) {
    console.error('Error loading Google Maps:', error);
  }
}

async function loadStops(tripId: number) {
  try {
    await loadStopsToCache(tripId);
    renderMarkers(true);
  } catch (error) {
    console.error('Error loading stops:', error);
  }
}

function renderMarkers(fitBounds = false) {
  if (!map || !currentTrip.value) return;

  // Clear existing markers and polyline
  markers.forEach(marker => marker.map = null);
  markers = new Map();
  if (polyline) {
    polyline.setMap(null);
    polyline = null;
  }

  // Get stops from shared cache and sort by date and time
  const stops = getStopsForTrip(currentTrip.value.id);
  const sortedStops = [...stops].sort((a, b) => {
    const dateA = `${a.meta?.date || '9999-99-99'}T${a.meta?.time || '00:00'}`;
    const dateB = `${b.meta?.date || '9999-99-99'}T${b.meta?.time || '00:00'}`;
    return dateA.localeCompare(dateB);
  });

  const bounds = new google.maps.LatLngBounds();
  const path: google.maps.LatLngLiteral[] = [];
  let hasValidCoords = false;

  let markerNumber = 0;
  sortedStops.forEach((stop) => {
    const lat = stop.meta?.latitude;
    const lng = stop.meta?.longitude;

    if (lat && lng) {
      markerNumber++;
      const position = { lat: Number(lat), lng: Number(lng) };
      bounds.extend(position);
      path.push(position);
      hasValidCoords = true;

      const isSelected = selectedStopId.value === stop.id;
      const marker = createMarker(stop, position, markerNumber, isSelected);
      markers.set(stop.id, marker);
    }
  });

  // Draw polyline connecting markers
  if (path.length > 1) {
    polyline = new google.maps.Polyline({
      path,
      geodesic: true,
      strokeColor: '#111111',
      strokeOpacity: 0,
      strokeWeight: 2,
      icons: [{
        icon: {
          path: 'M 0,-1 0,1',
          strokeOpacity: 1,
          scale: 2,
        },
        offset: '0',
        repeat: '10px',
      }],
      map,
    });
  }

  if (fitBounds && hasValidCoords) {
    map.fitBounds(bounds, 50);
    // Don't zoom in too close if only one marker
    google.maps.event.addListenerOnce(map, 'idle', () => {
      const zoom = map?.getZoom();
      if (zoom && zoom > 15) {
        map?.setZoom(15);
      }
    });
  }
}

function createMarkerContent(number: number, isSelected: boolean): HTMLElement {
  const color = isSelected ? '#cb5a33' : '#111111';
  const pinSvg = document.createElement('div');
  pinSvg.innerHTML = `
    <svg width="28" height="40" viewBox="0 0 28 40" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M14 0C6.268 0 0 6.268 0 14c0 10.5 14 26 14 26s14-15.5 14-26c0-7.732-6.268-14-14-14z" fill="${color}"/>
      <text x="14" y="18" text-anchor="middle" fill="white" font-family="system-ui, sans-serif" font-size="12" font-weight="600">${number}</text>
    </svg>
  `;
  return pinSvg;
}

function createMarker(stop: Stop, position: google.maps.LatLngLiteral, number: number, isSelected: boolean): google.maps.marker.AdvancedMarkerElement {
  const marker = new google.maps.marker.AdvancedMarkerElement({
    map,
    position,
    content: createMarkerContent(number, isSelected),
    title: stop.title.rendered,
  });

  marker.addListener('click', () => {
    selectStop(stop.id);
  });

  return marker;
}

function selectStop(stopId: number) {
  const previousId = selectedStopId.value;
  selectedStopId.value = stopId;

  // Update just the affected markers without full re-render
  if (previousId !== null) {
    updateMarkerSelection(previousId, false);
  }
  updateMarkerSelection(stopId, true);

  // Zoom in to the selected marker, offset to account for modal
  const marker = markers.get(stopId);
  if (marker && map) {
    const position = marker.position as google.maps.LatLngLiteral;
    map.setZoom(14);

    // Offset center so marker appears in middle of visible area (top 2/3)
    // Modal takes bottom 1/3, so we shift the center down by 1/6 of screen height
    const mapDiv = map.getDiv();
    const offsetPixels = mapDiv.offsetHeight / 6;
    const scale = Math.pow(2, map.getZoom() || 14);
    const worldCoordinateCenter = map.getProjection()?.fromLatLngToPoint(new google.maps.LatLng(position));

    if (worldCoordinateCenter) {
      const pixelOffset = offsetPixels / scale;
      const newCenter = map.getProjection()?.fromPointToLatLng(
        new google.maps.Point(worldCoordinateCenter.x, worldCoordinateCenter.y + pixelOffset)
      );
      if (newCenter) {
        map.setCenter(newCenter);
      } else {
        map.setCenter(position);
      }
    } else {
      map.setCenter(position);
    }
  }
}

function updateMarkerSelection(stopId: number, isSelected: boolean) {
  const marker = markers.get(stopId);
  if (!marker) return;

  // Find the stop's number in sorted order (sortedStops is already filtered)
  const index = sortedStops.value.findIndex(s => s.id === stopId);
  if (index !== -1) {
    marker.content = createMarkerContent(index + 1, isSelected);
  }
}

</script>

<style scoped>
.map-container {
  width: 100%;
  height: 100%;
  touch-action: none;
}

.map {
  width: 100%;
  height: 100%;
  touch-action: none;
}

/* Google Maps control styling */
.map :deep(.gm-style button) {
  font-size: 14px !important;
}

.map :deep(.gm-style-mtc) {
  font-size: 13px !important;
}

.map :deep(.gm-style-mtc *) {
  font-size: 13px !important;
  line-height: 1.4 !important;
}

.map :deep(.gm-style-mtc > div) {
  padding: 6px 12px !important;
}

.map :deep(.gm-style-mtc label) {
  font-size: 13px !important;
}

/* Stop modal styling */
.stop-modal-content {
  padding: 16px;
  height: 100%;
}

.stop-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.stop-modal-header ion-button {
  --color: var(--ion-color-medium);
  font-size: 14px;
}

.stop-modal-header ion-button:disabled {
  opacity: 0.4;
}

.stop-modal-body h2 {
  margin: 0 0 8px 0;
  font-size: 22px;
  font-weight: 600;
  color: var(--ion-color-dark);
}

.stop-address {
  margin: 0 0 8px 0;
  color: var(--ion-color-medium);
  font-size: 15px;
}

.stop-date {
  margin: 0;
  color: var(--ion-color-medium);
  font-size: 14px;
}

body.dark .stop-modal-header ion-button {
  --color: #ffffff;
}

body.dark .stop-modal-body h2 {
  color: #ffffff;
}

body.dark .stop-address,
body.dark .stop-date {
  color: #cccccc;
}
</style>
