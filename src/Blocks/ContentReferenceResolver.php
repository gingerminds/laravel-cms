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

            $blocks[$index]['data'] = self::resolveFields($block->fields(), $item['data'], $resolvers, $loaded);
        }

        return $blocks;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $data
     * @param array<string, ReferenceFieldResolver> $resolvers
     * @param array<string, array<int|string, mixed>> $loaded
     * @return array<string, mixed>
     */
    private static function resolveFields(array $fields, array $data, array $resolvers, array $loaded): array
    {
        foreach ($fields as $field) {
            $type = $field['type'] ?? null;
            $name = $field['name'];

            if ($type === 'repeater') {
                $rows = is_array($data[$name] ?? null) ? $data[$name] : [];

                $data[$name] = array_map(
                    static fn (mixed $row): mixed => is_array($row)
                        ? self::resolveFields($field['fields'] ?? [], $row, $resolvers, $loaded)
                        : $row,
                    $rows,
                );

                continue;
            }

            if (!isset($resolvers[$type]) || !array_key_exists($name, $data)) {
                continue;
            }

            $data[$name] = self::resolveFieldValue(
                $resolvers[$type],
                $loaded[$type],
                $data[$name],
                (bool) ($field['multiple'] ?? false),
            );
        }

        return $data;
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
     * deduplicated — regardless of which block, field, or repeater row
     * declares it, so `loadMany()` runs once per type no matter how many
     * places reference that same type.
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

            array_push($ids, ...self::collectFieldIds($block->fields(), $item['data'], $fieldType));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $data
     * @return array<int, int|string>
     */
    private static function collectFieldIds(array $fields, array $data, string $fieldType): array
    {
        $ids = [];

        foreach ($fields as $field) {
            $type = $field['type'] ?? null;

            if ($type === 'repeater') {
                foreach ((array) ($data[$field['name']] ?? []) as $row) {
                    if (is_array($row)) {
                        array_push($ids, ...self::collectFieldIds($field['fields'] ?? [], $row, $fieldType));
                    }
                }

                continue;
            }

            if ($type !== $fieldType) {
                continue;
            }

            $value = $data[$field['name']] ?? null;

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

        return $ids;
    }
}
