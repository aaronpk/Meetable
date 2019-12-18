<span class="avatar">
    @if($rsvp->author()['photo'])
        <img src="@image_proxy($rsvp->author()['photo'], '96x96,sc')" width="48" class="photo">
    @endif
    <a href="{{ $rsvp->source_url ?: $rsvp->author()['url'] }}">{{ $rsvp->author()['name'] ?? p3k\url\display_url($rsvp->author()['url']) }}</a>
</span>
