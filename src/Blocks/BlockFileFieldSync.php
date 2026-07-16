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
                $rows = is_array($data[$name] ?? null) ? $data[$name] : [];

                foreach ($rows as $index => $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $rows[$index] = self::syncFields(
                        $field['fields'] ?? [],
                        $request,
                        $row,
                        "$prefix.$name.$index",
                        $uploadService,
                    );
                }

                $data[$name] = $rows;
                continue;
            }

            if ($type !== 'file') {
                continue;
            }

            $inputKey = "$prefix.$name";

            /** @var UploadedFile|null $uploaded */
            $uploaded = $request->file($inputKey);

            $oldId = $request->input($inputKey);
            $old   = is_string($oldId) && $oldId !== '' ? File::query()->find($oldId) : null;

            if ($uploaded instanceof UploadedFile) {
                $data[$name] = $uploadService->replace($uploaded, $old, self::UPLOAD_FOLDER)->id;

                continue;
            }

            if ($request->boolean("{$inputKey}_remove")) {
                $uploadService->delete($old);
                $data[$name] = null;

                continue;
            }

            if (($data[$name] ?? null) === '') {
                $data[$name] = null;
            }
        }

        return $data;
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

            if ($type === 'repeater' && is_array($data[$name] ?? null)) {
                foreach ($data[$name] as $row) {
                    if (is_array($row)) {
                        array_push($ids, ...self::collectFieldFileIds($field['fields'] ?? [], $row));
                    }
                }
            }
        }

        return $ids;
    }
}
