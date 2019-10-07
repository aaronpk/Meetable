@extends('layouts/main')

@section('content')

<h1>{{ env('APP_NAME') }}</h1>

<ul>
@foreach($events as $event)

    <li>
        <a href="{{ $event->permalink() }}">{{ $event->name }}</a>
    </li>

@endforeach
</ul>


@endsection
