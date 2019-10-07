@extends('layouts/main')

@section('content')

<h1>Add an Event</h1>

<form action="{{ route('create_event') }}" method="post">


    {{ csrf_field() }}
</form>


@endsection
