import { alertController } from '@ionic/vue';

export function useAlerts() {
  async function showError(message: string, header = 'Error'): Promise<void> {
    const alert = await alertController.create({
      header,
      message,
      buttons: ['OK'],
    });
    await alert.present();
  }

  async function showSuccess(message: string, header = 'Success'): Promise<void> {
    const alert = await alertController.create({
      header,
      message,
      buttons: ['OK'],
    });
    await alert.present();
  }

  async function confirm(
    message: string,
    header = 'Confirm',
    options?: { confirmText?: string; cancelText?: string }
  ): Promise<boolean> {
    return new Promise((resolve) => {
      alertController.create({
        header,
        message,
        buttons: [
          {
            text: options?.cancelText || 'Cancel',
            role: 'cancel',
            handler: () => resolve(false),
          },
          {
            text: options?.confirmText || 'OK',
            handler: () => resolve(true),
          },
        ],
      }).then((alert) => alert.present());
    });
  }

  async function confirmDelete(
    itemName: string,
    header = 'Delete'
  ): Promise<boolean> {
    return new Promise((resolve) => {
      alertController.create({
        header,
        message: `Are you sure you want to delete "${itemName}"?`,
        buttons: [
          {
            text: 'Cancel',
            role: 'cancel',
            handler: () => resolve(false),
          },
          {
            text: 'Delete',
            role: 'destructive',
            handler: () => resolve(true),
          },
        ],
      }).then((alert) => alert.present());
    });
  }

  return {
    showError,
    showSuccess,
    confirm,
    confirmDelete,
  };
}
