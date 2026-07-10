<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Shared request-handling glue for any controller powering a block canvas's
 * ajax endpoints (see docs/Blocks.md): resolving a block by key (or
 * aborting), resolving/generating its uid, and rendering one of its views
 * with the usual {block, uid, data} variable set. `PageBlockController` is
 * the only consumer today, but none of this is Page-specific — a future
 * block-canvas-bearing resource's own controller can reuse it as-is.
 */
class BlockRequestSupport
{
    public static function resolveOrAbort(string $key): BlockInterface
    {
        $block = BlockRegistry::find($key);

        if (!$block instanceof BlockInterface) {
            abort(404);
        }

        return $block;
    }

    /**
     * `Request::input()` already merges the query string and the request
     * body regardless of HTTP verb, so this reads a submitted uid the same
     * way whether it came from a GET query param (the form fragment
     * endpoint) or a POST body (the validate endpoint) — no need for the
     * caller to pick between `query()` and `input()`.
     */
    public static function resolveUid(Request $request): string
    {
        $raw = $request->input('uid');

        return is_string($raw) && $raw !== '' ? $raw : (string) Str::uuid();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderView(string $view, BlockInterface $block, string $uid, array $data): string
    {
        /** @var view-string $viewName */
        $viewName = $view;

        return view($viewName, [
            'block' => $block,
            'uid'   => $uid,
            'data'  => $data,
        ])->render();
    }
}
