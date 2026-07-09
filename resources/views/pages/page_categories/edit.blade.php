@extends('gingerminds-core::layouts.crud.form-tabs')

@section('title')
    @lang('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.page_categories.name_s')])
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.page_categories.name_s')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.page_categories.name_p'), 'url' => route('gingerminds-cms.page-categories.index')],
            ['label' => __('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.page_categories.name_s')]), 'active' => true],
        ]"
    />
@endsection

@php
    $action = route('gingerminds-cms.page-categories.update', $pageCategory);
    $indexRoute = route('gingerminds-cms.page-categories.index');
    $method = 'PATCH';
    $id = 'edit-page_category-form';
    $title = __('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.page_categories.name_s')]);
@endphp

@section('tabs')
    @include('gingerminds-cms::pages.menu_items.partials.form_nav')
@endsection

@section('tab-content')
    <div class="tab-pane fade show active" id="general">
        <div class="row">
            @include('gingerminds-cms::pages.page_categories.partials.fields')
        </div>
    </div>
    <div class="tab-pane fade" id="translations">
        @include('gingerminds-cms::pages.page_categories.partials.fields_translations')
    </div>
@endsection
