<div class="row">
    <div class="col-lg-8">
        <div class="row mb-3">
            <x-gingerminds-core::form.inputs.basic
                    id="translations_{{ $language->id }}_name"
                    label="{{ __('gingerminds-cms::translation.page_categories.form.name') }}"
                    :required="$required"
                    name="translations[{{ $language->id }}][name]"
                    value="{{ old('translations.'.$language->id.'.name', $translation?->name) }}"
                    size="xl"
            />
        </div>
        <div class="row mb-3">
            @php
                $parentPath = $parentPaths[$language->id] ?? '';
            @endphp
            <x-gingerminds-core::form.inputs.basic
                    id="translations_{{ $language->id }}_prefix"
                    label="{{ __('gingerminds-cms::translation.page_categories.form.prefix') }}"
                    :required="false"
                    name="translations[{{ $language->id }}][prefix]"
                    value="{{ old('translations.'.$language->id.'.prefix', $translation?->prefix) }}"
                    prefix="/{{ $parentPath }}{{ '' !== $parentPath ? '/' : '' }}"
                    suffix="/"
                    :helper="__('gingerminds-cms::translation.page_categories.form.prefix_hint')"
                    size="xl"
            />
        </div>
    </div>
</div>
