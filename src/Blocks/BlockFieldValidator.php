<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use Closure;
use Illuminate\Http\UploadedFile;
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
        $type     = $field['type'] ?? 'text';

        if ($type === 'file') {
            return self::fileRules($field, $required);
        }

        if ($type === 'repeater') {
            // Per-row rules (`{prefix}.{name}.*.{subField}`) are added by
            // `rulesForBlock()` instead — this only covers the top-level
            // key itself, same split already used for a multiple-media
            // field's own `.*` companion rule.
            return [$required ? 'required' : 'nullable', 'array'];
        }

        $rules           = [$required ? 'required' : 'nullable'];
        $isMultipleMedia = $type === 'media' && (bool) ($field['multiple'] ?? false);

        $rules[] = match (true) {
            $type === 'select' => 'string',
            $type === 'toggle' => 'boolean',
            $isMultipleMedia => 'array',
            $type === 'media' => 'integer',
            default => 'string',
        };

        if ($type === 'select' && !empty($field['options'])) {
            $rules[] = Rule::in(array_keys($field['options']));
        }

        // The `.*` companion rule for a multiple-media field is added by
        // `rulesForBlock()` instead (needs its own top-level rule key).
        if ($type === 'media' && !$isMultipleMedia) {
            $rules[] = Rule::exists('medias', 'id');
        }

        return array_merge($rules, $field['rules'] ?? []);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<int, mixed>
     */
    private static function fileRules(array $field, bool $required): array
    {
        $maxKb = (int) ($field['max_size_kb'] ?? 5120);
        $mimes = array_key_exists('mimes', $field) ? $field['mimes'] : ['image/*'];
        $mimes = empty($mimes) ? null : (array) $mimes;

        $rules = [$required ? 'required' : 'nullable'];
        // $attribute is required by Laravel's closure-rule contract (it's
        // called positionally as ($attribute, $value, $fail)) but unused
        // here: $fail() already substitutes ":attribute" using it under the
        // hood, so there's nothing left for this closure's own body to do
        // with it. NOSONAR: dropping it would shift $value/$fail into the
        // wrong argument slots instead of actually removing anything.
        $rules[] = static function (string $attribute, mixed $value, Closure $fail) use ($maxKb, $mimes): void {
            // NOSONAR
            $error = self::fileError($value, $mimes, $maxKb);

            if ($error !== null) {
                $fail($error);
            }
        };

        return array_merge($rules, $field['rules'] ?? []);
    }

    /**
     * All the file-upload checks `fileRules()`'s closure needs, collapsed
     * into a single result instead of `$fail()`-and-return per case — kept
     * separate mainly so that closure itself stays a trivial one-branch
     * wrapper.
     *
     * @param array<int, string>|null $mimes
     */
    private static function fileError(mixed $value, ?array $mimes, int $maxKb): ?string
    {
        if ($value === null || $value === '' || is_string($value)) {
            return null;
        }

        return match (true) {
            !$value instanceof UploadedFile => 'The :attribute must be a file.',
            !$value->isValid() => 'The :attribute failed to upload.',
            $mimes !== null && !self::mimeMatches((string) $value->getMimeType(), $mimes) =>
                'The :attribute must be a file of type: ' . implode(', ', $mimes) . '.',
            $value->getSize() > $maxKb * 1024 => "The :attribute must not be greater than {$maxKb} kilobytes.",
            default => null,
        };
    }

    /**
     * Matches a mime type against a list of patterns supporting a trailing
     * wildcard (`image/*`) as well as exact matches (`application/pdf`) —
     * same syntax as the HTML `accept` attribute, so a field's `mimes`
     * schema value can double as its `accept` hint (see `form.blade.php`).
     *
     * @param array<int, string> $patterns
     */
    private static function mimeMatches(string $mimeType, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $mimeType) {
                return true;
            }

            if (str_ends_with($pattern, '/*') && str_starts_with($mimeType, substr($pattern, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rulesForBlock(BlockInterface $block, string $prefix = 'data'): array
    {
        $rules = [];

        foreach ($block->fields() as $field) {
            $rules[$prefix . '.' . $field['name']] = self::rulesForField($field);

            if (($field['type'] ?? null) === 'media' && ($field['multiple'] ?? false)) {
                $rules[$prefix . '.' . $field['name'] . '.*'] = ['integer', Rule::exists('medias', 'id')];
            }

            // One level of nesting only: a repeater row's own sub-fields
            // are validated via Laravel's `.*` wildcard expansion (applies
            // to however many rows were actually submitted, regardless of
            // their index values) — a sub-field being itself a `repeater`
            // isn't supported.
            if (($field['type'] ?? null) === 'repeater') {
                foreach ($field['fields'] ?? [] as $subField) {
                    $rules[$prefix . '.' . $field['name'] . '.*.' . $subField['name']] = self::rulesForField($subField);
                }
            }
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitizeDataForBlock(BlockInterface $block, array $data): array
    {
        foreach ($block->fields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? null;

            $isUnsetSingleMedia = $type === 'media' && !($field['multiple'] ?? false);

            if (($isUnsetSingleMedia || $type === 'file') && ($data[$name] ?? null) === '') {
                $data[$name] = null;
            }

            if ($type === 'repeater' && is_array($data[$name] ?? null)) {
                $data[$name] = self::sanitizeRepeaterRows($field['fields'] ?? [], $data[$name]);
            }
        }

        return $data;
    }

    /**
     * @param array<int, array<string, mixed>> $subFields
     * @param array<int|string, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function sanitizeRepeaterRows(array $subFields, array $rows): array
    {
        $sanitized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($subFields as $subField) {
                $subName = $subField['name'];
                $subType = $subField['type'] ?? null;

                $isUnsetSingleMedia = $subType === 'media' && !($subField['multiple'] ?? false);

                if (($isUnsetSingleMedia || $subType === 'file') && ($row[$subName] ?? null) === '') {
                    $row[$subName] = null;
                }
            }

            $sanitized[] = $row;
        }

        return $sanitized;
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

            // Registered with the literal `*` segment, not a concrete row
            // index: Laravel's validator already resolves a wildcard-style
            // custom attribute (`data.cards.*.title`) against any concrete
            // error key matching that shape (`data.cards.0.title`,
            // `data.cards.1.title`...), same mechanism relied on for rules.
            if (($field['type'] ?? null) === 'repeater') {
                foreach ($field['fields'] ?? [] as $subField) {
                    $attributes[$prefix . '.' . $field['name'] . '.*.' . $subField['name']]
                        = $subField['label'] ?? $subField['name'];
                }
            }
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
