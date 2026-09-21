@php use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface; @endphp
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

            $mediaEagerLoads = is_subclass_of($mediaModelClass, EagerLoadableModelInterface::class)
                ? $mediaModelClass::getEagerLoads()
                : [];

            $selectedMedia = $isMultipleMedia
                ? $mediaModelClass::query()->with($mediaEagerLoads)->whereIn('id', array_filter((array) $value))->get()
                : (empty($value) ? null : $mediaModelClass::query()->with($mediaEagerLoads)->find($value));

            $languageModelClass = \Gingerminds\LaravelMultisite\Resolver\ResourceResolver::model('language');
            $allLanguageIsos    = $languageModelClass::query()->pluck('iso')->all();
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
            :languages="$allLanguageIsos"
            :endpoint="$field['endpoint'] ?? null"
            :category-endpoint="$field['category_endpoint'] ?? null"
        />
        @break

    @case('file')
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
