# Laravel CMS

Menus, pages, and a WYSIWYG editor for Laravel projects built on `gingerminds/laravel-core` and `gingerminds/laravel-multisite`. It provides:

- `Menu` and `MenuItem` models — site-scoped, with translatable menu items (name, URL, description per language).
- An admin CRUD for menus with a drag-and-drop tree UI for reordering items.
- `Page` and `PageCategory` models — site-scoped, translatable, with nested categories, optional page-category assignment, and a precomputed per-language URL index (`PageUrl`) kept automatically in sync.
- An admin CRUD for pages and page categories, including a "choose category" tree modal shared with `laravel-media-manager`'s look.
- A TipTap-based WYSIWYG editor Blade component, with configurable toolbar presets.
- A read-only API exposing menus and their active item tree, plus pages (by id, by path, or by code).

## Requirements

- PHP ^8.4
- `gingerminds/laravel-core` ^3.0
- `gingerminds/laravel-multisite` ^2.2

## Quick start

```bash
composer require gingerminds/laravel-cms
php artisan vendor:publish --tag=gingerminds-cms-config
php artisan vendor:publish --tag=gingerminds-assets
php artisan migrate
```

Then register the package's models with API Platform (see [Installation](docs/Installation.md#2-register-the-packages-models-with-api-platform)) and install the required npm packages (TipTap, SortableJS).

## Documentation

- [Installation](docs/Installation.md)
- [Configuration](docs/Configuration.md)
- [Components](docs/Components.md) — the WYSIWYG editor
- [Menus](docs/Menus.md) — models, admin screens, drag-and-drop reordering
- [Pages](docs/Pages.md) — models, home-page semantics, admin screens, filters
- [API](docs/API.md) — admin routes and API Platform endpoints
