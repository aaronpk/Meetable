@extends('layouts/main')

@section('content')
<section class="section content">

<h2 class="title">Recurring Events</h2>

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

