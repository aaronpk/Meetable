{{-- One link per year with its number of events, for jumping around an archive.
     $years is [year => count]; with $tags the links go to that tag's year page. --}}
@if(count($years))
@php
$tag_string = !empty($tags) ? implode(',', array_map(function($t){ return $t->tag; }, $tags)) : null;
@endphp
<nav class="tags are-medium year-index" aria-label="{{ __('events.archive.year_index') }}">
    @foreach($years as $year => $count)
        <a href="{{ $tag_string ? route('year-tag', [$year, $tag_string]) : route('year', $year) }}" class="tag is-rounded is-hoverable">{{ $year }}<span class="count">{{ $count }}</span></a>
    @endforeach
</nav>
@endif
