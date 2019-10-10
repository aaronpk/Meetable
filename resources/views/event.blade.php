@extends('layouts/main')

@section('content')
<div class="ui container">

@if(Auth::user())
    <div class="ui buttons right floated">
      <a class="ui button" href="{{ route('edit-event', $event) }}"><i class="edit icon"></i> Edit</a>
      <div class="ui floating dropdown icon button">
        <i class="dropdown icon"></i>
        <div class="menu">
          <a class="item" href="{{ route('event-history', $event) }}"><i class="clock icon"></i> View Revision History</a>
          <a class="item" href="{{ route('clone-event', $event) }}"><i class="copy icon"></i> Clone Event</a>
        </div>
      </div>
    </div>
@endif

<article class="h-event event">

    <h1 class="p-name event-name">{{ $event->name }}</h1>

    <div class="date segment">
        🕘
        {{ $event->date_summary() }}
    </div>

    <div class="location segment">
        📍
        {{ $event->location_name }}<br>
        {{ $event->location_summary() }}
    </div>

    @if($event->website)
        <div class="segment">
            <a href="{{ $event->website }}" class="u-url">{{ \p3k\url\display_url($event->website) }}</a>
        </div>
    @endif

    <div class="e-content description segment">
        {!! $event->html() !!}
    </div>

    <div class="tags segment">
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

</article>

</div>
@endsection
