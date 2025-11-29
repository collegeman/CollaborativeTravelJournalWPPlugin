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
        <ion-title v-else>Media</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment v-model="viewMode">
          <ion-segment-button value="card">
            <ion-icon :icon="squareOutline"></ion-icon>
          </ion-segment-button>
          <ion-segment-button value="grid">
            <ion-icon :icon="gridOutline"></ion-icon>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div v-if="currentTrip" class="media-container">
        <div v-if="loading" class="loading-state">
          <ion-spinner></ion-spinner>
        </div>

        <div v-else-if="media.length === 0" class="empty-state">
          <ion-icon :icon="images" class="empty-icon"></ion-icon>
          <p>No media yet</p>
          <p class="hint">Tap the + button to add photos, videos, or audio</p>
        </div>

        <!-- Card View -->
        <div v-else-if="viewMode === 'card'" class="media-cards">
          <div
            v-for="item in media"
            :key="item.id"
            class="media-card"
            @click="openMedia(item)"
          >
            <div class="card-media">
              <img
                v-if="getDisplayType(item) === 'image'"
                :src="getLargeUrl(item)"
                :alt="item.title.rendered"
                loading="lazy"
              />
              <video
                v-else-if="getDisplayType(item) === 'video'"
                :src="item.source_url"
                preload="metadata"
                muted
                playsinline
              ></video>
              <div v-else-if="getDisplayType(item) === 'audio'" class="card-placeholder audio">
                <ion-icon :icon="musicalNote"></ion-icon>
              </div>
              <div v-else class="card-placeholder file">
                <ion-icon :icon="document"></ion-icon>
              </div>
            </div>
            <div class="card-info">
              <div class="card-title">{{ item.title.rendered }}</div>
              <div class="card-meta">{{ getDisplayType(item) }}</div>
            </div>
          </div>
        </div>

        <!-- Grid View -->
        <div v-else class="media-grid">
          <div
            v-for="item in media"
            :key="item.id"
            class="media-item"
            @click="openMedia(item)"
          >
            <img
              v-if="getDisplayType(item) === 'image'"
              :src="getThumbnailUrl(item)"
              :alt="item.title.rendered"
              loading="lazy"
            />
            <video
              v-else-if="getDisplayType(item) === 'video'"
              :src="item.source_url"
              preload="metadata"
              muted
              playsinline
            ></video>
            <div v-else-if="getDisplayType(item) === 'audio'" class="grid-placeholder audio">
              <ion-icon :icon="musicalNote"></ion-icon>
            </div>
            <div v-else class="grid-placeholder file">
              <ion-icon :icon="document"></ion-icon>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <p>No trip selected</p>
      </div>
    </ion-content>

    <MediaModal
      :is-open="modalIsOpen"
      :media="editingMedia"
      @close="closeMediaModal"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />
  </ion-page>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonButtons,
  IonMenuButton,
  IonSpinner,
  IonIcon,
  IonSegment,
  IonSegmentButton,
} from '@ionic/vue';
import { images, musicalNote, document, squareOutline, gridOutline } from 'ionicons/icons';
import MediaModal from '../components/MediaModal.vue';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import { useEventStream } from '../composables/useEventStream';
import { useMediaModal } from '../composables/useMediaModal';
import { getMediaByTrip, type MediaItem } from '../services/media';

const { currentTrip } = useCurrentTrip();
const { onMediaChange } = useEventStream();
const { isOpen: modalIsOpen, editingMedia, openMediaModal, closeMediaModal, handleSaved, handleDeleted } = useMediaModal();

const media = ref<MediaItem[]>([]);
const loading = ref(false);
const viewMode = ref<'card' | 'grid'>('card');

function openMedia(item: MediaItem) {
  openMediaModal(item, {
    onSaved: () => loadMedia(currentTrip.value!.id),
    onDeleted: () => loadMedia(currentTrip.value!.id),
  });
}

async function loadMedia(tripId: number) {
  loading.value = true;
  try {
    media.value = await getMediaByTrip(tripId);
  } catch (error) {
    console.error('Failed to load media:', error);
  } finally {
    loading.value = false;
  }
}

function getThumbnailUrl(item: MediaItem): string {
  return item.media_details?.sizes?.medium?.source_url
    || item.media_details?.sizes?.thumbnail?.source_url
    || item.source_url;
}

function getLargeUrl(item: MediaItem): string {
  return item.media_details?.sizes?.large?.source_url
    || item.media_details?.sizes?.medium?.source_url
    || item.source_url;
}

function getDisplayType(item: MediaItem): 'image' | 'video' | 'audio' | 'file' {
  const mime = item.mime_type || '';
  if (mime.startsWith('image/')) return 'image';
  if (mime.startsWith('video/')) return 'video';
  if (mime.startsWith('audio/')) return 'audio';
  return 'file';
}

watch(
  currentTrip,
  (trip) => {
    if (trip) {
      loadMedia(trip.id);
    } else {
      media.value = [];
    }
  },
  { immediate: true }
);

onMediaChange(() => {
  if (currentTrip.value) {
    loadMedia(currentTrip.value.id);
  }
});
</script>

<style scoped>
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
  min-width: 60px;
}

ion-content {
  --background: #faf8f5;
}

.media-container {
  height: 100%;
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100%;
  color: var(--ion-color-medium);
  text-align: center;
  padding: 20px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.hint {
  font-size: 14px;
  opacity: 0.7;
}

/* Card View */
.media-cards {
  padding: 16px 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 100px;
}

.media-card {
  background: white;
  border-radius: 0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  cursor: pointer;
}

.card-media {
  aspect-ratio: 4 / 3;
  overflow: hidden;
  background: var(--ion-color-light);
}

.card-media img,
.card-media video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--ion-color-light-shade);
}

.card-placeholder ion-icon {
  font-size: 48px;
  color: var(--ion-color-medium);
}

.card-info {
  padding: 12px 16px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a1a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-meta {
  font-size: 13px;
  color: var(--ion-color-medium);
  margin-top: 4px;
  text-transform: capitalize;
}

/* Grid View */
.media-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  padding-bottom: 100px;
}

.media-item {
  aspect-ratio: 1;
  overflow: hidden;
  background: var(--ion-color-light);
  cursor: pointer;
}

.media-item img,
.media-item video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.grid-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background: var(--ion-color-light-shade);
}

.grid-placeholder ion-icon {
  font-size: 32px;
  color: var(--ion-color-medium);
}
</style>
