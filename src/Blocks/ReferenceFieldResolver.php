<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

/**
 * Contract for a "reference field" resolver (docs/ContentBlocks.md,
 * "Exposition API (headless)"), one per field `type` a block schema can
 * declare as a reference (`file`, `media`, or any type a project registers
 * of its own — see `gingerminds-cms.reference_resolvers`). Kept separate
 * from `ContentReferenceResolver` (which only orchestrates: batching ids,
 * walking the block schema, substituting) so adding a new reference field
 * type never means editing that class — implement this interface, register
 * the field type => FQCN mapping in config, done. Same spirit as
 * `BlockInterface`/`BlockRegistry` for block types themselves.
 */
interface ReferenceFieldResolver
{
    /**
     * Batch-loads every id referenced by this field type across the whole
     * page in one query — the reason `ContentReferenceResolver` collects
     * ids per type before resolving anything, instead of loading one at a
     * time per field (N+1 across a page with many blocks).
     *
     * @param array<int, int|string> $ids
     * @return array<int|string, mixed> loaded models/rows keyed by id
     */
    public function loadMany(array $ids): array;

    /**
     * Turns one already-loaded value (an entry from `loadMany()`'s result,
     * or `null` when the id didn't resolve to anything) into the shape
     * served by the API. Never called with an id — only ever with what
     * `loadMany()` returned for it, so this never re-queries.
     *
     * @return array<string, mixed>|null
     */
    public function resolveOne(mixed $loaded): ?array;
}
