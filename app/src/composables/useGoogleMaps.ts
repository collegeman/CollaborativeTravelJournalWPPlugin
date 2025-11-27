let googleMapsLoaded = false;
let loadPromise: Promise<void> | null = null;

function getApiKey(): string {
  return (window as any).GOOGLE_MAPS_API_KEY || import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';
}

export async function loadGoogleMaps(): Promise<void> {
  if (googleMapsLoaded) return;

  // If already loading, return the existing promise
  if (loadPromise) return loadPromise;

  const apiKey = getApiKey();
  if (!apiKey) {
    throw new Error('Google Maps API key not configured');
  }

  loadPromise = new Promise((resolve, reject) => {
    // Check if already loaded
    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
      googleMapsLoaded = true;
      resolve();
      return;
    }

    const callbackName = 'initGoogleMapsCallback';

    // If callback already exists, wait for it
    if ((window as any)[callbackName]) {
      const checkLoaded = setInterval(() => {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
          googleMapsLoaded = true;
          clearInterval(checkLoaded);
          resolve();
        }
      }, 100);
      return;
    }

    (window as any)[callbackName] = () => {
      googleMapsLoaded = true;
      delete (window as any)[callbackName];
      resolve();
    };

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=${callbackName}&loading=async`;

    script.onerror = () => {
      delete (window as any)[callbackName];
      loadPromise = null;
      reject(new Error('Failed to load Google Maps'));
    };

    document.head.appendChild(script);
  });

  return loadPromise;
}

export function isGoogleMapsLoaded(): boolean {
  return googleMapsLoaded;
}

export function useGoogleMaps() {
  return {
    loadGoogleMaps,
    isGoogleMapsLoaded,
    getApiKey,
  };
}
