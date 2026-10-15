{{-- Links from a year page to the nearest earlier and later years that have events.
     $previous and $next are years or null; with $tags the links stay on that tag's year pages. --}}
@if($previous || $next)
@php
$tag_string = !empty($tags) ? implode(',', array_map(function($t){ return $t->tag; }, $tags)) : null;
$year_url = function($y) use($tag_string) {
    return $tag_string ? route('year-tag', [$y, $tag_string]) : route('year', $y);
};
@endphp
<nav class="pagination is-centered year-nav" role="navigation" aria-label="{{ __('events.year_nav.label') }}">
    {{-- A missing side keeps an invisible placeholder so the other link stays on its own side --}}
    @if($previous)
        <a class="pagination-previous" href="{{ $year_url($previous) }}" rel="prev" aria-label="{{ __('events.year_nav.previous') }}">&laquo; {{ $previous }}</a>
    @else
        <span class="pagination-previous is-invisible" aria-hidden="true"></span>
    @endif
    @if($next)
        <a class="pagination-next" href="{{ $year_url($next) }}" rel="next" aria-label="{{ __('events.year_nav.next') }}">{{ $next }} &raquo;</a>
    @else
        <span class="pagination-next is-invisible" aria-hidden="true"></span>
    @endif
</nav>
@endif
