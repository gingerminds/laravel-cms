<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Reference block for the block contract (docs/ContentBlocks.md step 2):
 * a title + video field, both schema-driven (no custom form Blade).
 */
class Video extends AbstractBlock
{
    public function key(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.video.label');
    }

    public function icon(): string
    {
        return 'bi-play-circle';
    }

    public function order(): int
    {
        return 200;
    }

    public function fields(): array
    {
        return [
            // Was pointing at `title_text.fields.title` (copy-paste leftover
            // from that reference block) — harmless today since both keys
            // happen to translate to the same "Titre"/"Title", but wrong
            // and would silently ignore a future edit to this block's own
            // key. See resources/lang/*/translation.php's `video.fields.title`.
            $this->textField('title', __('gingerminds-cms::translation.blocks.video.fields.title')),
            $this->textField(
                'embed_code',
                __('gingerminds-cms::translation.blocks.video.fields.embed_code'),
                required: true,
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.video.preview';
    }
}
