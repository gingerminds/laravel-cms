@extends('gingerminds-core::layouts.crud.list-tree')

@section('title')
    @lang('gingerminds-cms::translation.page_categories.manage')
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_list', ['model' => __('gingerminds-cms::translation.page_categories.name_p')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.page_categories.name_p'), 'url' => route('gingerminds-cms.page-categories.index')],
            ['label' => __('gingerminds-cms::translation.page_categories.manage'), 'active' => true],
        ]"
    />
@endsection

@section('actions')
    <a href="{{ route('gingerminds-cms.page-categories.create') }}" class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg me-1"></i> @lang('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.page_categories.name_s')])
    </a>
@endsection

@section('tree')
    @if($rootItems->isEmpty())
        <div class="text-center text-muted py-5">
            @lang('gingerminds-cms::translation.page_categories.message.no_result')
        </div>
    @else
        @include('gingerminds-cms::pages.page_categories.partials.tree', [
            'treeItems' => $rootItems,
            'depth' => 0,
        ])
    @endif
@endsection

@push('modals')
    <x-gingerminds-core::modal.modal-delete
        :model="__('gingerminds-cms::translation.page_categories.name_s')"
        routing="gingerminds-cms.page-categories"/>
@endpush
