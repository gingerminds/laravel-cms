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
class LinkList extends AbstractBlock
{
    public function key(): string
    {
        return 'link_list';
    }

    public function label(): string
    {
        return __('gingerminds-cms::translation.blocks.link_list.label');
    }

    public function icon(): string
    {
        return 'bi-link-45deg';
    }

    public function order(): int
    {
        return 400;
    }

    public function fields(): array
    {
        return [
            [
                'name' => 'title',
                'type' => 'text',
                'label' => __('gingerminds-cms::translation.blocks.link_list.fields.title'),
                'required' => true,
                'size' => 'md',
            ],
            [
                'name' => 'links',
                'type' => 'repeater',
                'label' => __('gingerminds-cms::translation.blocks.link_list.fields.links'),
                'required' => false,
                'default' => [],
                'add_label' => __('gingerminds-cms::translation.blocks.link_list.fields.add_link'),
                'item_label' => __('gingerminds-cms::translation.blocks.link_list.fields.link_item_label'),
                'fields' => [
                    [
                        'name' => 'label',
                        'type' => 'text',
                        'label' => __('gingerminds-cms::translation.blocks.link_list.fields.link_label'),
                        'required' => true,
                        'size' => 'md',
                    ],
                    [
                        'name' => 'url',
                        'type' => 'text',
                        'label' => __('gingerminds-cms::translation.blocks.link_list.fields.link_url'),
                        'required' => true,
                        'size' => 'md',
                        'rules' => ['url'],
                    ],
                    [
                        'name' => 'image',
                        'type' => 'file',
                        'label' => __('gingerminds-cms::translation.blocks.link_list.fields.link_image'),
                        'required' => false,
                        'size' => 'md',
                        'max_size_kb' => 2048,
                        'max_size_mb' => 2,
                    ],
                ],
            ],
        ];
    }

    public function previewView(): string
    {
        return 'gingerminds-cms::blocks.type.link-list.preview';
    }
}
