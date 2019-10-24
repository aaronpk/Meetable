@extends('layouts/main')

@section('content')
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

<ul class="event-list h-feed">
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


</section>
@endsection
