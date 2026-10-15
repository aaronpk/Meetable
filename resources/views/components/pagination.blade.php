{{-- Newer and older links for a page of an archive. $page comes from Controller::cursor_page(). --}}
@if(!empty($page['newer']) || !empty($page['older']))
<nav class="pagination is-centered archive-pagination" role="navigation" aria-label="{{ __('events.archive.pagination') }}">
    @if(!empty($page['newer']))
        <a class="pagination-previous" href="{{ $page['newer'] }}" rel="prev">{{ __('events.archive.newer') }}</a>
    @else
        <span class="pagination-previous is-disabled">{{ __('events.archive.newer') }}</span>
    @endif
    @if(!empty($page['older']))
        <a class="pagination-next" href="{{ $page['older'] }}" rel="next">{{ __('events.archive.older') }}</a>
    @else
        <span class="pagination-next is-disabled">{{ __('events.archive.older') }}</span>
    @endif
</nav>
@endif
