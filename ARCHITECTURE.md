# Ionic-WordPress Plugin Architecture

This document describes the architecture pattern used in the Collaborative Travel Journal WordPress plugin, which embeds an Ionic single-page application (SPA) within WordPress.

## Overview

This architecture pattern enables building mobile-first, native-feeling web applications using Ionic Framework while leveraging WordPress as:
- The authentication layer (WordPress users)
- The data persistence layer (Custom Post Types, Post Meta, Taxonomies)
- The REST API provider (WP REST API + custom endpoints)
- The deployment platform (standard WordPress plugin)

### Framework Agnostic Frontend

The Ionic app lives in a completely isolated `app/` directory and is served via a dedicated PHP template. It has **zero dependencies on WordPress's frontend codebase** (no theme integration, no Gutenberg blocks, no wp-scripts). This isolation means:

- **Vue or React**: Choose whichever framework suits your team. Ionic supports both equally well.
- **Independent build tooling**: Use Vite, Webpack, or any modern bundler
- **Full control**: No WordPress frontend constraints or jQuery legacy

The reference implementation uses **Ionic Vue**, but the patterns translate directly to **Ionic React**:

| Vue Concept | React Equivalent |
|-------------|------------------|
| Composables (`useCurrentTrip`) | Custom Hooks (`useCurrentTrip`) |
| `ref()` / `reactive()` | `useState()` / `useReducer()` |
| `watch()` | `useEffect()` |
| `<script setup>` | Function components |
| Vue Router | React Router |

The frontend is a fully-featured Ionic application with:
- Tab-based navigation
- Native-like mobile UI components
- Offline-capable architecture (via Capacitor)
- Real-time updates via Server-Sent Events (SSE)

---

## Directory Structure

```
plugin-name/
├── plugin-name.php              # WordPress plugin entry point
├── composer.json                # PHP dependencies & autoloading
├── src/                         # PHP source code (PSR-4 autoloaded)
│   ├── Plugin.php               # Main plugin orchestrator
│   ├── Activator.php            # Activation hooks
│   ├── Deactivator.php          # Deactivation hooks
│   ├── Routes.php               # URL routing & template handling
│   ├── Media.php                # Media/attachment handling
│   ├── LoginCustomization.php   # Optional branding customization
│   ├── PostTypes/               # Custom Post Type definitions
│   │   ├── Trip.php
│   │   ├── Stop.php
│   │   ├── Entry.php
│   │   ├── Expense.php
│   │   └── Song.php
│   ├── Taxonomies/              # Custom Taxonomy definitions
│   │   ├── StopType.php
│   │   ├── StopStatus.php
│   │   ├── TripStatus.php
│   │   └── ExpenseCategory.php
│   ├── Rest/                    # Custom REST API controllers
│   │   ├── CollaboratorsController.php
│   │   └── MediaController.php
│   └── Events/                  # Real-time event system
│       ├── EventDispatcher.php
│       ├── EventStore.php
│       └── SSEController.php
├── templates/
│   └── app.php                  # Template that serves the Ionic app
├── app/                         # Ionic Vue application
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   ├── index.html
│   ├── capacitor.config.ts      # Capacitor configuration (native apps)
│   ├── dist/                    # Built frontend assets
│   └── src/
│       ├── main.ts              # Vue app entry point
│       ├── App.vue              # Root Vue component
│       ├── router/              # Vue Router configuration
│       │   └── index.ts
│       ├── views/               # Page-level components
│       │   ├── TabsPage.vue     # Tab container with navigation
│       │   ├── TimelinePage.vue
│       │   ├── MapPage.vue
│       │   ├── MediaPage.vue
│       │   └── ...
│       ├── components/          # Reusable UI components
│       │   ├── TripMenu.vue
│       │   ├── StopModal.vue
│       │   ├── ActionFab.vue
│       │   └── ...
│       ├── composables/         # Vue Composition API utilities
│       │   ├── useCurrentTrip.ts
│       │   ├── useEventStream.ts
│       │   ├── useStops.ts
│       │   ├── useMediaUpload.ts
│       │   └── ...
│       ├── services/            # API communication layer
│       │   ├── api.ts           # Base API client
│       │   ├── trips.ts
│       │   ├── stops.ts
│       │   ├── media.ts
│       │   ├── eventStream.ts
│       │   └── ...
│       └── theme/               # Ionic theming
│           ├── variables.css    # Color palette & dark mode
│           └── utilities.css    # Global utility styles
└── vendor/                      # Composer dependencies
```

