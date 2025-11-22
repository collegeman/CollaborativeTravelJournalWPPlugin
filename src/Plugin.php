<?php

declare(strict_types=1);

namespace Collegeman\CollaborativeTravelJournal;

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

    private function loadTextDomain(): void {
        load_plugin_textdomain(
            'collaborative-travel-journal',
            false,
            dirname(CTJ_PLUGIN_BASENAME) . '/languages'
        );
    }

    private function registerHooks(): void {
        add_action('init', [$this, 'onInit']);
    }

    public function onInit(): void {
        // Register custom post types, taxonomies, etc.
    }

    public function getVersion(): string {
        return CTJ_VERSION;
    }
}
