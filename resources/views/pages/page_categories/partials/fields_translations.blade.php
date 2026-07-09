@php
    use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;

    $selectedParentId = old('parent_id', isset($pageCategory)
        ? $pageCategory->parent_id
        : ($parentId ?? null)
    );

    /** @var PageCategory|null $selectedParent */
    $selectedParent = $selectedParentId
        ? collect($categories)->firstWhere('category.id', (int) $selectedParentId)['category'] ?? null
        : null;

    $parentPaths = [];
    foreach ($languages as $language) {
        $parentPaths[$language->id] = $selectedParent?->getFullPathForLanguage($language->id) ?? '';
    }
@endphp

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <x-gingerminds-multisite::form.inputs.translations
                :languages="$languages"
                :translations="isset($pageCategory)
        ? $pageCategory->translations->keyBy('language_id')
        : []"
                fields-view="gingerminds-cms::pages.page_categories.partials.translation-field"
                :default-language="$defaultLanguage"
                :extra="['parentPaths' => $parentPaths]"
            />
        </div>
    </div>
</div>
