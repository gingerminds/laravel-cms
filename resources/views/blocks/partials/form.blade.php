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
@php use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver as MediaResourceResolver; @endphp
<form
    class="cms-block-form"
    data-cms-block-key="{{ $block->key() }}"
    data-cms-block-uid="{{ $uid }}"
    enctype="multipart/form-data"
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

                @case('toggle')
                    {{-- id doubles as name, same limitation as textarea/select
                         above: the toggle component renders its own hidden
                         "0" fallback + the checkbox under that one name —
                         `new FormData(form)` (add-block.js) resolves the
                         pair using plain HTML form semantics, no custom JS
                         collection needed. --}}
                    <x-gingerminds-core::form.inputs.toggle
                        id="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :checked="(bool) $value"
                        :helper="$field['helper'] ?? null"
                    />
                    @break

                @case('media')
                    {{-- id doubles as name too — media-select builds its own
                         hidden input(s) from `name` (`data[xxx]` single, or
                         `data[xxx][]` per selected item when `multiple`),
                         collected as-is by `new FormData(form)`
                         (add-block.js). --}}
                    @php
                        $mediaModelClass = MediaResourceResolver::model('media');
                        $isMultipleMedia = (bool) ($field['multiple'] ?? false);
                        $selectedMedia   = $isMultipleMedia
                            ? $mediaModelClass::query()->whereIn('id', array_filter((array) $value))->get()
                            : (empty($value) ? null : $mediaModelClass::query()->find($value));
                    @endphp
                    <x-gingerminds-media-manager::form.inputs.media-select
                        id="{{ $inputId }}"
                        name="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :multiple="$isMultipleMedia"
                        :selected="$selectedMedia"
                        :size="$size"
                        :category-codes="$field['category_codes'] ?? []"
                    />
                    @break

                @case('file')
                    {{-- Exclusive upload (BlockFileFieldSync), not the
                         shared media library — see docs/Blocks.md. The
                         hidden input below carries the current File id
                         forward across a save that doesn't touch this
                         field; sharing its name with the file input below
                         is safe (Laravel keeps $_POST/$_FILES separate even
                         under an identical key), and lets
                         BlockFileFieldSync::sync() always know the "old"
                         file regardless of what else was submitted.

                         `accept` mirrors the field's `mimes` schema key
                         (BlockFieldValidator::fileRules() — same default:
                         images only when `mimes` is omitted, unrestricted
                         when explicitly set to null/[]), so the browser's
                         file picker and the server-side validation never
                         disagree. An explicit `accept` key overrides this
                         when the HTML hint should differ from the
                         validation patterns. --}}
                    @php
                        $existingFileModel = is_string($value) && $value !== ''
                            ? \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($value)
                            : null;

                        $fieldMimes = array_key_exists('mimes', $field) ? $field['mimes'] : ['image/*'];
                        $fieldMimes = empty($fieldMimes) ? null : (array) $fieldMimes;
                        $accept     = $field['accept'] ?? ($fieldMimes ? implode(',', $fieldMimes) : null);
                    @endphp
                    <input type="hidden" name="data[{{ $fieldName }}]" value="{{ is_string($value) ? $value : '' }}">
                    <x-gingerminds-media-manager::form.inputs.file
                        id="{{ $inputId }}"
                        name="data[{{ $fieldName }}]"
                        :label="$field['label']"
                        :required="$required"
                        :existing-file="$existingFileModel"
                        :accept="$accept"
                        :max-size="$field['max_size_mb'] ?? 5"
                        :size="$size"
                    />
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
</form>
