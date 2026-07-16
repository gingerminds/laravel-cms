<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Blocks\Type;

use Gingerminds\LaravelCms\Blocks\AbstractBlock;

/**
 * Title + a `repeater` of links, each link being `label` (text), `url`
 * (text, validated with Laravel's `url` rule via the sub-field's own
 * `rules` — same `array_merge($rules, $field['rules'] ?? [])` escape
 * hatch every other field type already goes through, `BlockFieldValidator
 * ::rulesForField()`), and an optional `image` (file, image mimes only —
 * the default when `mimes` is omitted, `BlockFieldValidator::fileRules()`
 * — capped at 2 MB via `max_size_kb`/`max_size_mb`, the two keys that
 * respectively drive server-side validation and the upload widget's own
 * hint, see `field.blade.php`/`BlockFieldValidator::fileRules()`).
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
            [
                'name'     => 'title',
                'type'     => 'text',
                'label'    => __('gingerminds-cms::translation.blocks.link_list.fields.title'),
                'required' => true,
                'size'     => 'md',
            ],
            [
                'name'       => 'items',
                'type'       => 'repeater',
                'label'      => __('gingerminds-cms::translation.blocks.faq.fields.items'),
                'required'   => false,
                'default'    => [],
                'add_label'  => __('gingerminds-cms::translation.blocks.faq.fields.add_item'),
                'item_label' => __('gingerminds-cms::translation.blocks.faq.fields.item_label'),
                'fields'     => [
                    [
                        'name'     => 'question',
                        'type'     => 'text',
                        'label'    => __('gingerminds-cms::translation.blocks.faq.fields.question'),
                        'required' => true,
                        'size'     => 'xl',
                    ],
                    [
                        'name'     => 'answer',
                        'type'     => 'wysiwyg',
                        'label'    => __('gingerminds-cms::translation.blocks.faq.fields.answer'),
                        'required' => true,
                        'size'     => 'xl',
                        'preset'   => 'default',
                        'rows'     => 8,
                    ],
                ],
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.faq.preview';
    }
}
