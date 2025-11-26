<template>
  <ion-modal :is-open="isOpen" @didDismiss="closeModal" @didPresent="loadCollaborators">
    <ion-header>
      <ion-toolbar>
        <ion-title>Trip Settings</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="closeModal">Close</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="currentTrip">
        <h2>{{ currentTrip.title.rendered }}</h2>

        <div class="settings-section">
          <h3>Collaborators</h3>

          <div v-if="loadingCollaborators" class="loading-state">
            <ion-spinner></ion-spinner>
          </div>

          <ion-list v-else>
            <ion-item v-for="collab in collaborators" :key="collab.user_id">
              <ion-label>
                <h3>{{ collab.display_name }}</h3>
                <p>{{ collab.email }}</p>
              </ion-label>
              <ion-badge slot="end" :color="getRoleBadgeColor(collab.role)">
                {{ collab.role }}
              </ion-badge>
              <ion-button
                v-if="collab.role !== 'owner'"
                slot="end"
                fill="clear"
                color="danger"
                @click="confirmRemoveCollaborator(collab)"
              >
                <ion-icon slot="icon-only" :icon="closeCircleOutline"></ion-icon>
              </ion-button>
            </ion-item>
          </ion-list>

          <div class="invite-form">
            <ion-item>
              <ion-input
                v-model="inviteEmail"
                type="email"
                placeholder="Enter email to invite"
                :disabled="inviting"
              ></ion-input>
            </ion-item>
            <ion-button
              expand="block"
              :disabled="!isValidEmail || inviting"
              @click="handleInvite"
            >
              <ion-spinner v-if="inviting" slot="start"></ion-spinner>
              {{ inviting ? 'Inviting...' : 'Invite Collaborator' }}
            </ion-button>
          </div>
        </div>

        <div class="settings-section">
          <h3>Actions</h3>
          <ion-button expand="block" fill="outline">
            Edit Trip Details
          </ion-button>
          <ion-button expand="block" fill="outline" color="danger" @click="confirmDelete">
            Delete Trip
          </ion-button>
        </div>
      </div>
      <div v-else>
        <p>No trip selected</p>
      </div>
    </ion-content>
  </ion-modal>
</template>

<script setup lang="ts">
import {
  IonModal,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonButtons,
  IonButton,
  IonContent,
  IonList,
  IonItem,
  IonLabel,
  IonInput,
  IonIcon,
  IonBadge,
  IonSpinner,
  alertController
} from '@ionic/vue';
import { closeCircleOutline } from 'ionicons/icons';
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useCurrentTrip } from '../composables/useCurrentTrip';
import {
  getCollaborators,
  inviteCollaborator,
  removeCollaborator,
  type Collaborator
} from '../services/collaborators';

interface Props {
  isOpen: boolean;
}

interface Emits {
  (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const router = useRouter();
const { currentTrip, trips, setCurrentTrip, setTrips } = useCurrentTrip();

const deleting = ref(false);
const collaborators = ref<Collaborator[]>([]);
const loadingCollaborators = ref(false);
const inviteEmail = ref('');
const inviting = ref(false);

const isValidEmail = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(inviteEmail.value);
});

function getRoleBadgeColor(role: string): string {
  switch (role) {
    case 'owner': return 'primary';
    case 'contributor': return 'secondary';
    case 'viewer': return 'medium';
    default: return 'medium';
  }
}

async function loadCollaborators() {
  if (!currentTrip.value) return;

  try {
    loadingCollaborators.value = true;
    collaborators.value = await getCollaborators(currentTrip.value.id);
  } catch (e) {
    console.error('Error loading collaborators:', e);
    collaborators.value = [];
  } finally {
    loadingCollaborators.value = false;
  }
}

