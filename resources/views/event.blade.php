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

    <div class="date segment with-icon">
        <span class="icon">@icon(clock)</span>
        <span>
          {!! $event->display_date() !!}
          @if(!$event->is_multiday())
            <br><span class="time">{!! $event->display_time() !!}</span>
          @endif
        </span>
    </div>

    @if( $event->location_name || $event->location_summary() )
    <div class="location segment with-icon">
        <span class="icon">@icon(map-pin)</span>
        <span>{{ $event->location_name }}<br>
              {{ $event->location_summary() }}</span>
    </div>
    @endif

    @if($event->website)
        <div class="website segment with-icon">
            <span class="icon">@icon(link)</span>
            <span><a href="{{ $event->website }}" class="u-url">{{ \p3k\url\display_url($event->website) }}</a></span>
        </div>
    @endif

    <div class="e-content description segment content">
        {!! $event->html() !!}
    </div>

    <div class="segment tags are-medium" id="tags">
        @foreach($event->tags as $tag)
          <a href="{{ $tag->url() }}" class="tag is-rounded">#{{ $tag->tag }}</a>
        @endforeach
    </div>

    @if($event->has_rsvps() || Auth::user())
        <div class="responses rsvps" id="rsvps">
            <h2 class="subtitle">RSVPs</h2>

            @if(Auth::user())
              <button id="rsvp-button" class="button {{ $event->rsvp_string_for_user(Auth::user()) == 'yes' ? 'is-primary' : '' }}" data-action="{{ route('event-rsvp', $event->id) }}">I'm Going!</button>
              <br><br>
            @endif

            <ul>
                @foreach($event->rsvps as $rsvp)
                    @if($rsvp->rsvp == 'yes')
                    <li class="avatar">
                      <img src="{{ $rsvp->author()['photo'] }}" width="48">
                      <a href="{{ $rsvp->author()['url'] }}">{{ $rsvp->author()['name'] ?? p3k\url\display_url($rsvp->author()['url']) }}</a>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_photos())
        <div class="responses photos" id="photos">
            <h2>Photos</h2>
            <ul>
                @foreach($event->photos as $photo)
                    <li>{{ $photo->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_posts())
        <div class="responses posts" id="posts">
            <h2>Blog Posts</h2>
            <ul>
                @foreach($event->posts as $post)
                    <li>{{ $post->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_comments())
        <div class="responses comments" id="comments">
            <h2>Comments</h2>
            <ul>
                @foreach($event->comments as $comment)
                    <li>{{ $comment->id }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ csrf_field() }}

</article>

</section>
@endsection
