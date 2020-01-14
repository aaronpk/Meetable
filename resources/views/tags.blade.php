@extends('layouts/main')

@section('content')
<section class="section">

<div class="tagcloud tags">
    @foreach($tags as $tag)
        <nobr><a href="{{ route('tag', $tag['tag']) }}" class="tag is-rounded {{ $tag['class'] }}">#{{ $tag['tag'] }}</a></nobr>
    @endforeach
</div>

</section>
@endsection
