@extends('layouts/main')

@section('content')
<section class="section content">

<h2 class="title">Recurring Events</h2>

@if(count($events) == 0)
    <div class="content">
        <p>There are no recurring events yet. You can schedule a recurring event by creating an event template from an existing event.</p>
        <p><a href="/">See upcoming events</a></p>
    </div>
@endif

<ul class="">

    @foreach($events as $event)
        <li>
            <a href="{{ route('edit-event', $event) }}">
                {{ $event->name }}
            </a>
            <br>

            {{ $event->recurrence_description() }}
            starting {{ (new DateTime($event->start_date))->format('M j, Y') }}

            @if(count($instances[$event->id]))
                <br>
                Future occurrences:
                <ul>
                    @foreach($instances[$event->id] as $instance)
                        <li><a href="{{ $instance->permalink() }}">{{ $instance->date_summary_text() }}</a></li>
                    @endforeach
                    <li>...</li>
                </ul>
            @endif
        </li>
    @endforeach

</ul>

</section>
@endsection

