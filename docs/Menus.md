# Menus & Menu Items

## Models

### `Menu`

- Site-scoped via `laravel-multisite`'s `SiteContextedModelTrait` — a menu belongs to [the current site](../../gingerminds-laravel-multisite/docs/Context.md).
- Fillable: `code`, `site_id`.
- `items(): HasMany` — every item, ordered by `position`.
- `activeItems(): HasMany` — root-level (`parent_id` null), active items only, with `children` eager-loaded recursively. This is what the read API exposes (see [API](./API.md)).

### `MenuItem`

- Also site-scoped, and translatable via `TranslatableModelTrait` (`protected string $translationModel = MenuItemTranslation::class;`) — see the [multisite Traits docs](../../gingerminds-laravel-multisite/docs/Traits.md) for how translation resolution works.
- Fillable: `code`, `menu_id`, `site_id`, `parent_id`, `is_active`, `is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow`, `position`.
- Self-referencing tree: `parent(): BelongsTo`, `children(): HasMany` (active only, recursive, used for the public/front tree), `adminChildren(): HasMany` (all items regardless of `is_active`, recursive, used for the admin tree UI so inactive items stay manageable).
- `name`, `url`, `description` are accessors proxying to `currentTranslation` (empty string if none).

### `MenuItemTranslation`

Uses multisite's `TranslationModelTrait`. Fillable: `name`, `url`, `description`, `language_id`.

## Admin screens

- `{admin_prefix}/menus` — menu CRUD.
- `{admin_prefix}/menus/{menu}/items` — a drag-and-drop tree of that menu's items (create/edit/delete + reorder). The create/edit form has two tabs: "General" (code, active flag, position, parent, target/referrer/opener/follow link options) and "Translations" (per-language name, URL, and a [WYSIWYG](./Components.md) description field, using multisite's `form.inputs.translations` component).

## Drag-and-drop reordering

The tree UI itself is generic: it's `laravel-core`'s shared `layouts.crud.list-tree` layout (SortableJS-based; see core's own sorting docs), which this package's `menu_items.index` view extends. CMS only wires up the endpoint:

- The index view sets `window.treeReorderUrl` to `POST {admin_prefix}/menus/{menu}/items/reorder` (route `gingerminds-cms.menu_items.reorder`).
- Dragging an item within a level POSTs:
  ```json
  { "ids": [3, 1, 2], "parent_id": 5 }
  ```
  (`ids` is that level's items in their new order; `parent_id` is `null` at the root level.)
- `MenuItemController::reorder()` updates each listed item's `position` to its index in `ids`.

**Note:** `parent_id` is validated (must be a valid `menu_items.id` or `null`) but is **not currently persisted** by the reorder endpoint — only `position` is updated. In practice this means dragging an item to reorder it *within* its current level works as expected, but dragging it *into a different level* (re-parenting) will not actually move it server-side, even though the UI may visually allow the drag.
