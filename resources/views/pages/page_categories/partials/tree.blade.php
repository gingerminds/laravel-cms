@php $depth = $depth ?? 0; @endphp

<div class="sortable-level"
     data-parent-id="{{ $treeItems->first()?->parent_id ?? '' }}"
     style="@if($depth > 0) padding-left: 28px; border-left: 2px solid #f7f7f7; margin-left: 20px; @endif">

    @foreach($treeItems as $item)
        <div class="sortable-item" data-item-id="{{ $item->id }}">
            <div class="tree-item-row d-flex align-items-center justify-content-between py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    @if($depth > 0)
                        <span class="tree-connector text-muted">
                            <i class="bi bi-arrow-return-right"></i>
                        </span>
                    @endif
                    <span class="fw-medium">{{ $item->name }}</span>
                    <span class="text-muted small">/{{ $item->full_path }}</span>
                </div>

                <fieldset class="btn-group btn-group-sm">
                    <a href="{{ route('gingerminds-cms.page-categories.create', ['parent_id' => $item->id]) }}"
                       class="btn btn-outline-success"
                       title="@lang('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.page_categories.name_s')])">
                        <i class="bi bi-plus-lg"></i>
                        @lang('gingerminds-cms::translation.page_categories.action.add_child')
                    </a>
                    <a class="btn btn-sm btn-outline-primary"
                       href="{{ route('gingerminds-cms.page-categories.edit', $item) }}"
                       title="@lang('gingerminds-core::translation.action.see')">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <button type="button"
                            class="btn btn-outline-danger js-remove-item"
                            data-bs-toggle="modal"
                            data-bs-target="#removeModal"
                            data-model="@lang('gingerminds-cms::translation.page_categories.name_s')"
                            data-remove-name="{{ $item->name ?? $item->id }}"
                            data-destroy-url="{{ route('gingerminds-cms.page-categories.destroy', $item) }}"
                            title="@lang('gingerminds-core::translation.action.remove')">
                        <i class="bi bi-trash"></i>
                    </button>
                </fieldset>
            </div>

            @if($item->adminChildren->isNotEmpty())
                @include('gingerminds-cms::pages.page_categories.partials.tree', [
                    'treeItems' => $item->adminChildren,
                    'depth'     => $depth + 1,
                ])
            @endif
        </div>
    @endforeach

</div>
