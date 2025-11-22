# Collaborative Travel Journal WordPress Plugin

A modern WordPress plugin for collaborative travel journaling.

## Requirements

- PHP 8.3 or higher
- WordPress 6.4 or higher
- Composer

## Installation

### For Development

1. Clone this repository:
   ```bash
   git clone https://github.com/collegeman/collaborative-travel-journal-wp-plugin.git
   cd collaborative-travel-journal-wp-plugin
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Symlink or copy the plugin directory to your WordPress plugins folder:
   ```bash
   ln -s $(pwd) /path/to/wordpress/wp-content/plugins/collaborative-travel-journal
   ```

4. Activate the plugin in WordPress admin.

### For Bedrock WordPress

Add to your Bedrock project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/collegeman/collaborative-travel-journal-wp-plugin"
    }
  ],
  "require": {
    "collegeman/collaborative-travel-journal-wp-plugin": "dev-main"
  }
}
```

Then run:
```bash
composer install
```

## Features

- Modern PHP 8.3+ codebase with strict types
- PSR-4 autoloading
- Composer dependency management with Mozart for dependency scoping
- Namespace isolation to prevent conflicts
- Clean architecture with separation of concerns

## Development

### Adding Dependencies

When adding PHP dependencies via Composer, they will automatically be scoped/prefixed using Mozart to prevent conflicts with other plugins:

```bash
composer require vendor/package
```

Mozart will automatically:
- Prefix the dependency namespace with `Collegeman\CollaborativeTravelJournal\Vendor\`
- Move scoped files to `src/Vendor/`
- Prevent version conflicts with other plugins

### Running Tests

```bash
composer test
```

## License

GPL-2.0-or-later

## Author

Aaron Collegeman
- Email: aaroncollegeman@gmail.com
- GitHub: [@collegeman](https://github.com/collegeman)
