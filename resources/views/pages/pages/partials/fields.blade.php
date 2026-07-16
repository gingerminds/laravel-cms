@php use Gingerminds\LaravelCms\State\Page\Status\Draft; @endphp
<div class="col-lg-8">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <x-gingerminds-core::form.inputs.basic
                        id="code"
                        type="text"
                        label="Code"
                        required="true"
                        :value="old('code', isset($page) ? $page->code : null)"
                />
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <x-gingerminds-media-manager::form.inputs.file
                        id="main_visual"
                        :label="__('gingerminds-cms::translation.form.main_visual')"
                        accept="image/*"
                        :required="false"
                        :existing-file="isset($page) ? $page->mainVisual : null"
                />
                <x-gingerminds-media-manager::form.inputs.file
                        id="thumbnail"
                        :label="__('gingerminds-media-manager::translation.form.thumbnail')"
                        accept="image/*"
                        :required="false"
                        :existing-file="isset($page) ? $page->thumbnail : null"
                />
            </div>
        </div>
    </div>
</div>
<div class="col-lg-4">
    <div class="card">
        <div class="card-body">
            @php
                $selectedCategoryId = old('category_id', isset($page) ? $page->category_id : $category?->id);
            @endphp
            <x-gingerminds-core::form.inputs.select
                    id="category_id"
                    :label="__('gingerminds-cms::translation.pages.form.category')"
                    :required="false"
                    size="xl"
            >
                <option value="" {{ !$selectedCategoryId ? 'selected' : '' }}>— @lang('gingerminds-core::translation.none') —</option>
                @foreach($categories as $option)
                    <option
                            value="{{ $option['category']->id }}"
                            {{ (int) $selectedCategoryId === (int) $option['category']->id ? 'selected' : '' }}
                    >{{ str_repeat('— ', $option['depth']) }}{{ $option['category']->name }}</option>
                @endforeach
            </x-gingerminds-core::form.inputs.select>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                @php
                    $selectedStatus = old('status', isset($page)
                        ? $page->status
                        : Draft::class
                    );
                @endphp
                <x-gingerminds-core::form.inputs.select
                        id="status"
                        :label="__('gingerminds-cms::translation.pages.form.status')"
                        :required="true"
                        size="xl"
                >
                    <option value="{{ $selectedStatus }}" selected>@lang('gingerminds-cms::translation.pages.statuses.' . $selectedStatus::code())</option>
                    @foreach($statuses as $status)
                        <option
                                value="{{ $status }}"
                        >
                            @lang('gingerminds-cms::translation.pages.statuses.' . $status::code())
                        </option>
                    @endforeach
                </x-gingerminds-core::form.inputs.select>
                @if(isset($page) && null !== $page->published_at)
                    <p><br><strong>@lang('gingerminds-cms::translation.form.published_at')&nbsp;:</strong> {{ $page->published_at }}</p>
                @endif
                @if(isset($page) && null !== $page->archived_at)
                    <p><br><strong>@lang('gingerminds-cms::translation.form.archived_at')&nbsp;:</strong> {{ $page->archived_at }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
