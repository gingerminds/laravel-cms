<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + a `repeater` of media items, each item being a `media` (shared
 * library, single) and an optional `subtitle` (text). Structurally the same
 * shape as `LinkList`/`Cards`, just with a shared-library `media` field
 * instead of an exclusive `file` upload per row.
 *
 * This block has no notion of anything beyond that generic shape (no
 * broadcast country, no availability logic, ...) — a consuming project that
 * needs to enrich or restrict it further (e.g. an extra field, a different
 * preview, or resolving its items without some project-specific scope) does
 * so by overriding this class from its own `App\Cms\BlockOverrides`
 * namespace and registering it under `gingerminds-cms.blocks.media_list`,
 * same mechanism as every other block type here.
 */
class MediaList extends AbstractBlock
{
    public function key(): string
    {
        return 'media_list';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.media_list.label');
    }

    public function icon(): string
    {
        return 'bi-collection-play';
    }

    public function order(): int
    {
        return 450;
    }

    public function fields(): array
    {
        return [
            $this->textField('title', __('gingerminds-cms::translation.blocks.media_list.fields.title')),
            $this->repeaterField(
                'items',
                __('gingerminds-cms::translation.blocks.media_list.fields.items'),
                [
                    $this->mediaField(
                        'media',
                        __('gingerminds-cms::translation.blocks.media_list.fields.media'),
                        required: true,
                    ),
                    $this->textField('subtitle', __('gingerminds-cms::translation.blocks.media_list.fields.subtitle')),
                ],
                __('gingerminds-cms::translation.blocks.media_list.fields.add_item'),
                __('gingerminds-cms::translation.blocks.media_list.fields.item_label'),
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.media-list.preview';
    }
}
