<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use Gingerminds\LaravelMediaManager\Models\File\File;
use Gingerminds\LaravelMediaManager\Services\File\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class BlockFileFieldSync
{
    private const string UPLOAD_FOLDER = 'cms-blocks';

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sync(BlockInterface $block, Request $request, array $data): array
    {
        return self::syncFields($block->fields(), $request, $data, 'data', app(FileUploadService::class));
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function syncFields(
        array $fields,
        Request $request,
        array $data,
        string $prefix,
        FileUploadService $uploadService,
    ): array {
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? null;

            if ($type === 'repeater') {
                $data[$name] = self::syncRepeaterRows($field, $request, $data[$name] ?? null, $prefix, $uploadService);
                continue;
            }

            if ($type === 'file') {
                $data[$name] = self::syncFileField($request, $data[$name] ?? null, "$prefix.$name", $uploadService);
            }
        }

        return $data;
    }

    /**
     * One level of `syncFields()`'s repeater branch, split out purely to
     * keep both functions' cognitive complexity down — the loop-inside-a-
     * loop-inside-an-if shape was what pushed the original over budget, not
     * any of the logic itself.
     *
     * @param array<string, mixed> $field
     * @return array<int, mixed>
     */
    private static function syncRepeaterRows(
        array $field,
        Request $request,
        mixed $rows,
        string $prefix,
        FileUploadService $uploadService,
    ): array {
        $rows = is_array($rows) ? $rows : [];

        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $rows[$index] = self::syncFields(
                    $field['fields'] ?? [],
                    $request,
                    $row,
                    "$prefix.{$field['name']}.$index",
                    $uploadService,
                );
            }
        }

        return $rows;
    }

    /**
     * `syncFields()`'s `file`-type branch: upload a new file, honor an
     * explicit "remove" flag, or fall back to whatever was already there
     * (normalizing an empty-string value to `null`).
     */
    private static function syncFileField(
        Request $request,
        mixed $currentValue,
        string $inputKey,
        FileUploadService $uploadService,
    ): mixed {
        /** @var UploadedFile|null $uploaded */
        $uploaded = $request->file($inputKey);

        $oldId = $request->input($inputKey);
        $old   = is_string($oldId) && $oldId !== '' ? File::query()->find($oldId) : null;

        if ($uploaded instanceof UploadedFile) {
            return $uploadService->replace($uploaded, $old, self::UPLOAD_FOLDER)->id;
        }

        if ($request->boolean("{$inputKey}_remove")) {
            $uploadService->delete($old);

            return null;
        }

        return $currentValue === '' ? null : $currentValue;
    }

    /**
     * Deletes every `file`-type File referenced in `$oldBlocks` that's no
     * longer referenced anywhere in `$newBlocks` — a block was removed
     * from the page's content, or one of its `file` fields was replaced or
     * cleared. Pass an empty `$newBlocks` to prune everything (e.g. the
     * whole page/translation is being deleted).
     *
     * @param array<int, array<string, mixed>>|null $oldBlocks
     * @param array<int, array<string, mixed>> $newBlocks
     */
    public static function pruneOrphanedFiles(?array $oldBlocks, array $newBlocks): void
    {
        $orphanedIds = array_diff(
            self::collectFileIds($oldBlocks),
            self::collectFileIds($newBlocks)
        );

        if ($orphanedIds === []) {
            return;
        }

        $uploadService = app(FileUploadService::class);

        File::query()->whereIn('id', $orphanedIds)->get()->each(
            static fn (File $file) => $uploadService->delete($file)
        );
    }

    /**
     * @param array<int, array<string, mixed>>|null $blocks
     * @return list<string>
     */
    private static function collectFileIds(?array $blocks): array
    {
        $ids = [];

        foreach ($blocks ?? [] as $item) {
            $block = BlockRegistry::find((string) ($item['type'] ?? ''));

            if (!$block instanceof BlockInterface || !is_array($item['data'] ?? null)) {
                continue;
            }

            array_push($ids, ...self::collectFieldFileIds($block->fields(), $item['data']));
        }

        return array_values(array_unique($ids));
    }

    /**
     * Recurses one level into `repeater` rows — same reasoning as
     * `syncFields()`: a card's `image` is a `file` field like any other,
     * just nested under its row's own `data`.
     *
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function collectFieldFileIds(array $fields, array $data): array
    {
        $ids = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? null;

            if ($type === 'file') {
                $value = $data[$name] ?? null;

                if (is_string($value) && $value !== '') {
                    $ids[] = $value;
                }

                continue;
            }

            if ($type === 'repeater') {
                array_push($ids, ...self::collectRepeaterFileIds($field['fields'] ?? [], $data[$name] ?? null));
            }
        }

        return $ids;
    }

    /**
     * `collectFieldFileIds()`'s repeater branch, split out for the same
     * reason as `syncRepeaterRows()` above — a loop nested inside another
     * loop's `if` is exactly the shape that drives cognitive complexity up
     * fastest, regardless of how simple the body is.
     *
     * @param array<int, array<string, mixed>> $fields
     * @return list<string>
     */
    private static function collectRepeaterFileIds(array $fields, mixed $rows): array
    {
        $ids = [];

        foreach ((is_array($rows) ? $rows : []) as $row) {
            if (is_array($row)) {
                array_push($ids, ...self::collectFieldFileIds($fields, $row));
            }
        }

        return $ids;
    }
}
