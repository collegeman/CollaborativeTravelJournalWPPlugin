export interface CTJEvent {
  id: number;
  trip_id: number;
  event_type: string;
  object_type: string;
  object_id: number;
  user_id: number;
  payload: Record<string, unknown>;
  created_at: string;
}

export type EventHandler = (event: CTJEvent) => void;
export type ConnectionHandler = (connected: boolean) => void;

export interface EventStreamOptions {
  onEvent: EventHandler;
  onConnectionChange?: ConnectionHandler;
  pollFallbackInterval?: number;
}

export class EventStream {
  private tripId: number;
  private eventSource: EventSource | null = null;
  private pollTimer: number | null = null;
  private lastEventTime: string;
  private options: EventStreamOptions;
  private usePolling = false;
  private sseFailCount = 0;
  private readonly MAX_SSE_FAILURES = 3;

  constructor(tripId: number, options: EventStreamOptions) {
    this.tripId = tripId;
    this.options = options;
    this.lastEventTime = new Date().toISOString();
  }

  start(): void {
    if (this.usePolling) {
      this.startPolling();
    } else {
      this.startSSE();
    }
  }

  stop(): void {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
      this.pollTimer = null;
    }
    this.options.onConnectionChange?.(false);
  }

  private startSSE(): void {
    const wpApiUrl = (window as any).WP_API_URL || '/wp-json/';
    const nonce = (window as any).WP_NONCE || '';

    // Build the base URL - WP_API_URL might be '/wp-json/' or full URL
    const baseUrl = wpApiUrl.startsWith('http')
      ? wpApiUrl
      : `${window.location.origin}${wpApiUrl}`;

    const url = new URL(`ctj/v1/trips/${this.tripId}/events`, baseUrl);
    url.searchParams.set('since', this.lastEventTime);
    url.searchParams.set('mode', 'sse');
    url.searchParams.set('_wpnonce', nonce);

    this.eventSource = new EventSource(url.toString(), {
      withCredentials: true,
    });

    this.eventSource.addEventListener('connected', () => {
      this.sseFailCount = 0;
      this.options.onConnectionChange?.(true);
    });

    this.eventSource.addEventListener('error', () => {
      this.sseFailCount++;
      this.options.onConnectionChange?.(false);

      if (this.sseFailCount >= this.MAX_SSE_FAILURES) {
        console.warn('SSE failed multiple times, falling back to polling');
        this.usePolling = true;
        this.eventSource?.close();
        this.eventSource = null;
        this.startPolling();
      }
    });

    // Listen for all CTJ event types
    const eventTypes = [
      'stop.created',
      'stop.updated',
      'stop.deleted',
      'entry.created',
      'entry.updated',
      'entry.deleted',
      'expense.created',
      'expense.updated',
      'expense.deleted',
      'song.created',
      'song.updated',
      'song.deleted',
      'media.created',
      'media.deleted',
      'trip.updated',
      'collaborator.added',
      'collaborator.removed',
    ];

    for (const type of eventTypes) {
      this.eventSource.addEventListener(type, (e: MessageEvent) => {
        const event: CTJEvent = JSON.parse(e.data);
        this.lastEventTime = event.created_at;
        this.options.onEvent(event);
      });
    }
  }

  private startPolling(): void {
    const interval = this.options.pollFallbackInterval ?? 3000;

    const poll = async () => {
      try {
        const wpApiUrl = (window as any).WP_API_URL || '/wp-json/';
        const nonce = (window as any).WP_NONCE || '';

        const baseUrl = wpApiUrl.startsWith('http')
          ? wpApiUrl
          : `${window.location.origin}${wpApiUrl}`;

        const url = new URL(`ctj/v1/trips/${this.tripId}/events`, baseUrl);
        url.searchParams.set('since', this.lastEventTime);
        url.searchParams.set('mode', 'poll');
        url.searchParams.set('_wpnonce', nonce);

        const response = await fetch(url.toString(), {
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': nonce },
        });

        if (response.ok) {
          const data = await response.json();
          this.options.onConnectionChange?.(true);

          for (const event of data.events) {
            this.lastEventTime = event.created_at;
            this.options.onEvent(event);
          }
        }
      } catch (err) {
        console.error('Polling error:', err);
        this.options.onConnectionChange?.(false);
      }
    };

    poll();
    this.pollTimer = window.setInterval(poll, interval);
  }
}
