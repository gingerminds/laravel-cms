<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

class ContentReferenceResolver
{
    /**
     * @param array<int, array<string, mixed>>|null $blocks
     * @return array<int, array<string, mixed>>|null
     */
    public static function resolve(?array $blocks): ?array
    {
        if ($blocks === null || $blocks === []) {
            return $blocks;
        }

        $resolvers = self::resolvers();

        if ($resolvers === []) {
            return $blocks;
        }

        // One batch load per registered reference type, not per field/block
        // — see class docblock.
        $loaded = [];

        foreach ($resolvers as $type => $resolver) {
            $ids           = self::collectIds($blocks, $type);
            $loaded[$type] = $ids === [] ? [] : $resolver->loadMany($ids);
        }

        foreach ($blocks as $index => $item) {
            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if (!$block instanceof BlockInterface || !is_array($item['data'] ?? null)) {
                continue;
            }

            foreach ($block->fields() as $field) {
                $type = $field['type'] ?? null;
                $name = $field['name'];

                if (!isset($resolvers[$type]) || !array_key_exists($name, $item['data'])) {
                    continue;
                }

                $blocks[$index]['data'][$name] = self::resolveFieldValue(
                    $resolvers[$type],
                    $loaded[$type],
                    $item['data'][$name],
                    (bool) ($field['multiple'] ?? false),
                );
            }
        }

        return $blocks;
    }

    /**
     * @param array<int|string, mixed> $loaded
     * @return array<int, array<string, mixed>|null>|array<string, mixed>|null
     */
    private static function resolveFieldValue(
        ReferenceFieldResolver $resolver,
        array $loaded,
        mixed $value,
        bool $isMultiple,
    ): array|null {
        if (!$isMultiple) {
            return ($value === null || $value === '')
                ? null
                : $resolver->resolveOne($loaded[$value] ?? null);
        }

        return array_values(array_filter(array_map(
            static fn (mixed $id): ?array => $resolver->resolveOne($loaded[$id] ?? null),
            array_filter((array) $value, static fn (mixed $id): bool => $id !== null && $id !== ''),
        )));
    }

    /**
     * @return array<string, ReferenceFieldResolver>
     */
    private static function resolvers(): array
    {
        $resolvers = [];

        foreach (config('gingerminds-cms.reference_resolvers', []) as $fieldType => $class) {
            $resolvers[$fieldType] = app($class);
        }

        return $resolvers;
    }

    /**
     * Every id a given reference field `type` holds across the whole page,
     * deduplicated — regardless of which block or which field declares it,
     * so `loadMany()` runs once per type no matter how many blocks/fields
     * reference that same type.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array<int, int|string>
     */
    private static function collectIds(array $blocks, string $fieldType): array
    {
        $ids = [];

        foreach ($blocks as $item) {
            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if (!$block instanceof BlockInterface || !is_array($item['data'] ?? null)) {
                continue;
            }

            foreach ($block->fields() as $field) {
                if (($field['type'] ?? null) !== $fieldType) {
                    continue;
                }

                $value = $item['data'][$field['name']] ?? null;

                if ($field['multiple'] ?? false) {
                    array_push($ids, ...array_filter(
                        (array) $value,
                        static fn (mixed $id): bool => $id !== null && $id !== '',
                    ));
                    continue;
                }

                if ($value !== null && $value !== '') {
                    $ids[] = $value;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
