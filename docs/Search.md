# Search

Cross-content public search: a single denormalized `search_index` table fed
from every content type registered in `config('gingerminds-cms.search_resources')`
(`Page` out of the box; a consuming project registers its own types — News,
Event, Product, ...), exposed as two read-only API Platform collections.

**Not** the same thing as `Gingerminds\LaravelCore\Models\SearchableModelInterface`
(`getSearchableFields()`), which only drives the single-type `LIKE` filter
behind an admin list's free-text search box (and the equivalent
`filters[search]` on a resource's own collection, e.g. `/api/pages?filters[search]=...`).
That mechanism is untouched by any of this — it still works exactly as
before, on every model that already implements it.

## Why a separate table, not a live cross-model query

Pagination and free-text search across several unrelated tables (`pages`,
`news`, `events`, `products`, ...) at once isn't something a single SQL query
can do. The alternative — running one query per type per request and merging
in PHP — gets more expensive as the number of registered types and the
content volume grow, and complicates pagination (you'd have to over-fetch
every type just to know how to slice a single page).

`search_index` instead holds one row per `(record, language)` pair, kept in
sync by observers/jobs on write (see below), so a search is a single indexed
`SELECT` regardless of how many content types are registered.

## Models

### `SearchIndex`

Eloquent model backing the `search_index` table (`Models/Search/SearchIndex.php`).
Columns: `type` (the resource key, e.g. `page`), `record_id`, `site_id`,
`language_id`, `title`, `url`, `excerpt`, `searchable_text`, `published_at`.

- Implements `SearchableModelInterface` (`getSearchableFields(): ['searchable_text']`)
  and `FilterableModelInterface` (`getFilters(): ['type' => ['type' => 'facet', 'multiple' => true]]`)
  — reusing `laravel-core`'s existing `AbstractRepository`/`AbstractApiProvider`
  machinery (the exact same one every other resource's collection endpoint
  already uses) rather than hand-rolling a parallel query/filter/pagination
  pipeline. `filters[search]` and `filters[type]` on `/api/search-*` work
  through this, not through bespoke code — see
  [Faceted filtering](#faceted-filtering-filterstype) below for what the
  `'facet'` type actually does.
- Site-scoped (`SiteContextedModelTrait`, same as every other multisite
  model).
- **Language-scoped by its own global scope** (`booted()`): every read is
  automatically restricted to the current visitor's language (current,
  falling back to the site's fallback language — see [laravel-multisite's
  Context doc](../../gingerminds-laravel-multisite/docs/Context.md)), so a
  search never returns more than one row per underlying record. Writes go
  through `SearchIndexer`, which explicitly bypasses this scope (and the
  site one) via `withoutGlobalScopes()` — indexing writes rows for *every*
  language/site a record has, not just whichever happens to be "current" in
  whatever context (console, queue, request) triggered the write.
- `$table = 'search_index'` is set explicitly — Eloquent's default
  pluralization would otherwise guess `search_indices`.
- Read-only from the API: `SearchRepository::update()` throws. The only
  writer is `SearchIndexer`.

### `SearchResultItem`

