@extends('layouts/main')

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

<ul class="year">
@foreach($data as $year => $months)

    <li>
        <a href="{{ route('year', $year) }}" class="title">{{ $year }}</a>

        <ul class="month">
            @foreach($months as $month => $events)
                <li>
                    <a href="{{ route('month', [$year, sprintf('%02d', $month)]) }}" class="subtitle">
                        {{ date('F', mktime(0,0,0, $month, 1, $year)) }}
                    </a>
                    {{-- only show count of events for dates older than 18 months ago --}}
                    @if( mktime(0,0,0, $month, 1, $year) < strtotime('18 months ago') )
                        &bull; <a href="{{ route('month', [$year, sprintf('%02d', $month)]) }}">{{ count($events) }} {{ count($events) == 1 ? 'event' : 'events' }}</a>
                    @else
                        <ul>
                        @foreach($events as $event)
                            <li class="event">
                                {{ date('M j', strtotime($event->start_date)) }}
                                &bull;
                                <a href="{{ $event->permalink() }}">
                                    {{ $event->name }}
                                </a>
                            </li>
                        @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </li>

@endforeach
</ul>

</section>
@endsection
