@extends('layouts/main')

@section('content')
<section class="section event">

<div class="content">
    <h1>Responses on {{ $event->name }}</h1>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
</div>


<div class="responses comments">
    <ul>
        @foreach($responses as $response)
            <li id="response-{{ $response->id }}">
                <div class="level" style="margin-bottom: 0; align-items: start;">
                  <div class="level-left">

                    <span class="avatar">
                        @if($response->author()['photo'])
                            <img src="@image_proxy($response->author()['photo'], '96x96,sc')" width="48" class="photo">
                        @endif
                        <span class="author-details">
                            <a href="{{ $response->author()['url'] }}" class="author-name">{{ $response->author()['name'] ?? p3k\url\display_url($response->author()['url']) }}</a>
                            <a href="{{ $response->author()['url'] }}" class="author-url">{{ p3k\url\display_url($response->author()['url']) }}</a>
                            @if($response->rsvp)
                                <div class="comment-rsvp"><img src="/images/rsvp-{{ $response->rsvp }}.png" width="79"></div>
                            @endif
                        </span>
                    </span>

                  </div>
                  <div class="level-right">

                    <div class="buttons has-addons with-dropdown">

                        <div class="dropdown is-right">
                            <div class="dropdown-trigger">
                                <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
                                    <span>Actions</span>
                                    <span class="icon is-small">@icon(angle-down)</span>
                                </button>
                            </div>

                            <div class="dropdown-menu" id="dropdown-menu" role="menu">
                                <div class="dropdown-content">
                                    <a class="dropdown-item view-response-details" href="{{ route('get-response-details', [$event, $response]) }}">
                                        <span class="icon">@icon(info-circle)</span>
                                        <span>View Details</span>
                                    </a>
                                    <a class="dropdown-item delete-response" href="{{ route('delete-response', [$event, $response]) }}">
                                        <span class="icon">@icon(trash)</span>
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                  </div>
                </div>
                @if($response->name)
                    <p class="post-name"><a href="{{ $response->link() }}">{{ $response->name }}</a></p>
                @endif
                @if($response->content_text)
                    <span class="comment-content">{{ $response->content_text }}</span>
                @endif
                @if($response->photos)
                    <div class="photos">
                        <ul class="photo-album admin">
                            @foreach($response->photos as $p)
                                <li><a href="{{ $response->link() }}"><img src="@image_proxy($p, '230x230,sc')" width="230" height="230" class="square"></a></li>
                             @endforeach
                        </ul>
                    </div>
                @endif
                @if($response->published)
                    <span class="meta">
                        <a href="{{ $response->link() }}">
                            <time datetime="{{ date('c', strtotime($response->published)) }}">
                                {{ date('M j, Y', strtotime($response->published)) }}
                            </time>
                        </a>
                    </span>
                @endif
                @if($response->source_url)
                    <span class="meta">
                        Received
                        <time datetime="{{ date('c', strtotime($response->created_at)) }}">
                            {{ date('M j, Y g:ia', strtotime($response->created_at)) }}
                        </time>
                        from
                        <a href="{{ $response->source_url }}">
                            {{ parse_url($response->source_url, PHP_URL_HOST) }}
                        </a>
                    </span>
                @elseif($response->created_by)
                    <span class="meta">
                        Added
                        <time datetime="{{ date('c', strtotime($response->created_at)) }}">
                            {{ date('M j, Y g:ia', strtotime($response->created_at)) }}
                        </time>
                        by
                        <a href="{{ $response->creator->url }}">
                            {{ $response->creator->name ?: p3k\url\display_url($response->creator->url) }}
                        </a>
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>

<div class="modal" id="response-details">
    <div class="modal-background"></div>
    <div class="modal-card">
        <div class="modal-card-body">

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Author Name</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-author_name" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Author Photo</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-author_photo" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Author URL</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-author_url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Name</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-name" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Content</label>
                </div>
                <div class="field-body">
                    <textarea class="textarea" id="response-content_text" rows="4" readonly></textarea>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">RSVP</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-rsvp" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Photos</label>
                </div>
                <div class="field-body">
                    <textarea class="textarea" id="response-photos" rows="4" readonly></textarea>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">URL</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Webmention Source URL</label>
                </div>
                <div class="field-body">
                    <input class="input" type="url" id="response-source_url" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Published At</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-published" readonly>
                </div>
            </div>

            <div class="field is-horizontal">
                <div class="field-label">
                    <label class="label">Created At</label>
                </div>
                <div class="field-body">
                    <input class="input" type="text" id="response-created" readonly>
                </div>
            </div>


        </div>
    </div>
    <button class="modal-close is-large" aria-label="close"></button>
</div>


<style>
.event .responses {
    padding: 0;
}
.responses li {
    border-bottom: 1px #ddd solid;
    padding-bottom: 0.75em;
}
</style>

<script>
$(function(){

    $(".delete-response").click(function(evt){
        evt.preventDefault();
        $.post($(evt.target).attr("href"), {
            _token: csrf_token()
        }, function(response){
            $("#response-"+response.response_id).remove();
        });
    });

    $(".view-response-details").click(function(evt){
        evt.preventDefault();
        $.get($(evt.target).attr("href"), function(response){

            ['created_at','updated_at','url','source_url','published',
             'author_name','author_photo','author_url','name','content_text','rsvp'].forEach(function(field){
                $("#response-"+field).val(response[field]);
            });
             $("#response-photos").val(response.photos.join("\n\n"));
            $("#response-details").addClass("is-active");
        });
    });

});
</script>

{{ csrf_field() }}

</section>
@endsection
