@foreach($items as $menu)
    <tr>
        <td>{{ $menu->id }}</td>
        <td>{{ $menu->code }}</td>
        <td class="text-end">
            <fieldset class="btn-group">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('gingerminds-cms.menus.edit', $menu) }}">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <a class="btn btn-sm btn-outline-success" href="{{ route('gingerminds-cms.menu_items.index', [
    'menu' => $menu->id
]) }}">
                    <i class="bi bi-card-list"></i>
                    @lang('gingerminds-cms::translation.menu_items.name_p')
                </a>
                <button type="button"
                        class="btn btn-outline-danger btn-sm js-remove-item"
                        data-bs-toggle="modal"
                        data-bs-target="#removeModal"
                        data-model="@lang('gingerminds-cms::translation.menus.name_s')"
                        data-remove-name="{{ $menu->code ?? $menu->id }}"
                        data-destroy-url="{{ route('gingerminds-cms.menus.destroy', $menu) }}"
                >
                    <i class="bi-i bi-trash"></i>
                </button>
            </fieldset>
        </td>
    </tr>
@endforeach
