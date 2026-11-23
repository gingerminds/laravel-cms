<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Http\Controllers\Page;

use Gingerminds\LaravelCms\Blocks\BlockFieldValidator;
use Gingerminds\LaravelCms\Blocks\BlockFileFieldSync;
use Gingerminds\LaravelCms\Blocks\BlockRequestSupport;
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
 *
 * The parts that aren't Page-specific (resolving a block or 404, resolving
 * its uid, rendering one of its views) live in `Blocks\BlockRequestSupport`
 * — this controller only owns what actually is Page-specific: the
 * authorization check and the two response shapes.
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

        $block = BlockRequestSupport::resolveOrAbort($key);

        $data = BlockFieldValidator::defaultsForBlock($block);

        $submitted = json_decode((string) $request->query('data', ''), true);
        if (is_array($submitted)) {
            $data = array_merge($data, $submitted);
        }

        $uid  = BlockRequestSupport::resolveUid($request);
        $html = BlockRequestSupport::renderView('gingerminds-cms::blocks.partials.form', $block, $uid, $data);

        return response()->json([
            'uid' => $uid,
            'type' => $block->key(),
            'label' => $block->label(),
            'html' => $html,
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

        $block = BlockRequestSupport::resolveOrAbort($key);

        $requestData         = $request->all();
        $requestData['data'] = BlockFieldValidator::sanitizeDataForBlock($block, $requestData['data'] ?? []);

        // Validator::make(...) rather than $request->validate(...): the
        // latter has no way to pass custom attribute names, and without
        // them a failed "title" rule reads "The data.title field is
        // required." instead of "The Title field is required.".
        $validated = Validator::make(
            $requestData,
            BlockFieldValidator::rulesForBlock($block),
            [],
            BlockFieldValidator::attributesForBlock($block)
        )->validate();

        $data = array_merge(BlockFieldValidator::defaultsForBlock($block), $validated['data'] ?? []);
        $data = BlockFileFieldSync::sync($block, $request, $data);
        $uid  = BlockRequestSupport::resolveUid($request);

        $preview = BlockRequestSupport::renderView($block->previewView(), $block, $uid, $data);

        return response()->json([
            'uid' => $uid,
            'type' => $block->key(),
            'label' => $block->label(),
            'data' => $data,
            'preview' => $preview,
        ]);
    }

    /**
     * Copy-structure endpoint: returns each submitted block with its own
     * duplicated File(s) and a freshly rendered preview, under a new uid.
     */
    public function duplicate(Request $request): JsonResponse
    {
        $this->authorize('update', ResourceResolver::model('page'));

        $submitted = $request->validate([
            'blocks'        => ['present', 'array'],
            'blocks.*.type' => ['required', 'string'],
            'blocks.*.data' => ['nullable', 'array'],
        ])['blocks'];

        $payloads = [];

        foreach ($submitted as $item) {
            $block = BlockRequestSupport::resolveOrAbort($item['type']);
            $data  = BlockFileFieldSync::duplicateFiles($block, $item['data'] ?? []);
            $uid   = (string) Str::uuid();

            $payloads[] = [
                'uid' => $uid,
                'type' => $block->key(),
                'label' => $block->label(),
                'data' => $data,
                'preview' => BlockRequestSupport::renderView($block->previewView(), $block, $uid, $data),
            ];
        }

        return response()->json(['blocks' => $payloads]);
    }
}
