{{--
    Renders one schema field's input, whatever its nesting: a top-level
    block field (name="data[title]") or a `repeater` row's sub-field
    (name="data[cards][0][title]") — extracted from form.blade.php so
    `repeater.blade.php` can reuse the exact same per-type rendering for a
    row's sub-fields instead of duplicating the switch. Never included
    directly for a `repeater` field itself (form.blade.php/repeater.blade.php
    handle that case before reaching here).

    Expects, precomputed by the caller (see form.blade.php/repeater.blade.php):
    - $field: the schema entry (name, type, label, ...)
    - $value: the field's current value
    - $name: the full bracketed input name, e.g. "data[title]" or
      "data[cards][0][title]" (or "data[cards][__INDEX__][title]" inside the
      row <template>, replaced client-side — see repeater.js)
    - $inputId: a matching unique id, underscore-joined, e.g.
      "cms_block_field_title" or "cms_block_field_cards_0_title"

    Two different id schemes coexist below, both inherited unchanged from
    the original flat-only form.blade.php: a component taking `:id`/`name`
    separately (wysiwyg, media, file, default) uses $inputId/$name as-is;
    textarea/select/toggle have no separate `name` prop and use $name
    (the bracketed string) as their single `id` — doubling as name — since
    that's what those three components' own markup key off internally.
--}}
@php use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver as MediaResourceResolver; @endphp
@switch($field['type'] ?? 'text')
    @case('wysiwyg')
        <x-gingerminds-cms::form.inputs.wysiwyg
            :id="$inputId"
            name="{{ $name }}"
            :label="$field['label']"
            :required="$required"
            :value="$value"
            :size="$size"
            :preset="$field['preset'] ?? 'default'"
            :rows="$field['rows'] ?? 6"
        />
        @break

    @case('textarea')
        <x-gingerminds-core::form.inputs.textarea
            id="{{ $name }}"
            :label="$field['label']"
            :required="$required"
            :value="$value"
            :size="$size"
        />
        @break

    @case('select')
        <x-gingerminds-core::form.inputs.select
            id="{{ $name }}"
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
        {{-- The toggle component renders its own hidden "0" fallback + the
             checkbox under this one name — `new FormData(form)`
             (add-block.js) resolves the pair using plain HTML form
             semantics, no custom JS collection needed. --}}
        <x-gingerminds-core::form.inputs.toggle
            id="{{ $name }}"
            :label="$field['label']"
            :checked="(bool) $value"
            :helper="$field['helper'] ?? null"
        />
        @break

    @case('media')
        @php
            $mediaModelClass = MediaResourceResolver::model('media');
            $isMultipleMedia = (bool) ($field['multiple'] ?? false);
            $selectedMedia   = $isMultipleMedia
                ? $mediaModelClass::query()->whereIn('id', array_filter((array) $value))->get()
                : (empty($value) ? null : $mediaModelClass::query()->find($value));
        @endphp
        <x-gingerminds-media-manager::form.inputs.media-select
            id="{{ $inputId }}"
            name="{{ $name }}"
            :label="$field['label']"
            :required="$required"
            :multiple="$isMultipleMedia"
            :selected="$selectedMedia"
            :size="$size"
            :category-codes="$field['category_codes'] ?? []"
        />
        @break

    @case('file')
        {{-- Exclusive upload (BlockFileFieldSync), not the shared media
             library — see docs/Blocks.md. The hidden input below carries
             the current File id forward across a save that doesn't touch
             this field; sharing its name with the file input below is safe
             (Laravel keeps $_POST/$_FILES separate even under an identical
             key), and lets BlockFileFieldSync::sync() always know the
             "old" file regardless of what else was submitted — same for a
             repeater row's own image, addressed as
             data.cards.{index}.image.

             `accept` mirrors the field's `mimes` schema key
             (BlockFieldValidator::fileRules() — same default: images only
             when `mimes` is omitted, unrestricted when explicitly set to
             null/[]), so the browser's file picker and the server-side
             validation never disagree. An explicit `accept` key overrides
             this when the HTML hint should differ from the validation
             patterns. --}}
        @php
            $existingFileModel = is_string($value) && $value !== ''
                ? \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($value)
                : null;

            $fieldMimes = array_key_exists('mimes', $field) ? $field['mimes'] : ['image/*'];
            $fieldMimes = empty($fieldMimes) ? null : (array) $fieldMimes;
            $accept     = $field['accept'] ?? ($fieldMimes ? implode(',', $fieldMimes) : null);
        @endphp
        <input type="hidden" name="{{ $name }}" value="{{ is_string($value) ? $value : '' }}">
        <x-gingerminds-media-manager::form.inputs.file
            id="{{ $inputId }}"
            name="{{ $name }}"
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
            name="{{ $name }}"
            :label="$field['label']"
            :required="$required"
            :value="$value"
            :size="$size"
        />
@endswitch