---

## PHP Backend Architecture

### Plugin Entry Point Pattern

The main plugin file (`plugin-name.php`) follows a minimal, declarative pattern:

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName;

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('PLUGIN_VERSION', '1.0.0');
define('PLUGIN_FILE', __FILE__);
define('PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLUGIN_BASENAME', plugin_basename(__FILE__));

// Configurable app path (where the SPA will be served)
if (!defined('PLUGIN_APP_PATH')) {
    define('PLUGIN_APP_PATH', 'app');  // e.g., yoursite.com/app
}

require_once PLUGIN_DIR . 'vendor/autoload.php';

// Singleton accessor
function plugin(): Plugin {
    static $instance = null;
    if ($instance === null) {
        $instance = new Plugin();
    }
    return $instance;
}

// Lifecycle hooks
register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    plugin()->init();
});
```

### Plugin Orchestrator Pattern

The `Plugin` class is the central orchestrator that initializes all components:

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName;

final class Plugin {
    private bool $initialized = false;

    public function init(): void {
        if ($this->initialized) {
            return;
        }

        $this->loadTextDomain();
        $this->registerHooks();

        $this->initialized = true;
    }

    private function registerHooks(): void {
        // Core WordPress init hook for post types & taxonomies
        add_action('init', [$this, 'onInit']);

        // Register other components
        Routes::register();
        Rest\CollaboratorsController::register();
        Rest\MediaController::register();
        Events\EventDispatcher::register();
        Events\SSEController::register();
        Media::register();
        LoginCustomization::register();
    }

    public function onInit(): void {
        $this->registerPostTypes();
        $this->registerTaxonomies();
    }

    private function registerPostTypes(): void {
        PostTypes\Trip::register();
        PostTypes\Stop::register();
        // ... additional post types
    }

    private function registerTaxonomies(): void {
        Taxonomies\StopType::register();
        Taxonomies\StopStatus::register();
        // ... additional taxonomies
    }
}
```

### Custom Post Type Pattern

Each post type is a self-contained class with:
- A `POST_TYPE` constant for the type slug
- A static `register()` method
- Meta field registration with REST API exposure

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName\PostTypes;

final class Stop {
    public const POST_TYPE = 'prefix_stop';

    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [...],
            'public' => false,              // Not publicly visible
            'publicly_queryable' => true,   // But queryable via REST
            'show_ui' => false,             // No admin UI
            'show_in_menu' => false,
            'show_in_rest' => true,         // CRITICAL: Enable REST API
            'rest_base' => 'stops',         // Custom REST base
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'custom-fields'],
        ]);

        self::register_meta_fields();
        self::register_rest_query_params();
    }

    private static function register_meta_fields(): void {
        $meta_fields = [
            'trip_id' => [
                'type' => 'integer',
                'description' => 'Associated trip ID',
                'sanitize_callback' => 'absint',
            ],
            'latitude' => [
                'type' => 'number',
                'description' => 'Latitude coordinate',
                'sanitize_callback' => fn($v) => (float) $v,
            ],
            'longitude' => [
                'type' => 'number',
                'description' => 'Longitude coordinate',
                'sanitize_callback' => fn($v) => (float) $v,
            ],
            // ... additional fields
        ];

        foreach ($meta_fields as $key => $args) {
            register_post_meta(self::POST_TYPE, $key, [
                'type' => $args['type'],
                'description' => $args['description'],
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => $args['sanitize_callback'] ?? null,
                'auth_callback' => fn($allowed, $meta_key, $post_id) =>
                    current_user_can('edit_post', $post_id),
            ]);
        }
    }

    private static function register_rest_query_params(): void {
        // Add custom query parameters to REST collection endpoint
        add_filter('rest_' . self::POST_TYPE . '_collection_params', function($params) {
            $params['trip_id'] = [
                'description' => 'Filter by trip ID',
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ];
            return $params;
        });

        // Handle custom query parameters
        add_filter('rest_' . self::POST_TYPE . '_query', function($args, $request) {
            if (isset($request['trip_id'])) {
                $args['meta_query'] = [[
                    'key' => 'trip_id',
                    'value' => $request['trip_id'],
                    'compare' => '='
                ]];
            }
            return $args;
        }, 10, 2);
    }
}
```

### Custom Taxonomy Pattern

Taxonomies follow a similar self-contained pattern:

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName\Taxonomies;

use Vendor\PluginName\PostTypes\Stop;

final class StopType {
    public const TAXONOMY = 'prefix_stop_type';

    public static function register(): void {
        register_taxonomy(self::TAXONOMY, [Stop::POST_TYPE], [
            'labels' => [...],
            'hierarchical' => false,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_rest' => true,
        ]);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void {
        $default_terms = [
            'Location',
            'Attraction',
            'Restaurant',
            // ...
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }
}
```

