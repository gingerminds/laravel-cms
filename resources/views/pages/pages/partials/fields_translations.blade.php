<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <x-gingerminds-multisite::form.inputs.translations
                :languages="$languages"
                :translations="isset($page)
        ? $page->translations->keyBy('language_id')
        : []"
                fields-view="gingerminds-cms::pages.pages.partials.translation-field"
                :default-language="$defaultLanguage"
            />
        </div>
    </div>
</div>
