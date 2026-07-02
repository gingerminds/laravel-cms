@extends('gingerminds-core::layouts.crud.form-tabs')

@section('title')
    @lang('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.menu_items.name_s')])
@endsection

@section('breadcrumb')
    <x-gingerminds-core::navigation.breadcrumb
        :title="__('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.menu_items.name_s')])"
        :items="[
            ['label' => __('gingerminds-cms::translation.menus.manage'), 'url' => route('gingerminds-cms.menus.index')],
            ['label' => $menuItem->menu?->code],
            ['label' => __('gingerminds-cms::translation.menu_items.name_p'), 'url' => route('gingerminds-cms.menu_items.index', [
                'menu' => $menuItem->menu_id
])],
            ['label' => $menuItem->currentTranslation?->name, 'active' => true],
        ]"
    />
@endsection

@php
    $action = route('gingerminds-cms.menu_items.update', [
        'menu' => $menuItem->menu_id,
        'menuItem' => $menuItem->id
]);
    $indexRoute = route('gingerminds-cms.menu_items.index', [
        'menu' => $menuItem->menu_id
]);
    $method = 'PATCH';
    $id = 'edit-menu_items-form';
    $title = __('gingerminds-core::translation.title_m_edit', ['model' => __('gingerminds-cms::translation.menu_items.name_s')]);
@endphp

@section('tabs')
    @include('gingerminds-cms::pages.menu_items.partials.form_nav')
@endsection

@section('tab-content')
    <div class="tab-pane fade show active" id="general">
        <div class="row">
            @include('gingerminds-cms::pages.menu_items.partials.fields')
        </div>
    </div>
    <div class="tab-pane fade" id="translations">
        @include('gingerminds-cms::pages.menu_items.partials.fields_translations')
    </div>
@endsection
