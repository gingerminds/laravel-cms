@php $depth = $depth ?? 0; @endphp

@php
    $hasChildren = $category->adminChildren->isNotEmpty();
    $paddingLeft = 1 + ($depth * 1.5);
@endphp

<div class="category-tree-item"
     data-category-id="{{ $category->id }}"
     data-category-name="{{ $category->name }}"
     style="padding-left: {{ $paddingLeft }}rem;">

    <span class="toggle-icon">
        @if($hasChildren)
            <i class="bi bi-chevron-right" style="font-size: .75rem;"></i>
        @endif
    </span>

    <i class="bi {{ $hasChildren ? 'bi-folder2' : 'bi-folder2-open' }} category-icon"></i>

    <span>{{ $category->name }}</span>
    <span class="text-muted small ms-1">{{ $category->code }}</span>
</div>

@if($hasChildren)
    <div class="category-tree-children">
        @foreach($category->adminChildren as $child)
            @include('gingerminds-cms::pages.pages.partials.modal-choose-category-options', [
                'category' => $child,
                'depth'    => $depth + 1,
            ])
        @endforeach
    </div>
@endif
