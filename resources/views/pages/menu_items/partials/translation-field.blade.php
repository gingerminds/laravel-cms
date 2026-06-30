<div class="row mb-3">
    <x-gingerminds-core::form.inputs.basic
        id="translations_{{ $language->id }}_name"
        label="{{ __('gingerminds-core::translation.form.name') }}"
        :required="$required"
        name="translations[{{ $language->id }}][name]"
        value="{{ old('translations.'.$language->id.'.name', $translation?->name) }}"
    />
</div>
<div class="row mb-3">
    <x-gingerminds-core::form.inputs.basic
        type="text"
        id="translations_{{ $language->id }}_url"
        label="{{ __('gingerminds-cms::translation.form.url') }}"
        :required="$required"
        name="translations[{{ $language->id }}][url]"
        value="{{ old('translations.'.$language->id.'.url', $translation?->url) }}"
    />
</div>
<div class="row">
    <x-gingerminds-core::form.inputs.textarea
        id="translations_{{ $language->id }}_description"
        label="{{ __('gingerminds-core::translation.form.description') }}"
        :required="false"
        name="translations[{{ $language->id }}][description]"
        value="{{ old('translations.'.$language->id.'.description', $translation?->description) }}"
    />
</div>
