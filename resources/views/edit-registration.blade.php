@extends('layouts/main')

@section('content')
<section class="section event">

<div class="content">
    <h1>Configure Event Registration</h1>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
</div>


<div class="registration">


</div>


<style>
.event .responses {
    padding: 0;
}
.responses li {
    border-bottom: 1px #ddd solid;
    padding-bottom: 0.75em;
}
</style>

{{ csrf_field() }}

</section>
@endsection
