# Pages & Page Categories

## Models

### `Page`

- Site-scoped (`SiteContextedModelTrait`) and translatable (`TranslatableModelTrait`, `protected string $translationModel = PageTranslation::class`).
- Fillable: `code`, `status`, `main_visual_id`, `thumbnail_id`, `published_at`, `archived_at`, `site_id`, `category_id`.
- `status` is a Spatie `ModelStates` state (`StatusState`: `Draft`, `Published`, `Archived`). Status changes go through `$page->status->transitionTo(...)` (not a plain attribute assignment) so allowed-transition validation runs and `SetPublishedAtOnPublish` can stamp `published_at` on the `StateChanged` event.
- `category(): BelongsTo` — **optional**. A page with no category resolves at a bare `/{slug}` with no prefix.
- Accessors proxy to `currentTranslation`: `getTitle()`, `getSlug()`, `getHook()`, `getContent()`, thumbnail/main visual file ids.
- `getFullPath(): string` — this page's full public path in the current-context language: category prefix chain + own slug. Delegates the actual joining to the static helper below. Used by the `by-slug` API route to rebuild its own `@id` when serializing.
- `Page::composePath(string $categoryPath, string $slug): string` — joins a category path and a page's own slug correctly for the two blank cases: a category "home page" (blank slug, non-blank category path) is just the category path with **no trailing slash**, and the site's own home page (both blank) is `''`. Shared between `getFullPath()` and `PageUrlSyncer` so both use byte-for-byte the same formula.
- `code` is a plain, non-translatable, per-site-unique identifier (unrelated to the URL) — see `GET /pages/by-code/{code}` in [API](./API.md).

### `PageTranslation`

Fillable: `title`, `slug`, `hook`, `content`, `main_visual_id`, `thumbnail_id`, `language_id`, `site_id`.

- `slug` can be **blank** (stored as `NULL`, never `''` — see "Home pages" below). It's never `required` in the admin form, on any language, not just non-default ones.
- `slug` is otherwise unique per `(site_id, language_id)` — **globally**, regardless of category. Two pages in different categories can never share a slug, even though their full public paths (category prefix + slug) differ.

### `PageCategory`

- Site-scoped, translatable (`PageCategoryTranslation`), self-referencing tree via `parent(): BelongsTo` / `children(): HasMany` / `adminChildren(): HasMany` (recursive eager-load, mirrors `MenuItem`/`MediaCategory`).
- Fillable: `code`, `parent_id`, `site_id`, `is_unique`.
- Not an API resource (`#[ApiResource(operations: [])]`) — purely an admin/back-office concept, never exposed directly. A page's category shows up denormalized into its own URL instead (see [API](./API.md)).
- `pages(): HasMany` — every `Page` directly attached (not descendants).
- `is_unique` (bool): when true, **at most one page** may ever reference this category — enforced in `PageRequest` (a friendly validation error, `pages.message.is_unique_taken`), not just at save time.
- `getFullPathAttribute()` / `getFullPathForLanguage(?int $languageId = null)`: walks from this category up through every ancestor, collecting each one's non-blank prefix (root to leaf), joined with `/`. Without a language argument, uses the request's current language (`currentTranslation`); with one, resolves that exact language's translation **falling back to `currentTranslation`** if that category was never translated into it — see "Incomplete category translations" below.

### `PageCategoryTranslation`

Fillable: `name`, `prefix`, `language_id`, `site_id`.

- `prefix` can be **blank** (stored as `NULL`) — it means "this category contributes no URL segment". Never `required`, on any language.
- Uniqueness is scoped to **sibling categories** (same `parent_id`, same site) rather than checked globally, and only when non-blank: two categories in different branches of the tree can share a prefix (or both leave it blank) since their final full path still differs thanks to their distinct ancestors — only same-level siblings sharing an identical, non-blank prefix would actually collide. See `PageCategoryRequest::rules()`.

### `PageUrl`

A precomputed, per-language index of every page's actual public URL — `page_id`, `language_id`, `site_id`, `path`. Purely an internal read-side cache: no API resource, no admin CRUD, never edited directly.

