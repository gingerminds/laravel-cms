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
            // Was pointing at `link_list.fields.title` (copy-paste
            // leftover from that block, see its docblock this one used to
            // share verbatim) — same "harmless today, wrong and fragile"
            // situation as Video/Media's own title-field slip.
            $this->textField('title', __('gingerminds-cms::translation.blocks.faq.fields.title'), required: true),
            $this->repeaterField(
                'items',
                __('gingerminds-cms::translation.blocks.faq.fields.items'),
                [
                    $this->textField(
                        'question',
                        __('gingerminds-cms::translation.blocks.faq.fields.question'),
                        required: true,
                        size: 'xl',
                    ),
                    $this->wysiwygField(
                        'answer',
                        __('gingerminds-cms::translation.blocks.faq.fields.answer'),
                        required: true,
                    ),
                ],
                __('gingerminds-cms::translation.blocks.faq.fields.add_item'),
                __('gingerminds-cms::translation.blocks.faq.fields.item_label'),
            ),
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.faq.preview';
    }
}
