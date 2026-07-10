<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Controllers\Page;

use Gingerminds\LaravelCms\Blocks\BlockFieldValidator;
use Gingerminds\LaravelCms\Blocks\BlockRegistry;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Http\Controllers\AbstractController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Ajax endpoints powering the block canvas (add/edit modal, step 2 — see
 * docs/Blocks.md). Step 1 (the block picker) is rendered server-side inline
 * with the page, no ajax round-trip needed for that part.
 */
class PageBlockController extends AbstractController
{
    /**
     * Returns the schema-driven form fragment for a block type, pre-filled
     * with `data`/`uid` query params when reopening an existing block for
     * editing.
     */
    public function form(Request $request, string $key): JsonResponse
    {
        $this->authorize('update', ResourceResolver::model('page'));

        $block = BlockRegistry::find($key);

        if (!$block) {
            abort(404);
        }

        $data = BlockFieldValidator::defaultsForBlock($block);

        $submitted = json_decode((string) $request->query('data', ''), true);
        if (is_array($submitted)) {
            $data = array_merge($data, $submitted);
        }

        $uid = (string) ($request->query('uid') ?: Str::uuid());

        $html = view('gingerminds-cms::blocks.partials.form', [
            'block' => $block,
            'uid'   => $uid,
            'data'  => $data,
        ])->render();

        return response()->json([
            'uid'   => $uid,
            'type'  => $block->key(),
            'label' => $block->label(),
            'html'  => $html,
        ]);
    }

    /**
     * Validates one block's submitted data against its field schema and
     * returns its rendered preview fragment — the modal never trusts a
     * JS-side re-render of the preview.
     */
    public function validateBlock(Request $request, string $key): JsonResponse
    {
        $this->authorize('update', ResourceResolver::model('page'));

        $block = BlockRegistry::find($key);

        if (!$block) {
            abort(404);
        }

        // Validator::make(...) rather than $request->validate(...): the
        // latter has no way to pass custom attribute names, and without
        // them a failed "title" rule reads "The data.title field is
        // required." instead of "The Title field is required.".
        $validated = Validator::make(
            $request->all(),
            BlockFieldValidator::rulesForBlock($block),
            [],
            BlockFieldValidator::attributesForBlock($block)
        )->validate();

        $data = array_merge(BlockFieldValidator::defaultsForBlock($block), $validated['data'] ?? []);

        $uid = (string) ($request->input('uid') ?: Str::uuid());

        $preview = view($block->previewView(), [
            'block' => $block,
            'uid'   => $uid,
            'data'  => $data,
        ])->render();

        return response()->json([
            'uid'     => $uid,
            'type'    => $block->key(),
            'label'   => $block->label(),
            'data'    => $data,
            'preview' => $preview,
        ]);
    }
}
