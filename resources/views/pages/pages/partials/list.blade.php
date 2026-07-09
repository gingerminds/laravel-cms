@foreach($items as $page)
    <tr>
        <td>{{ $page->id }}</td>
        <td>{{ $page->code }}</td>
        <td>{{ $page->category->currentTranslation?->name }}</td>
        <td>@include('gingerminds-cms::components.field.display.pages.status', ['code' => $page->status::code()])</td>
        <td class="text-end">
            <fieldset class="btn-group">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('gingerminds-cms.pages.edit', $page) }}">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <button type="button"
                        class="btn btn-outline-danger btn-sm js-remove-item"
                        data-bs-toggle="modal"
                        data-bs-target="#removeModal"
                        data-model="@lang('gingerminds-cms::translation.pages.name_s')"
                        data-remove-name="{{ $page->currentTranslation?->title ?? $page->id }}"
                        data-destroy-url="{{ route('gingerminds-cms.pages.destroy', $page) }}"
                >
                    <i class="bi-i bi-trash"></i>
                </button>
            </fieldset>
        </td>
    </tr>
@endforeach
