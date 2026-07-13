<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use Illuminate\Validation\Rule;

/**
 * Everything a FormRequest needs to accept a content-blocks field safely —
 * an array of `{uid, type, data}` blocks, one per language translation
 * (see docs/Blocks.md): decoding + pruning stale data, building validation
 * rules, building nice per-field attribute labels. Not Page-specific:
 * every method is parameterized by field name/prefix, so any resource with
 * its own `<x-gingerminds-cms::form.inputs.canvas field="...">` can reuse
 * this instead of duplicating the same three concerns in its own
 * FormRequest.
 */
class ContentFieldSupport
{
    /**
     * Decodes each language's `$field` from its submitted JSON string into
     * a PHP array (matching the `array` cast Eloquent expects), and prunes
     * each block's `data` down to the keys its type's field schema
     * *currently* declares.
     *
     * Without the pruning, a field removed from a block's config (e.g. a
     * "Mobile title" field deleted from TitleText) would stay in the
     * stored JSON forever: the ajax modal only ever writes back the fields
     * it currently renders for a block that gets reopened, so a save that
     * never reopens that specific block would otherwise keep carrying the
     * stale key indefinitely.
     *
     * Blocks of an unrecognized type are left untouched — pruning would
     * otherwise destroy their data on a save that races a deploy where the
     * registry is momentarily out of sync. A translation's `$field` is
     * left untouched too (and later rejected by the `array` rule) if it
     * isn't valid JSON to begin with.
     *
     * @param array<int|string, array<string, mixed>> $translations
     * @return array<int|string, array<string, mixed>>
     */
    public static function decodeAndPrune(array $translations, string $field): array
    {
        foreach ($translations as $langId => $fields) {
            if (!array_key_exists($field, $fields) || !is_string($fields[$field])) {
                continue;
            }

            $decoded = json_decode($fields[$field], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $translations[$langId][$field] = self::pruneUnknownFields($decoded ?? []);
            }
        }

        return $translations;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @return array<int, array<string, mixed>>
     */
    private static function pruneUnknownFields(array $blocks): array
    {
        foreach ($blocks as $index => $item) {
            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if (!$block instanceof BlockInterface || !is_array($item['data'] ?? null)) {
                continue;
            }

            $allowedKeys = array_column($block->fields(), 'name');

            $blocks[$index]['data'] = BlockFieldValidator::sanitizeDataForBlock(
                $block,
                array_intersect_key($item['data'], array_flip($allowedKeys))
            );
        }

        return $blocks;
    }

    /**
     * Builds `{prefix}.{index}.uid` / `.type` / `.data.{field}` rules for
     * every block already submitted under `$prefix`, reusing each block's
     * own field schema (`BlockFieldValidator::rulesForBlock()` — one
     * place, one set of rules per block, whether validated here on full
     * save or per-block in an ajax modal).
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array<string, mixed>
     */
    public static function rulesFor(string $prefix, array $blocks): array
    {
        $rules = [
            $prefix => ['nullable', 'array'],
        ];

        foreach ($blocks as $index => $item) {
            $itemPrefix = "$prefix.$index";

            $rules["$itemPrefix.uid"]  = ['required', 'string'];
            $rules["$itemPrefix.type"] = ['required', 'string', Rule::in(array_keys(BlockRegistry::all()))];

            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if ($block instanceof BlockInterface) {
                foreach (BlockFieldValidator::rulesForBlock($block, "$itemPrefix.data") as $key => $fieldRules) {
                    $rules[$key] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * Labels each block field individually (e.g. "Title — Title + Text
     * (FR)") so a validation error reads naturally instead of showing the
     * raw "translations.3.content.2.data.title" key.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array<string, string>
     */
    public static function attributesFor(string $prefix, array $blocks, string $languageLabel): array
    {
        $attributes = [];

        foreach ($blocks as $index => $item) {
            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if (!$block instanceof BlockInterface) {
                continue;
            }

            foreach ($block->fields() as $field) {
                $key              = "$prefix.$index.data.{$field['name']}";
                $attributes[$key] = "{$field['label']} — {$block->label()} ($languageLabel)";
            }
        }

        return $attributes;
    }
}
