# Installation

## Requirements

- PHP ^8.4
- [`gingerminds/laravel-core`](https://github.com/gingerminds/laravel-core) ^2.8
- [`gingerminds/laravel-multisite`](https://github.com/gingerminds/laravel-multisite) ^2.2 — `Menu` and `MenuItem` are site-scoped (`SiteContextedModelTrait`) and `MenuItem` is translatable (`TranslatableModelTrait`), so a working multisite setup (at least one `Site`) is required for menus to resolve correctly.

## 1. Require the package

```bash
composer require gingerminds/laravel-cms
```

The service provider (`LaravelCmsServiceProvider`) is auto-discovered — nothing to add to `config/app.php` or `bootstrap/providers.php`. It also registers an internal `LaravelCmsAuthServiceProvider` for you.

## 2. Register the package's models with API Platform

`Menu` and `MenuItem` are exposed as API resources via `#[ApiResource]` attributes. Add the package's `Models` directory to `config/api-platform.php`:

```php
'resources' => [
    // ...your existing entries
    base_path('vendor/gingerminds/laravel-cms/src/Models'),
],
```

## 3. Publish the config

```bash
php artisan vendor:publish --tag=gingerminds-cms-config
```

Creates `config/gingerminds-cms.php` — see [Configuration](./Configuration.md).

## 4. Run the migrations

```bash
php artisan migrate
```

Creates `menus`, `menu_items`, `menu_item_translations`, `pages`, `page_translations`, `page_categories`, `page_category_translations`, and `page_urls` (a precomputed per-language URL index kept in sync automatically — see [Pages](./Pages.md#pageurl)). Note there is no `menu_translations` table — only `MenuItem` is translatable, `Menu` itself is not.

## 5. Publish the JS assets

This package ships no SCSS, only the WYSIWYG editor's JS. Publish it the same way as `laravel-media-manager`'s assets:

```bash
php artisan vendor:publish --tag=gingerminds-assets
```

This copies `resources/js` → `resources/js/vendor/gingerminds-cms`. Then import it from your own entry point:

```js
// resources/js/app.js
import './vendor/gingerminds-cms/app.js';
```

As with any published assets, this is a plain file copy (not a symlink) — re-running `vendor:publish` overwrites local edits, and updating the package requires re-publishing and rebuilding to pick up JS changes.

> Note: `--tag=gingerminds-assets` is shared with `laravel-media-manager` — running it publishes both packages' assets (JS and SCSS) in one go if both are installed.

> **Cross-package styling dependency:** the "choose a category" tree modal (pages *and* page categories) reuses `laravel-media-manager`'s `.category-tree`/`.category-tree-item`/`.toggle-icon` SCSS classes rather than shipping its own — `laravel-media-manager` must be installed and its SCSS imported for that modal to render correctly.

### Required npm packages

The WYSIWYG editor is built on [TipTap](https://tiptap.dev/). Install its dependencies (and SortableJS, used by the menu item drag-and-drop tree — see [Menus](./Menus.md)):

```bash
npm install @tiptap/core@^2.11.5 @tiptap/starter-kit@^2.11.5 @tiptap/extension-link@^2.11.5 @tiptap/extension-underline@^2.11.5 @tiptap/extension-table@^2.11.5 @tiptap/extension-table-row@^2.11.5 @tiptap/extension-table-header@^2.11.5 @tiptap/extension-table-cell@^2.11.5 sortablejs@^1.15.7
```

## 6. (Optional) Seed permissions

```php
$this->call(\Gingerminds\LaravelCms\Database\Seeders\PermissionSeeder::class);
```

Creates `view/edit/delete menus`, `view/edit/delete menu_items`, and `view/edit/delete page_categories` (guard `web`). Not run automatically.

> **Known gap:** `PagePolicy` checks `'edit pages'`/`'delete pages'`, but this seeder never actually creates a `pages` permission set — grant those manually (or extend the seeder) until it's added.

## What you get out of the box

- Admin CRUD for menus and a drag-and-drop tree UI for menu items, at `{admin_prefix}/menus` and `{admin_prefix}/menus/{menu}/items`.
- A WYSIWYG editor Blade component — see [Components](./Components.md).
- A read-only API for menus (with their nested, active items) — see [API](./API.md).
