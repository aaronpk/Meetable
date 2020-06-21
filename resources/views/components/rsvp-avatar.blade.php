<span class="avatar" id="response-{{ $rsvp->id }}">
    @if($rsvp->author_photo())
        <img src="{{ $rsvp->author_photo() }}" width="48" class="photo">
    @else
        <img src="/images/placeholder.png" width="48" class="photo">
    @endif
    @if($rsvp->rsvp_link())
        <a href="{{ $rsvp->rsvp_link() }}">{{ $rsvp->author_display_name() }}</a>
    @else
        {{ $rsvp->author_display_name() }}
    @endif
</span>
