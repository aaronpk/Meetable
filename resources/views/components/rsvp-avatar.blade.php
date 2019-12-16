<span class="avatar">
    @if($rsvp->author()['photo'])
        <img src="{{ $rsvp->author()['photo'] }}" width="48">
    @endif
    <a href="{{ $rsvp->source_url ?: $rsvp->author()['url'] }}">{{ $rsvp->author()['name'] ?? p3k\url\display_url($rsvp->author()['url']) }}</a>
</span>
