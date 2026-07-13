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
        $uploadService = app(FileUploadService::class);

        foreach ($block->fields() as $field) {
            if (($field['type'] ?? null) !== 'file') {
                continue;
            }

            $name = $field['name'];

            /** @var UploadedFile|null $uploaded */
            $uploaded = $request->file("data.$name");

            $oldId = $request->input("data.$name");
            $old   = is_string($oldId) && $oldId !== '' ? File::query()->find($oldId) : null;

            if ($uploaded instanceof UploadedFile) {
                $data[$name] = $uploadService->replace($uploaded, $old, self::UPLOAD_FOLDER)->id;

                continue;
            }

            if ($request->boolean("data.{$name}_remove")) {
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

            foreach ($block->fields() as $field) {
                if (($field['type'] ?? null) !== 'file') {
                    continue;
                }

                $value = $item['data'][$field['name']] ?? null;

                if (is_string($value) && $value !== '') {
                    $ids[] = $value;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
