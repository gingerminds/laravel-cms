@props([
    'categoryTree',
    'createRoute',
])

<div class="modal fade" id="modalChoosePageCategory" tabindex="-1" aria-labelledby="modalChoosePageCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChoosePageCategoryLabel">
                    @lang('gingerminds-cms::translation.page_categories.action.choose')
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="pageCategoryTree" class="category-tree" style="max-height: 420px; overflow-y: auto;">
                    {{-- A category is optional: a page can sit at a bare /{slug} with no prefix. --}}
                    <div class="category-tree-item" data-category-id="" data-category-name="@lang('gingerminds-core::translation.none')" style="padding-left: 1rem;">
                        <span class="toggle-icon"></span>
                        <i class="bi bi-folder2-open category-icon"></i>
                        <span>@lang('gingerminds-core::translation.none')</span>
                    </div>
                    @foreach($categoryTree as $category)
                        @include('gingerminds-cms::pages.pages.partials.modal-choose-category-options', [
                            'category' => $category,
                            'depth'    => 0,
                        ])
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto text-muted small" id="selectedPageCategoryLabel">
                    @lang('gingerminds-core::translation.none')
                </span>
                <input type="hidden" id="selectedPageCategoryId" value="">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    @lang('gingerminds-core::translation.action.cancel')
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmPageCategory" data-create-url="{{ $createRoute }}" disabled>
                    <i class="bi bi-plus-lg me-1"></i>
                    @lang('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')])
                </button>
            </div>
        </div>
    </div>
</div>
