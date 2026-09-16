@extends('layouts/main')

@section('content')
<section class="section content">

<h2 class="title">{{ __('event_form.templates.title') }}</h2>

<div class="content">
    @if(count($events) == 0)
        <p>{{ __('event_form.templates.none_yet') }}</p>
        <p><a href="/">{{ __('event_form.templates.see_upcoming') }}</a></p>
    @else
        <p>{!! __('event_form.templates.how_to', ['existing_event' => '<a href="/">'.e(__('event_form.templates.existing_event')).'</a>']) !!}</p>
    @endif
</div>

<ul class="">

    @foreach($events as $event)
        <li>
            <a href="{{ route('edit-event', $event) }}">
                {{ $event->name }}
            </a>
            <br>

            {{ $event->recurrence_description() }}
            {{ __('event_form.templates.starting', ['date' => \App\Helpers\Dates::format($event->start_date, 'date')]) }}

            @if(count($instances[$event->id]))
                <br>
                {{ __('event_form.templates.future_occurrences') }}
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

