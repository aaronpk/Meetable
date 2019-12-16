@extends('layouts/main')

@section('headtags')
<link rel="webmention" href="{{ route('webmention') }}">
@endsection

@section('scripts')
<script src="/assets/justified-layout.js"></script>
<script src="/assets/photo-layout.js"></script>
@endsection

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
            <div>{!! $event->display_date() !!}</div>
            @if(!$event->is_multiday() && $event->display_time())
                <div class="time">{!! $event->weekday() !!} {!! $event->display_time() !!}</div>
            @endif
            {!! $event->mf2_date_html() !!}
            <div class="add-to-calendar">
                <div class="dropdown">
                    <div class="dropdown-trigger">
                        <a aria-haspopup="true" aria-controls="add-to-calendar-menu">
                            <span>Add to Calendar</span>
                        </a>
                    </div>
                    <div class="dropdown-menu" role="menu" id="add-to-calendar-menu">
                        <div class="dropdown-content">
                            <a href="{{ $event->ics_permalink() }}" class="dropdown-item" target="_blank">
                                @icon(calendar) iCal
                            </a>
                            <a href="{{ route('add-to-google', $event->key) }}" class="dropdown-item" target="_blank">
                                @brand_icon(google) Google Calendar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </span>
    </div>

    @if( $event->location_name || $event->location_summary() )
    <div class="location segment with-icon">
        <span class="icon">@icon(map-pin)</span>
        <div class="p-location h-card">
            <div class="p-name">{{ $event->location_name }}</div>
            <div>{!! $event->location_summary_with_mf2() !!}</div>
        </div>
    </div>
    @endif

    <a href="{{ $event->absolute_permalink() }}" class="u-url"></a>

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
                @if($event->rsvp_string_for_user(Auth::user()) == 'yes')
                    <div class="buttons has-addons">
                        <button id="rsvp-button" class="button is-pressed is-light" data-action="{{ route('event-rsvp', $event->id) }}">I'm Going!</button>
                        <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(trash)</button>
                    </div>
                @else
                    <div class="buttons has-addons">
                        <button id="rsvp-button" class="button is-light" data-action="{{ route('event-rsvp', $event->id) }}">I'm Going!</button>
                        @if($event->rsvp_string_for_user(Auth::user()) == 'no')
                            <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(trash)</button>
                        @endif
                    </div>
                @endif
                <br><br>
            @endif

            <ul>
                @foreach($event->rsvps as $rsvp)
                    @if($rsvp->rsvp == 'yes')
                    <li class="h-entry">
                        <span class="u-author h-card avatar">
                            @if($rsvp->author()['photo'])
                                <img src="{{ $rsvp->author()['photo'] }}" width="48" class="u-photo">
                            @endif
                            <a href="{{ $rsvp->source_url ?: $rsvp->author()['url'] }}" class="u-url p-name">{{ $rsvp->author()['name'] ?? p3k\url\display_url($rsvp->author()['url']) }}</a>
                        </span>
                        <data class="p-rsvp" value="{{ $rsvp->rsvp }}"></data>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_photos())
        <div class="responses photos" id="photos">
            <ul class="photo-album">
                @foreach($event->photos()->get() as $photo)
                    @foreach($photo->photos() as $p)
                        <li class="h-entry">
                            <a href="{{ $photo->url }}" class="u-url"><img src="{{ $p }}" height="180" alt="{{ $photo->name }}" class="u-photo p-name"></a>
                        </li>
                    @endforeach
                 @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_blog_posts())
        <div class="responses blog_posts" id="blog_posts">
            <h2 class="subtitle">Blog Posts</h2>
            <ul>
                @foreach($event->blog_posts as $post)
                    <li class="h-entry">
                        <p class="post-name"><a href="{{ $post->url ?: $post->source_url }}" class="u-url p-name">{{ $post->name }}</a></p>
                        <p>by <a href="{{ $post->author()['url'] }}" class="u-author h-card">{{ $post->author()['name'] }}</a> on {{ date('M j, Y', strtotime($post->published)) }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_comments())
        <div class="responses comments" id="comments">
            <h2 class="subtitle">Comments</h2>
            <ul>
                @foreach($event->comments as $comment)
                    <li class="h-entry">
                        <span class="u-author h-card avatar">
                            @if($comment->author()['photo'])
                                <img src="{{ $comment->author()['photo'] }}" width="48" class="u-photo">
                            @endif
                            <span class="author-details">
                                <a href="{{ $comment->author()['url'] }}" class="p-name author-name">{{ $comment->author()['name'] ?? p3k\url\display_url($comment->author()['url']) }}</a>
                                <a href="{{ $comment->author()['url'] }}" class="u-url author-url">{{ p3k\url\display_url($comment->author()['url']) }}</a>
                            </span>
                        </span>
                        <span class="p-content comment-content">{{ $comment->content_text }}</span>
                        <span class="meta">
                            <a href="{{ $comment->url }}" class="u-url">
                                <time class="dt-published" datetime="{{ date('c', strtotime($comment->published)) }}">
                                    {{ date('M j, Y', strtotime($comment->published)) }}
                                </time>
                            </a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ csrf_field() }}

</article>

</section>
@endsection
