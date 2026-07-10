<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Content block registry.
 *
 * Fed by every `path`/`namespace` pair listed in `gingerminds-cms.block_paths`
 * (package's own `src/Blocks/Type` and the project's `app/Cms/Blocks` by
 * default, see config) — additive, a project can list extra paths without
 * losing the package's own. A block can live only project-side if its
 * dependency isn't available package-side (e.g. media gallery depending on
 * laravel-media-manager).
 *
 * Override an existing block: `gingerminds-cms.blocks.<key> => FQCN`, same
 * mechanism as `ResourceResolver` for other resources.
 *
 * Static for consistency with `ResourceResolver`. Disk scan is cached for
 * the request (`self::$blocks`); see `flush()` for tests.
 */
class BlockRegistry
{
    /** @var array<string, BlockInterface>|null */
    private static ?array $blocks = null;

    /**
     * All known blocks (package + project + config overrides), enabled or
     * not. Use `active()` for the picker.
     *
     * @return array<string, BlockInterface>
     */
    public static function all(): array
    {
        if (self::$blocks === null) {
            self::$blocks = self::discover();
        }

        return self::$blocks;
    }

    /**
     * Enabled blocks (not in `disabled_blocks`), sorted by
     * `gingerminds-cms.block_order` weight, falling back to the block's own
     * `order()`.
     *
     * @return list<BlockInterface>
     */
    public static function active(): array
    {
        $disabled = config('gingerminds-cms.disabled_blocks', []);
        $weights  = config('gingerminds-cms.block_order', []);

        $blocks = array_filter(
            self::all(),
            static fn (BlockInterface $block): bool => !in_array($block->key(), $disabled, true)
        );

        // usort() re-indexes to a sequential, 0-based list as a side
        // effect — an extra array_values() after it would be a no-op.
        usort(
            $blocks,
            static fn (BlockInterface $a, BlockInterface $b): int => ($weights[$a->key()]
                    ?? $a->order()) <=> ($weights[$b->key()]
                    ?? $b->order())
        );

        return $blocks;
    }

    /**
     * Resolve a block by key, disabled or not: disabling only hides a block
     * from the picker, it must not break existing pages already using it.
     */
    public static function find(string $key): ?BlockInterface
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function activeKeys(): array
    {
        return array_map(
            static fn (BlockInterface $block): string => $block->key(),
            self::active()
        );
    }

    /**
     * Clears the in-memory cache (tests, or long-lived workers e.g. Octane).
     */
    public static function flush(): void
    {
        self::$blocks = null;
    }

    /**
     * @return array<string, BlockInterface>
     */
    private static function discover(): array
    {
        $classes = [];

        foreach (config('gingerminds-cms.block_paths', []) as $entry) {
            self::scan($entry['path'] ?? '', $entry['namespace'] ?? '', $classes);
        }

        /** @var array<string, BlockInterface> $blocks */
        $blocks = [];

        foreach ($classes as $class) {
            /** @var BlockInterface $instance */
            $instance = app($class);

            $blocks[$instance->key()] = $instance;
        }

        // Explicit config override, like ResourceResolver for other
        // resources. Only holds entries a project chooses to override — no
        // exhaustive list to maintain in the package's default config.
        foreach (config('gingerminds-cms.blocks', []) as $key => $overrideClass) {
            $blocks[$key] = app($overrideClass);
        }

        return $blocks;
    }

    /**
     * @param array<int, class-string> $classes
     */
    private static function scan(string $path, string $namespace, array &$classes): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($path) + 1, -4);
            $class    = $namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (
                $reflection->isAbstract()
                || $reflection->isInterface()
                || !$reflection->implementsInterface(BlockInterface::class)
            ) {
                continue;
            }

            $classes[] = $class;
        }
    }
}
