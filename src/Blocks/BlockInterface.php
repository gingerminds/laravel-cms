<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

/**
 * Contract for a content block (see docs/ContentBlocks.md).
 *
 * Auto-discovered by `BlockRegistry` (scans `src/Blocks/Type/**` in this
 * package and `app/Cms/Blocks/**` in the consuming project) — no manual
 * registration needed. Override an existing block from the project via
 * `gingerminds-cms.blocks.<key>`.
 */
interface BlockInterface
{
    /**
     * Stable identifier, used as `type` in the PageTranslation::content JSON
     * and as the registry key. Never change once pages use this block.
     */
    public function key(): string;

    /** Label shown in the block picker (add-block modal, step 1). */
    public function label(): string;

    /** Bootstrap Icons class (e.g. "bi-type") shown next to the label. */
    public function icon(): string;

    /**
     * Default sort weight in the picker (lower = higher). Overridable
     * without subclassing via `gingerminds-cms.block_order`.
     */
    public function order(): int;

    /**
     * Declarative field schema. Each entry: `name`, `type` (text, textarea,
     * wysiwyg, select...), `label`, `required`, `size`, `default`, `rules`
     * (extra Laravel rules), `options` (for select). Type-specific extras:
     * `preset` and `rows` for `wysiwyg` (see `gingerminds-cms.wysiwyg.presets.*`,
     * same presets as the existing wysiwyg component).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array;

    /** Blade view for the admin preview rendered inside the page canvas. */
    public function previewView(): string;
}