### Routes & Template Handling

The `Routes` class handles:
- URL rewriting to serve the SPA
- Authentication enforcement
- Template injection

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName;

final class Routes {
    public static function register(): void {
        add_action('init', [self::class, 'addRewriteRules']);
        add_action('init', [self::class, 'maybeFlushRules'], 99);
        add_filter('template_include', [self::class, 'handleTemplate']);
        add_filter('query_vars', [self::class, 'addQueryVars']);
        add_action('rest_api_init', [self::class, 'registerRestRoutes']);
    }

    public static function addRewriteRules(): void {
        $path = self::getAppPath();

        if ($path === '') {
            // Root path - match everything except WP admin paths
            add_rewrite_rule(
                '^(?!wp-admin|wp-json|wp-login|wp-content).*',
                'index.php?plugin_app=1',
                'top'
            );
        } else {
            add_rewrite_rule(
                '^' . preg_quote($path, '/') . '/?.*',
                'index.php?plugin_app=1',
                'top'
            );
        }
    }

    public static function handleTemplate(string $template): string {
        $is_app = get_query_var('plugin_app');

        if ($is_app) {
            // Require authentication
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(self::getAppUrl()));
                exit;
            }

            $app_template = PLUGIN_DIR . 'templates/app.php';
            if (file_exists($app_template)) {
                return $app_template;
            }
        }

        return $template;
    }
}
```

### Custom REST Controller Pattern

For operations beyond standard CRUD, create custom REST controllers:

```php
<?php
declare(strict_types=1);

namespace Vendor\PluginName\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class CollaboratorsController {
    public const NAMESPACE = 'prefix/v1';

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/trips/(?P<trip_id>\d+)/collaborators', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'getCollaborators'],
                'permission_callback' => [self::class, 'canViewTrip'],
                'args' => [
                    'trip_id' => [
                        'validate_callback' => fn($p) => is_numeric($p),
                    ],
                ],
            ],
            [
                'methods' => 'POST',
                'callback' => [self::class, 'inviteCollaborator'],
                'permission_callback' => [self::class, 'canEditTrip'],
                'args' => [
                    'trip_id' => ['validate_callback' => fn($p) => is_numeric($p)],
                    'email' => ['required' => true, 'validate_callback' => 'is_email'],
                    'role' => ['default' => 'contributor'],
                ],
            ],
        ]);
    }

    public static function canViewTrip(WP_REST_Request $request): bool {
        if (!is_user_logged_in()) return false;

        $trip_id = (int) $request->get_param('trip_id');
        $trip = get_post($trip_id);

        // Check ownership or collaborator status
        // ... permission logic

        return true;
    }
}
```

### Real-Time Events System (SSE)

The event system provides real-time updates to connected clients.

**EventStore** - Persists events to a custom database table:

```php
<?php
final class EventStore {
    public const TABLE_NAME = 'prefix_events';

    public static function createTable(): void {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            trip_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            object_type VARCHAR(50) NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            payload JSON,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trip_created (trip_id, created_at)
        ) ...";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function recordEvent(
        int $tripId,
        string $eventType,
        string $objectType,
        int $objectId,
        int $userId,
        array $payload = []
    ): int {
        global $wpdb;
        $wpdb->insert(self::getTableName(), [...]);
        return (int) $wpdb->insert_id;
    }

    public static function getEventsSince(int $tripId, string $since, int $limit = 100): array {
        // Fetch events newer than $since timestamp
    }
}
```

**EventDispatcher** - Hooks into WordPress actions to record events:

```php
<?php
final class EventDispatcher {
    private const TRACKED_POST_TYPES = [
        'prefix_stop' => 'stop',
        'prefix_entry' => 'entry',
    ];

    public static function register(): void {
        foreach (array_keys(self::TRACKED_POST_TYPES) as $postType) {
            add_action("rest_after_insert_{$postType}", [self::class, 'onRestInsertPost'], 10, 3);
        }
        add_action('before_delete_post', [self::class, 'onDeletePost']);

        // Cleanup cron job
        add_action('prefix_cleanup_events', [EventStore::class, 'cleanup']);
        if (!wp_next_scheduled('prefix_cleanup_events')) {
            wp_schedule_event(time(), 'hourly', 'prefix_cleanup_events');
        }
    }

