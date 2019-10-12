@extends('layouts/main')

@section('content')
<section class="section">

<h1>{{ env('APP_NAME') }}</h1>

<ul>
@foreach($events as $event)

    <li>
        <a href="{{ $event->permalink() }}">{{ $event->name }}</a>
    </li>

@endforeach
</ul>


</section>
@endsection
