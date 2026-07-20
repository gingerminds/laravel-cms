<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use RuntimeException;

/**
 * Thrown by `BlockRegistry::discover()` when two distinct block classes
 * declare the same `key()` — a project-level configuration/coding error
 * (see docs/Blocks.md), never something to recover from at runtime, hence
 * still a `RuntimeException` under the hood. Dedicated type mainly so
 * callers (tests, error reporting) can catch/assert on it specifically
 * instead of matching on message text.
 */
class DuplicateBlockKeyException extends RuntimeException
{
    /**
     * @param class-string $existingClass
     * @param class-string $newClass
     */
    public static function forKey(string $key, string $existingClass, string $newClass): self
    {
        return new self(sprintf(
            'Duplicate content block key "%s": both %s and %s declare it. '
                . 'Block keys must be unique (see docs/Blocks.md).',
            $key,
            $existingClass,
            $newClass
        ));
    }
}