    public static function onRestInsertPost(\WP_Post $post, \WP_REST_Request $request, bool $creating): void {
        $eventType = $creating ? 'stop.created' : 'stop.updated';
        EventStore::recordEvent($tripId, $eventType, 'stop', $post->ID, get_current_user_id());
    }
}
```

**SSEController** - Serves events via Server-Sent Events or polling:

```php
<?php
final class SSEController {
    public static function streamEvents(WP_REST_Request $request): void {
        $tripId = (int) $request->get_param('trip_id');
        $mode = $request->get_param('mode'); // 'sse' or 'poll'

        if ($mode === 'sse') {
            self::handleSSE($tripId, $since);
        } else {
            self::handlePoll($tripId, $since);
        }
    }

    private static function handleSSE(int $tripId, string $since): void {
        // Disable output buffering
        while (ob_get_level()) ob_end_clean();

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Event loop
        $startTime = time();
        while ((time() - $startTime) < 25) {
            if (connection_aborted()) break;

            $events = EventStore::getEventsSince($tripId, $since);
            foreach ($events as $event) {
                echo "id: {$event['id']}\n";
                echo "event: {$event['event_type']}\n";
                echo "data: " . wp_json_encode($event) . "\n\n";
                flush();
                $since = $event['created_at'];
            }

            echo ": heartbeat\n\n";
            flush();
            sleep(2);
        }
        exit;
    }
}
```

---

## Ionic Frontend Architecture

> **Note**: This section shows Vue examples from the reference implementation. For React, substitute Vue composables with React hooks, Vue Router with React Router, and Vue components with React components. The architectural patterns remain identical.

### Technology Stack

- **Vue 3** with Composition API (`<script setup>`) — *or React 18 with hooks*
- **Ionic Framework 8** for UI components
- **Vue Router** with hash-based routing — *or React Router*
- **Vite** for development and building
- **TypeScript** for type safety
- **Capacitor** for native mobile app deployment

### Main Entry Point

```typescript
// main.ts
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';
import { IonicVue } from '@ionic/vue';

// Ionic CSS imports
import '@ionic/vue/css/core.css';
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';
import '@ionic/vue/css/palettes/dark.class.css';

// Custom theme
import './theme/variables.css';

const app = createApp(App)
  .use(IonicVue)
  .use(router);

router.isReady().then(() => {
  app.mount('#app');

  // Dark mode detection
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
  const updateDarkMode = (e: MediaQueryList | MediaQueryListEvent) => {
    document.body.classList.toggle('dark', e.matches);
  };
  updateDarkMode(prefersDark);
  prefersDark.addEventListener('change', updateDarkMode);
});
```

### Router Configuration

Uses hash-based routing for WordPress compatibility:

```typescript
// router/index.ts
import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import TabsPage from '../views/TabsPage.vue'

const routes = [
  {
    path: '/',
    redirect: '/tabs/timeline'
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      { path: '', redirect: '/tabs/timeline' },
      { path: 'timeline', component: () => import('../views/TimelinePage.vue') },
      { path: 'media', component: () => import('../views/MediaPage.vue') },
      { path: 'map', component: () => import('../views/MapPage.vue') },
      // ... additional tabs
    ]
  },
  {
    path: '/trip/create',
    name: 'CreateTrip',
    component: () => import('../views/CreateTripPage.vue')
  }
]

const router = createRouter({
  history: createWebHashHistory(),  // Hash routing for WP compatibility
  routes
})

export default router
```

### API Service Layer

Base API client that uses WordPress nonce authentication:

```typescript
// services/api.ts
const API_BASE_URL = '/wp-json/wp/v2';

export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;

  const defaultHeaders: HeadersInit = {
    'Content-Type': 'application/json',
  };

  // WordPress REST API nonce
  const wpNonce = (window as any).WP_NONCE;
  if (wpNonce) {
    defaultHeaders['X-WP-Nonce'] = wpNonce;
  }

  const response = await fetch(url, {
    ...options,
    headers: { ...defaultHeaders, ...options.headers },
    credentials: 'same-origin',  // Include cookies
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw { message: errorData.message, status: response.status };
  }

  return response.json();
}

