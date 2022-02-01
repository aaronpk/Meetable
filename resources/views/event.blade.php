@extends('layouts/main')

@php
use App\Setting;
@endphp

@section('headtags')

@if(Setting::value('enable_webmention_responses'))
<link rel="webmention" href="{{ route('webmention') }}">
@endif

<link rel="stylesheet" href="/jquery/jquery-ui-1.12.1/jquery-ui.min.css">
<script type="application/ld+json">
@if($mode != 'archive')
{!! $event->toGoogleJSON() !!}
@endif
</script>
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $event->name }}">
<meta property="og:url" content="{{ $event->absolute_shortlink() }}">
@if($event->cover_image)
<meta property="og:image" content="{{ $event->cover_image_absolute_url() }}">
<meta name="twitter:image" content="{{ $event->cover_image_absolute_url() }}">
<meta name="twitter:card" content="summary_large_image">
@endif
<meta name="twitter:label1" value="Date">
<meta name="twitter:data1" value="{{ $event->date_summary_text() }}">
@if($event->location_summary())
<meta name="twitter:label2" value="Location">
<meta name="twitter:data2" value="{{ $event->location_summary_with_name() }}">
@endif
@endsection

@section('scripts')
<script src="/jquery/jquery-ui-1.12.1/jquery-ui.min.js"></script>
@endsection

@section('content')
<section class="section">

@can('manage-event', $event)
@if($mode != 'archive')

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
                    @if(Setting::value('enable_registration'))
                        <a class="dropdown-item" href="{{ route('edit-registration', $event) }}">
                            <span class="icon">@icon(file-alt)</span>
                            <span>{{ $event->registration ? 'Configure' : 'Enable' }} Registration</span>
                        </a>
                    @endif
                    <a class="dropdown-item" href="{{ route('revision-history', $event) }}">
                        <span class="icon">@icon(history)</span>
                        <span>Revision History</span>
                    </a>
                    @if($num=$event->num_pending_responses())
                    <a class="dropdown-item" href="{{ route('moderate-responses', $event) }}">
                        <span class="icon">@icon(comment)</span>
                        <span>Moderate Responses {!! $num ? "<span class='badge'>($num)</span>" : '' !!}</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

  </div>
</div>

@endif
@endcan


