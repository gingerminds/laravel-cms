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

## API Platform resources

### Menu

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/menus` | Paginated collection. |
| `GET` | `/api/menus/{id}` | A single menu. |

Fields: `id`, `code`, `active_items` — the menu's active, root-level items with their `children` nested recursively.

### MenuItem

Declares `#[ApiResource(operations: [])]` — **no directly callable endpoints**. It only appears nested inside a `Menu`'s `active_items`, with fields `id`, `code`, `name`, `url`, `description`, `children`, `is_active`, `is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow`, `position`.
