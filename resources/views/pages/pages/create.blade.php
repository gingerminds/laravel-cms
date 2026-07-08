@extends('gingerminds-core::layouts.crud.form-tabs')

@section('title')
    @lang('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')])
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.pages.name_p'), 'url' => route('gingerminds-cms.pages.index')],
            ['label' => __('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')]), 'active' => true],
        ]"
    />
@endsection

@php
    $action = route('gingerminds-cms.pages.store');
    $indexRoute = route('gingerminds-cms.pages.index');
    $method = 'POST';
    $id = 'create-pages-form';
    $title = __('gingerminds-core::translation.title_m_create', ['model' => __('gingerminds-cms::translation.pages.name_s')]);
@endphp

@section('tabs')
    @include('gingerminds-cms::pages.menu_items.partials.form_nav')
@endsection

@section('tab-content')
    <div class="tab-pane fade show active" id="general">
        <div class="row">
            @include('gingerminds-cms::pages.pages.partials.fields')
        </div>
    </div>
    <div class="tab-pane fade" id="translations">
        @include('gingerminds-cms::pages.pages.partials.fields_translations')
    </div>
@endsection
