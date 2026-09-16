@extends('layouts/main')

@section('content')
<section class="section">

<h1 class="title">{{ __('events.proposed.title') }}</h1>

@if(count($events) == 0)
    <div class="content"><p>{{ __('events.proposed.none') }}</p></div>
@else
    <ul class="event-list proposed-list h-feed">
    @foreach($events as $event)
        <li class="event h-event">
            <h3><a href="{{ $event->permalink() }}" class="u-url p-name">{!! $event->status_tag() !!}{{ $event->name }}</a></h3>

            <p>{{ $event->date_options->map(function($option){ return $option->display_date(false); })->join(', ') }}</p>

            @php
            $option_count = $event->date_options->count();
            $voter_count = $event->voter_count();
            @endphp
            <p>
                {{ trans_choice('events.proposed.option_count', $option_count, ['count' => $option_count]) }}
                &bull;
                {{ trans_choice('events.proposed.voter_count', $voter_count, ['count' => $voter_count]) }}
            </p>

            @if($event->location_city())
                <p>{{ $event->location_city() }}</p>
            @endif

            @foreach($event->tags as $tag)
                <a href="{{ $tag->url() }}" class="tag is-rounded">#<span class="p-category">{{ $tag->tag }}</span></a>
            @endforeach
        </li>
    @endforeach
    </ul>
@endif

</section>
@endsection
