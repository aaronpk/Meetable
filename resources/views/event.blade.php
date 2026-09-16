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
@if($event->cover_image_absolute_url())
<meta property="og:image" content="{{ $event->cover_image_absolute_url() }}">
<meta name="twitter:image" content="{{ $event->cover_image_absolute_url() }}">
<meta name="twitter:card" content="summary_large_image">
@endif
<meta name="twitter:label1" value="{{ __('events.meta.date') }}">
<meta name="twitter:data1" value="{{ $event->date_summary_text() }}">
@if($event->location_summary())
<meta name="twitter:label2" value="{{ __('events.meta.location') }}">
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
            <span>{{ __('common.edit') }}</span>
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
                        <span>{{ __('events.actions.clone') }}</span>
                    </a>
                    <a class="dropdown-item" href="{{ route('recurring-event', $event) }}">
                        <span class="icon">@icon(redo)</span>
                        <span>{{ __('events.actions.create_recurring') }}</span>
                    </a>
                    <a class="dropdown-item" href="{{ route('add-event-photo', $event) }}">
                        <span class="icon">@icon(camera)</span>
                        <span>{{ __('events.actions.add_photo') }}</span>
                    </a>
                    <a class="dropdown-item" href="{{ route('new-event', ['parent'=>$event]) }}">
                        <span class="icon">@icon(calendar)</span>
                        <span>{{ __('events.actions.add_sub_event') }}</span>
                    </a>
                    @if(Setting::value('enable_webmention_responses'))
                    <a class="dropdown-item" href="{{ route('edit-responses', $event) }}">
                        <span class="icon">@icon(comment)</span>
                        <span>{{ __('events.actions.edit_responses') }}</span>
                    </a>
                    @endif
                    @if(Setting::value('enable_registration'))
                        <a class="dropdown-item" href="{{ route('edit-registration', $event) }}">
                            <span class="icon">@icon(file-alt)</span>
                            <span>{{ $event->registration ? __('events.actions.configure_registration') : __('events.actions.enable_registration') }}</span>
                        </a>
                    @endif
                    <a class="dropdown-item" href="{{ route('revision-history', $event) }}">
                        <span class="icon">@icon(history)</span>
                        <span>{{ __('events.actions.revision_history') }}</span>
                    </a>
                    @if($num=$event->num_pending_responses())
                    <a class="dropdown-item" href="{{ route('moderate-responses', $event) }}">
                        <span class="icon">@icon(comment)</span>
                        <span>{{ __('events.actions.moderate_responses') }} {!! $num ? "<span class='badge'>($num)</span>" : '' !!}</span>
                    </a>
                    @endif
                    @if($event->export_secret)
                    <a class="dropdown-item" href="{{ route('secret-export-json', [$event, $event->export_secret]) }}">
                        <span class="icon">@icon(link)</span>
                        <span>{{ __('events.actions.copy_export_url') }}</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

  </div>
</div>

@endif

@if($event->meeting_url && !$event->timezone)
    <div class="notification is-danger">
        {{ __('events.meeting_url_needs_timezone') }}
    </div>
@endif

@endcan


