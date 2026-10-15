@extends('layouts/main')

@section('headtags')
@include('components/pagination-head')
@endsection

@section('content')
<section class="section">

<style>
ul.year > li {
    margin-bottom: 1em;
}
ul.year > li > ul.month > li {
    margin-left: 2em;
    margin-top: 0.4em;
}
ul.year > li > ul.month > li > ul > li.event {
    margin-left: 2em;
}
a.title:hover, a.subtitle:hover {
    color: #3273dc;
}
</style>

<h1 class="title">{{ $page_title }}</h1>

@include('components/year-index', ['years' => $years])

@if(count($data))
<ul class="year h-feed">
    <data class="p-name" value="{{ __('events.past_events') }}"></data>
@foreach($data as $year => $months)

    <li>
        <a href="{{ route('year', $year) }}" class="title">{{ $year }}</a>

        <ul class="month">
            @foreach($months as $month => $events)
                <li>
                    <a href="{{ route('month', [$year, sprintf('%02d', $month)]) }}" class="subtitle">
                        {{ \App\Helpers\Dates::format(mktime(0,0,0, $month, 1, $year), 'month') }}
                    </a>
                    <ul>
                    @foreach($events as $event)
                        <li class="event h-event">
                            {{ \App\Helpers\Dates::format($event->start_date, 'month_day') }}
                            &bull;
                            <a href="{{ $event->permalink() }}" class="u-url p-name">
                                {{ $event->status_text() }}{{ $event->name }}
                            </a>

                            <data style="display: none;">
                                {!! $event->mf2_date_html() !!}
                            </data>

                            @if($event->parent)
                                <a class="u-x-parent-event" href="{{ $event->parent->permalink() }}"></a>
                            @endif
                        </li>
                    @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </li>

@endforeach
</ul>
@else
<div class="content"><p>{{ __('events.no_events') }}</p><div class="h-feed"></div></div>
@endif

@include('components/pagination', ['page' => $page])

</section>
@endsection
