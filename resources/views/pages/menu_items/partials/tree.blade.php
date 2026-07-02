@php $depth = $depth ?? 0; @endphp

<div class="sortable-level"
     data-parent-id="{{ $treeItems->first()?->parent_id ?? '' }}"
     style="@if($depth > 0) padding-left: 28px; border-left: 2px solid #f7f7f7; margin-left: 20px; @endif">

    @foreach($treeItems as $item)
        <div class="sortable-item" data-item-id="{{ $item->id }}">
            <div class="tree-item-row d-flex align-items-center justify-content-between py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="drag-handle text-muted" style="cursor: grab;" title="">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    @if($depth > 0)
                        <span class="tree-connector text-muted">
                            <i class="bi bi-arrow-return-right"></i>
                        </span>
                    @endif
                    <span class="fw-medium">{{ $item->currentTranslation?->name ?? $item->code }}</span>
                    @if(!$item->is_active)
                        <span class="badge bg-danger-subtle text-danger">@lang('gingerminds-core::translation.form.inactive')</span>
                    @endif
                </div>

                <fieldset class="btn-group btn-group-sm">
                    {{-- Add child --}}
                    <a href="{{ route('gingerminds-cms.menu_items.create', ['menu' => $menu->id, 'parent_id' => $item->id]) }}"
                       class="btn btn-outline-success"
                       title="@lang('gingerminds-cms::translation.menu_items.action.add_child')">
                        <i class="bi bi-plus-lg me-1"></i>
                        @lang('gingerminds-cms::translation.menu_items.action.add_child')
                    </a>

                    {{-- Edit --}}
                    <a href="{{ route('gingerminds-cms.menu_items.edit', ['menu' => $menu->id, 'menuItem' => $item->id]) }}"
                       class="btn btn-outline-primary"
                       title="@lang('gingerminds-core::translation.action.see')">
                        <i class="bi bi-pencil-square"></i>
                    </a>

                    {{-- Delete --}}
                    <button type="button"
                            class="btn btn-outline-danger js-remove-item"
                            data-bs-toggle="modal"
                            data-bs-target="#removeModal"
                            data-model="@lang('gingerminds-cms::translation.menu_items.name_s')"
                            data-remove-name="{{ $item->currentTranslation?->name ?? $item->id }}"
                            data-destroy-url="{{ route('gingerminds-cms.menu_items.destroy', ['menu' => $menu->id, 'menuItem' => $item->id]) }}"
                            title="@lang('gingerminds-core::translation.action.remove')">
                        <i class="bi bi-trash"></i>
                    </button>
                </fieldset>
            </div>

            @if($item->adminChildren->isNotEmpty())
                @include('gingerminds-cms::pages.menu_items.partials.tree', [
                    'treeItems' => $item->adminChildren,
                    'depth'     => $depth + 1,
                    'menu'      => $menu,
                ])
            @endif
        </div>
    @endforeach

</div>
