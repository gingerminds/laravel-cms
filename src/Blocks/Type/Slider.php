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
            $this->textField('title', $this->fieldLabel('title')),
            $this->repeaterField(
                'slides',
                $this->fieldLabel('slides'),
                [
                    $this->fileField('image', $this->fieldLabel('slide_image'), required: true),
                ],
                $this->fieldLabel('add_slide'),
                $this->fieldLabel('slide_item_label'),
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.slider.preview';
    }
}