A plain, non-persisted DTO (`Models/Search/SearchResultItem.php`) built by
`SearchResultProvider` for `/search-results` — see [API](#api) below.

## Contracts

### `PubliclySearchableInterface`

```php
interface PubliclySearchableInterface
{
    /**
     * @return iterable<array{
     *     language_id: int,
     *     title: string,
     *     url: string,
     *     excerpt: string|null,
     *     searchable_text: string,
     * }>
     */
    public function getPublicSearchTranslations(): iterable;

    public function isPubliclyVisible(): bool;
}
```

A model registered in `search_resources` (see [below](#search_resources)) must
implement this. `getPublicSearchTranslations()` yields one entry **per
translation the record actually has** — skip incomplete/empty ones (same
rule every model already applies to its own `switch_lang`/URL-sync logic,
e.g. `Page::getSwitchLangAttribute()`) — since a multi-site, multi-language
CMS has no single "the" title/url for a record. `isPubliclyVisible()` is
language-independent: whatever makes the record count as "published" at all
(a status state machine, or unconditionally `true` for a model with no such
concept, e.g. `Product`).

`Page`'s implementation is the reference example
(`Models/Page/Page.php`):

```php
public function getPublicSearchTranslations(): iterable
{
    foreach ($this->translations as $translation) {
        if (null === $translation->title || '' === $translation->title) {
            continue;
        }

        $categoryPath = $this->category?->getFullPathForLanguage($translation->language_id) ?? '';

        yield [
            'language_id' => $translation->language_id,
            'title' => $translation->title,
            'url' => self::composePath($categoryPath, $translation->slug ?? ''),
            'excerpt' => $translation->hook,
            'searchable_text' => $translation->title,
        ];
    }
}

public function isPubliclyVisible(): bool
{
    return $this->status instanceof Published;
}
```

### `ExcludableFromSearchInterface` / `ExcludableFromSearchTrait`

Optional — only implement this on a model that should support being
manually hidden from search, either on the record itself or via a parent
taxonomy (category, range, ...).

```php
interface ExcludableFromSearchInterface
{
    public function isExcludedFromSearch(): bool;
}
```

`ExcludableFromSearchTrait` (`Models/Trait/ExcludableFromSearchTrait.php`)
is the default implementation: excluded if the model's own
`is_hidden_from_search` column is set, **or** if its parent taxonomy
(declared by overriding `searchExclusionParentRelation(): ?string`,
default `null`) is itself excluded. Works for both a single parent
(`BelongsTo`, e.g. `Page->category`) and a multi-valued one (`BelongsToMany`,
e.g. `Product->productRanges`) — for the latter, a record is excluded only
once *every* related taxonomy is hidden; attached to at least one
still-visible one, it stays searchable.

```php
class Page extends Model implements ExcludableFromSearchInterface, /* ... */
{
    use ExcludableFromSearchTrait;

    // ...

    protected function searchExclusionParentRelation(): ?string
    {
        return 'category';
    }
}
```

A model with no taxonomy at all (e.g. `Event`) implements the interface and
uses the trait, but doesn't override `searchExclusionParentRelation()` —
only the item's own flag applies. The taxonomy model itself (`PageCategory`,
`NewsCategory`, `ProductRange`, ...) also implements
`ExcludableFromSearchInterface` + the trait (its own flag, no parent of its
own), independently of whether it implements `PubliclySearchableInterface`
— a category is a filter/taxonomy, not a search result itself, so it
doesn't need to.

Either way, both interfaces require a `boolean` `is_hidden_from_search`
column (see the migration step below).

## Keeping the index in sync

`Services/Search/SearchIndexer.php` is the only thing that ever writes to
`search_index`:

- `indexModel(string $resourceKey, Model $model)` — upserts one row per
  entry from `getPublicSearchTranslations()`, or deletes all of the
  record's rows if `isPubliclyVisible()` is false or
  `isExcludedFromSearch()` is true. Also prunes rows for languages the
  record no longer has a translation for.
- `forget(string $resourceKey, Model $model)` — deletes all of a record's
  rows outright (used on model deletion).
- `reindexType(string $resourceKey)` — chunked (`chunkById(100, ...)`) full
  reindex of one registered type, eager-loading both the type's own
  `EagerLoadableModelInterface::getEagerLoads()` and its `category_relation`
  (not necessarily part of the former — e.g. `Product::getEagerLoads()`
  doesn't include `productRanges` — but needed by
  `ExcludableFromSearchTrait`), so chunking through a whole table doesn't
  trigger a relation query per record.

Two generic observers call into it — attach them to your own models the
same way the package attaches them to its own (see
[Registering a new content type](#registering-a-new-content-type) below):

- **`SearchIndexObserver`** (`Observers/Search/SearchIndexObserver.php`) —
  `saved()`/`deleted()` on a `PubliclySearchableInterface` model. Resolves
  which registered resource key the model belongs to
  (`SearchIndexer::resolveResourceKey()`, matching by class against
  `search_resources`), then calls `indexModel()`/`forget()`.
- **`SearchExclusionCascadeObserver`** (`Observers/Search/SearchExclusionCascadeObserver.php`)
  — `saved()` on a taxonomy model. If `is_hidden_from_search` changed
  (`wasChanged()`), walks every registered `search_resources` entry whose
  `category_relation`'s related class matches this taxonomy's class, and
  dispatches `Jobs\Search\ReindexCategoryItemsJob` for each match — entirely
  derived from the registry, nothing to configure on the taxonomy side.

`ReindexCategoryItemsJob` (`ShouldQueue`, runs on the queue — so an admin
save that hides a category isn't blocked by reindexing everything in it) then
reindexes every item attached to that one category, chunked
(`chunkById(100, ...)`), eager-loading the same way `reindexType()` does.

### `search:reindex` command

```bash
php artisan search:reindex                # every registered type
php artisan search:reindex --type=product # only these (repeatable)
php artisan search:reindex --type=product --type=news
```

Backfills/rebuilds the index cold — run it after a fresh `migrate` (nothing
in `search_index` until then) and whenever you suspect the index has drifted
from the source tables. Unknown `--type` values are reported as warnings and
skipped rather than failing the whole run.

## Faceted filtering (`filters[type]`)

`SearchIndex::getFilters()` declares `type` as a `'facet'` filter (not a plain
`select`) — one flat, multi-select control mixing two granularities:

- a bare resource type (`"news"`) — matches every hit of that type;
- a `"{type}:{categoryId}"` composite (`"news:12"`) — scopes down to one of
  that type's own categories.

Selected values are OR'd together regardless of granularity, e.g.
`filters[type][]=event&filters[type][]=news:12` matches every event OR a
news item in category 12. A type with no `category_relation` (e.g. `Event`)
never gets composite entries under it. The front never builds these values
itself — it only ever replays whatever `filters.type.options[].value` the API
handed back (see [API](#api) below).

### `search_index_categories`

A record's category/categories, denormalized alongside `search_index` so
facet counts can be computed with a single indexed join instead of a query
per type's own taxonomy table. Columns: `search_index_id` (FK to
`search_index`, cascade-deletes with it), `category_id` (no FK — like
`search_index.type`/`record_id`, which table this points at depends on the
owning row's own `type`). Composite primary key `(search_index_id,
category_id)`.

Kept in sync by `SearchIndexer::indexModel()` (its private `syncCategories()`
step): resolves `search_resources.<key>.category_relation` on the model — a
`BelongsTo` (single category) or `BelongsToMany` (several, e.g.
`Product->productRanges`) — and rewrites the rows for every language variant
of that record on each index write. A type with `category_relation => null`
never gets any row here.

### Handler, compute service, store, middleware

- **`Repositories/Filters/Handlers/SearchFacetFilterHandler.php`** — the
  `FilterHandlerInterface` registered under the `'facet'` type
  (`LaravelCmsServiceProvider::register()`, via
  `FilterHandlerRegistry::register('facet', ...)`, `extend()`-based so it
  layers on top of `laravel-core`'s built-in handlers regardless of provider
  boot order). Splits the selected values into bare types vs.
  `type => [categoryIds]`, then applies one OR'd `where`: a plain `whereIn`
  on `type` for the bare ones, a `search_index_categories`-backed `whereIn`
  on `id` for each type's composites.
- **`Services/Search/SearchFilterComputeService.php`** — computes the
  `type` filter's available `options`: one per registered type (if it has
  any hit, or is currently selected), plus one per category under it with a
  hit (or currently selected — kept at `total: 0` rather than disappearing).
  Every count applies every *other* currently active filter and the
  free-text search term, same convention as `PageFilterComputeService`.
  Category labels are resolved from `search_resources.<key>.category_model`
  — **required** for a type that declares a `category_relation`; without it,
  a category option's `label` silently falls back to its raw numeric id.
- **`Services/Search/SearchFilterStore.php`** — a request-scoped singleton
  holding the last-computed filters, written by `SearchResultProvider` and
  read back by `InjectSearchFiltersMiddleware` (bound `singleton()` in
  `LaravelCmsServiceProvider::register()` — without that, each side gets its
  own instance and the middleware only ever sees an empty store).
- **`Http/Middleware/Api/InjectSearchFiltersMiddleware.php`** — registered
  globally onto every api-platform route
  (`config('api-platform.routes.middleware')`); a no-op whenever the store is
  empty (only `/search-results` ever populates it — see [API](#api) below),
  otherwise merges a `filters` key into the JSON response body.

## `search_resources`

```php
// config/gingerminds-cms.php
'search_resources' => [
    'page' => [
        'model' => Page::class,
        'label' => 'Pages',
        'category_relation' => 'category', // null if the type has no taxonomy at all
        'category_model' => PageCategory::class, // required if category_relation is set — see Faceted filtering above
        'group' => Page::GROUP_LIST,       // used by /search-results, see API below
    ],
],
```

Merged additively at boot time (`LaravelCmsServiceProvider::register()` —
`mergeAdditiveArrayConfig('search_resources', associative: true)`), the same
mechanism `block_paths`/`reference_resolvers` already use: a project
registering its own types only needs to list what it *adds* — `page` is
never silently dropped by Laravel's shallow `mergeConfigFrom()`. See
[Configuration](./Configuration.md#resources) for the general pattern (and
why the plain `resources` key, by contrast, is *not* additive and must be
fully republished when overridden).

### Registering a new content type

Say a project wants to add a `Document` model to search. In the **consuming
app** (not this package, unless the model is package-owned like `Page`):

1. **Implement the contracts** on the model:
   ```php
   class Document extends Model implements
       PubliclySearchableInterface,
       ExcludableFromSearchInterface // only if it should be excludable
   {
       use ExcludableFromSearchTrait; // only if excludable

       public function getPublicSearchTranslations(): iterable { /* ... */ }
       public function isPubliclyVisible(): bool { /* ... */ }
       protected function searchExclusionParentRelation(): ?string { return 'category'; } // if any
   }
   ```
2. **Migration** — add `is_hidden_from_search` (boolean, default `false`) to
   `documents` (and to its taxonomy table too, if it has one and that
   should also be excludable).
3. **Register it** in the app's published `config/gingerminds-cms.php`:
   ```php
   'search_resources' => [
       'document' => [
           'model' => Document::class,
           'label' => 'Documents',
           'category_relation' => 'category', // or null
           'category_model' => DocumentCategory::class, // required if category_relation is set
           'group' => Document::GROUP_LIST,
       ],
   ],
   ```
4. **Attach the observers**, in an app service provider's `boot()` (the
   package does the same for `Page`/`PageCategory` in
   `LaravelCmsServiceProvider::boot()`, resolved through `ResourceResolver`
   rather than a hardcoded class — see the note below):
   ```php
   Document::observe(SearchIndexObserver::class);
   DocumentCategory::observe(SearchExclusionCascadeObserver::class); // if it has a taxonomy
   ```
5. `php artisan migrate` then `php artisan search:reindex --type=document`
   to backfill.

> **If the model can be subclassed/overridden** (like `Page` can via the
> `resources.page.model` config entry), always attach observers via
> `ResourceResolver::model('the_key')::observe(...)`, never via a directly
> imported class. Eloquent model events are scoped by the model's *exact*
> runtime class — an observer registered on a package class never fires for
> a project's subclass instances, and vice versa. This is exactly why the
> package's own registration in `LaravelCmsServiceProvider::boot()` reads
> `ResourceResolver::model('page')::observe(SearchIndexObserver::class)`
> rather than `Page::observe(...)`.

## API

Two read-only collections, both backed by `SearchIndex` and the exact same
filtered/paginated `search_index` query (`filters[search]` — strict `LIKE`
on `searchable_text`; `filters[type]` — one or more bare types and/or
`type:categoryId` composites, OR'd together, see
[Faceted filtering](#faceted-filtering-filterstype) above; standard
`page`/`itemsPerPage` pagination; automatic site + language scoping, see
[above](#searchindex)):

### `GET /api/search-indices`

Lean hits — `id`, `type`, `title`, `url`, `excerpt`, `published_at`. Reserved
for a future autocomplete UI (fast, single-table, no per-item resource
fetch) — not currently consumed anywhere. Its provider (`SearchProvider`)
never writes to `SearchFilterStore`, so this response never carries a
`filters` key (see `/search-results` below).

```
GET /api/search-indices?filters[search]=convention&filters[type]=event
```
```json
{
  "pagination": {"page": 1, "itemsPerPage": 10, "totalItems": 1, "totalPages": 1},
  "member": [
    {"id": 14, "type": "event", "title": "Yanmar Convention", "url": "yanmar-convention", "excerpt": "...", "published_at": "2026-08-17T15:53:37+00:00"}
  ]
}
```

### `GET /api/search-results`

Same filters/pagination, but every hit's `resource` field carries the full
underlying record (`Page`/`News`/`Event`/`Product`/...), normalized with
**that type's own existing `GROUP_LIST`** (`search_resources.<key>.group` —
whatever fields that type's own `/pages`, `/news`, etc. collection endpoint
already returns, e.g. `thumbnail_file`, `status_label`, `switch_lang`).
Nothing about that shape is redeclared or duplicated for search — this is
the endpoint to build a results-list page (cards) from; `search-indices`
above is deliberately too lean for that.

`ApiProvider/Search/SearchResultProvider.php` implements this by:
grouping the current page's `search_index` hits by `type`, bulk-fetching
each type's real models by id (one query per type present on the page, not
per item, eager-loaded via `EagerLoadableModelInterface::getEagerLoads()`),
then normalizing each one with Symfony's `NormalizerInterface`/`Serializer`
directly (`$normalizer->normalize($model, 'jsonld', ['groups' => [$group]])`
— resource class and IRI are inferred from the object's own class, so this
correctly produces each type's own shape without the provider needing to
know anything type-specific beyond its configured `group`).

> **Resolved lazily, not constructor-injected.** `SearchResultProvider`
> fetches `NormalizerInterface` inside `provide()` (`app(NormalizerInterface::class)`),
> not via its constructor. Constructor-injecting it causes an infinite
> recursive container resolution: that normalizer is built from
> api-platform's `api_platform_normalizer_list`, itself compiled by walking
> every registered resource's operations/providers — including this one
> (`SearchIndex`'s `/search-results` operation names `SearchResultProvider`
> as its provider) — so eagerly resolving it at construction time means
> building the normalizer list requires instantiating this provider, which
> requires the (still-being-built) normalizer list, forever. If you write a
> provider following this same "fetch another resource's own normalized
> shape" pattern, keep that dependency lazy.

```
GET /api/search-results?filters[type]=product&itemsPerPage=1
```
```json
{
  "pagination": {"page": 1, "itemsPerPage": 1, "totalItems": 7, "totalPages": 7},
  "member": [
    {
      "id": 22, "type": "product", "title": "Product 2", "url": "product-2", "excerpt": "",
      "resource": {
        "@id": "/api/products/2", "@type": "Product", "id": 2, "code": "product_2",
        "name": "Product 2", "switch_lang": {"fr": "produit-2", "en": "product-2"}
      }
    }
  ],
  "filters": {
    "type": {
      "type": "facet",
      "multiple": true,
      "options": [
        {"value": "event", "label": "Events", "total": 3, "group": null},
        {"value": "product", "label": "Products", "total": 7, "group": null},
        {"value": "page:12", "label": "Legal", "total": 2, "group": "page"}
      ]
    }
  }
}
```

`resource` is `null` for a hit whose underlying record couldn't be found
(stale index row) or whose type has no `group` configured.

Every `/search-results` response carries this `filters` key (injected by
`InjectSearchFiltersMiddleware`, computed by `SearchFilterComputeService` —
see [Faceted filtering](#faceted-filtering-filterstype) above): one entry per
declared filter (only `type` today), each with the same
`{type, multiple, options}` shape and only options that currently have at
least one hit, or are part of the request's own active selection. Selecting
`value: "page:12"` above re-sends it verbatim as
`filters[type][]=page:12` — the front never constructs a composite value
itself.

> **Known quirk:** `Page`'s normalized `resource` currently gets a
> JSON-LD blank-node `@id` (`/api/.well-known/genid/...`) instead of a clean
> `/api/pages/{id}`, and an inlined `@context` rather than a
> `/api/contexts/Page` reference — cosmetic only, every other field
> (including `switch_lang`) is correct. Not yet investigated further.

## Roadmap

Autocomplete (querying `/api/search-indices` with a short debounce, small
`itemsPerPage`) is intentionally not built yet — the lean endpoint above is
already shaped for it.
