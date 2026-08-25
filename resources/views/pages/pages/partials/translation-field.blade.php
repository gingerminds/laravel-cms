<div class="row">
    <div class="col-lg-8">
        <x-gingerminds-cms::form.inputs.canvas
                :language="$language"
                :translation="$translation"
        />
    </div>
    <div class="col-lg-4">
        @isset($page)
            <div class="row mb-3">
                <div class="d-flex justify-content-end">
                    <x-gingerminds-cms::preview-button
                            :site="$page->site"
                            content-type="page"
                            :content-id="$page->id"
                            :language="$language"
                    />
                </div>
            </div>
            <hr>
        @endisset
        <div class="row mb-3">
            <x-gingerminds-core::form.inputs.basic
                    id="translations_{{ $language->id }}_title"
                    label="{{ __('gingerminds-cms::translation.form.title') }}"
                    :required="$required"
                    name="translations[{{ $language->id }}][title]"
                    value="{{ old('translations.'.$language->id.'.title', $translation?->title) }}"
                    size="xl"
            />
        </div>
        <div class="row mb-3">
            @php
                $categoryPath = $categoryPaths[$language->id] ?? '';
            @endphp
            <x-gingerminds-core::form.inputs.basic
                    id="translations_{{ $language->id }}_slug"
                    label="{{ __('gingerminds-cms::translation.form.slug') }}"
                    :required="false"
                    name="translations[{{ $language->id }}][slug]"
                    value="{{ old('translations.'.$language->id.'.slug', $translation?->slug) }}"
                    prefix="/{{ $categoryPath }}{{ '' !== $categoryPath ? '/' : '' }}"
                    size="xl"
                    data-slug-source="#translations_{{ $language->id }}_title"
                    data-slug-overwrite="{{ $slugOverwrite ? 'true' : 'false' }}"
            />
        </div>
        <div class="row mb-3">
            <x-gingerminds-media-manager::form.inputs.file
                    id="translations_{{ $language->id }}_main_visual"
                    name="translations[{{ $language->id }}][main_visual]"
                    :label="__('gingerminds-cms::translation.form.main_visual')"
                    accept="image/*"
                    :required="false"
                    :existing-file="$translation?->mainVisual"
                    size="xl"
                    :maxSize="round(config('gingerminds-media-manager.max_thumbnail_size') / 1024, 1)"
            />
        </div>
        <div class="row mb-3">
            <x-gingerminds-media-manager::form.inputs.file
                    id="translations_{{ $language->id }}_thumbnail"
                    name="translations[{{ $language->id }}][thumbnail]"
                    :label="__('gingerminds-media-manager::translation.form.thumbnail')"
                    accept="image/*"
                    :required="false"
                    :existing-file="$translation?->thumbnail"
                    size="xl"
                    :maxSize="round(config('gingerminds-media-manager.max_thumbnail_size') / 1024, 1)"
            />
        </div>
        <div class="row">
            <x-gingerminds-cms::form.inputs.wysiwyg
                    id="translations_{{ $language->id }}_hook"
                    name="translations[{{ $language->id }}][hook]"
                    :required="false"
                    :label="__('gingerminds-cms::translation.form.hook')"
                    :value="old(
            'translations.'.$language->id.'.hook',
            $translation?->hook
        )"
                    preset="minimal"
                    rows="8"
                    size="xl"
            />
        </div>
    </div>
</div>
