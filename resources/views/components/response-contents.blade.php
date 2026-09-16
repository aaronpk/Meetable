                @if($response->is_like)
                    <div class="like-of" style="display: flex;">
                        <span class="icon">@icon(star)</span> {{ __('responses.likes_this', ['name' => $response->author_display_name()]) }}
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
                                {{ \App\Helpers\Dates::format($response->published, 'date') }}
                            </time>
                        </a>
                    </span>
                @endif
                @if($response->source_url)
                    <span class="meta">
                        {!! __('responses.webmention_received', [
                            'date' => '<time datetime="'.e(date('c', strtotime($response->created_at))).'">'.e(\App\Helpers\Dates::format($response->created_at, 'datetime')).'</time>',
                            'source' => '<a href="'.e(\App\Helpers\Uri::safe_href($response->source_url)).'">'.e(parse_url($response->source_url, PHP_URL_HOST)).'</a>',
                        ]) !!}
                    </span>
                @elseif($response->created_by)
                    <span class="meta">
                        {!! __('responses.added_by', [
                            'date' => '<time datetime="'.e(date('c', strtotime($response->created_at))).'">'.e(\App\Helpers\Dates::format($response->created_at, 'datetime')).'</time>',
                            'author' => '<a href="'.e(\App\Helpers\Uri::safe_href($response->creator->url)).'">'.e($response->creator->name ?: p3k\url\display_url($response->creator->url)).'</a>',
                        ]) !!}
                    </span>
                @endif
