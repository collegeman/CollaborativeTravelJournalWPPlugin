import { apiGet, apiPost, apiDelete } from './api';

export interface UploadProgress {
  loaded: number;
  total: number;
  percent: number;
}

export interface MediaItem {
  id: number;
  source_url: string;
  mime_type: string;
  title: { rendered: string };
  media_type: 'image' | 'video' | 'audio' | 'file';
  media_details?: {
    sizes?: {
      thumbnail?: { source_url: string };
      medium?: { source_url: string };
      large?: { source_url: string };
    };
    image_meta?: {
      latitude?: number;
      longitude?: number;
      created_timestamp?: string;
    };
  };
  meta?: {
    latitude?: number;
    longitude?: number;
    captured_at?: string;
  };
}

export function getMediaByTrip(tripId: number): Promise<MediaItem[]> {
  return apiGet<MediaItem[]>(`/media?parent=${tripId}&per_page=100`);
}

export function updateMedia(mediaId: number, data: { title?: string; caption?: string }): Promise<MediaItem> {
  return apiPost<MediaItem>(`/media/${mediaId}`, data);
}

export function deleteMedia(mediaId: number): Promise<void> {
  return apiDelete(`/media/${mediaId}`);
}

export function uploadMedia(
  file: File,
  tripId: number,
  onProgress?: (progress: UploadProgress) => void
): Promise<MediaItem> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();

    formData.append('file', file);
    formData.append('title', file.name);
    formData.append('post', tripId.toString());
    formData.append('meta[trip_id]', tripId.toString());

    xhr.upload.addEventListener('progress', (event) => {
      if (event.lengthComputable && onProgress) {
        onProgress({
          loaded: event.loaded,
          total: event.total,
          percent: Math.round((event.loaded / event.total) * 100),
        });
      }
    });

    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const response = JSON.parse(xhr.responseText);
          resolve(response as MediaItem);
        } catch {
          reject(new Error('Invalid response from server'));
        }
      } else {
        try {
          const error = JSON.parse(xhr.responseText);
          reject(new Error(error.message || 'Upload failed'));
        } catch {
          reject(new Error(`Upload failed with status ${xhr.status}`));
        }
      }
    });

    xhr.addEventListener('error', () => {
      reject(new Error('Network error during upload'));
    });

    xhr.addEventListener('abort', () => {
      reject(new Error('Upload cancelled'));
    });

    xhr.open('POST', '/wp-json/wp/v2/media');

    const wpNonce = (window as unknown as { WP_NONCE?: string }).WP_NONCE;
    if (wpNonce) {
      xhr.setRequestHeader('X-WP-Nonce', wpNonce);
    }

    xhr.withCredentials = true;
    xhr.send(formData);
  });
}
