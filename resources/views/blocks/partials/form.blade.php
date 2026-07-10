{{--
    Generic schema-driven block form. Loops over $block->fields() and
    instantiates the matching existing input component — a simple block
    needs neither a new Blade view nor new JS (see docs/Blocks.md). Blocks
    that need more than this (conditional fields, custom layout) declare a
    `formView()` instead of `fields()` — out of scope for this step, see
    docs/ContentBlocks.md.

    Rendered as a standalone fragment (returned as JSON `html` by
    PageBlockController::form), injected into the step-2 modal body by
    content-blocks.js. Field names are plain `data[xxx]` (not
    `translations[...]`) since this mini-form is only ever submitted via
    ajax to PageBlockController::validateBlock, never as part of the page's
    main <form>.
--}}
<div
    class="cms-block-form"
    data-cms-block-key="{{ $block->key() }}"
    data-cms-block-uid="{{ $uid }}"
>
    <div class="row g-3">
        @foreach($block->fields() as $field)
            @php
                $fieldName = $field['name'];
                $value     = $data[$fieldName] ?? ($field['default'] ?? null);
                $inputId   = 'cms_block_field_' . $fieldName;
                $required  = (bool) ($field['required'] ?? false);
                $size      = $field['size'] ?? null;
            @endphp

            @switch($field['type'] ?? 'text')
                @case('wysiwyg')
                    <x-gingerminds-cms::form.inputs.wysiwyg
                        :id="$inputId"
                        name="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :value="$value"
                        :size="$size"
                        :preset="$field['preset'] ?? 'default'"
                        :rows="$field['rows'] ?? 6"
                    />
                    @break

                @case('textarea')
                    {{-- id doubles as name: the textarea component has no
                         separate name prop (same limitation as select below). --}}
                    <x-gingerminds-core::form.inputs.textarea
                        id="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :value="$value"
                        :size="$size"
                    />
                    @break

                @case('select')
                    <x-gingerminds-core::form.inputs.select
                        id="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :size="$size"
                    >
                        @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </x-gingerminds-core::form.inputs.select>
                    @break

                @default
                    <x-gingerminds-core::form.inputs.basic
                        :id="$inputId"
                        name="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :value="$value"
                        :size="$size"
                    />
            @endswitch
        @endforeach
    </div>

    <div class="cms-block-form-errors invalid-feedback d-block mt-2"></div>
</div>
