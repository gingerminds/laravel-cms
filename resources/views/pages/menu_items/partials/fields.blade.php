<div class="col-lg-8">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <x-gingerminds-core::form.inputs.basic
                        id="code"
                        :label="__('gingerminds-core::translation.form.code')"
                        :required="true"
                        value="{{ old('name', isset($menuItem) ? $menuItem->code : null) }}"
                    />
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.toggle
                        id="is_active"
                        :label="__('gingerminds-core::translation.form.is_active')"
                        :required="false"
                        :checked="isset($menuItem) && 1 === $menuItem->is_active"
                />
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.basic
                        type="number"
                        id="position"
                        :label="__('gingerminds-core::translation.form.position')"
                        :required="false"
                        :value="old('position', isset($menuItem) ? $menuItem->position : 0)"
                        :min="0"
                        size="xl"
                />
            </div>
            <div class="row">
                @php
                    $selectedParentId = old('parent_id', isset($menuItem)
                        ? $menuItem->parent_id
                        : (request()->query('parent_id') !== null ? (int) request()->query('parent_id') : null)
                    );
                @endphp
                <x-gingerminds-core::form.inputs.select
                        id="parent_id"
                        :label="__('gingerminds-cms::translation.menu_items.form.parent_id')"
                        :required="false"
                        size="xl"
                >
                    <option value="">— @lang('gingerminds-core::translation.none') —</option>
                    @foreach($menuItems as $menuItemOption)
                        <option
                                value="{{ $menuItemOption->id }}"
                                {{ (int)$selectedParentId === (int)$menuItemOption->id ? 'selected' : '' }}
                        >
                            {{ $menuItemOption->currentTranslation?->name }}
                        </option>
                    @endforeach
                </x-gingerminds-core::form.inputs.select>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.toggle
                    id="is_target_blank"
                    :label="__('gingerminds-cms::translation.form.is_target_blank')"
                    :required="false"
                    :checked="old('is_target_blank', isset($menuItem) ? $menuItem->is_target_blank : null)"
                />
            </div>
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.toggle
                        id="is_no_referrer"
                        :label="__('gingerminds-cms::translation.form.is_no_referrer')"
                        :required="false"
                        :checked="old('is_no_referrer', isset($menuItem) ? $menuItem->is_no_referrer : null)"
                />
            </div>
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.toggle
                        id="is_no_opener"
                        :label="__('gingerminds-cms::translation.form.is_no_opener')"
                        :required="false"
                        :checked="old('is_no_opener', isset($menuItem) ? $menuItem->is_no_opener : null)"
                />
            </div>
            <div class="row">
                <x-gingerminds-core::form.inputs.toggle
                        id="is_no_follow"
                        :label="__('gingerminds-cms::translation.form.is_no_follow')"
                        :required="false"
                        :checked="old('is_no_follow', isset($menuItem) ? $menuItem->is_no_follow : null)"
                />
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="menu_id" value="{{ isset($menuItem) ? $menuItem->menu_id : $menu->id }}">
