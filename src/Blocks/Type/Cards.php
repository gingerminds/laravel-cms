<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + text, followed by a `repeater` of cards (title, description,
 * image) — the first block in this package to use the `repeater` field
 * type (docs/Blocks.md): a card's `image` is an exclusive `file` field of
 * its own row (BlockFileFieldSync), just like `TextImage::image`, uploaded/
 * replaced/pruned independently per card.
 */
class Cards extends AbstractBlock
{
    public function key(): string
    {
        return 'cards';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.cards.label');
    }

    public function icon(): string
    {
        return 'bi-grid-3x2-gap';
    }

    public function order(): int
    {
        return 300;
    }

    public function fields(): array
    {
        return [
            [
                'name'     => 'title',
                'type'     => 'text',
                'label'    => __('gingerminds-cms::translation.blocks.cards.fields.title'),
                'required' => true,
                'size'     => 'md',
            ],
            [
                'name'     => 'text',
                'type'     => 'wysiwyg',
                'label'    => __('gingerminds-cms::translation.blocks.cards.fields.text'),
                'required' => false,
                'size'     => 'xl',
                'preset'   => 'default',
                'rows'     => 6,
            ],
            [
                'name'       => 'cards',
                'type'       => 'repeater',
                'label'      => __('gingerminds-cms::translation.blocks.cards.fields.cards'),
                'required'   => false,
                'default'    => [],
                'add_label'  => __('gingerminds-cms::translation.blocks.cards.fields.add_card'),
                'item_label' => __('gingerminds-cms::translation.blocks.cards.fields.card_item_label'),
                'fields'     => [
                    [
                        'name'     => 'title',
                        'type'     => 'text',
                        'label'    => __('gingerminds-cms::translation.blocks.cards.fields.card_title'),
                        'required' => false,
                        'size'     => 'md',
                    ],
                    [
                        'name'     => 'description',
                        'type'     => 'textarea',
                        'label'    => __('gingerminds-cms::translation.blocks.cards.fields.card_description'),
                        'required' => false,
                        'size'     => 'xl',
                    ],
                    [
                        'name'     => 'image',
                        'type'     => 'file',
                        'label'    => __('gingerminds-cms::translation.blocks.cards.fields.card_image'),
                        'required' => true,
                        'size'     => 'md',
                    ],
                ],
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.cards.preview';
    }
}
