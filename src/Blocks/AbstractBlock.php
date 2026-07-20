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
