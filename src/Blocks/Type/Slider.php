<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + a `repeater` of slides, each slide being nothing but an
 * exclusive `file` upload (BlockFileFieldSync) — same "one field per row"
 * shape as `Cards`, just without the extra description/title per item:
 * a slide is exactly as much a `file` field as `TextImage::image`, just
 * addressed as `data.slides.{index}.image` instead of `data.image`.
 */
class Slider extends AbstractBlock
{
    public function key(): string
    {
        return 'slider';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.slider.label');
    }

    public function icon(): string
    {
        return 'bi-images';
    }

    public function order(): int
    {
        return 350;
    }

    public function fields(): array
    {
        return [
            [
                'name'     => 'title',
                'type'     => 'text',
                'label'    => __('gingerminds-cms::translation.blocks.slider.fields.title'),
                'required' => false,
                'size'     => 'md',
            ],
            [
                'name'       => 'slides',
                'type'       => 'repeater',
                'label'      => __('gingerminds-cms::translation.blocks.slider.fields.slides'),
                'required'   => false,
                'default'    => [],
                'add_label'  => __('gingerminds-cms::translation.blocks.slider.fields.add_slide'),
                'item_label' => __('gingerminds-cms::translation.blocks.slider.fields.slide_item_label'),
                'fields'     => [
                    [
                        'name'     => 'image',
                        'type'     => 'file',
                        'label'    => __('gingerminds-cms::translation.blocks.slider.fields.slide_image'),
                        'required' => true,
                        'size'     => 'md',
                    ],
                ],
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.slider.preview';
    }
}
