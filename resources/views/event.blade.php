@extends('layouts/main')

@section('headtags')
<link rel="webmention" href="{{ route('webmention') }}">
<link rel="stylesheet" href="/jquery/jquery-ui-1.12.1/jquery-ui.min.css">
@endsection

@section('scripts')
<script src="/jquery/jquery-ui-1.12.1/jquery-ui.min.js"></script>
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
                    <a class="dropdown-item" href="{{ route('add-event-photo', $event) }}">
                        <span class="icon">@icon(camera)</span>
                        <span>Add Photo</span>
                    </a>
                    <a class="dropdown-item" href="{{ route('edit-responses', $event) }}">
                        <span class="icon">@icon(comment)</span>
                        <span>Edit Responses</span>
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
            @if(!$event->is_past())
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
            @endif
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
            <span><a href="{{ $event->website }}" class="u-url" rel="canonical">{{ \p3k\url\display_url($event->website) }}</a></span>
        </div>
    @endif

    <div class="e-content description segment content">
        {!! $event->html() !!}
    </div>

    <div class="segment tags are-medium" id="tags">
        @foreach($event->tags as $tag)
            <a href="{{ $tag->url() }}" class="tag is-rounded">#<span class="p-category">{{ $tag->tag }}</span></a>
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
            @endif

            <ul>
                @foreach($event->rsvps_yes as $rsvp)
                    <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                @endforeach
            </ul>

            @if(count($event->rsvps_remote))
                <h3 class="subtitle">Remote Attendees</h3>
                <ul>
                    @foreach($event->rsvps_remote as $rsvp)
                        <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                    @endforeach
                </ul>
            @endif

            @if(count($event->rsvps_maybe))
                <h3 class="subtitle">Maybe</h3>
                <ul>
                    @foreach($event->rsvps_maybe as $rsvp)
                        <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                    @endforeach
                </ul>
            @endif

            @if(count($event->rsvps_no))
                <h3 class="subtitle">Can't Go</h3>
                <ul>
                    @foreach($event->rsvps_no as $rsvp)
                        <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                    @endforeach
                </ul>
            @endif

        </div>
    @endif

    @if($event->has_photos())
        <div class="responses photos" id="photos">
            <ul class="photo-album">
                @foreach($event->photo_urls() as $p)
                    <li data-photo-url="{{ $p[0] }}"><a href="@image_proxy($p[0], '1600x0')" class="u-photo photo-popup" data-original-url="{{ $p[1]->link() ?: ($p[1]->creator ? $p[1]->creator->url : '') }}" data-author-name="{{ ($p[1]->author_name ?: parse_url($p[1]->link(), PHP_URL_HOST)) ?: ($p[1]->creator ? $p[1]->creator->name : '') }}" data-alt-text="{{ $p[1]->photo_alt_text($p[0]) }}" data-response-id="{{ $p[1]->id }}" data-photo-url="{{ $p[0] }}"><img src="@image_proxy($p[0], '230x230,sc')" width="230" height="230" alt="{{ $p[1]->photo_alt_text($p[0]) }}" title="{{ $p[1]->photo_alt_text($p[0]) }}" class="square"><img src="@image_proxy($p[0], '710x0')" class="full" alt="{{ $p[1]->photo_alt_text($p[0]) }}" title="{{ $p[1]->photo_alt_text($p[0]) }}"></a></li>
                 @endforeach
            </ul>
        </div>

        <div class="modal" id="photo-preview">
            <div class="modal-background"></div>
            <div class="modal-card">
                <div class="modal-card-body" style="border-radius: 8px">
                    <p class="image"><img src=""></p>
                    @if(Auth::user())
                    <div style="margin-top: 1em">
                        <div class="field has-addons">
                            <div class="control has-icons-right is-expanded">
                                <input class="input photo-alt-text" type="text" placeholder="alt text">
                                <span class="hidden icon is-small is-right">@icon(check)</span>
                            </div>
                            <div class="control">
                                <button class="button" id="save-photo-alt">Save</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="response_id">
                    <input type="hidden" id="photo_url">
                    @endif
                    <p class="original-source">via <a href=""></a></p>
                </div>
            </div>
            <button class="modal-close is-large" aria-label="close"></button>
        </div>
    @endif

    @if($event->has_blog_posts())
        <div class="responses blog_posts" id="blog_posts">
            <h2 class="subtitle">Blog Posts</h2>
            <ul>
                @foreach($event->blog_posts as $post)
                    <li>
                        <p class="post-name"><a href="{{ $post->link() }}">{{ $post->name }}</a></p>
                        <p>by <a href="{{ $post->author()['url'] }}">{{ $post->author()['name'] }}</a>
                            @if($post->published)
                                on {{ date('M j, Y', strtotime($post->published)) }}
                            @endif
                        </p>
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
                    <li>
                        <span class="avatar">
                            @if($comment->author()['photo'])
                                <img src="@image_proxy($comment->author()['photo'], '96x96,sc')" width="48" class="photo">
                            @endif
                            <span class="author-details">
                                <a href="{{ $comment->author()['url'] }}" class="author-name">{{ $comment->author()['name'] ?? p3k\url\display_url($comment->author()['url']) }}</a>
                                <a href="{{ $comment->author()['url'] }}" class="author-url">{{ p3k\url\display_url($comment->author()['url']) }}</a>
                            </span>
                        </span>
                        <span class="comment-content">{{ $comment->content_text }}</span>
                        <span class="meta">
                            <a href="{{ $comment->link() }}">
                                <time datetime="{{ date('c', strtotime($comment->published)) }}">
                                    {{ date('M j, Y', strtotime($comment->published)) }}
                                </time>
                            </a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(Auth::user())
    <script>
        $(function(){
            $(".photo-album").sortable({
                placeholder: "ui-state-highlight",
                stop: function(event, ui) {
                    var photoURLs = $(".photo-album li").map(function(){
                        return $(this).data("photo-url");
                    }).get();
                    console.log(photoURLs);
                    $.post("{{ route('set-photo-order', $event) }}", {
                        _token: $("input[name=_token]").val(),
                        order: photoURLs
                    }, function(response){
                        console.log(response);
                    });
                }
            });
        });
    </script>
    @endif

    <input type="hidden" id="event_id" value="{{ $event->id }}">

    {{ csrf_field() }}

</article>

</section>
@endsection
