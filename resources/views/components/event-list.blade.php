        <ul class="event-list h-feed">
        @foreach($data as $y => $months)
            @foreach($months as $m => $events)
                <li>
                    @if(empty($month))
                        <span class="subtitle month">{{ date('F'.(isset($tag)?' Y':''), mktime(0,0,0, $m, 1, $y)) }}</span>
                    @endif
                    <ul>
                    @foreach($events as $event)
                        <li class="event h-event">
                            <h3><a href="{{ $event->permalink() }}" class="u-url p-name">{!! $event->status_tag() !!}{{ $event->name }}</a></h3>

                            <p>{!! $event->date_summary() !!}</p>

                            @if($event->location_city())
                                <p>{{ $event->location_city() }}</p>
                            @endif

                            <data style="display: none;">
                                {!! $event->mf2_date_html() !!}
                                @if($event->location_name || $event->location_summary_with_mf2())
                                <div class="p-location h-card">
                                    <div class="p-name">{{ $event->location_name }}</div>
                                    <div>{!! $event->location_summary_with_mf2() !!}</div>
                                </div>
                                @endif
                            </data>

                        </li>
                    @endforeach
                    </ul>
                </li>
            @endforeach
        @endforeach
        </ul>
