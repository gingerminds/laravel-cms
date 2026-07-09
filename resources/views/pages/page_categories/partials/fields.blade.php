<div class="col-lg-8">
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.basic
                        id="code"
                        type="text"
                        label="Code"
                        required="true"
                        :value="old('code', isset($pageCategory) ? $pageCategory->code : null)"
                />
            </div>
            <div class="row">
                <x-gingerminds-core::form.inputs.toggle
                        id="is_unique"
                        :label="__('gingerminds-cms::translation.page_categories.form.is_unique')"
                        :checked="old('is_unique', isset($pageCategory) && $pageCategory->is_unique)"
                />
            </div>
        </div>
    </div>
</div>
<div class="col-lg-4">
    <div class="card">
        <div class="card-body">
            @php
                $selectedParentId = old('parent_id', isset($pageCategory)
                    ? $pageCategory->parent_id
                    : ($parentId ?? null)
                );

                // A category can't become its own descendant — collect the ids
                // to exclude from the parent picker (mirrors the server-side
                // check in PageCategoryRequest::descendantAndSelfIds()).
                $excludedIds = [];
                if (isset($pageCategory)) {
                    $excludedIds[] = $pageCategory->id;
                    $stack = $pageCategory->adminChildren->all();
                    while ($stack) {
                        $node = array_pop($stack);
                        $excludedIds[] = $node->id;
                        foreach ($node->adminChildren as $child) {
                            $stack[] = $child;
                        }
                    }
                }
            @endphp
            <x-gingerminds-core::form.inputs.select
                    id="parent_id"
                    :label="__('gingerminds-cms::translation.page_categories.form.parent_id')"
                    :required="false"
                    size="xl"
            >
                <option value="">— @lang('gingerminds-core::translation.none') —</option>
                @foreach($categories as $option)
                    @if(!in_array($option['category']->id, $excludedIds, true))
                        <option
                                value="{{ $option['category']->id }}"
                                {{ (int) $selectedParentId === (int) $option['category']->id ? 'selected' : '' }}
                        >{{ str_repeat('— ', $option['depth']) }}{{ $option['category']->name }}</option>
                    @endif
                @endforeach
            </x-gingerminds-core::form.inputs.select>
        </div>
    </div>
</div>
