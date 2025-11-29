<template>
  <ion-modal :is-open="isOpen" @didDismiss="handleClose">
    <ion-page>
      <ion-header>
        <ion-toolbar>
          <ion-title>Edit Media</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="handleClose">
              <ion-icon slot="icon-only" :icon="close"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>

      <ion-content class="ion-padding">
        <div v-if="media" class="media-preview">
          <img
            v-if="displayType === 'image'"
            :src="media.source_url"
            :alt="media.title.rendered"
          />
          <video
            v-else-if="displayType === 'video'"
            :src="media.source_url"
            controls
            playsinline
          ></video>
          <audio
            v-else-if="displayType === 'audio'"
            :src="media.source_url"
            controls
          ></audio>
          <div v-else class="file-preview">
            <ion-icon :icon="document"></ion-icon>
            <p>{{ media.mime_type }}</p>
          </div>
        </div>

        <form @submit.prevent="handleSubmit">
          <ion-list>
            <ion-item>
              <ion-input
                v-model="title"
                label="Title"
                label-placement="stacked"
                placeholder="Enter a title..."
              ></ion-input>
            </ion-item>
          </ion-list>

          <div class="button-container">
            <ion-button expand="block" type="submit" :disabled="saving || deleting">
              <ion-spinner v-if="saving" slot="start"></ion-spinner>
              {{ saving ? 'Saving...' : 'Save' }}
            </ion-button>
            <ion-button
              expand="block"
              fill="outline"
              color="danger"
              :disabled="saving || deleting"
              @click="confirmDelete"
            >
              <ion-spinner v-if="deleting" slot="start"></ion-spinner>
              <ion-icon v-else :icon="trashOutline" slot="start"></ion-icon>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </ion-button>
          </div>
        </form>

        <ion-card v-if="error" color="danger">
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
  IonInput,
  IonSpinner,
  IonCard,
  IonCardContent,
} from '@ionic/vue';
import { close, trashOutline, document } from 'ionicons/icons';
import { ref, computed, watch } from 'vue';
import { updateMedia, deleteMedia, type MediaItem } from '../services/media';
import { useAlerts } from '../composables/useAlerts';

const props = defineProps<{
  isOpen: boolean;
  media: MediaItem | null;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
  deleted: [];
}>();

const { confirmDelete: confirmDeleteAlert } = useAlerts();

const title = ref('');
const saving = ref(false);
const deleting = ref(false);
const error = ref<string | null>(null);

const displayType = computed(() => {
  if (!props.media) return 'file';
  const mime = props.media.mime_type || '';
  if (mime.startsWith('image/')) return 'image';
  if (mime.startsWith('video/')) return 'video';
  if (mime.startsWith('audio/')) return 'audio';
  return 'file';
});

watch(() => props.isOpen, (newVal) => {
  if (newVal && props.media) {
    title.value = props.media.title.rendered;
    error.value = null;
  }
});

function handleClose() {
  emit('close');
}

async function confirmDelete() {
  if (!props.media) return;

  const confirmed = await confirmDeleteAlert(props.media.title.rendered, 'Delete Media');
  if (confirmed) {
    await handleDelete();
  }
}

async function handleDelete() {
  if (!props.media) return;

  try {
    deleting.value = true;
    error.value = null;
    await deleteMedia(props.media.id);
    emit('deleted');
    handleClose();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to delete media';
    console.error('Error deleting media:', e);
  } finally {
    deleting.value = false;
  }
}

async function handleSubmit() {
  if (!props.media) return;

  try {
    saving.value = true;
    error.value = null;
    await updateMedia(props.media.id, { title: title.value });
    emit('saved');
    handleClose();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to save media';
    console.error('Error saving media:', e);
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

.media-preview {
  margin-bottom: 20px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--ion-color-light);
}

.media-preview img,
.media-preview video {
  width: 100%;
  max-height: 300px;
  object-fit: contain;
  display: block;
}

.media-preview audio {
  width: 100%;
  display: block;
}

.file-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: var(--ion-color-medium);
}

.file-preview ion-icon {
  font-size: 48px;
  margin-bottom: 8px;
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
