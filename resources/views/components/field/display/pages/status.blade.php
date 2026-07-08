@php
    use Gingerminds\LaravelCms\State\Page\Status\Archived;
    use Gingerminds\LaravelCms\State\Page\Status\Draft;
    use Gingerminds\LaravelCms\State\Page\Status\Published;

    $color = 'primary';

    if (Published::code() === $code) {
        $color = 'success';
    }

    if (Archived::code() === $code) {
        $color = 'warning';
    }
@endphp

<span class="badge bg-{{ $color }}">@lang('gingerminds-cms::translation.pages.statuses.' . $code)</span>