export const apiGet = <T>(endpoint: string) => apiRequest<T>(endpoint, { method: 'GET' });
export const apiPost = <T>(endpoint: string, data: unknown) =>
  apiRequest<T>(endpoint, { method: 'POST', body: JSON.stringify(data) });
export const apiPut = <T>(endpoint: string, data: unknown) =>
  apiRequest<T>(endpoint, { method: 'PUT', body: JSON.stringify(data) });
export const apiDelete = <T>(endpoint: string) => apiRequest<T>(endpoint, { method: 'DELETE' });
```

### Composables Pattern

Vue composables provide shared reactive state and logic:

```typescript
// composables/useCurrentTrip.ts
import { ref, watch } from 'vue';

export interface Trip {
  id: number;
  title: { rendered: string };
}

const STORAGE_KEY = 'prefix_current_trip_id';
const currentTrip = ref<Trip | null>(null);
const trips = ref<Trip[]>([]);

// Persist selection
watch(currentTrip, (trip) => {
  if (trip) {
    localStorage.setItem(STORAGE_KEY, trip.id.toString());
  } else {
    localStorage.removeItem(STORAGE_KEY);
  }
});

export function useCurrentTrip() {
  function setCurrentTrip(trip: Trip | null) {
    currentTrip.value = trip;
  }

  function restoreSavedTrip() {
    const savedId = localStorage.getItem(STORAGE_KEY);
    if (savedId) {
      const trip = trips.value.find(t => t.id === parseInt(savedId));
      if (trip) currentTrip.value = trip;
    }
  }

  return {
    currentTrip,       // Reactive ref
    trips,
    setCurrentTrip,
    restoreSavedTrip,
    // ... additional methods
  };
}
```

### Real-Time Event Stream

Client-side event stream with SSE and polling fallback:

```typescript
// services/eventStream.ts
export class EventStream {
  private eventSource: EventSource | null = null;
  private usePolling = false;
  private sseFailCount = 0;

  constructor(tripId: number, options: EventStreamOptions) {
    this.tripId = tripId;
    this.options = options;
  }

  start(): void {
    if (this.usePolling) {
      this.startPolling();
    } else {
      this.startSSE();
    }
  }

  private startSSE(): void {
    const url = new URL(`ctj/v1/trips/${this.tripId}/events`, WP_API_URL);
    url.searchParams.set('mode', 'sse');
    url.searchParams.set('_wpnonce', WP_NONCE);

    this.eventSource = new EventSource(url.toString(), { withCredentials: true });

    this.eventSource.addEventListener('connected', () => {
      this.options.onConnectionChange?.(true);
    });

    this.eventSource.addEventListener('error', () => {
      if (++this.sseFailCount >= 3) {
        this.usePolling = true;
        this.eventSource?.close();
        this.startPolling();
      }
    });

    // Listen for event types
    const eventTypes = ['stop.created', 'stop.updated', 'stop.deleted', ...];
    for (const type of eventTypes) {
      this.eventSource.addEventListener(type, (e) => {
        const event = JSON.parse(e.data);
        this.options.onEvent(event);
      });
    }
  }
}
```

### Composable for Event Subscription

```typescript
// composables/useEventStream.ts
import { ref, watch, onUnmounted } from 'vue';
import { EventStream } from '../services/eventStream';
import { useCurrentTrip } from './useCurrentTrip';

const listeners = new Map<string, Set<EventCallback>>();
let activeStream: EventStream | null = null;

export function useEventStream() {
  const { currentTrip } = useCurrentTrip();

  // Auto-connect when trip changes
  watch(currentTrip, (trip) => {
    if (trip) {
      ensureStream(trip.id);
    } else {
      stopStream();
    }
  }, { immediate: true });

  function onStopChange(callback: EventCallback) {
    // Register callback, auto-cleanup on unmount
    if (!listeners.has('stop')) listeners.set('stop', new Set());
    listeners.get('stop')!.add(callback);

    onUnmounted(() => listeners.get('stop')?.delete(callback));
    return () => listeners.get('stop')?.delete(callback);
  }

  return {
    isConnected,
    onStopChange,
    onEntryChange,
    onMediaChange,
    onAnyChange,
  };
}
```

---

## WordPress-Ionic Integration

### Template Injection (app.php)

The template serves the Ionic app and handles development vs. production modes:

```php
<?php
// templates/app.php

