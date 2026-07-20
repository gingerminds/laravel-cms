<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + a `repeater` of question/answer pairs, each being a required
 * `question` (text) and a required `answer` (wysiwyg) — structurally the
 * simplest `repeater` block in this package, no `file`/media sub-field.
 */
class FrequentlyAskedQuestions extends AbstractBlock
{
    public function key(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.faq.label');
    }

    public function icon(): string
    {
        return 'bi-question-lg';
    }

    public function order(): int
    {
        return 500;
    }

    public function fields(): array
    {
        return [
            $this->textField('title', $this->fieldLabel('title'), required: true),
            $this->repeaterField(
                'items',
                $this->fieldLabel('items'),
                [
                    $this->textField('question', $this->fieldLabel('question'), required: true, size: 'xl'),
                    $this->wysiwygField('answer', $this->fieldLabel('answer'), required: true),
                ],
                $this->fieldLabel('add_item'),
                $this->fieldLabel('item_label'),
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.faq.preview';
    }
}