async function handleInvite() {
  if (!currentTrip.value || !isValidEmail.value) return;

  try {
    inviting.value = true;
    const newCollab = await inviteCollaborator(currentTrip.value.id, {
      email: inviteEmail.value,
      role: 'contributor'
    });
    collaborators.value.push(newCollab);
    inviteEmail.value = '';

    const alert = await alertController.create({
      header: 'Invitation Sent',
      message: `${newCollab.display_name} has been invited to collaborate on this trip.`,
      buttons: ['OK']
    });
    await alert.present();
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Failed to invite collaborator';
    const alert = await alertController.create({
      header: 'Error',
      message,
      buttons: ['OK']
    });
    await alert.present();
  } finally {
    inviting.value = false;
  }
}

async function confirmRemoveCollaborator(collab: Collaborator) {
  const alert = await alertController.create({
    header: 'Remove Collaborator',
    message: `Are you sure you want to remove ${collab.display_name} from this trip?`,
    buttons: [
      { text: 'Cancel', role: 'cancel' },
      {
        text: 'Remove',
        role: 'destructive',
        handler: () => {
          doRemoveCollaborator(collab);
        }
      }
    ]
  });
  await alert.present();
}

async function doRemoveCollaborator(collab: Collaborator) {
  if (!currentTrip.value) return;

  try {
    await removeCollaborator(currentTrip.value.id, collab.user_id);
    collaborators.value = collaborators.value.filter(c => c.user_id !== collab.user_id);
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Failed to remove collaborator';
    const alert = await alertController.create({
      header: 'Error',
      message,
      buttons: ['OK']
    });
    await alert.present();
  }
}

function closeModal() {
  inviteEmail.value = '';
  emit('close');
}

async function confirmDelete() {
  if (!currentTrip.value) return;

  const tripName = currentTrip.value.title.rendered;

  const alert = await alertController.create({
    header: 'Delete Trip',
    message: `This action cannot be undone. To confirm deletion, please type the trip name:\n\n"${tripName}"`,
    inputs: [
      {
        name: 'confirmName',
        type: 'text',
        placeholder: 'Type trip name here'
      }
    ],
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel'
      },
      {
        text: 'Delete',
        role: 'destructive',
        handler: (data) => {
          if (data.confirmName === tripName) {
            deleteTrip();
            return true;
          } else {
            showErrorAlert('Trip name does not match');
            return false;
          }
        }
      }
    ]
  });

  await alert.present();
}

async function showErrorAlert(message: string) {
  const alert = await alertController.create({
    header: 'Error',
    message: message,
    buttons: ['OK']
  });

  await alert.present();
}

async function deleteTrip() {
  if (!currentTrip.value) return;

  try {
    deleting.value = true;
    const tripId = currentTrip.value.id;

    const apiUrl = (window as any).WP_API_URL || '/wp-json/';
    const response = await fetch(apiUrl + `wp/v2/ctj_trip/${tripId}`, {
      method: 'DELETE',
      headers: {
        'X-WP-Nonce': (window as any).WP_NONCE || ''
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const updatedTrips = trips.value.filter(t => t.id !== tripId);
    setTrips(updatedTrips);

    closeModal();

    if (updatedTrips.length > 0) {
      setCurrentTrip(updatedTrips[0]);
      router.push('/tabs/feed');
    } else {
      setCurrentTrip(null);
      router.push('/trip/create');
    }
  } catch (e) {
    console.error('Error deleting trip:', e);
    showErrorAlert(e instanceof Error ? e.message : 'Failed to delete trip');
  } finally {
    deleting.value = false;
  }
}
</script>

<style scoped>
h2 {
  margin-bottom: 20px;
}

.settings-section {
  margin-top: 30px;
}

.settings-section h3 {
  margin-bottom: 15px;
  font-size: 18px;
}

.settings-section ion-button:not(:first-of-type) {
  margin-top: 10px;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 20px;
}

.invite-form {
  margin-top: 16px;
}

.invite-form ion-item {
  --padding-start: 0;
  margin-bottom: 12px;
}

.invite-form ion-button {
  margin-top: 0;
}

ion-badge {
  text-transform: capitalize;
}
</style>