- Unique on `(page_id, language_id)` and on `(site_id, language_id, path)`.
- Unlike `slug`/`prefix` above, `path` is **`NOT NULL`**, stored as `''` for a home page rather than `NULL` — the unique index must actually enforce "at most one page can resolve to this exact URL", including the blank/home case, which a `NULL`-based exemption would silently defeat.
- Kept in sync by `Gingerminds\LaravelCms\Services\Page\PageUrlSyncer`, called from three places (a page's own slug/category isn't the only thing that can change its URL — any ancestor category's prefix or parent can too):
  - `PageRepository::update()` → `syncPage($page)` after every page save.
  - `PageCategoryRepository::update()` → `syncCategorySubtree($category)` after every category save (its prefix or parent may have changed, cascading to every page anywhere under it in the tree, not just ones directly attached to it).
  - `PageCategoryController::destroy()` → `collectAffectedPageIds($category)` **before** deleting (a category delete re-parents its children to root and nulls `category_id` on its own direct pages, at which point the subtree can no longer be walked), then `syncPagesByIds($ids)` after.
  - Deleting a `Page` needs no explicit handling — `page_urls.page_id` is `cascadeOnDelete`.
- `PageUrlSyncer` itself is a thin subclass of `Gingerminds\LaravelCms\Services\Url\AbstractUrlSyncer` — see [Generic URL syncing](#generic-url-syncing-abstracturlsyncer) below. Only the category-subtree helpers (`syncCategorySubtree`, `collectAffectedPageIds`, `syncPagesByIds`) and the four Page-specific answers (URL model, foreign key, `Published`-only gating, category-prefixed path, `title`-based eligibility) still live in `PageUrlSyncer` itself; the actual `updateOrCreate` + stale-language pruning loop is inherited, not duplicated.
- The create/edit form always submits every one of the site's languages, so a `PageTranslation` row exists per language regardless of whether anyone has actually translated it — a blank `slug` alone can't tell "deliberately the category's home page in this language" apart from "nobody's gotten to this language yet". `PageUrlSyncer` (and the identical check in `PageRequest::withValidator()`) treats a **blank `title`** as the signal for the latter and skips that language entirely — no `page_urls` row is written (and any stale one is deleted) — while a filled-in `title` with a blank `slug` still resolves to the category's path, exactly like the default-language case. Example: `en.slug = "cgv"` under a category prefixed `sale-tools` → `sale-tools/cgv`; `fr.title` filled in but `fr.slug` left blank under a category prefixed `outils-de-vente` → `outils-de-vente` (deliberate FR home page for that category); a language with no `title` at all gets no `page_urls` row.
- `PageRepository::findPublishedByPath()` reads straight from this table (scoped to the resolved site, and to the request's current/fallback language in that preference order — matching `currentTranslation`'s own resolution — then `status = Published`) instead of walking the category tree and comparing candidates on every request.
- **Published-only, not just translated-only:** `PageUrlSyncer::syncPage()` writes rows only while the page's `status` is `Published` — a `Draft` or `Archived` page has no public URL to look up by, so every one of its rows is deleted (regardless of language) the moment it leaves that state. The slug itself is untouched in `page_translations`; only its `page_urls` entry disappears, so re-publishing recomputes it from the still-intact slug, and a draft/archived page's slug never blocks another page from taking that same path in the meantime. This makes the `whereHas('page', ...->where('status', Published::class))` guard in `findPublishedByPath()` belt-and-suspenders rather than load-bearing — kept anyway in case a row is ever left behind by a code path that bypasses `PageUrlSyncer`.

## Generic URL syncing (`AbstractUrlSyncer`)

`Gingerminds\LaravelCms\Services\Url\AbstractUrlSyncer` is the resource-agnostic engine extracted out of what used to be `PageUrlSyncer`'s only implementation: given an owner model with translations, it recomputes one `*_urls` row per language and deletes the ones that no longer qualify. It exists so a second URL-able resource (e.g. a consuming project's `Product`) doesn't have to reimplement that bookkeeping loop, only answer what's specific to it.

A concrete syncer extends it and implements:

- `urlModelClass(): string` — the `*_urls` model to write to (`PageUrl::class`, or a project's own `ProductUrl::class`).
- `ownerForeignKey(): string` — the column on that table pointing back to the owner (`page_id`, `product_id`, ...).
- `isPublishable(Model $owner): bool` — whether the owner should have any URL at all right now. `PageUrlSyncer` checks `$owner->status instanceof Published`; a resource with no draft/published concept just returns `true` unconditionally.
- `isEligible(Model $translation): bool` — whether one translation is "complete enough" for a row. `PageUrlSyncer` requires a non-empty `title`.
- `resolvePath(Model $owner, Model $translation): string` — composes the final `path`. `PageUrlSyncer` folds in the category prefix chain (`Page::composePath()`); a resource with no hierarchy can just return the translation's own slug.
- `eagerLoadRelations(): array` (optional, defaults to `[]`) — extra relations to eager-load before iterating translations, beyond `translations` itself (`PageUrlSyncer` adds `category`).
- `extraAttributes(Model $owner): array` (optional) — extra columns to store on every row alongside `path`. Defaults to denormalizing `$owner->getAttribute('site_id')`, which every URL-able model so far has needed.

The public entry point is `sync(Model $owner): void`; each concrete syncer typically wraps it in its own type-hinted method (`PageUrlSyncer::syncPage(Page $page)`) so callers don't have to pass a bare `Model`. Property access inside the abstract class goes through `getAttribute()`/`getKey()` rather than `->site_id`/`->id` directly, since PHPStan can't resolve dynamic properties through the generic `TOwner`/`TTranslation` template types otherwise.

## Shared traits for Page-like models

`Page` isn't the only resource shaped like it — a consuming project may need its own Page-like model (e.g. `App\Models\Event\Event` in the yanmar-extranet project) that doesn't extend `Page` (API Platform doesn't inherit `#[ApiResource]`/`#[ApiProperty]` attributes across a class hierarchy, and the two resources often diverge on enough fields/relations that inheritance would fight itself). Instead, the behavior `Page` needs is split into small, composable traits under `Gingerminds\LaravelCms\Models\Trait`, used by `Page` itself and available as-is to any other model with the same shape:

- **`HasMainVisualAndThumbnailTrait`** — `mainVisual()`/`thumbnail()` `BelongsTo` relations plus `getMainVisualFileAttribute()`/`getThumbnailFileAttribute()` accessors. Resolution order: the current translation's own `main_visual_id`/`thumbnail_id` if set, otherwise the owner's own relation — lets a translation override the owner's default image per-language.
- **`HasResolvedContentTrait`** — `getContentAttribute()`, resolving `file`/`media` type block fields in the translation's raw stored `content` json into richer objects (via `ContentReferenceResolver`) before serialization. Only fit for a translation model whose `content` needs no special per-block-type handling; a project with bespoke block types (e.g. a `media_list` block) should keep its own `getContentAttribute()` instead.
- **`HasStatusLabelTrait`** — `getStatusLabelAttribute()`, exposing the current `StatusState`'s short `code()`. Requires the model to cast `status` to a `Spatie\ModelStates\State` (typically `StatusState`, see below).

All three assume the using model is translatable (`TranslatableModelTrait`, exposing `currentTranslation`) and, for the first two, that its translation model exposes the matching columns/relations (`main_visual_id`/`mainVisual`, `thumbnail_id`/`thumbnail`, `content`).

### Shared status transitions (`AbstractPageStatusTransition`, `StatusState`)

`Gingerminds\LaravelCms\State\Page\StatusState` and its transitions (`Gingerminds\LaravelCms\State\Page\Transitions\*`, e.g. `DraftToPublished`) are named after `Page` for historical reasons but are generic on purpose: any model that casts a `status` column to `StatusState` — `Page` and a project's own `Event` alike — can reuse the exact same state machine. `AbstractPageStatusTransition` only does plain Eloquent attribute get/set + `save()`, so its constructor takes a bare `Illuminate\Database\Eloquent\Model`, not a `Page`.

### PHPStan markers (`Models\Contract\*`, `State\Page\Contract\HasStatusPropertyContract`)

Widening the traits/transition above to a generic `Model` means PHPStan/Larastan can no longer prove the dynamic properties they access (`main_visual_id`, `mainVisual`, `content`, `status`, ...) actually exist — a bare `Model` has none of them statically. Three small classes exist purely to fix this, never extended or instantiated at runtime:

- `Gingerminds\LaravelCms\Models\Contract\HasFileFieldsContract` — for `HasMainVisualAndThumbnailTrait`'s local `$translation`.
- `Gingerminds\LaravelCms\Models\Contract\HasContentFieldContract` — for `HasResolvedContentTrait`'s local `$translation`.
- `Gingerminds\LaravelCms\State\Page\Contract\HasStatusPropertyContract` — for `AbstractPageStatusTransition`'s local `$page`.

Each declares the expected shape via `@property`/`@property-read` tags and is cast to locally (`/** @var HasFileFieldsContract|null $translation */`) right where the generic `Model` needs narrowing — the same technique `Page` itself already uses to cast `currentTranslation` to `PageTranslation`. They're deliberately **abstract classes extending `Model`**, not plain interfaces: Larastan's `ModelPropertyExtension` only resolves `@property` docblock tags against an actual `Model`-descended class reflection, not against an interface intersected with `Model` — a plain interface here silently kept reporting "undefined property" even though the interface declared it. `PageTranslation`, `App\Models\Event\EventTranslation`, `Page`, and `App\Models\Event\Event` all already declare the exact same shape via their own class docblocks, so nothing about their real behavior changes.

## Home pages (blank slug)

A page's own slug being blank is a legitimate, deliberate state — it's the "home page" for whatever category it's under, or for the site itself if it also has no category. Visiting exactly that category's path (or the bare site root), with no further segment, resolves to it.

- Enforced strictly: `page_urls`'s unique index means **at most one page** can ever resolve to a given blank path per `(site, language)` — including the true site root. Saving a page whose slug (combined with its category, or lack of one) would collide with another page's already-resolved path fails validation (`pages.message.url_taken`) instead of a raw SQL error.
- Contrast with category `prefix` and page `slug` *considered on their own*: those are deliberately **not** scope-checked when blank (a blank prefix/slug alone is always allowed, by design — see the sibling-scoping note above). It's the final *computed path* in `page_urls` that's strictly unique, not the individual fields that feed into it.

### Known caveat

Nothing currently stops an admin from typing a literal `/` into a plain slug field. If that string happens to match another page's category-prefix-plus-slug combination, the resulting path collision is still caught (by the `page_urls` check above) — but as a generic "already taken" message rather than one that explains *why* (a slash inside a slug rather than an actual duplicate page).

## Incomplete category translations

A category's `prefix` can be filled in for some languages and not others. `PageCategory::getFullPathForLanguage($languageId)` falls back to that category's own `currentTranslation` when the exact requested language was never translated, rather than silently dropping that ancestor's segment for just that one language — a single untranslated category several levels up the tree would otherwise make an entire branch of pages 404 for that one language instead of resolving with a (possibly wrong-language, but present) prefix.

## Admin screens

- `{admin_prefix}/pages` — full resource (no `show`). "Add page" opens a **choose category** modal first (tree UI — folder icons, expand/collapse, `.category-tree`/`.category-tree-item` styling shared with `laravel-media-manager`'s own category picker) with a "None" entry, then redirects to `create?category_id=X` (or plain `create` for "None"). The category itself stays a normal, changeable `<select>` on the actual create/edit form (with "— None —"), not locked in by the modal choice.
  - Translation tab: per-language `title` (required on the default language only) and `slug` (never required, any language), the latter shown as an input-group with the resolved category path as a read-only prefix.
- `{admin_prefix}/page-categories` — full resource (no `show`), URI/route names hyphenated (`page-categories`) even though translation keys stay `page_categories` (see the comment in `routes/web.php`). Tree list UI (mirrors `MenuItem`'s), each row showing name + full path. Create/edit form: `code`, `is_unique` toggle, `parent_id` select (excludes self/descendants), and a translation tab (`name`, `prefix` — shown as an input-group with the *parent's* resolved path as prefix and a trailing `/` suffix).

## Filters

`Page::getFilters()` (works identically on the admin list and on the public `GetCollection` API endpoint — both go through the same `FilterHandlerRegistry`):

| Filter | Type | Notes |
|---|---|---|
| `published_at` | `date` | Range (`from`/`to`). |
| `status` | `select-state`, multiple | Draft / Published / Archived. |
| `category_id` | `select-model` (`PageCategory::class`) | E.g. resolving a "home" page by listing pages under a dedicated `is_unique` category and taking the first (only) result, instead of a slug-based lookup. |

**Note:** the public `/pages` collection and item-by-id endpoints apply **no automatic status restriction** — a draft is returned exactly like a published page unless the client explicitly passes `?filters[status][]=published`. This is deliberate (the front is expected to ask for what it wants) and differs from the dedicated `by-slug`/`by-code` routes, which are hard-coded to published-only (see [API](./API.md)).