<article class="h-event event">

    @if($mode == 'archive')
        <b>Viewing event at revision {{ $event->created_at }}</b>

        <p><a href="{{ route('revision-history', $event_id) }}">@icon(arrow-circle-left) revision history</a></p>
    @endif

    @if($event->cover_image)
        <div class="cover-image">
            <img src="{{ $event->cover_image }}" class="u-featured" style="max-width: 720px; width: 100%;">
        </div>
    @endif

    <h1 class="p-name event-name">{!! $event->status_tag() !!}{{ $event->name }}</h1>

    <div class="date segment with-icon">
        <span class="icon">@icon(clock)</span>
        <span>
            <div>{{ $event->status == 'postponed' ? 'TBD, originally ' : '' }}{!! $event->display_date() !!}</div>
            @if(!$event->is_multiday() && $event->display_time())
                <div class="time">
                    {!! $event->weekday() !!}
                    @if($event->timezone)
                        <a href="{{ route('local_time') }}?date={{ urlencode($event->start_datetime_local()) }}&tz={{ urlencode($event->timezone) }}">{!! $event->display_time() !!}</a>
                        <span class="timezone">({{ $event->timezone }})</span>
                    @else
                        {!! $event->display_time() !!}
                    @endif
                </div>
            @endif
            {!! $event->mf2_date_html() !!}
            @if(!$event->is_past() && !in_array($event->status, ['cancelled','postponed']))
            <div class="add-to-calendar">
                <div class="dropdown is-hoverable">
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
            @if($event->latitude && $event->longitude)
                <data class="p-latitude" value="{{ $event->latitude }}"></data>
                <data class="p-longitude" value="{{ $event->longitude }}"></data>
            @endif
        </div>
    </div>
    @endif

    <a href="{{ $event->absolute_permalink() }}" class="u-url"></a>

    @if($event->website)
        <div class="website segment with-icon">
            <span class="icon">@icon(link)</span>
            <span>
                <a href="{{ $event->website }}" class="u-url" rel="canonical" title="{{ $event->website }}">
                    {{ strlen($event->website) > 40 ?  parse_url($event->website, PHP_URL_HOST) : \p3k\url\display_url($event->website) }}
                </a>
            </span>
        </div>
    @endif

    @if($event->tickets_url)
        <div class="website segment with-icon">
            <span class="icon">@icon(ticket-alt)</span>
            <span>
                <a href="{{ $event->tickets_url }}" title="{{ $event->tickets_url }}">
                    {{ strlen($event->tickets_url) > 40 ?  parse_url($event->tickets_url, PHP_URL_HOST) : \p3k\url\display_url($event->tickets_url) }}
                </a>
            </span>
        </div>
    @endif

    @if($event->code_of_conduct_url)
        <div class="code-of-conduct segment with-icon">
            <span class="icon">@icon(gavel)</span>
            <span class="text">
                <span class="coc-title">Code of Conduct</span>
                @foreach($event->code_of_conduct_urls() as $url)
                    <a href="{{ $url }}" title="{{ $url }}" class="coc-url">
                        {{ strlen($url) > 80 ?  parse_url($url, PHP_URL_HOST) : \p3k\url\display_url($url) }}
                    </a>
                @endforeach
            </span>
        </div>
    @endif

    @if($mode == 'archive')
        @if($event->meeting_url)
            <div class="website segment with-icon">
                <span class="icon">@icon(video)</span>
                <span>
                    <a href="{{ $event->meeting_url }}" title="{{ $event->meeting_url }}" target="_blank">
                        {{ strlen($event->meeting_url) > 80 ?  parse_url($event->meeting_url, PHP_URL_HOST) : \p3k\url\display_url($event->meeting_url) }}
                    </a>
                </span>
            </div>
        @endif
    @else
        @if($event->meeting_url && !$event->is_past())
            <div class="website segment with-icon">
                <span class="icon">@icon(video)</span>
                <span>
                    @if($event->is_starting_soon())
                        <a href="{{ $event->meeting_url }}" title="{{ $event->meeting_url }}" class="pulsing-yellow" target="_blank">
                            Join the Online Meeting
                        </a>
                    @else
                        <a href="" class="pulsing-yellow hidden" target="_blank" id="event-meeting-url">Join the Online Meeting</a>
                        <span id="event-meeting-url-msg">The meeting link will be shown 15 minutes before the event</span>
                    @endif
                </span>
            </div>
        @endif
    @endif

    @if($event->video_url)
        <div class="website segment with-icon">
            <span class="icon">@brand_icon(youtube)</span>
            <span>
                <a href="{{ $event->video_url }}" title="{{ $event->video_url }}">
                    {{ \p3k\url\display_url(strlen($event->video_url) > 40 ? 'http://'.parse_url($event->video_url, PHP_URL_HOST) : $event->video_url) }}
                </a>
            </span>
        </div>
    @endif

    @if($event->video_url && $event->can_embed_video())
        <div class="video description segment content">
            {!! $event->video_embed_html() !!}
        </div>
    @endif

    @if($event->summary)
    <div class="e-summary description segment content">
        {!! $event->summary_html() !!}
    </div>
    @endif

    <div class="e-content description segment content">
        {!! $event->html() !!}
    </div>

    <div class="segment tags are-medium" id="tags">
        @if($mode == 'archive')
            @if($event->tags)
                @foreach(json_decode($event->tags, true) as $tag)
                    <a href="{{ route('tag', $tag) }}" class="tag is-rounded">#<span class="p-category">{{ $tag }}</span></a>
                @endforeach
            @endif
        @else
            @foreach($event->tags as $tag)
                <a href="{{ $tag->url() }}" class="tag is-rounded">#<span class="p-category">{{ $tag->tag }}</span></a>
            @endforeach
        @endif
    </div>

    @if($mode != 'archive')
    @if($event->has_likes())
        <div class="responses likes" id="likes">
            <ul>
                <li><span class="icon" style="width:30px; height:30px;">@icon(star)</span></li>
                @foreach($event->likes as $like)
                    <li>@include('components/like-avatar', ['like' => $like])</li>
                @endforeach
            </ul>
            <div style="clear: left;"></div>
        </div>
    @endif

    @if($event->rsvps_enabled && ($event->has_rsvps() || Auth::user()))
        <div class="responses rsvps" id="rsvps">
            <div class="level">
                <div class="level-left">
                    <h2 class="subtitle">RSVPs</h2>
                </div>
                <div class="level-right">
                    @if(Auth::user() && $event->status == 'confirmed')
                        @if($event->rsvp_string_for_user(Auth::user()) == 'yes')
                            <div class="buttons has-addons">
                                <button id="rsvp-button" class="button is-pressed is-light" data-action="{{ route('event-rsvp', $event->id) }}">
                                    {{ $event->is_past() ? 'I Went' : 'I\'m Going!' }}
                                </button>
                                <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(minus-circle)</button>
                            </div>
                        @else
                            <div class="buttons has-addons">
                                <button id="rsvp-button" class="button is-light" data-action="{{ route('event-rsvp', $event->id) }}">
                                    {{ $event->is_past() ? 'I Went' : 'I\'m Going!' }}
                                </button>
                                @if($event->rsvp_string_for_user(Auth::user()) == 'no')
                                    <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(minus-circle)</button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>

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
                @foreach($event->photos as $p)
                    <li data-photo-id="{{ $p->id }}"><a href="{{ $p->full_url }}" class="u-photo photo-popup" data-original-url="{{ $p->response->photo_original_url() }}" data-author-name="{{ $p->response->photo_author_name() }}" data-alt-text="{{ $p->alt }}" data-response-id="{{ $p->response_id }}" data-photo-id="{{ $p->id }}"><img src="{{ $p->square_url }}" width="230" height="230" alt="{{ $p->alt }}" title="{{ $p->alt }}" class="square"><img src="{{ $p->large_url }}" class="full" alt="{{ $p->alt }}" title="{{ $p->alt }}"></a></li>
                 @endforeach
            </ul>
        </div>

        <div class="modal" id="photo-preview">
            <div class="modal-background"></div>
            <div class="modal-card">
                <div class="modal-card-body" style="border-radius: 8px">
                    <p class="image"><img src=""></p>
                    @can('manage-event', $event)
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
                    <input type="hidden" id="photo_id">
                    @endcan
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
                        <p>
                            by <a href="{{ $post->author_url() }}">{{ $post->author_display_name() }}</a>
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
                            @if($comment->author_photo())
                                <img src="{{ $comment->author_photo() }}" width="48" class="photo">
                            @endif
                            <span class="author-details">
                                <a href="{{ $comment->author_url() }}" class="author-name">{{ $comment->author_display_name() }}</a>
                                <a href="{{ $comment->author_url() }}" class="author-url">{{ p3k\url\display_url($comment->author_url()) }}</a>
                                @if($comment->rsvp)
                                  <img src="/images/rsvp-{{ $comment->rsvp }}.png" width="79">
                                @endif
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

    @if(Setting::value('event_page_embed'))
        {!! Setting::event_page_embed($event) !!}
    @endif

    @endif

    @can('manage-event', $event)
    <script>
        $(function(){
            $(".photo-album").sortable({
                placeholder: "ui-state-highlight",
                stop: function(event, ui) {
                    var photoIDs = $(".photo-album li").map(function(){
                        return $(this).data("photo-id");
                    }).get();
                    $.post("{{ route('set-photo-order', $event) }}", {
                        _token: $("input[name=_token]").val(),
                        order: photoIDs
                    }, function(response){
                        console.log(response);
                    });
                }
            });
        });
    </script>
    @endcan

    @if($event->meeting_url && !$event->is_past())
    <script>
        var meetingURLTimer;
        $(function(){
            meetingURLTimer = setInterval(function(){
                $.getJSON("/event/{{ $event->key }}.json", function(data){
                    if(data.meeting_url) {
                        $("#event-meeting-url-msg").addClass("hidden");
                        $("#event-meeting-url").attr("href", data.meeting_url).removeClass("hidden");
                        clearInterval(meetingURLTimer);
                    }
                });
            }, 2000);
        });
    </script>
    @endif

    <input type="hidden" id="event_id" value="{{ $event->id }}">

    {{ csrf_field() }}

</article>

</section>
@endsection
