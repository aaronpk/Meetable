                @if($response->is_like)
                    <div class="like-of" style="display: flex;">
                        <span class="icon">@icon(star)</span> {{ $response->author_display_name() }} likes this
                    </div>
                @else
                    @if($response->name)
                        <p class="post-name"><a href="{{ $response->link() }}">{{ $response->name }}</a></p>
                    @elseif($response->content_text)
                        <span class="comment-content">{{ $response->content_text }}</span>
                    @endif
                    @if($response->photos)
                        <div class="photos">
                            <ul class="photo-album admin">
                                @foreach($response->photos as $p)
                                    <li><a href="{{ $response->link() }}"><img src="{{ $p->square_url }}" width="230" height="230" class="square"></a></li>
                                 @endforeach
                            </ul>
                        </div>
                    @endif
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
                        Webmention Received
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
