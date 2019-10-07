@extends('layouts/main')

@section('content')

<article class="h-event">

    <h1 class="p-name">{{ $event->name }}</h1>

    <div>
        🕘
        {{ $event->date_summary() }}
    </div>

    <div>
        📍
        {{ $event->location_name }}<br>
        {{ $event->location_summary() }}
    </div>

    @if($event->website)
        <div>
            <a href="{{ $event->website }}" class="u-url">{{ \p3k\url\display_url($event->website) }}</a>
        </div>
    @endif

    <div class="e-content">
        {!! $event->html() !!}
    </div>

    <div class="tags">
        @foreach($event->tags as $tag)
          <a href="{{ $tag->url() }}">#{{ $tag->tag }}</a>
        @endforeach
    </div>

    @if($event->has_rsvps())
        <div class="responses rsvps">
            <h2>RSVPs</h2>
            <ul>
                @foreach($event->rsvps as $rsvp)
                    <li>{{ $rsvp->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_photos())
        <div class="responses photos">
            <h2>Photos</h2>
            <ul>
                @foreach($event->photos as $photo)
                    <li>{{ $photo->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_posts())
        <div class="responses posts">
            <h2>Blog Posts</h2>
            <ul>
                @foreach($event->posts as $post)
                    <li>{{ $post->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_comments())
        <div class="responses comments">
            <h2>Comments</h2>
            <ul>
                @foreach($event->comments as $comment)
                    <li>{{ $comment->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif


<pre>Year: {{ $year }}
Month: {{ $month }}
Key: {{ $key }}
Slug: {{ $slug }}</pre>

</article>

@endsection
