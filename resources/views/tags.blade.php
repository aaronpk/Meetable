@extends('layouts/main')

@section('content')
<section class="section">

<div class="tagcloud">
    @foreach($tags as $tag)
        <span class="{{ $tag['class'] }}"><nobr><a href="{{ route('tag', $tag['tag']) }}">{{ $tag['tag'] }}</a></nobr></span>
    @endforeach
</div>

</section>
@endsection
