<span class="avatar">
    @if($rsvp->author_photo())
        <img src="{{ $rsvp->author_photo_square() }}" width="48" class="photo">
    @endif
    <a href="{{ $rsvp->source_url ?: $rsvp->author()['url'] }}">{{ $rsvp->author()['name'] ?: p3k\url\display_url($rsvp->author()['url']) }}</a>
</span>
