<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use Illuminate\Validation\Rule;

/**
 * Turns the declarative `BlockInterface::fields()` schema into Laravel
 * validation rules. Shared by `PageBlockController` (single-block
 * validation when its modal closes) and `PageRequest` (full revalidation of
 * `content` on page save) — same schema, same rules, no duplicated logic.
 */
class BlockFieldValidator
{
    /**
     * @param array<string, mixed> $field
     *
     * @return array<int, mixed>
     */
    public static function rulesForField(array $field): array
    {
        $required = (bool) ($field['required'] ?? false);
        $rules    = [$required ? 'required' : 'nullable'];

        $type = $field['type'] ?? 'text';

        $rules[] = match ($type) {
            'select' => 'string',
            default  => 'string',
        };

        if ($type === 'select' && !empty($field['options'])) {
            $rules[] = Rule::in(array_keys($field['options']));
        }

        return array_merge($rules, $field['rules'] ?? []);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rulesForBlock(BlockInterface $block, string $prefix = 'data'): array
    {
        $rules = [];

        foreach ($block->fields() as $field) {
            $rules[$prefix . '.' . $field['name']] = self::rulesForField($field);
        }

        return $rules;
    }

    /**
     * Custom validation attributes so an error reads "The Title field is
     * required." instead of "The data.title field is required." — used
     * wherever a block's rules are validated ad hoc (no FormRequest
     * `attributes()` involved), e.g. `PageBlockController::validateBlock()`.
     *
     * @return array<string, string>
     */
    public static function attributesForBlock(BlockInterface $block, string $prefix = 'data'): array
    {
        $attributes = [];

        foreach ($block->fields() as $field) {
            $attributes[$prefix . '.' . $field['name']] = $field['label'] ?? $field['name'];
        }

        return $attributes;
    }

    /**
     * Default values for a block (new block, or a field added after existing
     * pages already used this block type — defensive access rather than
     * breaking the preview, see docs/ContentBlocks.md "Dérive de schéma dans
     * le temps").
     *
     * @return array<string, mixed>
     */
    public static function defaultsForBlock(BlockInterface $block): array
    {
        $defaults = [];

        foreach ($block->fields() as $field) {
            $defaults[$field['name']] = $field['default'] ?? null;
        }

        return $defaults;
    }
}
