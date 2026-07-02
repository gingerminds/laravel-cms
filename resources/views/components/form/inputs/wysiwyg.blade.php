@props([
    'id',
    'name' => null,
    'label',
    'size' => null,
    'required' => false,
    'value' => null,
    'preset' => 'default',
    'rows' => 6,
])

@php
    $sizeClass = match ($size) {
        'sm'  => 'col-md-4 col-sm-12',
        'lg'  => 'col-md-8 col-sm-12',
        'xl'  => 'col-md-12',
        default => 'col-md-6 col-sm-12',
    };

    $fieldName = $name ?? $id;
    $presetConfig = config('gingerminds-cms.wysiwyg.presets.' . $preset)
        ?? config('gingerminds-cms.wysiwyg.presets.default');
@endphp

<style>
    .wysiwyg-wrapper .wysiwyg-btn { color: #495057; }
    .wysiwyg-wrapper .wysiwyg-btn:hover { color: #495057; background-color: #e9ecef; border-color: #adb5bd; }
    .wysiwyg-wrapper .wysiwyg-btn.active { color: #343a40; background-color: #dee2e6; border-color: #adb5bd; }
</style>

<div class="{{ $sizeClass }}">
    <label for="{{ $id }}" class="form-label">
        {{ $label }}
        @if($required) <span class="text-danger">*</span> @endif
    </label>

    <div
        class="wysiwyg-wrapper @error($fieldName) is-invalid @enderror"
        data-wysiwyg
        data-wysiwyg-config="{{ json_encode($presetConfig) }}"
        data-wysiwyg-rows="{{ $rows }}"
    >
        <div class="wysiwyg-toolbar border border-bottom-0 rounded-top p-1 d-flex flex-wrap gap-1" style="background-color: #f8f9fb;"></div>
        <div class="wysiwyg-editor border rounded-bottom p-2"></div>
        <textarea
            name="{{ $fieldName }}"
            id="{{ $id }}"
            @if($required) required @endif
            style="display: none;"
        >{{ $value }}</textarea>
    </div>

    @error($fieldName)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
