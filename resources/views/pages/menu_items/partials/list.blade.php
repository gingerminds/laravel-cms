@foreach($items as $menuItem)
    <tr>
        <td>{{ $menuItem->id }}</td>
        <td>{{ $menuItem->currentTranslation?->name }}</td>
        <td class="text-end">
            <fieldset class="btn-group">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('gingerminds-cms.menu_items.edit', [
    'menu' => $menuItem->menu_id, 'menuItem' => $menuItem->id
]) }}">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <button type="button"
                        class="btn btn-outline-danger btn-sm js-remove-item"
                        data-bs-toggle="modal"
                        data-bs-target="#removeModal"
                        data-model="@lang('gingerminds-cms::translation.menu_items.name_s')"
                        data-remove-name="{{ $menuItem->currentTranslation?->name ?? $menuItem->id }}"
                        data-destroy-url="{{ route('gingerminds-cms.menu_items.destroy', [
    'menu' => $menuItem->menu_id, 'menuItem' => $menuItem->id
]) }}"
                >
                    <i class="bi-i bi-trash"></i>
                </button>
            </fieldset>
        </td>
    </tr>
@endforeach
