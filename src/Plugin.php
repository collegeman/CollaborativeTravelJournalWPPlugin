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
        Routes::register();
    }

    public function onInit(): void {
        $this->registerPostTypes();
        $this->registerTaxonomies();
    }

    private function registerPostTypes(): void {
        PostTypes\Trip::register();
        PostTypes\Stop::register();
        PostTypes\Entry::register();
        PostTypes\Expense::register();
        PostTypes\Song::register();
    }

    private function registerTaxonomies(): void {
        Taxonomies\StopType::register();
        Taxonomies\StopStatus::register();
        Taxonomies\TripStatus::register();
        Taxonomies\ExpenseCategory::register();
    }

    public function getVersion(): string {
        return CTJ_VERSION;
    }
}
