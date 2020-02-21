<span class="avatar" id="response-{{ $like->id }}">
    @if($like->author_photo())
        <a href="{{ $like->rsvp_link() }}"><img src="{{ $like->author_photo() }}" width="48" class="photo"></a>
    @endif
</span>