<article class="h-event event">

    @if($mode == 'archive')
        <b>{{ __('revisions.viewing_event_at', ['date' => $event->created_at]) }}</b>

        <p><a href="{{ route('revision-history', $event_id) }}">@icon(arrow-circle-left) {{ __('revisions.back_to_history') }}</a></p>
    @endif

    @if($event->cover_image)
        <div class="cover-image">
            <img src="{{ \App\Helpers\Uri::safe_href($event->cover_image) }}" class="u-featured" style="max-width: 720px; width: 100%;">
        </div>
    @endif

    <h1 class="event-name">
        @if($event->meeting_url_is_visible())
            <a href="{{ \App\Helpers\Uri::safe_href($event->meeting_url) }}" target="_blank">
                {!! $event->status_tag() !!}
            </a>
        @else
            {!! $event->status_tag() !!}
        @endif
        <span class="p-name">{{ $event->name }}</span>
    </h1>

    @if($event->parent)
        <div class="parent-event segment with-icon">
            <span class="icon">@icon(calendar)</span>
            <span>
                <a href="{{ $event->parent->permalink() }}">{{ $event->parent->name }}</a>
            </span>
        </div>
    @endif

    <div class="date segment with-icon">
        <span class="icon">@icon(clock)</span>
        <span>
            <div>{{ $event->status == 'postponed' ? __('events.tbd_originally').' ' : '' }}
                <time datetime="{{ $event->start_datetime()->format('c') }}"  class="event-localize-date {{ (!$event->has_physical_location() ? 'is-virtual-event' : '') }}" data-timezone="{{ $event->timezone }}"  data-original-date="{{ \App\Helpers\Dates::format($event->start_datetime(), 'datetime') }}" data-dateformat="dateonly" data-show-tooltip="false">
                    {!! $event->display_date() !!}
                </time>
            </div>
            @if(!$event->is_multiday() && $event->display_time())
                <div class="time">
                    @if($event->timezone)
                        <a href="{{ route('local_time') }}?date={{ urlencode($event->start_datetime_local()) }}&tz={{ urlencode($event->timezone) }}">
                            <time datetime="{{ $event->start_datetime()->format('c') }}" class="has-tooltip-bottom event-localize-date {{ (!$event->has_physical_location() ? 'is-virtual-event' : '') }}" data-timezone="{{ $event->timezone }}" data-original-date="{{ \App\Helpers\Dates::format($event->start_datetime(), 'datetime') }}" data-dateformat="timeonly">
                                {!! $event->display_time() !!}
                            </time>
                        </a>
                        @if($event->has_physical_location())
                            <span class="timezone">({{ $event->timezone }})</span>
                        @endif
                    @else
                        {!! $event->display_time() !!}
                    @endif
                </div>
            @endif
            {!! $event->mf2_date_html() !!}
            @if($mode != 'archive' && !$event->is_past() && !in_array($event->status, ['cancelled','postponed']))
            <div class="add-to-calendar">
                <div class="dropdown is-hoverable">
                    <div class="dropdown-trigger">
                        <a aria-haspopup="true" aria-controls="add-to-calendar-menu">
                            <span>{{ __('events.calendar.add_to_calendar') }}</span>
                        </a>
                    </div>
                    <div class="dropdown-menu" role="menu" id="add-to-calendar-menu">
                        <div class="dropdown-content">
                            @if(count($event->tag_list) > 0)
                                <a href="{{ $event->tag_feed_ics_link() }}" class="dropdown-item">
                                    @icon(calendar-alt) {{ __('events.calendar.tag_feed') }} <span class="tag is-rounded">#{{ $event->tag_list[0] }}</span>
                                </a>
                                <hr class="dropdown-divider" />
                            @endif
                            <div class="dropdown-item"><b>{{ __('events.calendar.single_event') }}</b></div>
                            <a href="{{ $event->ics_permalink() }}" class="dropdown-item" target="_blank">
                                @icon(calendar) {{ __('events.calendar.ical') }}
                            </a>
                            <a href="{{ route('add-to-google', $event->key) }}" class="dropdown-item" target="_blank">
                                @brand_icon(google) {{ __('events.calendar.google') }}
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
        <div class="website segment with-icon with-url">
            <span class="icon">@icon(link)</span>
            <span>
                <a href="{{ \App\Helpers\Uri::safe_href($event->website) }}" class="u-url" rel="canonical" title="{{ $event->website }}">
                    {{ strlen($event->website) > 40 ?  parse_url($event->website, PHP_URL_HOST) : \p3k\url\display_url($event->website) }}
                </a>
            </span>
        </div>
    @endif

    @if($event->tickets_url)
        <div class="website segment with-icon with-url">
            <span class="icon">@icon(ticket-alt)</span>
            <span>
                <a href="{{ \App\Helpers\Uri::safe_href($event->tickets_url) }}" title="{{ $event->tickets_url }}">
                    {{ strlen($event->tickets_url) > 40 ?  parse_url($event->tickets_url, PHP_URL_HOST) : \p3k\url\display_url($event->tickets_url) }}
                </a>
            </span>
        </div>
    @endif

    @if($event->code_of_conduct_url)
        <div class="code-of-conduct segment with-icon with-url">
            <span class="icon">@icon(gavel)</span>
            <span class="text">
                <span class="segment-title">{{ __('events.code_of_conduct') }}</span>
                @foreach($event->code_of_conduct_urls() as $url)
                    <a href="{{ \App\Helpers\Uri::safe_href($url) }}" title="{{ $url }}" class="segment-url">
                        {{ strlen($url) > 80 ?  parse_url($url, PHP_URL_HOST) : \p3k\url\display_url($url) }}
                    </a>
                @endforeach
            </span>
        </div>
    @endif

    @if($mode == 'archive')
        @if($event->status == 'confirmed' && $event->meeting_url)
            <div class="website segment with-icon">
                <span class="icon">@icon(video)</span>
                <span>
                    <a href="{{ \App\Helpers\Uri::safe_href($event->meeting_url) }}" title="{{ $event->meeting_url }}" target="_blank">
                        {{ strlen($event->meeting_url) > 80 ?  parse_url($event->meeting_url, PHP_URL_HOST) : \p3k\url\display_url($event->meeting_url) }}
                    </a>
                </span>
            </div>
        @endif
    @else
        @if($event->status == 'confirmed' && $event->meeting_url && !$event->is_past())
            <div class="website segment with-icon">
                <span class="icon">@icon(video)</span>
                <span>
                    @if($event->is_starting_soon() || $event->is_ongoing())
                        <a href="{{ \App\Helpers\Uri::safe_href($event->meeting_url) }}" title="{{ $event->meeting_url }}" class="pulsing-yellow" target="_blank">
                            {{ __('events.join_meeting') }}
                        </a>
                    @else
                        <a href="" class="pulsing-yellow hidden" target="_blank" id="event-meeting-url">{{ __('events.join_meeting') }}</a>
                        <span id="event-meeting-url-msg">{{ __('events.meeting_link_shown_soon') }}</span>
                    @endif
                </span>
            </div>
        @endif
    @endif

    @if($event->video_url)
        <div class="website segment with-icon with-url">
            <span class="icon">@brand_icon(youtube)</span>
            <span>
                <a href="{{ \App\Helpers\Uri::safe_href($event->video_url) }}" title="{{ $event->video_url }}">
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

    @if($event->notes_url)
        <div class="notes segment with-icon with-url">
            <span class="icon">@icon(pen)</span>
            <span class="text">
                <span class="segment-title">{{ __('events.notes') }}</span>
                <a href="{{ \App\Helpers\Uri::safe_href($event->notes_url) }}" title="{{ $event->notes_url }}" class="segment-url">
                    {{ \p3k\url\display_url(strlen($event->notes_url) > 40 ? 'http://'.parse_url($event->notes_url, PHP_URL_HOST) : $event->notes_url) }}
                </a>
            </span>
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

    @if($event->children->count())
        <div class="sub-events" id="sub-events">
            <b>{{ __('events.sessions') }}</b>
            <ul>
                @foreach($event->children as $child)
                    <li>
                        <b><a href="{{ $child->permalink() }}">
                            {{ $child->name }}
                        </a></b>
                        {!! $child->display_date().' '.$child->display_time() !!}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

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

    @if(Setting::value('enable_rsvps') && $event->rsvps_enabled && ($event->has_rsvps() || Auth::user()))
        <div class="responses rsvps" id="rsvps">
            <div class="level">
                <div class="level-left">
                    <h2 class="subtitle">{{ __('responses.rsvps') }}</h2>
                </div>
                <div class="level-right">
                @can('can-rsvp')
                    @if($event->status == 'confirmed')
                        @if($event->rsvp_string_for_user(Auth::user()) == 'yes')
                            <div class="buttons has-addons">
                                <button id="rsvp-button" class="button is-pressed is-light" data-action="{{ route('event-rsvp', $event->id) }}">
                                    {{ $event->is_past() ? __('responses.rsvp_went') : __('responses.rsvp_going') }}
                                </button>
                                <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(minus-circle)</button>
                            </div>
                        @else
                            <div class="buttons has-addons">
                                <button id="rsvp-button" class="button is-light" data-action="{{ route('event-rsvp', $event->id) }}">
                                    {{ $event->is_past() ? __('responses.rsvp_went') : __('responses.rsvp_going') }}
                                </button>
                                @if($event->rsvp_string_for_user(Auth::user()) == 'no')
                                    <button id="rsvp-delete" class="button is-pressed is-danger is-light" data-action="{{ route('event-rsvp-delete', $event->id) }}">@icon(minus-circle)</button>
                                @endif
                            </div>
                        @endif
                    @endif
                @endcan
                </div>
            </div>

            <ul>
                @foreach($event->rsvps_yes as $rsvp)
                    <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                @endforeach
            </ul>

            @if(count($event->rsvps_remote))
                <h3 class="subtitle">{{ __('responses.remote_attendees') }}</h3>
                <ul>
                    @foreach($event->rsvps_remote as $rsvp)
                        <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                    @endforeach
                </ul>
            @endif

            @if(count($event->rsvps_maybe))
                <h3 class="subtitle">{{ __('responses.maybe') }}</h3>
                <ul>
                    @foreach($event->rsvps_maybe as $rsvp)
                        <li>@include('components/rsvp-avatar', ['rsvp' => $rsvp])</li>
                    @endforeach
                </ul>
            @endif

            @if(count($event->rsvps_no))
                <h3 class="subtitle">{{ __('responses.cant_go') }}</h3>
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
                                <input class="input photo-alt-text" type="text" placeholder="{{ __('responses.alt_text_placeholder') }}">
                                <span class="hidden icon is-small is-right">@icon(check)</span>
                            </div>
                            <div class="control">
                                <button class="button" id="save-photo-alt">{{ __('common.save') }}</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="response_id">
                    <input type="hidden" id="photo_id">
                    @endcan
                    <p class="original-source">{!! __('responses.photo_via', ['source' => '<a href=""></a>']) !!}</p>
                </div>
            </div>
            <button class="modal-close is-large" aria-label="close"></button>
        </div>
    @endif

    @if($event->has_blog_posts())
        <div class="responses blog_posts" id="blog_posts">
            <h2 class="subtitle">{{ __('responses.blog_posts') }}</h2>
            <ul>
                @foreach($event->blog_posts as $post)
                    <li>
                        <p class="post-name"><a href="{{ $post->link() }}">{{ $post->name }}</a></p>
                        <p>
                            @if($post->published)
                                {!! __('responses.blog_post_by_on', ['author' => '<a href="'.e($post->author_url()).'">'.e($post->author_display_name()).'</a>', 'date' => e(\App\Helpers\Dates::format($post->published, 'date'))]) !!}
                            @else
                                {!! __('responses.blog_post_by', ['author' => '<a href="'.e($post->author_url()).'">'.e($post->author_display_name()).'</a>']) !!}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($event->has_comments())
        <div class="responses comments" id="comments">
            <h2 class="subtitle">{{ __('responses.comments') }}</h2>
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
                                @if(Setting::value('enable_rsvps') && $comment->rsvp)
                                  <img src="/images/rsvp-{{ $comment->rsvp }}.png" width="79">
                                @endif
                            </span>
                        </span>
                        <!-- <span class="comment-content">{{ $comment->content_text }}</span> -->
                        <span class="comment-content-html">{!! $comment->html_cleaned() !!}</span>
                        <span class="meta">
                            <a href="{{ $comment->link() }}">
                                <time datetime="{{ date('c', strtotime($comment->published)) }}">
                                    {{ \App\Helpers\Dates::format($comment->published, 'date') }}
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
