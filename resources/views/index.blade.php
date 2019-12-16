@extends('layouts/main')

@section('content')
<style>
.event-list .subtitle.month {
    font-size: 1.5em;
}
.event-list .event {
    margin-left: 2em;
}
</style>
<section class="section">

    @if(isset($tag))
        <h1 class="title">Events Tagged #{{ $tag }}</h1>
    @elseif(!empty($month))
        <h1 class="title">{{ env('APP_NAME') }} in {{ date('F Y', strtotime($year.'-'.$month.'-01')) }}</h1>
    @elseif(!empty($year))
        <h1 class="title">{{ env('APP_NAME') }} in {{ $year }}</h1>
    @else
        <h1 class="title">{{ env('APP_NAME') }}</h1>
    @endif

    @if(count($data))
        <ul class="event-list h-feed">
        @foreach($data as $y => $months)
            @foreach($months as $m => $events)
                <li>
                    @if(empty($month))
                        <span class="subtitle month">{{ date('F'.(isset($tag)?' Y':''), mktime(0,0,0, $m, 1, $y)) }}</span>
                    @endif
                    <ul>
                    @foreach($events as $event)
                        <li class="event h-event">
                            <h3><a href="{{ $event->permalink() }}" class="u-url p-name">{{ $event->name }}</a></h3>

                            <p>{!! $event->date_summary(true) !!}</p>

                            @if($event->location_city())
                                <p>{{ $event->location_city() }}</p>
                            @endif

                        </li>
                    @endforeach
                    </ul>
                </li>
            @endforeach
        @endforeach
        </ul>
    @else
        <p>No events</p>
    @endif

    @if(isset($tag))
        <div class="subscribe-ics">
            <a href="{{ route('ics_tag', $tag) }}">iCalendar Feed</a>
        </div>
    @elseif(empty($month) && empty($year))
        <div class="subscribe-ics">
            <a href="{{ route('ics_index') }}">iCalendar Feed</a>
        </div>
    @endif

</section>
@endsection
