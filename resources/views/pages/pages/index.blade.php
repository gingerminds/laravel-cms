@extends('gingerminds-core::layouts.crud.list')

@php
    $filters = request()->get('filters', []);
    $indexRoute = 'gingerminds-cms.pages.index';
@endphp

@section('title')
    @lang('gingerminds-cms::translation.pages.manage')
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_list', ['model' => __('gingerminds-cms::translation.pages.name_p')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.pages.name_p'), 'url' => route('gingerminds-cms.pages.index')],
            ['label' => __('gingerminds-cms::translation.pages.manage'), 'active' => true],
        ]"
    />
@endsection

@section('actions')
    <a href="{{ route('gingerminds-cms.pages.create') }}" class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg me-1"></i> @lang('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')])
    </a>
@endsection

@php
    $columns = [
        ['name' => '#', 'sortable' => false],
        ['name' => __('gingerminds-core::translation.form.code'), 'sortable' => true, 'property' => 'code'],
        ['name' => __('gingerminds-cms::translation.form.status'), 'sortable' => true, 'property' => 'status'],
        ['name' => __('gingerminds-core::translation.actions'), 'sortable' => false],
    ];
    $sortBy = request()->query('sortBy');
    $sortOrder = request()->query('sort');
@endphp

@section('table_list')
    @include('gingerminds-cms::pages.pages.partials.list')
@endsection

@push('modals')
    <x-gingerminds-core::modal.modal-delete :model="__('translation.pages.name_s')" routing="gingerminds-cms.pages"/>
@endpush
