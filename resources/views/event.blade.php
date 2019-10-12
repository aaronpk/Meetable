@extends('layouts/main')

@section('content')
<section class="section">

@if(Auth::user())

<div class="level">
  <div class="level-left"></div>
  <div class="level-right">

    <div class="buttons has-addons with-dropdown">

      <a href="{{ route('edit-event', $event) }}" class="button">
        <span class="icon">@icon(edit)</span>
        <span>Edit</span>
      </a>

      <div class="dropdown is-right">
        <div class="dropdown-trigger">
          <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
            <span class="icon is-small">@icon(angle-down)</span>
          </button>
        </div>

        <div class="dropdown-menu" id="dropdown-menu" role="menu">
          <div class="dropdown-content">
            <a class="dropdown-item" href="{{ route('clone-event', $event) }}">
              <span class="icon">@icon(copy)</span>
              <span>Clone Event</span>
            </a>
          </div>
        </div>
      </div>

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

    @if( $event->location_name || $event->location_summary() )
    <div class="location segment">
        📍
        {{ $event->location_name }}<br>
        {{ $event->location_summary() }}
    </div>
    @endif

    @if($event->website)
        <div class="segment">
            <a href="{{ $event->website }}" class="u-url">{{ \p3k\url\display_url($event->website) }}</a>
        </div>
    @endif

    <div class="e-content description segment content">
        {!! $event->html() !!}
    </div>

    <div class="segment tags are-medium">
        @foreach($event->tags as $tag)
          <a href="{{ $tag->url() }}" class="tag is-rounded">#{{ $tag->tag }}</a>
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

</section>
@endsection