// Detect Vite dev server
$dev_server_url = 'http://localhost:5173';
$is_dev_mode = @file_get_contents($dev_server_url, false,
    stream_context_create(['http' => ['timeout' => 1]])) !== false;

// Inject WordPress configuration
$wp_config = sprintf(
    '<script>
      window.WP_API_URL = %s;
      window.WP_NONCE = %s;
      window.GOOGLE_MAPS_API_KEY = %s;
    </script>',
    wp_json_encode(rest_url()),
    wp_json_encode(wp_create_nonce('wp_rest')),
    wp_json_encode($google_maps_key)
);

if ($is_dev_mode) {
    // Development: Load from Vite dev server (HMR enabled)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="..." />
        <base href="/" />
        <?php echo $wp_config; ?>
        <script type="module" src="<?php echo $dev_server_url; ?>/@vite/client"></script>
        <script type="module" src="<?php echo $dev_server_url; ?>/src/main.ts"></script>
    </head>
    <body>
        <div id="app"></div>
    </body>
    </html>
    <?php
} else {
    // Production: Load from built dist/
    $html = file_get_contents(PLUGIN_DIR . 'app/dist/index.html');

    // Update base href for WordPress paths
    $html = str_replace(
        '<base href="/" />',
        '<base href="' . PLUGIN_URL . 'app/dist/" />',
        $html
    );

    // Inject config before </head>
    $html = str_replace('</head>', $wp_config . '</head>', $html);

    echo $html;
}
```

### Global Window Variables

The template injects these globals for API access:

```typescript
// Available in frontend
declare global {
  interface Window {
    WP_API_URL: string;      // e.g., "/wp-json/"
    WP_NONCE: string;        // WordPress REST nonce
    GOOGLE_MAPS_API_KEY: string;
    CTJ_APP_PATH: string;    // e.g., "journal"
  }
}
```

---

## Build & Development Workflow

### Vite Configuration

```typescript
// app/vite.config.ts
import legacy from '@vitejs/plugin-legacy'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  base: './',  // Relative paths for WordPress embedding
  plugins: [vue(), legacy()],
  resolve: {
    alias: { '@': path.resolve(__dirname, './src') }
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://localhost:5173',
    hmr: { host: 'localhost', protocol: 'ws' }
  }
})
```

### Development Workflow

1. Start WordPress (e.g., via Docker, Local, MAMP)
2. Run Vite dev server: `cd app && npm run dev`
3. Access WordPress at configured URL - HMR works automatically

### Production Build

```bash
cd app
npm run build   # Runs: vue-tsc && vite build
```

Build artifacts go to `app/dist/` and are served by the template.

### Capacitor (Native Apps)

The app includes Capacitor configuration for building native iOS/Android apps:

```typescript
// capacitor.config.ts
import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.example.app',
  appName: 'App Name',
  webDir: 'dist',
};

export default config;
```

---

## Key Patterns Summary

| Pattern | Purpose |
|---------|---------|
| **Singleton Plugin** | Single entry point with lazy initialization |
| **Static Registration** | Each class registers its own hooks |
| **PSR-4 Autoloading** | Clean namespace organization |
| **Post Meta REST** | Expose custom fields via REST API |
| **Custom REST Routes** | Complex operations beyond CRUD |
| **SSE + Polling** | Real-time with graceful fallback |
| **Composables / Hooks** | Shared reactive state (Vue composables or React hooks) |
| **Hash Routing** | WordPress URL compatibility |
| **Nonce Auth** | Secure API communication |
| **Dev/Prod Template** | Seamless development experience |
| **Isolated Frontend** | Framework-agnostic (Vue or React) |

---

## Extending This Architecture

When building new features:

1. **New Entity** → Create PostType class, register meta fields with `show_in_rest`
2. **New Classification** → Create Taxonomy class with default terms
3. **Complex API** → Create REST Controller with permission callbacks
4. **Real-time Updates** → Add post type to EventDispatcher tracking
5. **New UI Section** → Create View/Page component, add to router, use composables/hooks for state
6. **Shared Logic** → Create composable (Vue) or custom hook (React) for reactive state

### Choosing Vue vs React

| Consider Vue if... | Consider React if... |
|--------------------|----------------------|
| Team has Vue experience | Team has React experience |
| Prefer template syntax | Prefer JSX |
| Want simpler reactivity model | Need larger ecosystem |
| Lighter bundle size priority | More hiring pool available |

Both integrate identically with the WordPress backend—the PHP side is completely agnostic to your frontend framework choice.
