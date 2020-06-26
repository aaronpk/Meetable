                    <span class="avatar">
                        @if($response->author_photo())
                            <img src="{{ $response->author_photo() }}" width="48" class="photo">
                        @endif
                        <span class="author-details">
                            <a href="{{ $response->author_url() }}" class="author-name">{{ $response->author_display_name() }}</a>
                            <a href="{{ $response->author_url() }}" class="author-url">{{ p3k\url\display_url($response->author_url()) }}</a>
                            @if($response->rsvp)
                                <div class="comment-rsvp"><img src="/images/rsvp-{{ $response->rsvp }}.png" width="79"></div>
                            @endif
                        </span>
                    </span>
