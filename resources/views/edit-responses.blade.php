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
            <li>
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

<style>
.event .responses {
    padding: 0;
}
.responses li {
    border-bottom: 1px #ddd solid;
    padding-bottom: 0.75em;
}
</style>

{{ csrf_field() }}

</section>
@endsection
