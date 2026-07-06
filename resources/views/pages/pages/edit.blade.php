@extends('gingerminds-core::layouts.crud.form')

@section('title')
    @lang('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.pages.name_s')])
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.pages.name_s')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.pages.name_p'), 'url' => route('gingerminds-cms.pages.index')],
            ['label' => __('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.pages.name_s')]), 'active' => true],
        ]"
    />
@endsection

@php
    $action = route('gingerminds-cms.pages.update', $menu);
    $indexRoute = route('gingerminds-cms.pages.index');
    $method = 'PATCH';
    $id = 'edit-pages-form';
    $title = __('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.pages.name_s')]);
@endphp

@section('fields')
    @include('gingerminds-cms::pages.pages.partials.fields')
@endsection
