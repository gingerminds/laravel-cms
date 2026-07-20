<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

/**
 * Optional base class: default `order()`, same spirit as
 * `AbstractApiProvider` in laravel-core (thin interface, common behaviour
 * lives here).
 *
 * Also home to a handful of `fields()` builders (`textField()`,
 * `wysiwygField()`, ...). Every block's `fields()` schema entry shares the
 * same core shape (`name`/`type`/`label`/`required`[/`size`]) — writing that
 * shape out as a full array literal in every block, over and over, is what
 * drove the reference blocks' Sonar duplication score up: its clone
 * detector normalizes literals before comparing, so two field arrays that
 * only differ by their `name`/`label` string still count as one duplicated
 * block. Collapsing each into a single call removes the repeated shape
 * itself instead of just reformatting around it. `$extra` is the escape
 * hatch for whatever a field needs beyond its builder's own defaults
 * (`rules`, `mimes`, `helper`, `options`, an overridden `default`...) —
 * anything in it wins over the builder's own defaults since it's merged in
 * last.
 */
abstract class AbstractBlock implements BlockInterface
{
    public function order(): int
    {
        return 100;
    }

    /**
     * Shorthand for `{translationNamespace()}.blocks.{key()}.fields.{path}`
     * — every field/sub-field/repeater add_label/item_label translation key
     * for a block follows that shape, differing only in `$path`. Deriving
     * the `blocks.{key()}` segment from `key()` instead of typing it out per
     * call does two things at once: it's what lets most field calls fit on
     * one line (the full string was what pushed calls over phpcs' 120-column
     * limit, forcing multi-line calls whose *shape* Sonar's clone detector
     * then matched across blocks as duplication), and it makes a
     * copy-pasted block pointing at a sibling block's key by mistake
     * structurally impossible — which is exactly the bug `Video`, `Media`,
     * and `FrequentlyAskedQuestions` each had before this existed.
     */
    protected function fieldLabel(string $path): string
    {
        return __("{$this->translationNamespace()}.blocks.{$this->key()}.fields.{$path}");
    }

    /**
     * Defaults to this package's own lang namespace since every block
     * shipped here (`src/Blocks/Type/*`) translates through it — but
     * `fieldLabel()` has no business assuming that for a block it doesn't
     * own. A block living in another package, or a project's own
     * `App\Cms\Blocks\*` (`make:cms-block`) translating through its default,
     * prefix-less `lang/*.php` instead, overrides this one method (e.g.
     * `return 'my-package::translation';` or just `return 'translation';`)
     * and every `fieldLabel()` call in that block resolves correctly — no
     * need to touch `fieldLabel()` itself.
     *
     * A project-side *override* of one of this package's own blocks (see
     * `App\Cms\BlockOverrides\*` in the consuming app) is a different case:
     * it extends the package's block class, so it inherits this default and
     * `fieldLabel()` keeps resolving that block's *own* fields correctly —
     * any field the override adds on top (e.g. a project-only `headline`)
     * just isn't a `gingerminds-cms::` key at all, so it's translated with a
     * plain `__(...)` call instead, same as before `fieldLabel()` existed.
     */
    protected function translationNamespace(): string
    {
        return 'gingerminds-cms::translation';
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function textField(
        string $name,
        string $label,
        bool $required = false,
        string $size = 'md',
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'text',
            'label' => $label,
            'required' => $required,
            'size' => $size,
            ...$extra,
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function textareaField(
        string $name,
        string $label,
        bool $required = false,
        string $size = 'xl',
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'textarea',
            'label' => $label,
            'required' => $required,
            'size' => $size,
            ...$extra,
        ];
    }

    /**
     * `preset`/`rows` default to this package's own reference blocks'
     * convention (`default` preset, 8 rows) — override either via `$extra`.
     *
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function wysiwygField(
        string $name,
        string $label,
        bool $required = false,
        string $size = 'xl',
        int $rows = 8,
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'wysiwyg',
            'label' => $label,
            'required' => $required,
            'size' => $size,
            'preset' => 'default',
            'rows' => $rows,
            ...$extra,
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function fileField(
        string $name,
        string $label,
        bool $required = false,
        string $size = 'md',
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'file',
            'label' => $label,
            'required' => $required,
            'size' => $size,
            ...$extra,
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function mediaField(
        string $name,
        string $label,
        bool $required = false,
        string $size = 'md',
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'media',
            'label' => $label,
            'required' => $required,
            'size' => $size,
            ...$extra,
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function toggleField(
        string $name,
        string $label,
        bool $required = false,
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'toggle',
            'label' => $label,
            'required' => $required,
            ...$extra,
        ];
    }

    /**
     * `default` is always `[]` (a repeater is never pre-filled) — the one
     * key every repeater field always needs but no caller ever varies, so
     * it isn't a parameter at all, just baked in.
     *
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function repeaterField(
        string $name,
        string $label,
        array $fields,
        string $addLabel,
        string $itemLabel,
        bool $required = false,
        array $extra = [],
    ): array {
        return [
            'name' => $name,
            'type' => 'repeater',
            'label' => $label,
            'required' => $required,
            'default' => [],
            'add_label' => $addLabel,
            'item_label' => $itemLabel,
            'fields' => $fields,
            ...$extra,
        ];
    }
}
