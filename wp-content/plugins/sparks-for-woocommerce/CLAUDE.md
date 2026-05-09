# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Sparks for WooCommerce is a WordPress plugin that adds 8 conversion-boosting features to WooCommerce stores: product comparisons, variation swatches, wishlists, tabs manager, advanced product reviews, quick view, custom thank you pages, and multi-announcement bars.

## Architecture

### Core Structure

The plugin follows a modular architecture with clear separation of concerns:

- **Main Entry Point**: `sparks-for-woocommerce.php` - Defines constants, loads autoloader, and initializes the `Codeinwp\Sparks\Core\Loader` class
- **Core Namespace**: `includes/Core/` - Contains the plugin's core functionality:
  - `Loader.php` - Initializes all modules and handles module lifecycle (lines 61-158)
  - `Dashboard.php` - Manages the admin dashboard interface
  - `License.php` - Handles license validation and status
  - `Compatibility_Manager.php` - Checks plugin/theme compatibility requirements
  - `Dynamic_Styles.php` - Centralized dynamic CSS management

### Module System

All feature modules extend `Base_Module` (`includes/Modules/Base_Module.php`) and must implement:
- `get_name()` - Human readable module name
- `init()` - Module initialization logic
- `should_load()` - Determines if module should run
- `get_dashboard_description()` - Dashboard display text
- `register_dynamic_styles()` - Module-specific dynamic CSS

Available modules (registered in `Loader.php:87-97`):
- Common - Shared utilities across modules
- Comparison_Table - Product comparison feature
- Advanced_Product_Review - Enhanced review system
- Tab_Manager - Product tabs customization
- Cart_Notices - Shopping cart notification bars
- Custom_Thank_You - Customizable order confirmation pages
- Variation_Swatches - Visual product variation selector
- Wish_List - Customer wishlist functionality
- Quick_View - Product quick preview modal

**Important**: Modules only initialize if the license status is 'valid' (see `Loader.php:110-112`). The licensing system checks either:
1. WooCommerce.com header (for marketplace distribution)
2. License data stored in WordPress options

### Asset Structure

Assets are organized by module under `includes/assets/{module_name}/`:
- `js/src/` - JavaScript source files (React/vanilla JS)
- `js/build/` - Compiled JavaScript outputs (via wp-scripts)
- `css/` - Compiled CSS files
- `scss/` - SCSS source files
- `react/` - React-specific components (some modules)

## Development Commands

### JavaScript Development

Build all JavaScript assets:
```bash
npm run build
```

Build specific module (faster for focused development):
```bash
npm run build:dashboard
npm run build:variation-swatches
npm run build:comparison-table
# etc. - see package.json scripts for all options
```

Watch mode for development (auto-rebuilds on changes):
```bash
npm run dev  # Watches all modules in parallel
```

Watch specific module with hot reload:
```bash
npm run watch:dashboard  # Includes hot reload with --hot flag
npm run watch:variation-swatches
# etc. - see package.json for all watch commands
```

**Note**: The dashboard module supports hot reload (`--allowed-hosts all` flag) for faster development.

### CSS Development

Build all SCSS files:
```bash
npm run build:css
```

Watch SCSS files (auto-rebuilds):
```bash
npm run watch:css
```

The build process uses Grunt to compile SCSS files defined in `Gruntfile.js` (SASS_CSS_MAP at line 4).

### PHP Development

Install production dependencies:
```bash
composer install --no-dev
```

Install development dependencies (includes testing/linting tools):
```bash
COMPOSER=composer-dev.json composer install
```

**Code Standards** - Uses WordPress Coding Standards with Themeisle ruleset:
```bash
npm run dev:phpcs          # Check code standards
npm run dev:phpcbf         # Auto-fix code standards
```

**WooCommerce Standards** - For marketplace submission:
```bash
npm run woo:phpcs          # Check with WooCommerce standards
npm run woo:phpcbf         # Auto-fix with WooCommerce standards
COMPOSER=composer-woo.json composer install  # Install woo-specific deps
```

**Static Analysis**:
```bash
vendor/bin/phpstan analyze  # Run PHPStan (config: phpstan.neon)
```

**GrumPHP** - Pre-commit hooks:
```bash
npm run dev:grumphp  # Run GrumPHP tasks manually
```

### Testing

Run PHPUnit tests:
```bash
./vendor/bin/phpunit                          # All tests
./vendor/bin/phpunit tests/php/unit/class-api-test.php  # Specific test
```

Test configuration: `phpunit-unit.xml`

### Release Process

This project uses semantic-release with conventional changelog commits:

- `release: <description>` → Patch release (1.0.x)
- `release(minor): <description>` → Minor release (1.x.0)
- `release(major): <description>` → Major release (x.0.0)

Configuration: `.releaserc.yml`

Create distribution package:
```bash
npm run dist  # Runs bin/dist.sh
```

## Important Implementation Details

### Adding a New Module

1. Create module class extending `Base_Module` in `includes/Modules/YourModule/`
2. Define required properties: `$module_slug`, `$setting_prefix`, `$default_status`
3. Implement abstract methods: `get_name()`, `init()`, `should_load()`, `get_dashboard_description()`, `register_dynamic_styles()`
4. Register module in `Loader.php:87-97` array
5. Create assets structure: `includes/assets/your_module/js/src/app.js`
6. Add build scripts to `package.json` (build:your-module, watch:your-module)
7. If using SCSS, add mapping to `Gruntfile.js` SASS_CSS_MAP

### Dynamic Styles System

Modules register dynamic CSS through `Dynamic_Styles::get_instance()->push($selector, $rules)`. This centralizes all dynamic CSS generation and outputs it in a single `<style>` tag. Call this in `register_dynamic_styles()` or `register_dynamic_theme_styles()`.

### Theme Compatibility

Theme-specific adaptations are handled through `includes/Core/Compatibility/` classes. The current theme is detected and module styles can be customized per-theme through compatibility classes.

### Autoloading

PSR-4 autoloading maps `Codeinwp\Sparks\` → `includes/` (see composer.json). The plugin also autoloads utility files:
- `includes/utilities/core-wrappers.php`
- `includes/utilities/globals.php`
- `includes/utilities/sdk-utils.php`

### Migration System

`includes/Migrations/` contains migration classes (e.g., from Neve theme). Migrations run **before** module initialization in `Loader.php:72` to prevent option conflicts.

## Text Domain

Use `sparks-for-woocommerce` for all i18n functions. PHPCS enforces this (see `phpcs.xml:22-28`).

## Security Notes

- Always escape output using WordPress functions (`esc_html`, `esc_attr`, `wp_kses`)
- Sanitize input using WordPress sanitization functions (`sanitize_text_field`, etc.)
- PHPCS configuration includes `WordPress.Security.EscapeOutput` and `WordPress.Security.ValidatedSanitizedInput` rules
- Custom sanitizing function: `wc_clean` (whitelisted in phpcs.xml:29-33)
