# API

## Admin (web) routes

Registered under `web` + `gingerminds-core.auth`, prefixed with your `admin_prefix`, named `gingerminds-cms.`:

| Route | Purpose |
|---|---|
| `{admin_prefix}/menus` (full resource, no `show`) | Menu CRUD |
| `GET {admin_prefix}/menus/{menu}/items` | Menu item tree |
| `GET {admin_prefix}/menus/{menu}/items/create` | Create form |
| `GET {admin_prefix}/menus/{menu}/items/{menuItem}/edit` | Edit form |
| `POST {admin_prefix}/menus/{menu}/items` | Store |
| `PATCH {admin_prefix}/menus/{menu}/items/{menuItem}` | Update |
| `DELETE {admin_prefix}/menus/{menu}/items/{menuItem}` | Delete |
| `POST {admin_prefix}/menus/{menu}/items/reorder` | Reorder (see [Menus](./Menus.md#drag-and-drop-reordering)) |
| `{admin_prefix}/pages` (full resource, no `show`) | Page CRUD — see [Pages](./Pages.md#admin-screens) for the "choose category" modal flow |
| `{admin_prefix}/page-categories` (full resource, no `show`) | Page category tree CRUD — URI hyphenated, translation keys stay `page_categories` |

## API Platform resources

### Menu

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/menus` | Paginated collection. |
| `GET` | `/api/menus/{id}` | A single menu. |

Fields: `id`, `code`, `active_items` — the menu's active, root-level items with their `children` nested recursively.

### MenuItem

Declares `#[ApiResource(operations: [])]` — **no directly callable endpoints**. It only appears nested inside a `Menu`'s `active_items`, with fields `id`, `code`, `name`, `url`, `description`, `children`, `is_active`, `is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow`, `position`.

### Page

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/pages` | Paginated collection. Filterable — see [Pages](./Pages.md#filters) — with **no automatic status restriction**: pass `?filters[status][]=published` explicitly to exclude drafts. |
| `GET` | `/api/pages/{id}` | A single page, by id. Same lack of status restriction as the collection. |
| `GET` | `/api/pages/by-slug/{slug}` | Public, path-based lookup — `{slug}` actually captures a full path (e.g. `news/my-article`, or `news` for a category "home page", or empty for the site's own home page — see [Pages](./Pages.md#home-pages-blank-slug)). **Published-only**: a matching-but-unpublished page 404s exactly like a non-existent one. Backed by the `page_urls` index, not a live tree walk. |
| `GET` | `/api/pages/by-code/{code}` | Public, `code`-based lookup — `code` is a plain, non-translatable, per-site-unique column, unrelated to the URL. Same published-only rule as `by-slug`. Useful for addressing a specific page directly (e.g. the home page) regardless of its current slug/category. |

Fields (`GROUP_READ` unless noted `GROUP_LIST` too): `id`, `code` (list), `title` (list), `hook` (list), `content`, `slug`, `status` (serialized as `status_label`, list), `main_visual` (`main_visual_file`), `thumbnail` (list), `switch_lang` (list — every language this page is actually translated into, mapped to its full public path, not just its raw slug — `{iso: path}`, e.g. `{"en": "sale-tools/cgv", "fr": "outils-de-vente/cgv"}`).

`PageCategory` itself declares `#[ApiResource(operations: [])]` and is never exposed directly — a page's category is only reflected in its own resolved URL (`by-slug`) or filterable by id on the collection (`?filters[category_id]=`).
